/*
  RYDOZ ESP32 BLE scooter controller
  Board: ESP32 Dev Module
  Arduino IDE libraries: NimBLE-Arduino

  BLE name format: RYDOZ-<SCOOTER_ID>
  QR payload format: scooter:<SCOOTER_ID>
*/

#include <Arduino.h>
#include <NimBLEDevice.h>
#include <string.h>
#include <esp_sleep.h>
#include <driver/rtc_io.h>

#define SCOOTER_ID "SCOOTER001"

static const char *SERVICE_UUID = "7b6a1000-2f6d-4e7f-9b2e-30a0bbd80101";
static const char *COMMAND_UUID = "7b6a1001-2f6d-4e7f-9b2e-30a0bbd80101";
static const char *TELEMETRY_UUID = "7b6a1002-2f6d-4e7f-9b2e-30a0bbd80101";

const uint8_t POWER_RELAY_PIN = 26;      // drives relay/MOSFET gate driver input
const uint8_t BUTTON_OPTO_PIN = 27;      // PC817 LED drive to simulate 3-second button press
const uint8_t HALL_PIN = 25;             // hall sensor pulse input
const uint8_t BATTERY_ADC_PIN = 34;      // ADC-only pin for 36V divider
const uint8_t CHARGER_SENSE_PIN = 35;    // HIGH when charger input voltage is present through divider
const uint8_t ESP_POWER_HOLD_PIN = 32;   // optional latch/enable line for IoT power switch
const uint8_t SCOOTER_ON_SENSE_PIN = 33; // feed 3.3V HIGH only while scooter/controller output is actually ON

// Round 2: re-rode the same 200m/400m test on 0.439f - 200m showed 191m,
// 400m showed 377m (overall under-report ratio ~0.9467x this time).
// Corrected = 0.439 / 0.9467. Both rides now land within ~1% of real
// distance at 0.464f. Still needs the hall pulses-per-rev spin-test to
// confirm whether any remaining drift belongs here or in
// HALL_PULSES_PER_REV - revisit once that's verified.
const float WHEEL_CIRCUMFERENCE_M = 0.464f;
const uint8_t HALL_PULSES_PER_REV = 15;
const float DIVIDER_R_TOP = 150000.0f;
const float DIVIDER_R_BOTTOM = 10000.0f;
const float DIVIDER_RATIO = (DIVIDER_R_TOP + DIVIDER_R_BOTTOM) / DIVIDER_R_BOTTOM;
// Voltage comes from analogReadMilliVolts(), which applies this chip's factory
// eFuse ADC calibration. That replaced a hardcoded 3.30V reference (wrong for
// ADC_11db, whose full scale is ~3.9V) plus a 1.0346 gain factor trimmed at a
// full pack - a combination that read 2.3V low mid-pack, showing 52% as 27%.
//
// A residual trim is still needed because two errors partly cancel here:
//   - The divider resistors are 150k/10k but only +/-5% (gold band), so the
//     real ratio is not the ideal 16.000 assumed above. Measured in circuit at
//     GPIO34: 2.11-2.13V against a 36.8V pack, i.e. closer to 17.4:1, though a
//     multimeter's own impedance loads a 150k divider and exaggerates that.
//   - The pin sits near 2.1-2.4V, the top of ADC_11db's usable range, where the
//     ADC stays somewhat non-linear even after eFuse correction.
// Rather than guess a split, this trims the end-to-end result: ESP reported
// 37.92V against a true 36.8-36.9V, so 36.85/37.92 = 0.9718.
//
// Re-verify at a low and a high pack voltage after any divider rework. If the
// error is no longer a constant percentage, a single factor cannot fix it and
// the divider values themselves should be measured out of circuit instead.
const float ADC_VOLTAGE_CORRECTION = 0.9718f;
const uint32_t SCOOTER_ON_SENSE_GRACE_MS = 5000;
const uint32_t SCOOTER_ON_SENSE_OFF_DEBOUNCE_MS = 1000;
const bool USE_POWER_RELAY_CONTROL = true; // GPIO26 controls the MOSFET on the display/controller ground line.
const uint32_t FREE_TRIAL_LIMIT_MS = 60000;
const uint32_t FREE_TRIAL_DISTANCE_GRACE_MS = 5000;
const float FREE_TRIAL_LIMIT_KM = 0.100f;
const uint8_t MIN_START_BATTERY_PERCENT = 10;
const bool DEBUG_SCOOTER_ON_SENSE = true; // Set false after GPIO33 testing is complete.
// The 16:1 divider and the 10.84%/V slope below amplify small ADC errors hard:
// 1mV at the pin is ~0.016V at the battery, so a few tens of mV of SAR noise
// moves the reported percentage by a point or more even on a steady pack. A
// median rejects the outlier samples that drag a plain mean, and spreading the
// samples catches slower drift that a single short burst cannot.
const uint8_t VOLTAGE_SAMPLE_COUNT = 21;
const uint32_t VOLTAGE_SAMPLE_DELAY_MS = 4;
// Sampling now blocks ~84ms, so cache it: re-reading per caller would stall loop()
// long enough to delay the 100ms scooter-sense poll and its 1s off-debounce.
const uint32_t VOLTAGE_CACHE_TTL_MS = 900;
// Republish the percentage only once it moves by at least this much, so residual
// single-count jitter never reaches the app. Endpoints (0/100) always report.
const uint8_t BATTERY_PERCENT_HYSTERESIS = 2;
const float DEEP_SLEEP_VOLTAGE_THRESHOLD = 30.0f;
// Resume needs 1V of headroom above the sleep threshold so a pack resting near
// 30V cannot sleep/wake/sleep in a loop that burns more power than it saves.
const float DEEP_SLEEP_WAKE_VOLTAGE_THRESHOLD = 31.0f;
const float DEEP_SLEEP_IMPLAUSIBLE_VOLTAGE = 5.0f;
const uint32_t DEEP_SLEEP_LOW_VOLTAGE_SAMPLE_INTERVAL_MS = 2000;
const uint8_t DEEP_SLEEP_LOW_VOLTAGE_CONFIRM_COUNT = 15;
const uint32_t DEEP_SLEEP_BOOT_GRACE_MS = 20000;
const uint64_t DEEP_SLEEP_TIMER_WAKE_US = 300ULL * 1000000ULL;
const uint32_t DEEP_SLEEP_POST_WAKE_SETTLE_MS = 250;
const bool DEBUG_DEEP_SLEEP = true;

