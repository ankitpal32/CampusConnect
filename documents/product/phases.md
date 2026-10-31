# Project Phases & Implementation Roadmap — CampusConnect

This document details the progressive development phases of CampusConnect, tracking what has been completed, what decisions were made during each phase, and what remains on the upcoming roadmap.

---

## Phase Status Overview

| Phase | Title | Focus Area | Status |
| :--- | :--- | :--- | :--- |
| **Phase 1** | Database & Infrastructure | MySQL Schema, PDO Setup, Connection Handling | **Completed** |
| **Phase 2** | Security & Authentication | User Accounts, Password Hashing, Session & CSRF | **Completed** |
| **Phase 3** | Public Event Discovery | Homepage, Event Catalog, Search & Filtering | **Completed** |
| **Phase 4** | Event Details & Booking | Event Details Page, Transactional Registration Engine | **Completed** |
| **Phase 5** | Student Portal & Schedule | Student Dashboard, Registration List, Cancellations | **Completed** |
| **Phase 6** | Admin Management & Analytics | Event Forms, Capacity Controls, Attendee Roster, Reports | **Completed** |
| **Phase 7** | Editorial UI/UX Redesign | Brand Identity, Style System, Accessibility, Responsive UX | **Completed** |
| **Phase 8** | Poster & Asset Uploads | File Uploads, Media Validation, Storage in `uploads/` | *Incomplete / Planned* |
| **Phase 9** | Live Attendance & Check-in | Attendance Table Integration, Door Check-in UI | *Incomplete / Planned* |
| **Phase 10** | Notifications & Exports | Attendee CSV Exports, Email Confirmations & Reminders | *Incomplete / Planned* |

---

## Detailed Phase Breakdown

### Phase 1: Database & Infrastructure (Completed)
- **Goal**: Establish a robust MySQL schema and configure secure database access using PHP PDO.
- **Deliverables**:
  - `database.sql`: Tables for `users`, `events`, `registrations`, and `attendance` with foreign key constraints and composite indexes (`idx_events_date_status`, `idx_registration_event_status`).
  - `config/database.php`: PDO connection instance configured with `ATTR_ERRMODE_EXCEPTION`, `ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`, and disabled prepared statement emulation.
- **Verification**: Verified PDO connection to `localhost:3306` with clean error logging to system logs.

### Phase 2: Security & Authentication (Completed)
- **Goal**: Implement secure student registration, session handling, password hashing, and role verification.
- **Deliverables**:
  - `register.php`: Account creation for students with BCrypt password hashing (`PASSWORD_DEFAULT`), email format checks, and duplicate email prevention.
  - `login.php`: Credential verification via `password_verify()`, session regeneration (`session_regenerate_id(true)`), and role-based redirection.
  - `logout.php`: POST-only sign-out with CSRF token verification.
  - `includes/auth.php`: Core helper library (`isLoggedIn()`, `requireLogin()`, `requireAdmin()`, `csrfToken()`, `verifyCsrfToken()`).
- **Verification**: Tested login redirects, session rotation, and CSRF rejection on invalid tokens.

### Phase 3: Public Event Discovery (Completed)
- **Goal**: Provide anonymous visitors and students with a clean way to discover upcoming campus activities.
- **Deliverables**:
  - `index.php`: High-impact editorial homepage showcasing upcoming published events directly from MySQL, capacity fill rates, and curated category shortcuts.
  - `events.php`: Search and filter catalog with multi-keyword matching on event title, venue, and description, dynamic category detection from MySQL, and sorting controls.
- **Verification**: Verified search queries, category filters, and empty state fallbacks.

### Phase 4: Event Details & Transactional Booking (Completed)
- **Goal**: Provide transparent event schedules, capacity availability, and an atomic seat registration engine.
- **Deliverables**:
  - `event-details.php`: Schedule info, venue details, capacity progress bar, deadline notices, and user-state-aware actions (login prompt for guests, register form for students, status badge for registered users, management buttons for admins).
  - `register-event.php`: Atomic registration using PDO transactions and row-level locking (`SELECT ... FOR UPDATE`), checking future dates, deadlines, and current capacity before inserting or restoring registrations.
