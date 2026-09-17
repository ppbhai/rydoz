# Low-Battery Deep Sleep for ESP32 Scooter Controller

> Status: **planned, not implemented.** This document describes an approved design for
> `rydoz_scooter_ble_controller/rydoz_scooter_ble_controller.ino`. No firmware changes have been made yet.

## Context

The ESP32 on each scooter is powered directly from the scooter battery and currently runs at full power 24/7 — `loop()` never sleeps, BLE advertises continuously, and `readScooterVoltage()` polls the ADC every second. There is no power-saving code anywhere in the repo (verified: no `esp_sleep`, `deep_sleep`, or `light_sleep` references exist). A parked scooter therefore keeps draining its pack until it is fully flat, which risks damaging the battery through deep over-discharge.

**Goal:** when the pack reaches **≤ 30.0V** and the scooter is idle, put the ESP32 into deep sleep so it stops draining the battery. Wake immediately when a charger is connected, with a 5-minute timer as a fallback.

**Why 30.0V is a safe trigger:** the existing calibration (`voltageToPercent`, 33.0V = 10%, 41.3V = 100%) already clamps to 0% below ~32.08V, and `startBatteryAllowed()` blocks new rides at ≤10% / ≤33.0V. The app enforces the same floor (`MIN_ASSIGN_BATTERY_PERCENT = 10` in `scooter-iot-bridge.js:6`, server-side in `RideFlowController.php:578`). A scooter at ≤30.0V is therefore **already unrentable through every code path** — sleeping it removes zero rental capacity, it only stops the drain.

**Resolved during planning:** GPIO32 (`ESP_POWER_HOLD_PIN`) is **not connected** on current hardware, so there is no risk of the latch dropping and cutting the ESP32's own power during sleep. The RTC hold is still included defensively (cheap, and correct if the pin is ever wired), but it is no longer a blocking safety item.

## Files

- `firmware/esp32/rydoz_scooter_ble_controller/rydoz_scooter_ble_controller.ino` — **the only file changed.** It is the sole firmware file in the repo.
- Read-only references (no changes): `public/assets/js/scooter-iot-bridge.js`, `app/Http/Controllers/RideFlowController.php` — confirm the telemetry JSON contract and the ≤10% assign gate.

## Design decisions (locked)

| Setting | Value |
|---|---|
| Sleep trigger | ≤ 30.0V sustained |
| Resume threshold (hysteresis) | ≥ 31.0V |
| Confirmation window | 15 samples × 2s ≈ 30s sustained low |
| Timer wake fallback | 5 minutes |
| Charger wake | `ext1` on GPIO35, `ESP_EXT1_WAKEUP_ANY_HIGH` |
| Boot grace period | 20s before sleep is eligible |

**Why `ext1` and not `ext0`:** GPIO35 is input-only (GPIO34–39 have no internal pull resistors), so `ext0`'s only advantage — configurable internal pulls during sleep — does not exist on this pin. `ext1` additionally works with the RTC peripheral domain powered off, giving lower sleep current, which is the entire point of the feature. The external divider already defines the pin level deterministically, which is the same assumption `chargerIsConnected()` at line 134 already relies on.

**Why the 5-minute timer makes the no-BLE resleep path mandatory:** at 5-minute spacing there are 288 wakes/day. If each wake initialised BLE and advertised, the radio bring-up would negate most of the savings. The post-wake decision must therefore re-check voltage and go **straight back to sleep before `setupBle()`** when still low — making each fallback wake ~1s of CPU + ADC only.

## Implementation

### 1. Includes (after line 12)

```cpp
#include <esp_sleep.h>
#include <driver/rtc_io.h>
```

### 2. Constants (after `DEBUG_SCOOTER_ON_SENSE`, line 54)

