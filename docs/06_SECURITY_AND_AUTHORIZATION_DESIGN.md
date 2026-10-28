# BuildConnect — Security & Authorization Design

## 1. Security Architecture Principles

BuildConnect adheres to the **Principle of Defense in Depth**, implementing security controls across every layer of the application architecture:

```text
[ Client Request ]
       |
       v
+-------------------------------------------------------+
| 1. Transport Layer Security (HTTPS / SSL/TLS)         |
+-------------------------------------------------------+
       |
       v
+-------------------------------------------------------+
| 2. Session Integrity & Regeneration Guard             |
|    - session_regenerate_id(true) on login             |
|    - HttpOnly, SameSite=Lax, Secure cookie parameters |
+-------------------------------------------------------+
       |
       v
+-------------------------------------------------------+
| 3. CSRF Validation Layer                              |
|    - Cryptographic 64-character token check           |
+-------------------------------------------------------+
       |
       v
+-------------------------------------------------------+
| 4. Role-Based Access Control (RBAC)                   |
|    - require_login() + require_role($allowed_roles)   |
|    - HTTP 403 Forbidden on violation (unauthorized)   |
+-------------------------------------------------------+
       |
       v
+-------------------------------------------------------+
| 5. Input Validation & IDOR Ownership Enforcement      |
|    - Type casting & regex sanitization                |
|    - WHERE project_id = ? AND contractor_id = ?       |
+-------------------------------------------------------+
       |
       v
+-------------------------------------------------------+
| 6. Database Parameterized Queries                     |
|    - 100% PDO Prepared Statements (Zero Raw SQL)      |
+-------------------------------------------------------+
       |
       v
+-------------------------------------------------------+
| 7. Output Encoding & Activity Audit Logging           |
|    - htmlspecialchars(..., ENT_QUOTES, 'UTF-8')      |
|    - Immutable activity_logs capture                  |
+-------------------------------------------------------+
```

---

## 2. Role-Based Access Control (RBAC) Matrix

The table below delineates the verified operational permissions across each system module:

| Module / Operation | Admin (`admin`) | Contractor (`contractor`) | Worker (`worker`) | Client (`client`) | Guest / Public |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Public Landing & Information** | YES | YES | YES | YES | YES |
| **Account Registration** | NO | YES | YES | YES | YES |
| **Account Login / Logout** | YES | YES | YES | YES | YES |
| **Access Admin Dashboard (`admin/`)** | **YES** | NO (403) | NO (403) | NO (403) | NO (302 Login) |
| **User Administration & Suspension** | **YES** | NO | NO | NO | NO |
| **Verify Worker Documents** | **YES** | NO | NO | NO | NO |
| **Access Contractor Portal (`contractor/`)**| NO (403) | **YES** | NO (403) | NO (403) | NO (302 Login) |
| **Create Projects & Milestones** | NO | **YES** | NO | NO | NO |
| **Post Construction Jobs** | NO | **YES** | NO | NO | NO |
| **Review Applicants & Hire Workers** | NO | **YES** | NO | NO | NO |
| **Generate QR Attendance Session** | NO | **YES** | NO | NO | NO |
| **Issue Digital Contracts** | NO | **YES** | NO | NO | NO |
| **Access Worker Portal (`worker/`)** | NO (403) | NO (403) | **YES** | NO (403) | NO (302 Login) |
| **Browse & Apply for Jobs** | NO | NO | **YES** | NO | NO |
| **Scan QR Attendance** | NO | NO | **YES** | NO | NO |
| **Sign Digital Contract** | NO | NO | **YES** | NO | NO |
| **Accept Instant Booking (Mode A)** | NO | NO | **YES** | NO | NO |
| **Access Client Portal (`client/`)** | NO (403) | NO (403) | NO (403) | **YES** | NO (302 Login) |
| **Create Mode A Instant Booking** | NO | NO | NO | **YES** | NO |
| **Create Mode B Quote Request** | NO | NO | NO | **YES** | NO |
| **Inspect Booking Details** | **YES (Inspect)** | NO | NO | **YES (Owner)**| NO |
| **Submit Competitive Quote (Mode B)** | NO | **YES** | **YES** | NO | NO |
| **Accept Winning Quote** | NO | NO | NO | **YES** | NO |
| **Submit Star Rating & Review** | NO | **YES** | **YES** | **YES** | NO |
| **Moderate Reviews & Flag Reports** | **YES** | NO | NO | NO | NO |
| **View Audit Logs & System Settings** | **YES** | NO | NO | NO | NO |

---

## 3. Defense Mechanisms & Implementations