volatile uint32_t hallPulses = 0;
volatile uint32_t lastHallMicros = 0;

NimBLECharacteristic *telemetryCharacteristic = nullptr;
NimBLEAdvertising *bleAdvertising = nullptr;
bool rideActive = false;
bool scooterOutputWasOn = false;
bool scooterSenseConfirmedOn = false;
bool bleClientConnected = false;
uint32_t lastTelemetryMs = 0;
uint32_t lastAdvertisementMs = 0;
uint32_t lastScooterSenseMs = 0;
uint32_t scooterSenseLowSinceMs = 0;
uint32_t lastScooterSenseDebugMs = 0;
uint32_t rideStartMs = 0;
uint32_t actualScooterOnSeconds = 0;
uint32_t lastPulseSnapshot = 0;
uint32_t lastScooterSenseMonitorMs = 0;
float speedKph = 0.0f;
bool freeTrialActive = false;
uint32_t freeTrialStartMs = 0;
uint32_t freeTrialStartPulses = 0;
const char *freeTrialStopReason = "none";
uint8_t reportedBatteryPercent = 0;
bool batteryPercentInitialised = false;
float cachedVoltage = 0.0f;
uint32_t voltageCacheMs = 0;
uint8_t lowVoltageSampleCount = 0;
uint32_t lastLowVoltageSampleMs = 0;
float lastKnownVoltage = 0.0f;
bool wokeFromDeepSleep = false;

void IRAM_ATTR onHallPulse()
{
    uint32_t now = micros();

    if (now - lastHallMicros < 4000) {
        return;
    }

    lastHallMicros = now;
    hallPulses++;
}

float readScooterVoltage()
{
    uint16_t samples[VOLTAGE_SAMPLE_COUNT];

    for (uint8_t i = 0; i < VOLTAGE_SAMPLE_COUNT; i++) {
        samples[i] = (uint16_t)analogReadMilliVolts(BATTERY_ADC_PIN);
        delay(VOLTAGE_SAMPLE_DELAY_MS);
    }

    // Insertion sort: VOLTAGE_SAMPLE_COUNT is small and this keeps the median
    // exact without pulling in a sort library.
    for (uint8_t i = 1; i < VOLTAGE_SAMPLE_COUNT; i++) {
        const uint16_t current = samples[i];
        int16_t j = (int16_t)i - 1;

        while (j >= 0 && samples[j] > current) {
            samples[j + 1] = samples[j];
            j--;
        }

        samples[j + 1] = current;
    }

    const float pinVoltage = samples[VOLTAGE_SAMPLE_COUNT / 2] / 1000.0f;
    return pinVoltage * DIVIDER_RATIO * ADC_VOLTAGE_CORRECTION;
}

