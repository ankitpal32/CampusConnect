# Project Memory & Developer Log — CampusConnect

This document serves as an informal engineering journal for CampusConnect. It records the motivation behind the project, why specific architectural choices were made, problems encountered along the way, resolved bugs, and notes on future technical debt.

---

## 1. Project Background & Origins

CampusConnect started as an effort to solve a widespread problem in college campus life: event discovery and registration is chaotic. In our college, notices for hackathons, guest lectures, sports tournaments, and club orientations were scattered across WhatsApp group forwards, flyers taped to bulletin boards, and last-minute departmental emails.

The main consequences were:
- Students regularly missed registration deadlines.
- Popular seminar rooms were frequently overbooked, leading to turned-away attendees.
- Event organizers spent hours manually cross-checking Google Forms and deduplicating spreadsheets.
- Students had no central place to view their own upcoming schedules.

CampusConnect was built to fix this by creating a single, reliable university event hub with verified venue capacities, strict registration deadlines, and instant seat confirmation.

---

## 2. Key Architecture Decisions & Rationale

### Decision A: Native PHP 8.x Over a Heavy JavaScript Framework
- **Context**: When starting out, it was tempting to use React, Next.js, or Laravel.
- **Why we chose Core PHP**:
  - **Zero Build Tooling Overhead**: Native PHP requires no Node build steps, `npm install` dependency bloat, webpack/vite configs, or hydration bugs.
  - **Educational Value**: For a student or junior developer joining the team, native PHP with PDO is transparent and approachable. You can trace every HTTP request directly to a single file.
  - **Direct Portability**: Runs instantly on standard university servers, low-cost cPanel hosts, or local XAMPP without needing Node/Redis daemons.

### Decision B: Database Concurrency Control via `FOR UPDATE`
- **Context**: When 50 students try to claim the last 5 seats in a coding workshop at the same moment, standard `SELECT` then `INSERT` logic creates race conditions where 10 or 15 students might get confirmed.
- **Decision**: In `register-event.php`, we wrap registration in a PDO transaction and lock the event row with `SELECT id, capacity, registration_deadline FROM events WHERE id = :id FOR UPDATE`. This serializes registration checks so the capacity limit is strictly enforced.

### Decision C: Restoring Cancelled Registrations vs Creating New Rows
- **Context**: The `registrations` table enforces a `UNIQUE KEY unique_user_event (user_id, event_id)` to guarantee a student cannot register twice.
- **Decision**: If a student registers, cancels, and later decides to register again, `register-event.php` updates the existing cancelled record back to `'registered'` with an updated `registered_at` timestamp rather than inserting a duplicate row. This preserves the unique constraint cleanly.

### Decision D: Editorial Design Identity Over Generic AI Dashboard Templates
- **Context**: Many AI-generated starter templates default to dark neon gradients, heavy glassmorphism, floating blur orbs, and emoji clutter.
- **Decision**: We designed an **editorial collegiate visual system** (`assets/css/style.css`):
  - Primary **Deep Violet** (`#6842e8`) inspired by academic excellence.
  - Warm off-white surfaces (`#faf9fd`) that feel clean and trustworthy.
  - Google Fonts **Outfit** for confident titles and **Inter** for readable body text.
  - Real database capacity meters (`.cc-progress-track`) instead of decorative graphics.

---

## 3. Notable Bugs & How They Were Resolved

1. **The `filter_input(INPUT_GET)` CLI Testing Trap**:
   - *Problem*: In PHP CLI mode or automated test runner scripts, `filter_input(INPUT_GET, 'id')` returns `null` because CLI does not populate the SAPI input filter environment, even when `$_GET['id']` is populated.
   - *Fix*: Standardized parameter validation across `event-details.php`, `admin/edit-event.php`, and `admin/attendees.php` to use `filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT)`. This works reliably in both web servers and automated testing environments.

2. **Admin Events Table Column Mismatch**:
   - *Problem*: In `admin/events.php`, the table header defined 5 columns (`Event`, `Date`, `Attendees`, `Status`, `Details`), but each row had 7 cells (splitting View, Edit, and Attendees into three individual `<td>` tags). This caused layout rendering glitches on some browsers.
   - *Fix*: Replaced the split cells with a single unified `<td class="text-end">` containing clean action buttons.

3. **Restricting Dynamic Category Filtering**:
   - *Problem*: `events.php` previously used a hardcoded `$allowedCategories` array. If an administrator created an event with a custom category in MySQL, filtering for that category was rejected.
   - *Fix*: Updated `events.php` to dynamically query `SELECT DISTINCT category FROM events WHERE status = 'published'` and merge this with the default category list, ensuring all active categories can be filtered.

4. **Dead Links in Header and Footer**:
   - *Problem*: `includes/header.php` and `includes/footer.php` linked to `/about.php` and `/contact.php`, which didn't exist in the project, leading to 404 errors.
   - *Fix*: Removed dead links and linked to functional on-page sections (`#categories`, `#how-it-works`, `#faq`) and active routes (`/events.php`).

---

## 4. Current State & Known Technical Debt

### What is 100% Complete & Working
- User registration, login, logout, password hashing, and CSRF token protection.
- Student dashboard and schedule tracking with active vs past registration filtering.
- Transactional event booking with capacity and deadline enforcement.
- Student-side cancellation with seat recovery.
- Public homepage with live upcoming events, category exploration, and FAQ.
- Public event catalog with multi-keyword search, category filter, and sorting.
- Admin dashboard, event creation, event editing, attendee rosters, and reports.
- Comprehensive UI/UX redesign with zero syntax errors across 22 PHP files and 15 passing automated test endpoints.

### Known Technical Debt & Upcoming Tasks
1. **Attendance Marking UI**:
   - The `attendance` table exists in SQL, but there is no button in `admin/attendees.php` to mark a student as attended at the door. Need to add a check-in toggle.
2. **Poster Image Uploads**:
   - The `events.poster` column is ready, but `admin/event-form.php` does not yet accept file uploads to the `uploads/` directory.
3. **Attendee Roster CSV Export**:
   - Organizers currently have to copy names from the screen. Adding a `export-attendees.php` script that generates a `.csv` file will be a major convenience for faculty.
4. **Email Notifications**:
   - Integrating standard SMTP to dispatch booking confirmations to students.