### 3.1 Authentication & Session Security
* **Password Hashing**: Implemented via PHP's native `password_hash($password, PASSWORD_BCRYPT)`. BCrypt generates salted, irreversible cryptographic hashes storing algorithm parameters and cost factors within the hash string.
* **Session Fixation Prevention**: Every successful login executes `session_regenerate_id(true)`. The previous session ID is immediately invalidated on the server, neutralizing session fixation vectors.
* **Secure Cookie Attributes**: Configured in server initialization:
  ```php
  ini_set('session.cookie_httponly', 1);
  ini_set('session.cookie_samesite', 'Lax');
  ini_set('session.use_only_cookies', 1);
  ```
  `HttpOnly` prevents client-side JavaScript from accessing session tokens, while `SameSite=Lax` mitigates cross-site request forgery attacks.

---

### 3.2 SQL Injection (SQLi) Prevention
BuildConnect strictly mandates that **100% of database interactions execute through parameterized PDO prepared queries**.

```php
// Standard Secure Database Query Pattern in BuildConnect
$stmt = $db->prepare("
    SELECT id, title, pay_rate, status 
    FROM jobs 
    WHERE contractor_id = ? AND status = ?
    ORDER BY created_at DESC
");
$stmt->execute([(int)$contractor_id, $status_filter]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

* Zero string concatenation or variable interpolation into SQL strings is permitted.
* Database error output is silenced in user-facing contexts, preventing information disclosure of database schemas or table names.

---

### 3.3 Cross-Site Scripting (XSS) Prevention
All user-contributed content (names, job titles, scopes of work, descriptions, comments, reviews) is sanitized prior to rendering in HTML contexts using the centralized helper function:

```php
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}
```

* `ENT_QUOTES` ensures both single (`'`) and double (`"`) quotes are converted into safe HTML entities, preventing attribute escape attacks.
* Character set is explicitly set to `UTF-8` to prevent multibyte bypass exploits.

---

### 3.4 Cross-Site Request Forgery (CSRF) Prevention
Every state-changing POST operation (form submission or asynchronous AJAX mutation) requires a valid synchronized CSRF token:

1. **Token Generation**: During session startup, a cryptographically secure 64-character token is initialized:
   ```php
   if (empty($_SESSION['csrf_token'])) {
       $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   }
   ```
2. **Form Embedding**: Outputted using the helper:
   ```php
   function csrf_field() {
       return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
   }
   ```
3. **Verification**: Executed before processing POST payloads:
   ```php
   function verify_csrf_token($token) {
       return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
   }
   ```
   `hash_equals()` provides timing-attack-safe comparison.

---

### 3.5 Insecure Direct Object References (IDOR) Protection
To prevent unauthorized users from viewing, modifying, or deleting other users' records by altering URL query parameters (e.g. `?id=5`), all queries validate contextual resource ownership:

```php
// Secure IDOR Mitigation Example: Updating a Project Task
$stmt = $db->prepare("
    UPDATE tasks t
    JOIN projects p ON t.project_id = p.id
    SET t.status = ?, t.updated_at = NOW()
    WHERE t.id = ? AND p.contractor_id = ?
");
$stmt->execute([$new_status, $task_id, (int)$logged_user['id']]);
```
If an unauthorized contractor attempts to modify another contractor's task, zero rows are affected and the system responds with an unauthorized notification.

---

### 3.6 File Upload Security Sandbox
Uploaded worker certifications, government identity documents, and blueprints are processed through a hardened security pipeline in `includes/functions.php`:
1. **Extension Whitelisting**: Strictly limits uploads to `.pdf`, `.jpg`, `.jpeg`, and `.png`. Executable extensions (`.php`, `.phtml`, `.exe`, `.sh`) are unconditionally rejected.
2. **MIME Type Inspection**: Uses PHP's `finfo_open(FILEINFO_MIME_TYPE)` to verify the true file signature, ensuring renamed executables are blocked.
3. **File Size Enforcement**: Hard limit of 5 MB (5,242,880 bytes) per file.
4. **Filename Randomization**: Files are saved with cryptographically randomized hexadecimal names (e.g. `doc_65f8a92b_a8c91.pdf`), eliminating directory traversal and file overwrite attacks.
5. **Execution Blocking**: The `uploads/` directory includes an `.htaccess` configuration preventing direct server execution of scripts.

---

### 3.7 Immutable Activity Audit Logging
Every critical state transition (logins, logouts, user status modifications, worker verification decisions, contract signings, booking disputes) is recorded in the `activity_logs` table:
* Captures: `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `created_at`.
* Administrators can inspect, filter, and audit logs on `admin/audit-logs.php` without ability to alter or truncate historical logs.
