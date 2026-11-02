# Water Tank Telemeter (WTT)

A solar-powered, GSM-connected **remote telemetry and pump controller for water tanks**. Each field unit measures the tank level, reports it over cellular **MQTT**, and drives up to four pump/valve relays on command from a web **portal**.

The project has three parts:

| Part | Location | Technology |
|---|---|---|
| **Hardware** — field unit PCB | [`Hardware/`](Hardware/) | Altium Designer, STM32F103 + Gosuncn MG2609 GSM |
| **Firmware** — field unit software | [`Hardware/Design/Firmware/WTT/WTT`](Hardware/Design/Firmware/WTT/WTT) | C, STM32CubeIDE / HAL |
| **Portal** — monitoring & control web app | [`Portal/`](Portal/) | PHP, MySQL, MQTT (Mosquitto), Bootstrap 4 |

<p align="center">
  <img src="Hardware/Release/Schematic%20Diagrams/schematics.png" alt="Water Tank Telemeter block diagram" width="760"/>
</p>

---

> [!IMPORTANT]
> **Not a final product.** The firmware and portal in this repository are a **development snapshot** published for reference. They are **not** the production release:
>
> - Commercial and customer-specific parts (production configuration, deployment scripts, customer data, server endpoints and credentials) have been **removed or replaced with placeholders**.
> - The released firmware binaries are **not** included.
> - The code may be incomplete, may not build or run as-is against your infrastructure, and has not been hardened for production use (e.g. the portal needs a security review before being exposed to the internet).
>
> The repository history was restarted for publication; it does not contain the original development history.

---

## Table of contents