float cachedScooterVoltage()
{
    if (voltageCacheMs > 0 && millis() - voltageCacheMs < VOLTAGE_CACHE_TTL_MS) {
        return cachedVoltage;
    }

    cachedVoltage = readScooterVoltage();
    voltageCacheMs = millis();

    return cachedVoltage;
}

uint8_t voltageToPercent(float voltage)
{
    // Calibrated against two measured reference points instead of assuming
    // a straight 0%-100% pack range: 33.0V reads as 10%, 41.3V reads as 100%.
    // These are the TRUE physical voltages (multimeter-measured) - the ADC's
    // own reading error is corrected upstream in readScooterVoltage() via the
    // chip's eFuse calibration, so these need no fudging to compensate for it.
    const float refLowV = 33.0f;
    const float refLowPercent = 10.0f;
    const float refHighV = 41.3f;
    const float refHighPercent = 100.0f;
    const float slope = (refHighPercent - refLowPercent) / (refHighV - refLowV);

    if (voltage >= refHighV) {
        return 100;
    }

    float percent = refLowPercent + (voltage - refLowV) * slope;

    if (percent <= 0.0f) {
        return 0;
    }

    return (uint8_t)roundf(percent);
}

uint8_t stableBatteryPercent(float voltage)
{
    const uint8_t raw = voltageToPercent(voltage);

    if (!batteryPercentInitialised) {
        batteryPercentInitialised = true;
        reportedBatteryPercent = raw;
        return reportedBatteryPercent;
    }

    // Always follow the endpoints exactly, so a full or empty pack is never
    // reported as approximate.
    if (raw == 0 || raw == 100) {
        reportedBatteryPercent = raw;
        return reportedBatteryPercent;
    }

    const uint8_t delta = raw > reportedBatteryPercent
            ? raw - reportedBatteryPercent
            : reportedBatteryPercent - raw;

    if (delta >= BATTERY_PERCENT_HYSTERESIS) {
        reportedBatteryPercent = raw;
    }

    return reportedBatteryPercent;
}

bool chargerIsConnected()
{
    return digitalRead(CHARGER_SENSE_PIN) == HIGH;
}

bool startBatteryAllowed()
{
    // Uncached on purpose: a start request is a one-shot decision that deserves a
    // fresh reading. Gate on the published value the app shows, so a scooter
    // displaying an allowed percentage is never refused by an unseen raw reading.
    const float voltage = readScooterVoltage();
    const uint8_t batteryPercent = stableBatteryPercent(voltage);

    if (batteryPercent <= MIN_START_BATTERY_PERCENT) {
        Serial.printf(
            "START blocked: battery too low (%u%%, %.2fV). Minimum required is %u%%.\n",
            batteryPercent,
            voltage,
            MIN_START_BATTERY_PERCENT);
        return false;
    }

    return true;
}

void updateNearbyAdvertisement()
{
    if (!bleAdvertising || bleClientConnected) {
        return;
    }

    const float voltage = cachedScooterVoltage();
    const uint8_t batteryPercent = stableBatteryPercent(voltage);
    const bool charging = chargerIsConnected();
    const String deviceName = "RYDOZ-" + String(SCOOTER_ID);
    const size_t scooterIdLength = strlen(SCOOTER_ID);
    uint8_t manufacturerData[5 + scooterIdLength];
    NimBLEAdvertisementData advertisementData;
    NimBLEAdvertisementData scanResponseData;

    // 0xFFFF is the test manufacturer ID. The remaining bytes are
    // protocol version, battery percentage, and the ASCII scooter ID.
    manufacturerData[0] = 0xFF;
    manufacturerData[1] = 0xFF;
    manufacturerData[2] = 0x01;
    manufacturerData[3] = batteryPercent;
    manufacturerData[4] = charging ? 1 : 0;
    memcpy(manufacturerData + 5, SCOOTER_ID, scooterIdLength);

    // Keep advertisement data compact. A 128-bit service UUID plus battery
    // manufacturer data can exceed the legacy 31-byte BLE packet limit.
    advertisementData.setFlags(0x06);
    advertisementData.setManufacturerData(manufacturerData, sizeof(manufacturerData));
    scanResponseData.setName(deviceName.c_str());

    const bool dataSet = bleAdvertising->setAdvertisementData(advertisementData)
            && bleAdvertising->setScanResponseData(scanResponseData);
    bool dataRefreshed = true;

    if (bleAdvertising->isAdvertising()) {
        dataRefreshed = bleAdvertising->refreshAdvertisingData();
    }

    Serial.printf(
        "Nearby advertisement: id=%s battery=%u%% charging=%s set=%s refresh=%s\n",
        SCOOTER_ID,
        batteryPercent,
        charging ? "yes" : "no",
        dataSet ? "ok" : "failed",
        dataRefreshed ? "ok" : "failed");
}