```cpp
const float DEEP_SLEEP_VOLTAGE_THRESHOLD = 30.0f;
const float DEEP_SLEEP_WAKE_VOLTAGE_THRESHOLD = 31.0f;
const float DEEP_SLEEP_IMPLAUSIBLE_VOLTAGE = 5.0f;
const uint32_t DEEP_SLEEP_LOW_VOLTAGE_SAMPLE_INTERVAL_MS = 2000;
const uint8_t DEEP_SLEEP_LOW_VOLTAGE_CONFIRM_COUNT = 15;
const uint32_t DEEP_SLEEP_BOOT_GRACE_MS = 20000;
const uint64_t DEEP_SLEEP_TIMER_WAKE_US = 300ULL * 1000000ULL;
const uint32_t DEEP_SLEEP_POST_WAKE_SETTLE_MS = 250;
const bool DEBUG_DEEP_SLEEP = true;
```

`300ULL * 1000000ULL` must use the `ULL` suffix — plain `300 * 1000000` overflows 32-bit int before assignment.

### 3. Globals (after `freeTrialStopReason`, line 78)

```cpp
uint8_t lowVoltageSampleCount = 0;
uint32_t lastLowVoltageSampleMs = 0;
float lastKnownVoltage = 0.0f;
bool wokeFromDeepSleep = false;
```

### 4. Sleep entry — new functions after `sendTelemetry()` (~line 543)

`enterBatteryProtectionDeepSleep(float voltage)`:
1. `Serial.printf` the voltage, threshold, and wake sources.
2. Drive `POWER_RELAY_PIN` and `BUTTON_OPTO_PIN` LOW — sleep cannot drive them, so their pre-sleep state must be unambiguous.
3. `NimBLEDevice::stopAdvertising()` then `NimBLEDevice::deinit(true)` for an orderly radio shutdown.
4. Hold GPIO32 HIGH via `rtc_gpio_hold_en()`, falling back to `gpio_hold_en()` on error, then `gpio_deep_sleep_hold_en()`. **`gpio_deep_sleep_hold_en()` is the master enable — omitting it silently defeats the hold.** Defensive only, since GPIO32 is unconnected.
5. `esp_sleep_enable_ext1_wakeup(1ULL << CHARGER_SENSE_PIN, ESP_EXT1_WAKEUP_ANY_HIGH)`
6. `esp_sleep_enable_timer_wakeup(DEEP_SLEEP_TIMER_WAKE_US)`
7. `Serial.flush()` — without it the final diagnostic line is lost mid-transmission.
8. `esp_deep_sleep_start()`

**GPIO26 (`POWER_RELAY_PIN`) needs no RTC hold.** Sleep is only reachable when `!rideActive && !scooterOutputWasOn`, states in which GPIO26 is already LOW (set by `scooterOff()` line 430 and `setup()` line 637). A floating MOSFET gate resolves toward OFF, which is the fail-safe direction. Bench-verify (see Verification) and add a LOW hold only if it floats toward ON.

### 5. Sleep decision — `updateLowVoltageDeepSleep()`

Called **last in `loop()`**, after the advertising block. Sleeping is terminal, so running it last guarantees telemetry was notified and the advertisement refreshed before the board goes dark.

All of these must be true to sleep; **any of 1–5 resets the counter to zero** (not pauses it — a brief charger connect should restart the full 30s window):

1. `!rideActive`
2. `!scooterOutputWasOn` — belt-and-braces; `updateScooterOutputState()` can clear this independently
3. `!freeTrialActive`
4. `!chargerIsConnected()`
5. `!bleClientConnected` — staff actively connected; see rationale below
6. `millis() >= DEEP_SLEEP_BOOT_GRACE_MS`
7. Voltage ≤ 30.0V on 15 consecutive samples ≥2s apart
8. Voltage ≥ 5.0V — rejects a stuck/disconnected divider reading near zero, which would otherwise sleep a healthy scooter

**Voltage source — reuse, with a fallback.** `loop()` line 655 already calls `readScooterVoltage()` once per second for the debug print; capture that into `lastKnownVoltage` rather than adding a fourth ADC read per second (each call blocks ~32ms). But that line is marked `// TEMP: remove after calibration`, so the sleep logic must guard with `lastKnownVoltage > 0.0f ? lastKnownVoltage : readScooterVoltage()` — otherwise deleting the temp print would silently disable battery protection.