- [System overview](#system-overview)
- [Hardware](#hardware)
- [Firmware](#firmware)
- [Portal](#portal)
- [MQTT interface](#mqtt-interface)
- [Repository layout](#repository-layout)
- [Getting started](#getting-started)
- [License](#license)

---

## System overview

```
 ┌───────────────────── Field unit ─────────────────────┐
 │ Ultrasonic level sensor ──RS-232──┐                  │
 │ 8 × opto-isolated inputs ─────────┤                  │
 │                                   ▼                  │
 │                         STM32F103C8 (firmware)       │
 │                                   │ UART             │
 │ 4 × relays (pumps/valves) ◄───────┤                  │
 │                                   ▼                  │
 │                          Gosuncn MG2609 GSM ─────────┼──► MQTT broker ◄──► Portal (PHP + MySQL)
 │                                                      │   water-tanks/<IMEI>/...
 │ 12 V in · solar panel · battery + charger            │
 └──────────────────────────────────────────────────────┘
```

1. The field unit reads the tank level (ultrasonic sensor over RS-232, or a mechanical/percentage level input) and the state of its digital inputs.
2. It publishes level, status and heart-beat messages over MQTT through the on-board GSM modem.
3. The portal stores the data in MySQL, shows dashboards and history charts, and sends pump on/off commands back to the unit over MQTT.

---

## Hardware

Schematic revision **V1I1 / V1I2** (© 2020 PieVision). Released schematic: [`Hardware/Release/Schematic Diagrams/schematics.PDF`](Hardware/Release/Schematic%20Diagrams/schematics.PDF).

### Main components

| Block | Part | Notes |
|---|---|---|
| Microcontroller | **STM32F103C8T7** | 8 MHz crystal, SWD/JTAG header |
| Cellular modem | **Gosuncn MG2609** | GSM/GPRS with MQTT AT-command stack, SMA antenna, nano-SIM, ESD protection, 3.3 V ↔ VDD_EXT level translator |
| RS-232 transceiver | **ST3222BTR** | DB-9 port for the ultrasonic level sensor (+24 V supply on the connector) |
| Digital inputs | 8 × **LTV-217** optocouplers | `INT0..INT7`, TVS protected (SM05T1G), RC debounce |
| Relay outputs | 4 × **JW2SN** relays | `REL0..REL3`, driven by ZXMN3A MOSFETs, flyback diodes and status LEDs |
| 12 V → 5.3 V | **P2576L-ADJ** buck | main switching regulator |
| 5.3 V → 3.8 V | **LD29300** LDO | GSM modem supply |
| 5.3 V → 3.3 V | 3.3 V LDO | MCU and logic supply |
| VIN → +24 V | **NCV3063** boost | supply for the ultrasonic sensor |
| Battery charger | **LM317T** + **TL071** | solar-panel input (≈14.3 V), charge-state LEDs |
| Protection | resettable fuses, reverse-polarity MOSFETs, Zener clamps | on 12 V, solar and battery inputs |

### Schematic sheets

| Page | Sheet |
|---|---|
| 1 | Cover page |
| 2 | Block diagram |
| 3 | Power (12 V input, solar, battery charger, 5.3 V / 3.8 V / 3.3 V / 24 V rails) |
| 4 | Microcontroller and GSM |
| 5 | Peripherals (opto inputs, relays, RS-232 sensor port) |
| 6 | Revision history |

### Revision history

- **V1I1** — INT lines, microcontroller, GSM module, battery charger and power management implemented and verified.
- **V1I2** — debouncing capacitors on INT lines, stitching vias resized, footprints updated.

---

## Firmware

STM32CubeIDE project for the STM32F103C8 in [`Hardware/Design/Firmware/WTT/WTT`](Hardware/Design/Firmware/WTT/WTT).

| File | Purpose |
|---|---|
| `Core/Src/main.c` | main loop: sensor publish, status report, command polling, ping / fail-safe |
| `Core/Src/Gosun.c` / `Gosun.h` | driver for the Gosuncn modem: power on, SIM/cell checks, GPRS, MQTT connect / publish / subscribe |
| `Core/Src/Gsm.c` / `Gsm.h` | low-level AT command send/receive helpers |
| `Core/Src/usart.c`, `gpio.c` | CubeMX-generated peripheral setup |
| `WTT.ioc` | CubeMX configuration |

Main loop timing:

| Task | Interval |
|---|---|
| Publish tank level (`Level`) | 10 min |
| Publish status (`Status`: uptime, signal quality, relay ports) | 5 min |
| Poll for incoming MQTT commands | 2 s |
| Heart-beat (`ping`) | 20 s — relays are switched **off** if the ping fails more than 3 times in a row (fail-safe) |

The MQTT broker host is set by `DEFAULT_MQTT_SERVER` in `Core/Inc/Gosun.h` (placeholder `mqtt.example.com`); the APN is set in `Gosun_normalConnect()`.

---

## Portal

A PHP web application in [`Portal/`](Portal/) for operators.

| Page | Purpose |
|---|---|
| `index.php` | login |
| `dashboard.php` | tank levels, live status and 6-hour level chart (Chart.js) |
| `water_tanks.php` | tank management |
| `water_pumps.php` | pump list and **remote on/off** (publishes MQTT `Action` messages) |
| `water_controls.php` | control-box (field unit) management and online state |
| `water_wells.php` | well management |
| `water_users.php` | user and permission management |
| `lists.php` | DataTables server-side JSON endpoint |
| `includes/cron.php` | marks control boxes offline when no ping is received for 60 s |

Requirements: PHP 7.2+, MySQL/MariaDB, the [Mosquitto PHP extension](https://github.com/mgdm/Mosquitto-PHP), and an MQTT broker. The database schema is **not** included.

---

## MQTT interface

All topics are prefixed with the modem IMEI, which is also used as the MQTT client id and username:

| Topic | Direction | Payload example |
|---|---|---|
| `water-tanks/<IMEI>/Level` | unit → portal | `{'type':'uls','dist':1234}` (ultrasonic) or `{'type':'mec','perc':57.00}` |
| `water-tanks/<IMEI>/Status` | unit → portal | `{'uptime':123456,'csq':21,'port1':1,'port2':0,'port3':0}` |
| `water-tanks/<IMEI>/ping` | unit → portal | `p` |
| `water-tanks/<IMEI>/Action` | portal → unit | relay command |

---

## Repository layout

```
WTT/
├── Hardware/
│   ├── Design/            Altium project (Water Tank Telemeter.PrjPcb), schematics, PCB, libraries
│   │   └── Firmware/WTT/  STM32CubeIDE firmware project
│   ├── Release/           frozen release outputs — DO NOT EDIT
│   │   ├── Schematic Diagrams/   schematics.PDF, schematics.png
│   │   ├── PCB Manufacturing/    Gerber, NC drill, BOM
│   │   ├── Board Assembly/, 3D/
│   │   └── Firmware/             (binaries not published)
│   ├── Libs/              component models
│   ├── Artifacts/         datasheets, design guides, reports
│   └── Templates/         Altium templates
├── Portal/                PHP web portal
│   ├── includes/          config template, DB helpers, cron
│   └── assets/            CSS, JS, images
├── LICENSE
└── README.md
```

---

## Getting started

### Hardware

1. Open `Hardware/Design/Water Tank Telemeter.PrjPcb` in Altium Designer.
2. Manufacturing outputs (Gerber, drill, BOM) are in `Hardware/Release/PCB Manufacturing/`.
3. Follow `Hardware/Release/!!! IMPORTANT !!! Release Document for Altium Designer.txt` before making a new release.

### Firmware

1. Import `Hardware/Design/Firmware/WTT/WTT` into **STM32CubeIDE**.
2. Set your broker in `Core/Inc/Gosun.h` (`DEFAULT_MQTT_SERVER`, `DEFAULT_MQTT_PORT`) and your APN in `Gosun_normalConnect()` (`Core/Src/Gosun.c`).
3. Build and flash over SWD (ST-Link).

### Portal

1. Copy `Portal/` to a PHP-enabled web server.
2. Copy `Portal/includes/config.example.php` to `Portal/includes/config.php` and fill in your database and MQTT credentials. `config.php` is git-ignored.
3. Create the MySQL schema (tables `water_users`, `water_tanks`, `water_tanks_level_history`, `water_controlbox`, `water_motors`, …).
4. Schedule `includes/cron.php` to run every minute.

---

## License

Released under the [MIT License](LICENSE).