float distanceKm()
{
    uint32_t pulses;
    noInterrupts();
    pulses = hallPulses;
    interrupts();

    float revolutions = pulses / (float)HALL_PULSES_PER_REV;
    return (revolutions * WHEEL_CIRCUMFERENCE_M) / 1000.0f;
}

uint32_t hallPulseSnapshot()
{
    uint32_t pulses;
    noInterrupts();
    pulses = hallPulses;
    interrupts();

    return pulses;
}

float freeTrialDistanceKm()
{
    uint32_t pulses = hallPulseSnapshot();

    if (pulses < freeTrialStartPulses) {
        return 0.0f;
    }

    float revolutions = (pulses - freeTrialStartPulses) / (float)HALL_PULSES_PER_REV;
    return (revolutions * WHEEL_CIRCUMFERENCE_M) / 1000.0f;
}

void pressPowerButton()
{
    digitalWrite(BUTTON_OPTO_PIN, HIGH);
    delay(3200);
    digitalWrite(BUTTON_OPTO_PIN, LOW);
}

bool scooterOutputIsOn()
{
    return digitalRead(SCOOTER_ON_SENSE_PIN) == HIGH;
}

bool scooterOutputIsOnStable()
{
    uint8_t highCount = 0;

    for (int i = 0; i < 5; i++) {
        if (scooterOutputIsOn()) {
            highCount++;
        }

        delay(20);
    }

    return highCount >= 3;
}

void printScooterSenseMonitor()
{
    if (!DEBUG_SCOOTER_ON_SENSE || millis() - lastScooterSenseMonitorMs < 1000) {
        return;
    }

    lastScooterSenseMonitorMs = millis();

    Serial.printf(
        "GPIO33 sense raw=%d stable=%s rideActive=%s scooterOutputWasOn=%s actualSeconds=%lu\n",
        digitalRead(SCOOTER_ON_SENSE_PIN),
        scooterOutputIsOnStable() ? "HIGH" : "LOW",
        rideActive ? "true" : "false",
        scooterOutputWasOn ? "true" : "false",
        (unsigned long)scooterOnSeconds());
}

void scooterOn()
{
    if (USE_POWER_RELAY_CONTROL && rideActive && scooterOutputWasOn) {
        Serial.println("START ignored: MOSFET output already ON for active ride.");
        return;
    }

    if (scooterOutputIsOnStable()) {
        Serial.println("START ignored: scooter ON sense is already HIGH.");
        rideActive = true;
        scooterOutputWasOn = true;
        scooterSenseConfirmedOn = true;
        return;
    }

    if (USE_POWER_RELAY_CONTROL) {
        digitalWrite(ESP_POWER_HOLD_PIN, HIGH);
        digitalWrite(POWER_RELAY_PIN, HIGH);
        delay(300);
    }

    pressPowerButton();

    rideActive = true;
    scooterOutputWasOn = true;
    scooterSenseConfirmedOn = false;
    rideStartMs = millis();
    actualScooterOnSeconds = 0;
    freeTrialActive = false;
    freeTrialStartMs = 0;
    freeTrialStartPulses = 0;
    freeTrialStopReason = "none";
    scooterSenseLowSinceMs = 0;
    lastScooterSenseDebugMs = 0;

    if (scooterOutputIsOnStable()) {
        scooterSenseConfirmedOn = true;
        Serial.println("START completed: button pressed and scooter ON sense is HIGH.");
    } else {
        Serial.println("START command sent: button pressed, but scooter ON sense is still LOW.");
    }
}

void startFreeTrial()
{
    if (rideActive && scooterOutputWasOn) {
        Serial.println("START_TRIAL ignored: scooter already active.");
        return;
    }

    scooterOn();

    if (!rideActive || !scooterOutputWasOn) {
        Serial.println("START_TRIAL failed: scooter output did not become active.");
        return;
    }

    noInterrupts();
    hallPulses = 0;
    lastHallMicros = 0;
    interrupts();

    lastPulseSnapshot = 0;
    speedKph = 0.0f;
    freeTrialActive = true;
    freeTrialStartMs = millis();
    freeTrialStartPulses = 0;
    freeTrialStopReason = "running";

    Serial.println("START_TRIAL completed: ESP will auto stop after 60 seconds or 100 meters.");
}