**Why 30s / counter-based debounce:** the real false-positive risk is a load transient (the 3.2s `pressPowerButton()` opto drive, relay inrush, motor draw on a pack with internal resistance) — all bounded by a few seconds. 30s is ~10x the longest plausible sag. "N consecutive samples, reset on any high reading" is strictly safer than "low for M ms", which could be satisfied by one low reading at each end of a window with unsampled recovery between. Guards 1–3 already exclude the whole ride window, so this defends the residual cases: a sag straddling ride-end and the `scooterOff()` relay transient.

**Why block on `bleClientConnected`:** a connection means a human is working on this specific scooter — exactly the unit staff need to inspect at ≤30V. Sleeping mid-session drops the GATT link and surfaces in the app as a generic connection failure (`scooter-iot-bridge.js:513`), which reads as a fault. The cost is bounded and self-limiting: `onDisconnect` (line 588) clears the flag and the 30s confirmation restarts. Deliberately **not** adding a max-connected-time force-sleep — staff would experience it as a bug for negligible energy saving.

### 6. Wake handling — new functions before `setup()`

`logDeepSleepWakeCause()` — reads `esp_sleep_get_wakeup_cause()`, sets `wokeFromDeepSleep`, and logs one line per case following existing `Serial.printf` style:
- `ESP_SLEEP_WAKEUP_EXT1` → charger wake; also log current `chargerIsConnected()` state
- `ESP_SLEEP_WAKEUP_TIMER` → periodic check
- `ESP_SLEEP_WAKEUP_UNDEFINED` → genuine cold boot/reset; clears `wokeFromDeepSleep`
- `default` → log the numeric cause

`applyPostWakeBatteryDecision()` — returns immediately if `!wokeFromDeepSleep`. Otherwise `delay(DEEP_SLEEP_POST_WAKE_SETTLE_MS)` (the divider/ADC need a moment after the rail stabilises; reading instantly risks a spuriously low first sample bouncing a recovered scooter back to sleep), then:

| Charger | Voltage | Action |
|---|---|---|
| Connected | any | Stay awake — **charger overrides voltage unconditionally** |
| Absent | ≥ 31.0V | Stay awake, resume normal operation |
| Absent | < 31.0V | `enterBatteryProtectionDeepSleep()` — **before any BLE init** |

Charger must override voltage because a charging pack starts below threshold by definition; sleeping while charging would hide the scooter exactly when staff need to confirm the charge is working, and creates a deadlock where charging can never prove itself before the next sleep decision.

### 7. `setup()` changes

At the top, immediately after `Serial.begin(115200)` and **before** the `pinMode` calls — release any RTC hold, then log the wake cause:

```cpp
rtc_gpio_hold_dis((gpio_num_t)ESP_POWER_HOLD_PIN);
gpio_hold_dis((gpio_num_t)ESP_POWER_HOLD_PIN);
gpio_deep_sleep_hold_dis();
logDeepSleepWakeCause();
```

A held pad stays locked after wake — a later `digitalWrite()` would appear to work but have no electrical effect. These calls are idempotent and safe on a cold boot where nothing was held, so there is one code path with no wake-cause branching.

Then, after `attachInterrupt(...)` but **before `setupBle()`** (it needs `pinMode(CHARGER_SENSE_PIN, INPUT)` and the ADC config done, and must be able to resleep without initialising BLE):

```cpp
applyPostWakeBatteryDecision();
```

### 8. `loop()` changes

Two edits only: capture the existing voltage read into `lastKnownVoltage` in the telemetry block, and add `updateLowVoltageDeepSleep()` as the final statement.

## Do-not-regress guarantees

