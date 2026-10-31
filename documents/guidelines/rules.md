# Development Rules & Engineering Standards — CampusConnect

This document outlines the coding standards, security constraints, and database conventions enforced across the CampusConnect codebase.

---

## 1. General Principles & Stack Standards

1. **Native PHP Core**: Keep the codebase written in clear, procedural PHP 8.x with reusable helper libraries (`includes/auth.php`). Do not introduce heavy full-stack frameworks (such as Laravel or Symfony) unless the project scope explicitly demands it.
2. **Readability for Student Developers**: Code should be readable, well-commented, and straightforward so any developer can inspect and understand how authentication, routing, and database queries work.
3. **No Unverified Dependencies**: Rely on native PHP extensions (PDO, OpenSSL, Hash, Session, Filter) and trusted CDNs (Bootstrap 5.3.3, Google Fonts) rather than accumulating npm packages or composer dependencies for simple tasks.

---

## 2. Database & PDO Query Standards

### 2.1. Prepared Statements are Mandatory
- **Never** concatenate variables directly into SQL strings.
- **Always** use PDO prepared statements with named placeholders (`:id`, `:email`, `:category`, etc.):
  ```php
  // CORRECT:
  $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = :email LIMIT 1');
  $stmt->execute(['email' => $email]);

  // STRICTLY FORBIDDEN:
  $stmt = $pdo->query("SELECT id, name FROM users WHERE email = '$email'"); // Vulnerable to SQLi
  ```
- Emulated prepared statements must remain disabled in PDO settings (`PDO::ATTR_EMULATE_PREPARES => false`) to ensure true database-side parameter binding.

### 2.2. Concurrency & Transactions
- Any state-changing operation involving capacity (such as booking an event or reducing an event's maximum capacity) must run inside a transaction:
  ```php
  $pdo->beginTransaction();
  try {
      // 1. Lock the row to prevent concurrent race conditions
      $lock = $pdo->prepare('SELECT capacity FROM events WHERE id = :id FOR UPDATE');
      $lock->execute(['id' => $eventId]);

      // 2. Perform validations and updates...

      $pdo->commit();
  } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
          $pdo->rollBack();
      }
      error_log('Database error: ' . $e->getMessage());
      // Handle user feedback...
  }
  ```
- **Business Rule**: An event's capacity can never be reduced below its current confirmed active registrations.

---

## 3. Security & Authentication Rules

### 3.1. Password Security
- Passwords must be hashed using `password_hash($password, PASSWORD_DEFAULT)` which applies standard BCrypt.
- Passwords must be verified using `password_verify($password, $user['password_hash'])`.
- Never use legacy or weak algorithms (e.g. `md5()`, `sha1()`, or raw strings).
- Never log passwords or display password hashes anywhere in HTML markup or error logs.

### 3.2. Session Security
- Always call `session_regenerate_id(true)` immediately after successful password verification to mitigate session fixation attacks.
- Ensure sessions are initialized only once per request using:
  ```php
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }
  ```

### 3.3. Cross-Site Request Forgery (CSRF) Protection
- All state-changing requests (event booking, registration cancellation, event creation, event editing, logout) **must use HTTP POST**.
- Every POST form must include a hidden `csrf_token` input:
  ```html
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
  ```
- The backend handler must verify this token using `verifyCsrfToken($token)` which applies timing-attack-safe comparison (`hash_equals()`).

### 3.4. Role-Based Authorization & IDOR Defense
- Public pages are accessible to all visitors.
- Student routes (`dashboard.php`, `my-registrations.php`) must invoke `requireLogin()` and confirm student role.
- Administrator routes (`admin/*`) must invoke `requireAdmin()` and verify administrator status against the live `users` table to handle role revocations immediately.
- **Insecure Direct Object Reference (IDOR) Defense**: When a student cancels a registration, the SQL query must strictly bind their authenticated session user ID:
  ```sql
  UPDATE registrations
  SET status = 'cancelled'
  WHERE id = :registration_id
    AND user_id = :session_user_id
  ```
  Students must never be able to view, edit, or cancel another student's registration.

---

## 4. Frontend & XSS Prevention Rules

### 4.1. Universal HTML Escaping
- Every dynamic value printed into HTML markup must be sanitized with `htmlspecialchars()`:
  ```php
  function eventEscape($value): string {
      return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
  }
  ```
- Never use unescaped `<?= $variable ?>` for database content.

### 4.2. Clean CSS Token Usage
- When writing custom CSS, use the predefined tokens in `assets/css/style.css` (e.g. `var(--cc-primary)`, `var(--cc-border)`, `var(--cc-card)`).
- Avoid inline style duplication where a reusable component class exists (`.cc-card`, `.cc-badge`, `.cc-btn-primary`, `.cc-stat-card`).

### 4.3. Accessibility (a11y)
- Every `<input>` and `<select>` must have an associated `<label for="...">`.
- All interactive buttons must have clear text or `aria-label` attributes.
- Use native HTML elements (`<main>`, `<nav>`, `<article>`, `<aside>`) to ensure screen readers navigate easily.

---

## 5. Error Handling & Information Disclosure

- **Never display raw database error traces** to end users. A database connection error or query failure should present a polite user-facing alert:
  ```php
  http_response_code(500);
  exit('A database error occurred. Please try again later.');
  ```
- Use `error_log()` to write technical diagnostic details to the server error log for developer debugging.
- Use semantic HTTP status codes (`400` for bad input, `403` for forbidden actions, `404` for missing records, `405` for wrong HTTP methods).