uint32_t scooterOnSeconds()
{
    if (rideActive && scooterOutputWasOn && rideStartMs > 0) {
        return (millis() - rideStartMs) / 1000UL;
    }

    return actualScooterOnSeconds;
}

void captureScooterOnSeconds()
{
    if (rideActive && scooterOutputWasOn && rideStartMs > 0) {
        actualScooterOnSeconds = max(1UL, (millis() - rideStartMs) / 1000UL);
    }
}

void updateScooterOutputState()
{
    if (!rideActive || !scooterOutputWasOn) {
        return;
    }

    if (millis() - lastScooterSenseMs < 100) {
        return;
    }

    lastScooterSenseMs = millis();

    if (millis() - rideStartMs < SCOOTER_ON_SENSE_GRACE_MS) {
        return;
    }

    if (scooterOutputIsOn()) {
        if (!scooterSenseConfirmedOn) {
            scooterSenseConfirmedOn = true;
            Serial.printf(
                "Scooter ON sense HIGH confirmed at %lu seconds\n",
                (unsigned long)((millis() - rideStartMs) / 1000UL));
        }

        scooterSenseLowSinceMs = 0;
        return;
    }

    if (!scooterSenseConfirmedOn) {
        if (millis() - lastScooterSenseDebugMs >= 2000) {
            lastScooterSenseDebugMs = millis();
            Serial.println("Scooter ON sense is LOW and was never HIGH. Check GPIO 33 wiring/signal.");
        }
        return;
    }

    if (scooterSenseLowSinceMs == 0) {
        scooterSenseLowSinceMs = millis();
        Serial.printf(
            "Scooter ON sense LOW started at %lu seconds, waiting debounce...\n",
            (unsigned long)((millis() - rideStartMs) / 1000UL));
        return;
    }

    if (millis() - scooterSenseLowSinceMs >= SCOOTER_ON_SENSE_OFF_DEBOUNCE_MS) {
        captureScooterOnSeconds();
        scooterOutputWasOn = false;
        Serial.printf(
            "Scooter output OFF detected, actual on time=%lu seconds\n",
            (unsigned long)actualScooterOnSeconds);
    }
}

void scooterOff()
{
    captureScooterOnSeconds();

    if (freeTrialActive && strcmp(freeTrialStopReason, "running") == 0) {
        freeTrialStopReason = "manual";
    }

    freeTrialActive = false;

    if (USE_POWER_RELAY_CONTROL) {
        digitalWrite(POWER_RELAY_PIN, LOW);
        delay(100);
        rideActive = false;
        scooterOutputWasOn = false;
        scooterSenseConfirmedOn = false;

        Serial.printf(
            "STOP completed: MOSFET output OFF, GPIO26=%d, sense=%s, stored actual on time=%lu seconds\n",
            digitalRead(POWER_RELAY_PIN),
            scooterOutputIsOnStable() ? "HIGH" : "LOW",
            (unsigned long)actualScooterOnSeconds);
        return;
    }

    if (!scooterOutputIsOnStable()) {
        Serial.println("STOP ignored: scooter ON sense is already LOW.");
        rideActive = false;
        scooterOutputWasOn = false;
        scooterSenseConfirmedOn = false;
        return;
    }

    pressPowerButton();

    rideActive = false;
    scooterOutputWasOn = false;
    scooterSenseConfirmedOn = false;

    Serial.printf(
        "STOP completed: button pressed, stored actual on time=%lu seconds\n",
        (unsigned long)actualScooterOnSeconds);
}

void updateFreeTrialAutoStop()
{
    if (!freeTrialActive || !rideActive || !scooterOutputWasOn || freeTrialStartMs == 0) {
        return;
    }

    const uint32_t trialElapsedMs = millis() - freeTrialStartMs;

    if (millis() - freeTrialStartMs >= FREE_TRIAL_LIMIT_MS) {
        freeTrialStopReason = "time";
        Serial.println("FREE_TRIAL_AUTO_STOP: 60 seconds reached.");
        scooterOff();
        return;
    }

    if (trialElapsedMs < FREE_TRIAL_DISTANCE_GRACE_MS) {
        return;
    }

    if (freeTrialDistanceKm() >= FREE_TRIAL_LIMIT_KM) {
        freeTrialStopReason = "distance";
        Serial.printf(
            "FREE_TRIAL_AUTO_STOP: 100 meters reached. pulses=%lu meters=%u\n",
            (unsigned long)hallPulseSnapshot(),
            (unsigned int)roundf(freeTrialDistanceKm() * 1000.0f));
        scooterOff();
    }
}