| Feature | Why it cannot break |
|---|---|
| Ride start (`START`) | Sleep requires `!rideActive && !scooterOutputWasOn`; `scooterOn()` sets both true. `startBatteryAllowed()` already blocks starts ≤33.0V, so the guard windows do not even overlap the ≤30.0V sleep region. |
| Ride stop (`STOP`) | `scooterOff()` untouched. Sleep becomes *eligible* afterward but must still clear 30s of sustained low, so the relay-off transient cannot trigger a premature sleep. |
| Free trial auto-stop | `freeTrialActive` is guard #3 — cannot sleep mid-trial. `updateFreeTrialAutoStop()` stays first in `loop()`, our check last, so trial limits are always evaluated first. |
| Telemetry payload | `sendTelemetry()` body unchanged; it keeps its own `readScooterVoltage()` at line 514. JSON is byte-for-byte identical — no field added or removed, so `scooter-iot-bridge.js` and `RideFlowController.php` parsers are unaffected. |
| Nearby advertising | `updateNearbyAdvertisement()` untouched and still called before our check, so the last-published battery/charging bytes are current as of the moment before sleep. |
| Scooter-on sense debounce | `updateScooterOutputState()` untouched. All its state is only meaningful while `rideActive && scooterOutputWasOn` — precisely when sleep is blocked. |
| Charger reporting | `chargerIsConnected()` unchanged; `pinMode(CHARGER_SENSE_PIN, INPUT)` preserved. `ext1` is configured only in the instant before sleeping and does not affect normal digital reads while awake. |
| Hall / distance (`hallPulses`) | Volatile RAM, wiped by deep sleep — **acceptable**: sleep requires no active ride or trial, and both `startFreeTrial()` and `RESET_KM` zero the counter at the start of the window they measure. No consumer reads cumulative pulses across an idle period. |
| BLE reconnect after wake | Wake restarts at `setup()` → `setupBle()`, identical to a power cycle, a flow the fleet already handles. |

**The one intended behaviour change:** a scooter at ≤30.0V with no charger becomes BLE-invisible within ~50s (20s grace + 30s confirmation), then briefly visible every 5 minutes. The backend already treats this gracefully — `updateScooterLiveStats()` silently drops absent scooters, it increments the `offline_scooters` dashboard tile, and no fault or alert is raised. Note its last-known battery % disappears rather than showing as stale, since `is_online` and `battery` come from the same map. **Staff should be briefed that a dead scooter missing from the nearby list is expected, not a fault** — this is the most likely source of support tickets.

## Verification

Bench-test on hardware you can physically power-cycle, before any field unit:

1. **Sleep triggers.** Feed the divider a voltage equivalent to <30V (bench supply or divider injection) with no ride active. Confirm serial shows the countdown `1/15 … 15/15`, then the `DEEP SLEEP entering` line, then silence. Confirm current draw drops.
2. **Charger wake (primary path).** While asleep, assert GPIO35 HIGH / connect the charger. Confirm it wakes immediately and logs `WAKE cause=charger`.
3. **Timer wake + resleep.** With voltage still <30V and no charger, confirm it wakes after ~5 min, logs `WAKE cause=timer`, then `POST-WAKE: … returning to deep sleep` **without** advertising. Verify with a BLE scanner that it does not appear.
4. **Recovery.** Raise voltage above 31.0V, let the timer fire, and confirm it stays awake, runs `setupBle()`, and advertises again.
5. **No-regression pass at normal voltage (>31V).** Full ride cycle: assign → `START` → ride (hall pulses/km/speed increment) → `STOP`; then a `START_TRIAL` confirming 60s/100m auto-stop still fires. Confirm the countdown never starts and telemetry JSON is unchanged.
6. **No premature sleep on transients.** Run rides with the pack near-but-above threshold; confirm any countdown resets on the first reading above 30.0V and never reaches 15.
7. **GPIO26 float direction.** Scope the MOSFET gate driver input through a sleep cycle; confirm the scooter output stays OFF. If it drifts toward ON, add `rtc_gpio_hold_en()` on GPIO26 at LOW.
8. **GPIO35 idle level during sleep.** Confirm the divider holds GPIO35 reliably LOW with no charger (no internal pull is available on 34–39). A floating/noisy pin would cause spurious wakes that drain the pack faster than not sleeping — this is the most likely subtle field failure.
9. **Measure actual deep sleep current.** If the board's quiescent draw is dominated by other always-on regulators rather than the ESP32, the real benefit may be smaller than expected and the timer interval should be revisited.
10. **Brownout check.** Confirm the 36V→3.3V supply does not drop out above 30.0V — if it does, sleep would never trigger in practice and the feature is inert.

**Rollout:** keep `DEBUG_DEEP_SLEEP = true` and deploy to 1–2 bench units, then a small pilot subset, before fleet-wide — so wake causes and countdowns stay recoverable from serial logs.
