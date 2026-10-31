# Visual Design System & UI/UX Guidelines — CampusConnect

This document details the visual design philosophy, color system, typography scale, component library, and accessibility rules implemented in CampusConnect.

---

## 1. Design Philosophy

CampusConnect follows an **editorial, product-first visual identity** designed specifically for a university community:

- **Authentic & Credible**: Avoids exaggerated marketing clichés, generic AI-dashboard aesthetics, neon borders, and floating decorative shapes. The interface feels like a real digital product maintained by a university.
- **Content-Driven Hierarchy**: Generous whitespace, high-contrast headings, and clear optical hierarchy make event dates, venue locations, and seat capacities instantly scannable.
- **Informative State Representation**: Every card and table visually communicates status (open seats, almost full, full capacity, deadline passed, cancelled) through meaningful color tokens and progress indicators rather than plain text.
- **Subtle Elevation & Tactility**: Restrained borders (`#e9e5f3`) and natural drop shadows (`0 4px 14px rgba(29, 25, 46, 0.05)`) rather than heavy drop shadows on every element.

---

## 2. Color System & Design Tokens

The color palette is declared as CSS custom properties in [`assets/css/style.css`](file:///c:/xampp/htdocs/CampusConnect/assets/css/style.css):

```css
:root {
    /* Brand Colors */
    --cc-primary: #6842e8;            /* Deep Violet: Core brand action color */
    --cc-primary-hover: #5531d4;      /* Darker violet for hover states */
    --cc-primary-dark: #4322b0;       /* Active / pressed state */
    --cc-primary-subtle: #f3effe;     /* Soft lavender for badges & tinted panels */
    --cc-primary-light: #ece6fd;      /* Light lavender */
    --cc-primary-border: #dcd4fa;     /* Subtle brand border */

    /* Neutrals & Surfaces */
    --cc-bg: #faf9fd;                 /* Warm off-white page background */
    --cc-surface: #ffffff;            /* Pure white card and container surface */
    --cc-surface-muted: #f5f4fa;      /* Light gray-purple surface for panels */
    --cc-border: #e9e5f3;             /* Soft neutral divider and card border */
    --cc-border-strong: #d8d3e7;      /* Input and interactive element border */
    --cc-text-main: #1d192e;          /* Dark ink text for headings and titles */
    --cc-text-body: #423e55;          /* Neutral body copy */
    --cc-text-muted: #6e6a82;         /* Supporting captions and labels */
    --cc-text-light: #9490a6;         /* Placeholder and subtle text */

    /* Semantic States */
    --cc-success: #15803d;            /* Restrained green for open seats & confirmed status */
    --cc-success-subtle: #edf8f0;     /* Light green tint for alerts & badges */
    --cc-success-border: #bbf0cb;     /* Green border token */
    --cc-warning: #b45309;            /* Amber for deadline warnings & admin indicators */
    --cc-warning-subtle: #fffbeb;     /* Light amber tint */
    --cc-warning-border: #fde68a;     /* Amber border */
    --cc-danger: #b91c1c;             /* Crimson for full capacity & cancellations */
    --cc-danger-subtle: #fef2f2;      /* Light red tint */
    --cc-danger-border: #fecaca;      /* Red border */
}
```

---

## 3. Typography Scale

CampusConnect combines two modern Google Fonts:

1. **Outfit** (`font-family: 'Outfit', sans-serif`):
   - Used for all major display headings (`h1` through `h4`), brand marks, and statistical values.
   - Characterized by clean, modern geometric proportions and high readability at large sizes.
2. **Inter** (`font-family: 'Inter', sans-serif`):
   - Used for body copy, form inputs, metadata labels, navigation links, and small badges.
   - Excellent screen legibility across dense data tables and small mobile displays.

### Typographic Hierarchy

| Role | Font | Size / Weight | Line Height | Usage |
| :--- | :--- | :--- | :--- | :--- |
| **Hero Display** | Outfit | `2.5rem` – `3.0rem` / 800 | 1.15 | Homepage and key section headings |
| **Page Title** | Outfit | `1.75rem` – `2.0rem` / 700 | 1.25 | Directory headers, Dashboard welcome |
| **Card Heading** | Outfit | `1.15rem` – `1.25rem` / 700 | 1.35 | Event titles on cards and details |
| **Section Eyebrow** | Inter | `0.76rem` / 800 (Uppercase) | 1.4 | Small uppercase category label with dot |
| **Body Copy** | Inter | `0.95rem` – `1.0rem` / 400 | 1.65 | Descriptions, guidelines, FAQ answers |
| **Metadata / Badges** | Inter | `0.78rem` – `0.85rem` / 600–700 | 1.4 | Dates, venues, status pills, counts |

---

## 4. Component Library (`assets/css/style.css`)

### 4.1. Cards & Interactive Containers
- `.cc-card`: Pure white background, `16px` border-radius, `1px solid var(--cc-border)`, and subtle shadow (`--cc-shadow-sm`).
- `.cc-card-interactive`: Adds a smooth hover transition (`transform: translateY(-3px)` and lavender shadow `--cc-shadow-hover`) to communicate clickability on event cards.

### 4.2. Buttons & Actions
- `.cc-btn-primary`: Solid Deep Violet background with white text, 12px border-radius, and a subtle purple shadow.
- `.cc-btn-outline`: White background with Deep Violet text and a crisp neutral border. Turns soft lavender on hover.
- `.cc-btn-sm` / `.cc-btn-lg`: Compact size modifiers for table action buttons or hero CTA buttons.

### 4.3. Badges & Tags (`.cc-badge`)
Pill-shaped badges (`border-radius: 9999px`) used for immediate status comprehension:
- `.cc-badge-category`: Soft lavender background (`#f3effe`) with deep violet text for category tags (`Technology`, `Workshop`, `Cultural`).
- `.cc-badge-success`: Soft green background with dark green text for `Confirmed Seat` and `Seats Available`.
- `.cc-badge-warning`: Amber tint for `Only 3 Left` or `Deadline Soon`.
- `.cc-badge-danger`: Crimson tint for `Sold Out (Full)` or `Cancelled`.
- `.cc-badge-neutral`: Gray-lavender tint for concluded events or historical records.

### 4.4. Capacity Progress Bars
- `.cc-progress-track`: Background track (`height: 6px`, `border-radius: 9999px`) in soft border gray.
- `.cc-progress-fill`: Solid Deep Violet fill bar with CSS transition showing the exact percentage of room capacity booked (`registered / capacity * 100%`).

### 4.5. Metric Stat Cards (`.cc-stat-card`)
Used across student and admin dashboards:
- `.cc-stat-label`: Uppercase muted text tracking the metric name.
- `.cc-stat-value`: Extra-bold Outfit numeral (`2.2rem`) in dark ink or brand violet.
- `.cc-stat-desc`: Supporting caption describing what the figure represents.

### 4.6. Admin Subnavigation (`.cc-admin-nav`)
Sticky sub-bar displayed across all admin views (`Dashboard`, `Manage Events`, `Create Event`, `Attendees`, `Reports`) with embedded SVG icons and active state highlights (`.active`).

---

## 5. Responsive UX & Breakpoint Strategy

- **Mobile (< 768px)**:
  - Navigation collapses cleanly into an accessible burger toggle.
  - Multi-column event forms wrap into a single focused column.
  - Horizontal tables are wrapped in `.table-responsive` with clean cell padding and horizontal scroll safeguards.
  - Buttons expand to full width (`w-100`) for thumb-friendly tap targets.
- **Tablet (768px – 991px)**:
  - Events render in a clean 2-column grid.
  - Dashboard stats render in 2x2 grids.
- **Desktop (>= 992px)**:
  - Event catalog displays in a balanced 3-column grid.
  - Event details page organizes into a 2-column editorial split: 8-column event agenda on the left, 4-column sticky registration card on the right (`top: 90px`).

---

## 6. Accessibility & Usability Standards

1. **High Contrast Ratios**: Dark ink text (`#1d192e`) on white surfaces exceeds WCAG AAA contrast ratio standards (> 7:1).
2. **Keyboard Focus Rings**: The `:focus-visible` pseudo-class applies a high-contrast 2px violet ring with a 3px offset, ensuring clear keyboard navigation for accessibility tools.
3. **Semantic HTML5**: Native `<header>`, `<nav>`, `<main>`, `<article>`, `<aside>`, and `<footer>` elements used throughout.
4. **Accessible Forms**: Explicit `<label for="...">` association on all input elements with inline helper descriptions (`.form-text`).
5. **Reduced Motion Support**:
   ```css
   @media (prefers-reduced-motion: reduce) {
       *, ::before, ::after {
           animation-duration: 0.01ms !important;
           transition-duration: 0.01ms !important;
           scroll-behavior: auto !important;
       }
   }
   ```