void sendTelemetry()
{
    if (!telemetryCharacteristic) {
        return;
    }

    uint32_t pulses;
    uint32_t lastPulse;
    noInterrupts();
    pulses = hallPulses;
    lastPulse = lastHallMicros;
    interrupts();

    uint32_t nowMicros = micros();
    if (pulses != lastPulseSnapshot && lastPulse > 0) {
        float revPerSecond = 1000000.0f / (float)(nowMicros - lastPulse);
        speedKph = revPerSecond * WHEEL_CIRCUMFERENCE_M * 3.6f / HALL_PULSES_PER_REV;
        lastPulseSnapshot = pulses;
    } else if (millis() - (lastPulse / 1000UL) > 2500) {
        speedKph = 0.0f;
    }

    float voltage = cachedScooterVoltage();
    uint8_t batteryPercent = stableBatteryPercent(voltage);
    bool charging = chargerIsConnected();
    float km = distanceKm();
    updateScooterOutputState();
    uint32_t elapsed = scooterOnSeconds();

    char payload[320];
    snprintf(payload, sizeof(payload),
        "{\"id\":\"%s\",\"active\":%s,\"km\":%.3f,\"speed\":%.1f,\"battery\":%u,\"voltage\":%.2f,"
        "\"seconds\":%lu,\"onSeconds\":%lu,\"off_after_seconds\":%lu,\"actual_scooter_on_seconds\":%lu,"
        "\"scooterOutputOn\":%s,\"scooterSenseHigh\":%s,\"scooterSenseConfirmedOn\":%s,\"charging\":%s}",
        SCOOTER_ID,
        (rideActive && scooterOutputWasOn) ? "true" : "false",
        km,
        speedKph,
        batteryPercent,
        voltage,
        (unsigned long)elapsed,
        (unsigned long)elapsed,
        (unsigned long)elapsed,
        (unsigned long)elapsed,
        scooterOutputWasOn ? "true" : "false",
        scooterOutputIsOn() ? "true" : "false",
        scooterSenseConfirmedOn ? "true" : "false",
        charging ? "true" : "false");

    telemetryCharacteristic->setValue((uint8_t *)payload, strlen(payload));
    telemetryCharacteristic->notify();
}

void holdPowerLatchForDeepSleep()
{
    // GPIO output state is not retained in deep sleep. GPIO32 is unconnected on
    // current hardware, but hold it anyway so the board still survives sleep if
    // that line is ever wired as a power latch.
    digitalWrite(ESP_POWER_HOLD_PIN, HIGH);

    const gpio_num_t holdPin = (gpio_num_t)ESP_POWER_HOLD_PIN;

    if (rtc_gpio_hold_en(holdPin) != ESP_OK) {
        gpio_hold_en(holdPin);
    }

    // Master enable. Without this the per-pin holds above have no effect.
    gpio_deep_sleep_hold_en();
}

void enterBatteryProtectionDeepSleep(float voltage)
{
    Serial.printf(
        "DEEP SLEEP entering: battery %.2fV <= %.2fV, no ride active, charger absent. "
        "Wake on charger (GPIO%u HIGH) or timer in %lu seconds.\n",
        voltage,
        DEEP_SLEEP_VOLTAGE_THRESHOLD,
        CHARGER_SENSE_PIN,
        (unsigned long)(DEEP_SLEEP_TIMER_WAKE_US / 1000000ULL));

    // Sleep cannot drive these, so leave the scooter output hard OFF first.
    digitalWrite(POWER_RELAY_PIN, LOW);
    digitalWrite(BUTTON_OPTO_PIN, LOW);

    NimBLEDevice::stopAdvertising();
    NimBLEDevice::deinit(true);

    holdPowerLatchForDeepSleep();

    esp_sleep_enable_ext1_wakeup(1ULL << CHARGER_SENSE_PIN, ESP_EXT1_WAKEUP_ANY_HIGH);
    esp_sleep_enable_timer_wakeup(DEEP_SLEEP_TIMER_WAKE_US);

    Serial.flush();
    esp_deep_sleep_start();
}