- **Verification**: Tested registration under full capacity, past deadlines, and duplicate attempts.

### Phase 5: Student Portal & Schedule Management (Completed)
- **Goal**: Give students complete control over their event schedule and registrations.
- **Deliverables**:
  - `dashboard.php`: Student hub showing live statistics (upcoming registered events, active bookings, completed events) and upcoming schedule cards.
  - `my-registrations.php`: Detailed view split into "Active & Upcoming" cards and "Past / Cancelled Activity" table.
  - `cancel-registration.php`: Allows students to cancel only their own registrations for upcoming published events, restoring the seat to the open inventory.
- **Verification**: Confirmed cancellation updates registration status to `'cancelled'`, frees the seat count, and rejects cancellation of other students' bookings.

### Phase 6: Admin Management & Analytics (Completed)
- **Goal**: Equip campus administrators and faculty organizers with complete oversight of events and attendees.
- **Deliverables**:
  - `admin/index.php`: Overview with platform totals, management shortcuts, and recently added events.
  - `admin/events.php`: Comprehensive catalog table with status badges and attendee fill metrics.
  - `admin/event-form.php`: Event creation form with datetime inputs, category selection, and capacity bounds.
  - `admin/edit-event.php`: Event editing with row locking that enforces the rule: capacity cannot be lowered below current confirmed registrations.
  - `admin/attendees.php`: Event selector and student attendee roster with emails and registration timestamps.
  - `admin/reports.php`: Platform capacity utilization rate calculations and per-event occupancy metrics.
- **Verification**: Verified capacity reduction rejection when lower than active bookings, and verified attendance roster filtering.

### Phase 7: Editorial UI/UX Redesign (Completed)
- **Goal**: Transform the visual design into a modern, editorial, college-focused platform.
- **Deliverables**:
  - `assets/css/style.css`: Unified stylesheet with CSS variables for Deep Violet (`#6842e8`), lavender surfaces, and typography scales.
  - `includes/header.php` & `includes/footer.php`: Modern navigation with role pills, working anchor links, and clean footer navigation.
  - Redesigned all public, student, and admin views to use consistent cards, progress meters, badges, and focus rings.
- **Verification**: Tested responsive layouts across mobile and desktop breakpoints, ensuring 0 syntax errors across 22 PHP files and 15 passing automated endpoint tests.

---

## Upcoming Phases

### Phase 8: Event Poster Uploads & Media Handling (Planned)
- **Tasks**:
  - Enable `enctype="multipart/form-data"` on `admin/event-form.php` and `admin/edit-event.php`.
  - Validate file mime types (`image/jpeg`, `image/png`, `image/webp`), size limits (max 2MB), and sanitize filenames.
  - Store uploaded images in `uploads/posters/` and store the file path in `events.poster`.
  - Display poster banners gracefully on `event-details.php` and cards on `events.php`.

### Phase 9: Live Attendance Tracking & Check-In (Planned)
- **Tasks**:
  - Connect the existing `attendance` table to the administration interface.
  - Add a "Check In" toggle / button next to each student in `admin/attendees.php`.
  - Record the timestamp (`checked_in_at`) upon check-in.
  - Prevent double check-ins and display checked-in vs no-show counts in `admin/reports.php`.

### Phase 10: Organizer Tooling, Notifications & Exports (Planned)
- **Tasks**:
  - **CSV Attendee Export**: One-click download of the attendee roster from `admin/attendees.php` formatted with student names, emails, and check-in statuses.
  - **Email Confirmations**: Integrate PHPMailer or SMTP for registration confirmation emails.
  - **Calendar File (.ics)**: Provide an "Add to Google / Apple Calendar" button on `event-details.php`.
