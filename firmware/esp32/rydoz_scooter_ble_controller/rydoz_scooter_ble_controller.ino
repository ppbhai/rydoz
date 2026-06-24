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

const float WHEEL_CIRCUMFERENCE_M = 0.69f;
const uint8_t HALL_PULSES_PER_REV = 15;
const float ADC_REF_V = 3.30f;
const float ADC_MAX = 4095.0f;
const float DIVIDER_R_TOP = 150000.0f;
const float DIVIDER_R_BOTTOM = 10000.0f;
const float DIVIDER_RATIO = (DIVIDER_R_TOP + DIVIDER_R_BOTTOM) / DIVIDER_R_BOTTOM;
const uint32_t SCOOTER_ON_SENSE_GRACE_MS = 5000;
const uint32_t SCOOTER_ON_SENSE_OFF_DEBOUNCE_MS = 1000;
const bool USE_POWER_RELAY_CONTROL = true; // GPIO26 controls the MOSFET on the display/controller ground line.
const uint32_t FREE_TRIAL_LIMIT_MS = 60000;
const uint32_t FREE_TRIAL_DISTANCE_GRACE_MS = 5000;
const float FREE_TRIAL_LIMIT_KM = 0.100f;
const bool DEBUG_SCOOTER_ON_SENSE = true; // Set false after GPIO33 testing is complete.

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
    uint32_t total = 0;

    for (int i = 0; i < 16; i++) {
        total += analogRead(BATTERY_ADC_PIN);
        delay(2);
    }

    float adc = total / 16.0f;
    float pinVoltage = (adc / ADC_MAX) * ADC_REF_V;
    return pinVoltage * DIVIDER_RATIO;
}

uint8_t voltageToPercent(float voltage)
{
    const float emptyV = 30.0f;
    const float fullV = 42.0f;

    if (voltage <= emptyV) {
        return 0;
    }

    if (voltage >= fullV) {
        return 100;
    }

    return (uint8_t)roundf(((voltage - emptyV) / (fullV - emptyV)) * 100.0f);
}

bool chargerIsConnected()
{
    return digitalRead(CHARGER_SENSE_PIN) == HIGH;
}

void updateNearbyAdvertisement()
{
    if (!bleAdvertising || bleClientConnected) {
        return;
    }

    const float voltage = readScooterVoltage();
    const uint8_t batteryPercent = voltageToPercent(voltage);
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
        rideActive = false;
        scooterOutputWasOn = false;
        scooterSenseConfirmedOn = false;

        Serial.printf(
            "STOP completed: MOSFET output OFF, stored actual on time=%lu seconds\n",
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

    float voltage = readScooterVoltage();
    uint8_t batteryPercent = voltageToPercent(voltage);
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
            scooterOn();
        } else if (command == "START_TRIAL") {
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

void setup()
{
    Serial.begin(115200);
    pinMode(POWER_RELAY_PIN, OUTPUT);
    pinMode(BUTTON_OPTO_PIN, OUTPUT);
    pinMode(ESP_POWER_HOLD_PIN, OUTPUT);
    pinMode(HALL_PIN, INPUT_PULLUP);
    pinMode(CHARGER_SENSE_PIN, INPUT);
    pinMode(SCOOTER_ON_SENSE_PIN, INPUT); // External 22k divider resistor already pulls this pin LOW.

    digitalWrite(POWER_RELAY_PIN, LOW);
    digitalWrite(BUTTON_OPTO_PIN, LOW);
    digitalWrite(ESP_POWER_HOLD_PIN, HIGH);

    analogReadResolution(12);
    analogSetPinAttenuation(BATTERY_ADC_PIN, ADC_11db);
    attachInterrupt(digitalPinToInterrupt(HALL_PIN), onHallPulse, FALLING);

    setupBle();
}

void loop()
{
    updateFreeTrialAutoStop();
    printScooterSenseMonitor();

    if (millis() - lastTelemetryMs >= 1000) {
        lastTelemetryMs = millis();
        sendTelemetry();
    }

    if (!bleClientConnected && millis() - lastAdvertisementMs >= 10000) {
        lastAdvertisementMs = millis();
        updateNearbyAdvertisement();
    }
}