void updateLowVoltageDeepSleep()
{
    if (rideActive || scooterOutputWasOn || freeTrialActive) {
        lowVoltageSampleCount = 0;
        return;
    }

    if (chargerIsConnected()) {
        lowVoltageSampleCount = 0;
        return;
    }

    if (bleClientConnected) {
        lowVoltageSampleCount = 0;
        return;
    }

    if (millis() < DEEP_SLEEP_BOOT_GRACE_MS) {
        return;
    }

    if (millis() - lastLowVoltageSampleMs < DEEP_SLEEP_LOW_VOLTAGE_SAMPLE_INTERVAL_MS) {
        return;
    }

    lastLowVoltageSampleMs = millis();

    // loop() already reads the voltage once per second for telemetry. Fall back to
    // our own read if that value is missing, so removing the temporary calibration
    // print cannot silently disable battery protection.
    const float voltage = lastKnownVoltage > 0.0f ? lastKnownVoltage : cachedScooterVoltage();

    if (voltage > DEEP_SLEEP_VOLTAGE_THRESHOLD) {
        if (lowVoltageSampleCount > 0 && DEBUG_DEEP_SLEEP) {
            Serial.printf(
                "Deep sleep countdown reset: %.2fV is above %.2fV\n",
                voltage,
                DEEP_SLEEP_VOLTAGE_THRESHOLD);
        }

        lowVoltageSampleCount = 0;
        return;
    }

    // A disconnected or shorted divider reads near zero, which would otherwise
    // look like a critically low pack and sleep a healthy scooter.
    if (voltage < DEEP_SLEEP_IMPLAUSIBLE_VOLTAGE) {
        if (DEBUG_DEEP_SLEEP) {
            Serial.printf("Deep sleep skipped: implausible voltage reading %.2fV\n", voltage);
        }

        lowVoltageSampleCount = 0;
        return;
    }

    lowVoltageSampleCount++;

    if (DEBUG_DEEP_SLEEP) {
        Serial.printf(
            "Low battery %.2fV <= %.2fV, confirmation %u/%u\n",
            voltage,
            DEEP_SLEEP_VOLTAGE_THRESHOLD,
            lowVoltageSampleCount,
            DEEP_SLEEP_LOW_VOLTAGE_CONFIRM_COUNT);
    }

    if (lowVoltageSampleCount < DEEP_SLEEP_LOW_VOLTAGE_CONFIRM_COUNT) {
        return;
    }

    enterBatteryProtectionDeepSleep(voltage);
}

class CommandCallbacks : public NimBLECharacteristicCallbacks
{
    void onWrite(NimBLECharacteristic *characteristic, NimBLEConnInfo &connInfo) override
    {
        String command = characteristic->getValue().c_str();
        command.trim();
        command.toUpperCase();

        Serial.print("BLE command received: ");
        Serial.println(command);

        if (command == "START") {
            if (!startBatteryAllowed()) {
                sendTelemetry();
                return;
            }
            scooterOn();
        } else if (command == "START_TRIAL") {
            if (!startBatteryAllowed()) {
                sendTelemetry();
                return;
            }
            startFreeTrial();
        } else if (command == "STOP") {
            scooterOff();
        } else if (command == "RESET_KM") {
            noInterrupts();
            hallPulses = 0;
            interrupts();
        }

        sendTelemetry();
    }
};

class ServerCallbacks : public NimBLEServerCallbacks
{
    void onConnect(NimBLEServer *server, NimBLEConnInfo &connInfo) override
    {
        bleClientConnected = true;
        Serial.println("BLE connected");
    }

    void onDisconnect(NimBLEServer *server, NimBLEConnInfo &connInfo, int reason) override
    {
        bleClientConnected = false;
        updateNearbyAdvertisement();
        Serial.println("BLE disconnected, advertising restarted");
        NimBLEDevice::startAdvertising();
    }
};

void setupBle()
{
    String deviceName = "RYDOZ-" + String(SCOOTER_ID);

    NimBLEDevice::init(deviceName.c_str());
    NimBLEDevice::setPower(ESP_PWR_LVL_P6);
    NimBLEServer *server = NimBLEDevice::createServer();
    server->setCallbacks(new ServerCallbacks());
    NimBLEService *service = server->createService(SERVICE_UUID);

    NimBLECharacteristic *commandCharacteristic = service->createCharacteristic(
        COMMAND_UUID,
        NIMBLE_PROPERTY::WRITE | NIMBLE_PROPERTY::WRITE_NR);
    commandCharacteristic->setCallbacks(new CommandCallbacks());

    telemetryCharacteristic = service->createCharacteristic(
        TELEMETRY_UUID,
        NIMBLE_PROPERTY::READ | NIMBLE_PROPERTY::NOTIFY);

    service->start();

    bleAdvertising = NimBLEDevice::getAdvertising();
    bleAdvertising->enableScanResponse(true);
    updateNearbyAdvertisement();
    bleAdvertising->start();

    Serial.print("BLE advertising as ");
    Serial.println(deviceName);
}

