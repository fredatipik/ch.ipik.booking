# Practice Booking

Appointment booking for CiviCRM, designed for practices where several
practitioners share one or more consulting rooms.

Patients book through a WordPress form; appointments are recorded in CiviCRM
and synchronised with CalDAV calendars.

> **Interface language** — the administrative interface and the public form
> are currently in French. An English version is planned; contributions are
> welcome.

## What it does

**Online booking** — A WordPress shortcode renders a four-step form:
appointment type, time slot, contact details, confirmation. Available slots
account for declared availability, existing appointments, personal calendars
and room occupancy.

Patients never choose their practitioner: the system assigns one according to
a configurable strategy — round-robin, least loaded, or random.

**Two ways to declare availability.** *Weekly hours* suits regular schedules,
with holidays and one-off slots as exceptions. *Declared working days* suits
irregular teams: a six-month calendar where each working day is ticked with
its hours and room. The mode is set globally, and each practitioner may use
the other one.

**Rooms.** A room has its own calendar. An appointment held there makes the
slot unavailable to everyone, and conversely the room calendar can be used to
block time without going through a booking. Events written to a room calendar
carry only the practitioner's name.

**CalDAV synchronisation.** Appointments are written to the practitioner's
personal calendar and to the room calendar. Events already present there block
the corresponding slots. Synchronisation never conditions the booking itself:
if a calendar is unreachable, the appointment is still recorded and the
failure logged.

**Back office.** Create appointments over the phone, choosing among free slots
or entering a date and time directly for urgent cases. Filterable list of all
appointments, per-practitioner agenda, a *Appointments* tab on the patient
record, and a dashlet of upcoming appointments.

**Billing.** From a completed appointment, two buttons lead to the CiviCRM
contribution form or to the Swiss QR invoice form, with the contact
pre-filled. Default financial type and contribution status are configured
once.

**Emails.** Confirmation to the patient and to the practitioner, reminder
before the appointment, cancellation notice to both parties. Contents are
editable CiviCRM message templates, with extension-specific tokens alongside
the standard ones.

## Privacy

An appointment with a therapist is medical information. A few safeguards are
in place by default.

The dashlet only shows the appointments of the logged-in practitioner, never
those of others — including for an administrator account. The subject of
CiviCRM activities carries only the appointment type, never the patient's
name. Room calendars, shared among several people, receive only the
practitioner's name.

Activity creation can be disabled entirely when the mere existence of an
appointment should not appear in shared calendars.

## Requirements

- CiviCRM 5.60 or later, tested on 6.15
- WordPress 6.0 or later
- PHP 8.1 or later
- cURL extension for CalDAV synchronisation

Optional: [`ch.ipik.swissQRinvoice`](https://github.com/fredatipik/ch.ipik.swissQRinvoice)
for Swiss QR invoicing, and `ch.ipik.smtprouter` to route outgoing mail by
sender address.

## Installation

```bash
cd [civicrm]/ext/
tar xzf ch.ipik.practicebooking-x.y.z.tar.gz
cv ext:enable ch.ipik.practicebooking
```

Then **Administer → System Settings → Extensions → Practice Booking →
Install**.

Full configuration is described in [INSTALL.md](INSTALL.md): rooms,
practitioners, availability, appointment types, CalDAV calendars, billing,
public form, message templates.

## Status

Beta. In production on two sites since September 2026. The data model is
stable; the interface still evolves.

## License

AGPL-3.0. See [LICENSE](LICENSE).
