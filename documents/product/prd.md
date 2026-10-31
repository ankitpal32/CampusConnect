# Product Requirements Document (PRD) — CampusConnect

## 1. Executive Summary

**CampusConnect** is a realistic, student-focused web platform designed for colleges and universities to manage, discover, and register for campus events. 

In many colleges, campus events are announced via disparate channels—WhatsApp groups, social media flyers, physical bulletin boards, and departmental notice sheets. This leads to missed deadlines, overbooked venues, lack of verified registration records, and general confusion for students. CampusConnect solves this problem by providing a unified, centralized directory where organizers can publish events with verified venue capacities and registration deadlines, and students can discover activities, reserve seats instantly, and track their participation.

---

## 2. Target Audience & User Personas

### Persona A: The College Student ("Priya")
- **Profile**: 2nd-year undergraduate engineering student eager to participate in hackathons, robotics workshops, and cultural fests to build skills and campus connections.
- **Pain Points**:
  - Finds out about workshops after the registration deadline has already passed.
  - Arrives at a popular seminar room only to find all seats taken.
  - Has no single place to check the time, date, or venue directions for events she signed up for.
- **Goals**:
  - Quickly search and filter upcoming campus events by topic or venue.
  - Reserve a seat with one click using her student credentials.
  - Review her schedule and cancel gracefully if she has an unexpected lab exam.

### Persona B: The Campus Administrator / Faculty Organizer ("Prof. Sharma")
- **Profile**: Department faculty coordinator or campus club administrator responsible for organizing university events.
- **Pain Points**:
  - Manual Google Forms spreadsheets that easily exceed room capacity.
  - Duplicate registrations and lack of student accountability.
  - Difficulty tracking attendee rolls and generating attendance summaries for certificates or department reports.
- **Goals**:
  - Publish events with hard capacity limits and strict deadlines.
  - Prevent overbooking automatically through database transactions.
  - Access a clear roster of registered students with contact details.
  - View live occupancy rates and event performance metrics.

---

## 3. Core Problem & Product Principles

1. **Realistic, Not Overhyped**: No fake marketing testimonials, invented user statistics, or AI-dashboard clutter. The platform feels like authentic software built for a real college.
2. **Data Integrity Over Surface Glamour**: Every displayed count, seat availability indicator, and attendee status is backed strictly by live MySQL database queries.
3. **Guaranteed Capacity & Fair Access**: Overbooking is prevented at the database transaction layer using row-level locks (`SELECT ... FOR UPDATE`).
4. **Student Accountability & Graceful Cancellation**: Students can cancel bookings before event kickoff, immediately returning the seat to the open inventory for other students.
5. **Simplicity and Accessibility**: Built with accessible forms, high-contrast editorial typography, keyboard navigability, and responsive layouts that work on mobile and desktop alike.

---

## 4. Current Feature Scope (What's Implemented)

### 4.1. Account & Authentication Management
- **Student Registration**: Name, college email, password (8-72 characters), password confirmation, and BCrypt hashing (`register.php`).
- **Secure Sign-In**: Role-aware authentication (`login.php`). Students are redirected to the student portal (`dashboard.php`), administrators to the management center (`admin/index.php`).
- **Session Security**: Session ID regeneration on login (`session_regenerate_id(true)`), CSRF token rotation, and secure POST-only sign out (`logout.php`).
- **Role Permissions**: Role-based access control protecting student and admin routes (`requireLogin()`, `requireAdmin()`).

### 4.2. Public Event Discovery
- **Homepage (`index.php`)**: Editorial campus hero, database-driven upcoming events, capacity progress meters, category pathways, "How It Works" workflow, student checklist, and real student FAQ.
- **Event Catalog (`events.php`)**: Multi-field search (title, venue, description), category filtering (dynamically detected from the database), sort options (soonest date, latest date, A-Z, highest capacity), and active filter tags.
- **Event Detail Page (`event-details.php`)**: Complete event overview, date/time, venue, registration deadline, capacity meter, state-aware registration button, and student cancellation actions.

### 4.3. Student Portal
- **Dashboard (`dashboard.php`)**: Personalized greeting, live metrics (upcoming events, active bookings, past/cancelled records), quick shortcuts, and upcoming registered events grid.
- **My Registrations (`my-registrations.php`)**: Split view showing active upcoming registrations with cancellation actions, and historical past/cancelled records table.
- **Transactional Booking (`register-event.php`)**: Atomic seat reservation with capacity checks, deadline checks, and duplicate prevention.
- **Graceful Cancellation (`cancel-registration.php`)**: Cancels only the student's own registration for future published events, freeing the seat.

### 4.4. Administration Center
- **Admin Dashboard (`admin/index.php`)**: Platform overview showing total events, published events, student accounts, active registrations, quick management shortcuts, and recent events.
- **Event Catalog Management (`admin/events.php`)**: Complete table of all events with status badges (`published`, `draft`, `cancelled`), attendee/capacity fill bars, and actions to View, Edit, or open Attendees.
- **Event Creation (`admin/event-form.php`)**: Clean form with title, category, venue, description, datetime pickers, capacity bounds, and publication status.
- **Event Editing (`admin/edit-event.php`)**: Edit existing events with capacity enforcement (prevents reducing capacity below confirmed registrations).
- **Attendee Roster (`admin/attendees.php`)**: Event dropdown selector, confirmed vs cancelled summary, and student roster table with emails and registration timestamps.
- **Analytics & Reports (`admin/reports.php`)**: Overall capacity utilization bar, fill rate percentages, and per-event performance table.

---

## 5. Future Roadmap & Incomplete Features

While the core lifecycle (event discovery &rarr; registration &rarr; schedule tracking &rarr; admin oversight) is fully functional and stable, the following features remain planned for upcoming iterations:

1. **Live Check-in & Attendance Marking**:
   - The database schema already includes an `attendance` table (`id`, `registration_id`, `checked_in_at`), but the UI for checking in students at the venue door (or QR code check-in) is not yet built.
2. **Poster Image Uploads**:
   - The `events` table has a `poster` column and an `uploads/` directory exists, but uploading image files from `event-form.php` is not yet hooked up.
3. **Email Notifications & Calendar Invites**:
   - Confirmation emails upon registration and ICS calendar invite downloads.
4. **CSV Export for Event Organizers**:
   - Exporting the attendee roster from `admin/attendees.php` to CSV/Excel for offline check-in or printing.
5. **Student Profile & Password Updates**:
   - A settings screen where students can update their display name or change passwords.

---

## 6. Success Metrics

- **Zero Overbooking**: 100% adherence to venue capacity constraints under concurrent traffic.
- **High Seat Utilization**: Seat return rate via cancellations, ensuring open slots don't stay empty.
- **Search Latency**: Sub-50ms query times on standard XAMPP/MySQL infrastructure using proper composite indexes (`idx_events_date_status`).
- **Accessibility & Reliability**: Clean semantic HTML5, zero broken links, and accessible focus states across all user flows.