void logDeepSleepWakeCause()
{
    const esp_sleep_wakeup_cause_t cause = esp_sleep_get_wakeup_cause();

    wokeFromDeepSleep = true;

    switch (cause) {
        case ESP_SLEEP_WAKEUP_EXT1:
            Serial.printf(
                "WAKE cause=charger (ext1 GPIO%u), charger sense now %s\n",
                CHARGER_SENSE_PIN,
                chargerIsConnected() ? "HIGH" : "LOW");
            break;
        case ESP_SLEEP_WAKEUP_TIMER:
            Serial.println("WAKE cause=timer (battery protection periodic check)");
            break;
        case ESP_SLEEP_WAKEUP_UNDEFINED:
            wokeFromDeepSleep = false;
            Serial.println("WAKE cause=power-on/reset (not deep sleep)");
            break;
        default:
            Serial.printf("WAKE cause=other (%d)\n", (int)cause);
            break;
    }
}

void applyPostWakeBatteryDecision()
{
    if (!wokeFromDeepSleep) {
        return;
    }

    // Let the rail and divider settle. Reading immediately risks a spuriously low
    // first sample bouncing a recovered scooter straight back to sleep.
    delay(DEEP_SLEEP_POST_WAKE_SETTLE_MS);

    if (chargerIsConnected()) {
        Serial.println("POST-WAKE: charger connected, staying awake regardless of battery voltage.");
        return;
    }

    const float voltage = readScooterVoltage();
    lastKnownVoltage = voltage;

    if (voltage >= DEEP_SLEEP_WAKE_VOLTAGE_THRESHOLD) {
        Serial.printf(
            "POST-WAKE: battery recovered to %.2fV (>= %.2fV), resuming normal operation.\n",
            voltage,
            DEEP_SLEEP_WAKE_VOLTAGE_THRESHOLD);
        return;
    }

    Serial.printf(
        "POST-WAKE: battery still %.2fV and no charger, returning to deep sleep without starting BLE.\n",
        voltage);
    enterBatteryProtectionDeepSleep(voltage);
}

void setup()
{
    Serial.begin(115200);

    // A pad held through deep sleep stays latched after wake, which would make
    // later digitalWrite() calls silently ineffective. Safe on a cold boot too.
    rtc_gpio_hold_dis((gpio_num_t)ESP_POWER_HOLD_PIN);
    gpio_hold_dis((gpio_num_t)ESP_POWER_HOLD_PIN);
    gpio_deep_sleep_hold_dis();

    pinMode(POWER_RELAY_PIN, OUTPUT);
    pinMode(BUTTON_OPTO_PIN, OUTPUT);
    pinMode(ESP_POWER_HOLD_PIN, OUTPUT);
    pinMode(HALL_PIN, INPUT_PULLUP);
    pinMode(CHARGER_SENSE_PIN, INPUT);
    pinMode(SCOOTER_ON_SENSE_PIN, INPUT); // External 22k divider resistor already pulls this pin LOW.

    // After pinMode: this logs the live charger-sense level on an ext1 wake.
    logDeepSleepWakeCause();

    digitalWrite(POWER_RELAY_PIN, LOW);
    digitalWrite(BUTTON_OPTO_PIN, LOW);
    digitalWrite(ESP_POWER_HOLD_PIN, HIGH);

    analogReadResolution(12);
    analogSetPinAttenuation(BATTERY_ADC_PIN, ADC_11db);
    attachInterrupt(digitalPinToInterrupt(HALL_PIN), onHallPulse, FALLING);

    applyPostWakeBatteryDecision();

    setupBle();
}

void loop()
{
    updateFreeTrialAutoStop();
    printScooterSenseMonitor();

    if (millis() - lastTelemetryMs >= 1000) {
        lastTelemetryMs = millis();
        lastKnownVoltage = cachedScooterVoltage();
        Serial.printf("Computed battery voltage: %.3fV\n", lastKnownVoltage); // TEMP: remove after calibration
        sendTelemetry();
    }

    if (!bleClientConnected && millis() - lastAdvertisementMs >= 10000) {
        lastAdvertisementMs = millis();
        updateNearbyAdvertisement();
    }

    // Last: this never returns once it decides to sleep, so telemetry and the
    // advertisement above are always published before the board goes dark.
    updateLowVoltageDeepSleep();
}
