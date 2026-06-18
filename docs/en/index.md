# Documentation — Activity Plugin for GLPI

**License:** GNU GPL v2+  
**Author:** Infotel (Xavier CAILLAUD)  
**Repository:** https://github.com/InfotelGLPI/activity

---

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Features](#features)
   - [Planning and Activities](#planning-and-activities)
   - [Holiday Requests and Validation](#holiday-requests-and-validation)
   - [Public Holidays](#public-holidays)
   - [Activity Report (CRA)](#activity-report-cra)
   - [Statistics and Reports](#statistics-and-reports)
   - [Holiday Counters](#holiday-counters)
   - [Document Snapshots](#document-snapshots)
5. [Rights Management](#rights-management)
6. [User Preferences](#user-preferences)
7. [Notifications](#notifications)
8. [Uninstallation](#uninstallation)

---

## Overview

The **Activity** plugin extends GLPI with comprehensive technician activity management:

- Track **activities** in the GLPI planning calendar (color-coded external events by type)
- Manage **holiday requests** with a full approval workflow
- Display **public holidays** in the planning calendar
- Generate a monthly **Activity Report (CRA)** exportable as PDF
- View **statistics** by technician, event category, or project
- Maintain **holiday counters** (paid leave, RTT, etc.) per user
- Optional integration with the **manageentities** plugin

---

## Installation

1. Download the plugin from [GitHub](https://github.com/InfotelGLPI/activity) or the GLPI marketplace.
2. Extract the archive into the `plugins/` (or `marketplace/`) directory of your GLPI installation.
3. Run `composer install --no-dev` in the plugin directory.
4. Log in to GLPI as an administrator.
5. Go to **Setup › Plugins**, then click **Install** and **Enable** for *Activity*.

---

## Configuration

Access: **Setup › Plugins › Activity › Configure**

| Option | Description |
|--------|-------------|
| **Replace activity name by the activity type** | Displays the activity type as the label in the planning calendar |
| **Use time repartition** | Enables the time-distribution view per technician |
| **Use the view by man-day in planning** | Displays activities in man-days (visible when time repartition is enabled) |
| **Use paired schedules** | Allows only paired half-day slots (morning/afternoon) |
| **Authorize only whole schedules** | Blocks partial half-day entries |
| **Authorize activities on weekends** | Allows events to be entered on Saturdays and Sundays |
| **Principal client** | Client name shown in the CRA header |
| **Used mail for holidays** | Notification e-mail address for holiday requests |
| **CRA footer** | Free text displayed at the bottom of each CRA PDF |
| **Use group manager as holiday manager** | The group manager automatically receives holiday requests |
| **Default validation percent** | Pre-filled value in the validation form (e.g. 100%) |
| **Is on CRA by default** | Enables the CRA by default for activities |
| **Use projects** | Allows events to be associated with GLPI projects |
| **Is on CRA by default – Projects** | Enables the CRA for projects (visible when projects are enabled) |
| **Use hour on CRA and not half day** | Use hours instead of half-days for CRA entries |
| **Use planning activity hours to limit hours per day on the CRA** | Uses the activity planning to cap daily CRA entries |
| **Use event subcategories** | Enables subcategories on planning external events |
| **Show event entity on CRA** | Displays the entity column in the CRA |
| **Choose your project when entering external events and display on CRA** | Allows selecting a project on each event and showing it in the CRA |

---

## Features

### Planning and Activities

The plugin enriches the **GLPI planning calendar** with additional event types, each displayed in a distinct color:

| Color | Type |
|-------|------|
| Blue (#7DAEDF) | Holiday |
| Green (#84BE6A) | Activity (external event) |
| Cyan (#08A5AC) | Manageentities |
| Orange (#E85F0C) | Ticket |

Technicians can enter their activities directly in the calendar. Each event can be associated with an **event category** (from the GLPI dropdown) and, if the option is enabled, with a **subcategory** and a **project**.

---

### Holiday Requests and Validation

#### Submitting a Request

A user with the `plugin_activity_can_requestholiday` right can submit a holiday request from:
- The **Tools › Activity** menu (central interface)
- The side menu in the simplified (helpdesk) interface

Fields to fill in:
- **Holiday type** — dropdown (Paid leave, RTT, or custom type)
- **Period** — optional predefined period dropdown
- **Start date / End date**
- **Half-days** — morning, afternoon, or full day
- **Comment** — free text

The request appears in the GLPI planning calendar with a blue color.

#### Approval Workflow

A validator (`plugin_activity_can_validate`) receives an e-mail notification and can:
- **Accept** the request (specifying the validation percentage)
- **Refuse** the request with a reason

The validation history is available in the request's tab. A notification is sent to the requester after the decision.

#### Holiday Types

Managed under **Setup › Dropdowns › Holiday types**. Built-in constants:
- `CP` — Paid leave
- `RTT` — Time reduction (French labor law)

#### Holiday Periods

Managed under **Setup › Dropdowns › Holiday periods**. Each period has a short name, a start date, and an end date.

---

### Public Holidays

**Public holidays** (`PublicHoliday`) are displayed in the GLPI planning calendar. They can be entered manually or imported. They appear in all user planning views and in the CRA.

---

### Activity Report (CRA)

The **CRA** (Compte Rendu d'Activité) is a monthly activity report for a technician. It aggregates:
- **Activities/events** entered in the planning calendar
- **Validated holidays**
- **Ticket tasks** (if the option is enabled)
- **Project tasks** (if the projects option is enabled)

#### Generating a CRA

Access: **Tools › Activity › CRA**

The user selects:
- The month and year
- The user (for profiles with the `plugin_activity_all_users` right)

The CRA can be:
- Displayed on screen as an interactive table
- **Exported as a PDF** (via the FPDF library)

#### PDF Structure

- Header: technician name, month, principal client
- Table of working days with, for each day: entered activities (in half-days or hours)
- Holiday section: list of absences for the month
- Total working days / absence days
- Customizable footer text

---

### Statistics and Reports

Access restricted to the `plugin_activity_statistics` right.

Statistics allow visualization of time distribution by:
- Technician
- Event category
- Project (if the option is enabled)
- Period (week, month, quarter, year)

---

### Holiday Counters

The `HolidayCount` class maintains **counters** of leave days per user (days accrued, days taken, balance). Administrators can manually adjust counters from the user profile.

---

### Document Snapshots

When a GLPI document is deleted (`Document::purge`), the plugin automatically purges associated snapshots via the `Snapshot` class.

---

## Rights Management

Access: **Administration › Profiles › [profile] › Activities tab**

| Right | Description |
|-------|-------------|
| `plugin_activity` | Full access to activities and planning (read, write, delete, admin) |
| `plugin_activity_can_requestholiday` | Permission to submit a holiday request |
| `plugin_activity_can_validate` | Permission to approve or refuse holiday requests |
| `plugin_activity_statistics` | Access to statistics and reports |
| `plugin_activity_all_users` | View and manage activities of all users |

At installation, the Super-Admin profile receives all rights.

---

## User Preferences

An **Activities** tab is added in **User Preferences** for users with at least one plugin right. They can configure:
- Their delegate for holiday validation
- Planning filter display options

---

## Notifications

The plugin sends e-mail notifications at the following stages of the holiday workflow:

| Event | Recipient |
|-------|-----------|
| New holiday request | Designated validator(s) |
| Request accepted | Requester |
| Request refused | Requester |

Notifications use GLPI's mail system (SMTP configured in **Setup › Notifications**). The notification address can be overridden via the **Used mail for holidays** field in the plugin configuration.

---

## Uninstallation

1. Go to **Setup › Plugins**.
2. Click **Disable** then **Uninstall** for *Activity*.

> **Warning:** Uninstalling removes all plugin tables and associated data (activities, holiday requests, counters, snapshots).
