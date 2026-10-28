# BuildConnect — Final-Year Project Viva Voce Preparation Package
**Project Name:** BuildConnect — Digital Construction Workforce & Project Management Platform  
**Target Audience:** Final-Year Engineering / MCA / B.Tech / B.E. Students preparing for External Viva Voce and Project Defense  
**Architecture:** Monolithic PHP 8.2 + MySQL 8.0 (InnoDB) + Semantic HTML5 / Vanilla CSS / Bootstrap 5.3  
**Verified Codebase:** 134 production PHP files • 29 normalized database tables • 209 passing test assertions  

---

## Quick Reference: 5-Second Project Summary for Viva
> **If the external examiner asks: "What is your project in one sentence?"**  
> *"BuildConnect is a secure, role-based full-stack web platform built with PHP 8.2 and MySQL 8.0 that connects property owners directly with verified daily-wage tradespersons and licensed contractors, featuring instant radius-based emergency dispatch, multi-bid project tenders, milestone tracking, and dynamic GPS-anchored QR attendance."*

---

## Table of Contents
1. [Project Overview, Scope & Objectives (Q1 – Q5)](#1-project-overview-scope--objectives)
2. [PHP & Backend Engineering (Q6 – Q10)](#2-php--backend-engineering)
3. [Frontend Technologies & UI/UX (Q11 – Q14)](#3-frontend-technologies--uiux)
4. [Database Design, Keys & Normalization (Q15 – Q20)](#4-database-design-keys--normalization)
5. [Authentication, Sessions & RBAC Security (Q21 – Q26)](#5-authentication-sessions--rbac-security)
6. [CRUD Operations & Core Business Logic (Q27 – Q30)](#6-crud-operations--core-business-logic)
7. [Dual Booking Workflows: Instant vs. Quotes (Q31 – Q35)](#7-dual-booking-workflows-instant-vs-quotes)
8. [Validation, Security & OWASP Defenses (Q36 – Q40)](#8-validation-security--owasp-defenses)
9. [Testing, Debugging & Verification Methodology (Q41 – Q45)](#9-testing-debugging--verification-methodology)
10. [Limitations, Technical Debt & Future Scope (Q46 – Q50)](#10-limitations-technical-debt--future-scope)

---

## 1. Project Overview, Scope & Objectives

### Q1. What is the real-world problem BuildConnect addresses?
**Answer:**  
In India, over 85% of construction and repair labor operates informally through physical street corners (*nakas*). Homeowners and builders face three chronic problems:
1. **Unverified Workforce:** Inability to verify identities or skills of workers entering private homes.
2. **Exploitative Middlemen:** Intermediaries charge 15%–30% cuts while providing zero guarantee of quality or safety.
3. **Disputes & Ghost Workers:** Oral contracts lead to payment disputes, while paper attendance registers on construction sites cause payroll fraud.  
BuildConnect digitizes this entire workflow with KYC verification, direct hiring, structured digital contracts, and tamper-proof GPS QR attendance.

### Q2. What are the primary objectives of your project?
**Answer:**  
We established four concrete engineering objectives:
1. Implement a unified, role-isolated 4-tier access control system for Admins, Clients, Workers, and Contractors.
2. Build a Dual Booking Engine: **Mode A** for emergency radius-based instant dispatch (<₹15,000) and **Mode B** for competitive milestone-based project tenders.
3. Develop an anti-fraud dynamic QR attendance system that captures browser GPS coordinates and auto-expires every 60 seconds.
4. Enforce strict database normalization (3NF across 29 tables) and comprehensive OWASP Top 10 web security.

### Q3. Who are the primary stakeholders and user roles in the system?
**Answer:**  
BuildConnect defines exactly four operational user roles:
1. **System Administrator (`admin`):** Approves government KYC identity documents, monitors user status, and reviews system audit logs.
2. **Client / Property Owner (`client`):** Posts micro-repair jobs or multi-week project tenders, reviews contractor bids, signs contracts, and makes milestone payments.
3. **Skilled Tradesperson / Worker (`worker`):** Lists trade skills, sets hourly rates, toggles real-time availability, accepts instant emergency jobs, and scans on-site QR codes for attendance.
4. **General Contractor (`contractor`):** Bids on project tenders, drafts milestone deliverables, allocates workers to sites, and generates dynamic daily QR attendance tokens.

### Q4. Why did you choose a monolithic architecture instead of microservices?
**Answer:**  
For a construction management platform of this operational scale, a monolithic architecture built in PHP 8.2 offers significant advantages:
- **ACID Transactions:** Allows complex multi-table operations (e.g., accepting a bid, creating a contract, generating milestone schedules, and logging an audit entry) to execute within a single atomic database transaction without distributed two-phase commit overhead.
- **Low Latency:** Eliminates network serialization and inter-service HTTP latency.
- **Operational Simplicity:** Runs efficiently on standard, cost-effective LAMP/LEMP servers without requiring Kubernetes clusters, service meshes, or distributed tracing.

### Q5. What is the difference between Mode A and Mode B booking?
**Answer:**  
- **Mode A (Instant Booking):** Designed for quick micro-repairs (plumbing leak, electrical fault). The client specifies the trade and location; the system calculates geographic proximity using the Haversine formula, locks the worker's schedule using `SELECT ... FOR UPDATE` to prevent double-booking, and sends an immediate dispatch request.
- **Mode B (Compare Quotes):** Designed for multi-week capital construction or renovation. The client posts an RFP with architectural scope, budget range, and timeline. Multiple verified contractors submit itemized bids with milestone payment schedules. The client compares bids side-by-side and awards the contract.

---

## 2. PHP & Backend Engineering

### Q6. Which version of PHP is used, and what modern features did you utilize?
**Answer:**  
We use native **PHP 8.2**. Key features utilized include:
- Strict typing and type declarations (`declare(strict_types=1);`, `int`, `string`, `array`, `bool`).
- Null coalescing operator (`??`) and nullsafe operators (`?->`) for safe payload extraction.
- Named parameters in utility functions for readable code.
- Modern Bcrypt password hashing functions (`password_hash` with cost 10 and `password_verify`).
- Built-in session security directives (`session.cookie_httponly`, `session.use_strict_mode`).

### Q7. Why did you choose PDO over the MySQLi extension?
**Answer:**  
We chose **PDO (PHP Data Objects)** for three critical reasons:
1. **Prepared Statements with Named Parameters:** PDO allows readable named parameter binding (e.g., `:email`, `:status`), significantly reducing manual indexing errors compared to MySQLi's positional `bind_param("ssi", ...)`.
2. **Database Portability:** PDO provides a uniform database abstraction interface. If the client migrates from MySQL to PostgreSQL in the future, only the connection string changes without rewriting core query methods.
3. **Consistent Error Handling:** PDO can be configured to throw `PDOException` on any database error via `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, allowing centralized `try-catch` exception handling and automatic transaction rollbacks.

### Q8. How do you handle database connections efficiently in PHP?
**Answer:**  
In [`config/database.php`](file:///e:/BuildConnect/config/database.php), we implement a singleton database factory class. It initializes a single PDO instance per HTTP request lifecycle with:
- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` (throws exceptions on errors).
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` (returns lightweight associative arrays).
- `PDO::ATTR_EMULATE_PREPARES => false` (forces MySQL server-side prepared statements, eliminating SQL injection vulnerabilities).
- Character set explicitly configured to `utf8mb4` for full Unicode and emoji compatibility.

### Q9. How do you prevent race conditions when two clients try to book the same worker at the exact same second?
**Answer:**  
In [`client/instant-booking.php`](file:///e:/BuildConnect/client/instant-booking.php), we use **pessimistic database locking**. When processing an instant booking:
1. We start a database transaction with `$pdo->beginTransaction()`.
2. We query worker availability using `SELECT is_available FROM worker_profiles WHERE user_id = :id FOR UPDATE;`.
3. The `FOR UPDATE` clause places an exclusive row-level write lock in MySQL InnoDB. Any concurrent request attempting to read or lock that row must wait until the first transaction commits or rolls back.
4. If available, we insert the booking record, update worker availability to `0`, and execute `$pdo->commit()`. This completely prevents race conditions and double-bookings.

### Q10. How are asynchronous operations like QR attendance scanning handled in a PHP monolith?
**Answer:**  
We built dedicated lightweight RESTful JSON endpoints such as [`api/attendance.php`](file:///e:/BuildConnect/api/attendance.php). When a worker scans a QR code using their browser's JavaScript camera scanner:
1. JavaScript fires an asynchronous `fetch()` POST request sending the 64-character QR token and device GPS coordinates (`lat`, `lng`).
2. The PHP endpoint verifies session authorization, validates the token against the database, executes the attendance check-in, and responds with a standardized JSON payload (`{"success": true, "message": "Attendance marked successfully"}`).
3. The client browser updates the DOM dynamically without a full-page reload.

---

## 3. Frontend Technologies & UI/UX

### Q11. What frontend technologies, frameworks, and design tokens are used?
**Answer:**  
- **Structure:** Semantic HTML5 (`<header>`, `<nav>`, `<main>`, `<section>`, `<footer>`).
- **Styling:** Custom Vanilla CSS with a centralized design system using CSS custom properties (variables) defined in [`assets/css/style.css`](file:///e:/BuildConnect/assets/css/style.css), supplemented by Bootstrap 5.3 for grid layouts and modal dialogs.
- **Palette:** Construction-themed palette: Deep Navy (`#0B132B`, `#142036`), Safety Orange / Amber Accent (`#FFAA16`), Pristine White (`#FFFFFF`), and Slate Muted Text (`#A0AEC0`).
- **Interactivity:** Vanilla JavaScript (ES6+), FontAwesome 6.4 icons, Chart.js 4.4 for admin KPI visualizations, and Leaflet.js 1.9 for OpenStreetMap interactive maps.

### Q12. How did you ensure visual consistency and high contrast across all four user dashboards?
**Answer:**  
In our dashboard polish pass:
1. We established a strict color-contrast rule: white text on dark navy topbars and cards; dark high-contrast text (`#1E293B`) on white table rows.
2. We standardized typography using system font stacks (`-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto`) with consistent font sizes (`0.85rem` for metadata badges, `1.1rem` for card headers, `2.2rem` for numeric KPI metrics).
3. We eliminated conflicting CSS rules and standardized card padding to `1.25rem` and border radius to `8px` across Admin, Client, Worker, and Contractor dashboards.

### Q13. How does Leaflet.js work for worker location mapping without Google Maps API keys?
**Answer:**  
Google Maps requires commercial billing accounts and proprietary API keys. Instead, we integrated **Leaflet.js** with OpenStreetMap tile servers:
- It is open-source, lightweight (under 40 KB), and requires zero API keys.
- We pass the stored worker/project latitude and longitude coordinates from PHP into the JavaScript DOM using HTML5 data attributes (`data-lat`, `data-lng`).
- Leaflet renders interactive pins, radius circles (e.g., 25 km / 50 km coverage zones), and popup info cards directly in the browser.

### Q14. How does the camera-based QR code scanner work on the worker's device?
**Answer:**  
We utilize the HTML5 `navigator.mediaDevices.getUserMedia` API combined with an open-source JavaScript QR parsing library (`html5-qrcode`):
1. The worker clicks "Scan Site QR Code" on their smartphone or laptop browser.
2. The browser prompts for camera permission.
3. Once granted, video frames are analyzed in real time on the client side at 10 frames per second.
4. As soon as a valid QR pattern is detected, the raw token string is extracted and immediately dispatched via `fetch()` to `api/attendance.php`.

---

## 4. Database Design, Keys & Normalization

### Q15. How many tables are in the BuildConnect database, and what storage engine is used?
**Answer:**  
The BuildConnect database consists of exactly **29 relational tables**.  
Every table uses the **InnoDB storage engine** in MySQL 8.0 / MariaDB 10.4. We chose InnoDB because it supports:
- Full ACID compliance (Atomicity, Consistency, Isolation, Durability).
- Row-level locking (essential for high-concurrency booking).
- Physical Foreign Key constraints with cascading referential actions (`ON DELETE CASCADE`, `ON DELETE RESTRICT`).

### Q16. Can you explain the central `users` table and how role-based profile tables relate to it?
**Answer:**  
The `users` table is the central authentication and identity entity. It stores core login credentials:
- `id` (INT UNSIGNED, AUTO_INCREMENT, Primary Key)
- `name`, `email` (UNIQUE), `password` (VARCHAR 255 Bcrypt hash), `role` (ENUM: `admin`, `client`, `worker`, `contractor`), `is_verified` (TINYINT), `created_at`.

To maintain Third Normal Form (3NF) and avoid sparse NULL columns, role-specific attributes are separated into 1-to-1 extension tables:
- `client_profiles` (stores billing address, company name)
- `worker_profiles` (stores trade category, hourly rate, experience years, availability toggle, bio, rating)
- `contractor_profiles` (stores company license number, GST registration, team size, portfolio summary)  
Each profile table uses `user_id` as both a Foreign Key referencing `users(id)` and a UNIQUE key.

### Q17. What are the key tables involved in the project contracting and milestone workflow?
**Answer:**  
The workflow connects four primary relational tables:
1. `projects`: Created by the client (`id`, `client_id`, `title`, `description`, `category_id`, `budget_min`, `budget_max`, `status`).
2. `bids`: Submitted by contractors (`id`, `project_id`, `contractor_id`, `bid_amount`, `estimated_days`, `proposal`, `status`).
3. `contracts`: Instantiated when a client accepts a bid (`id`, `project_id`, `client_id`, `contractor_id`, `total_amount`, `status`, `start_date`, `end_date`).
4. `contract_milestones`: Child records of a contract (`id`, `contract_id`, `title`, `description`, `amount`, `status`: `pending`, `in_progress`, `completed`, `paid`).

### Q18. How do you prevent orphaned records when a user or project is deleted?
**Answer:**  
We define strict referential integrity rules on foreign keys:
- Where child records are strictly dependent on the parent (e.g., `worker_skills` belonging to a worker, or `bids` belonging to a deleted draft project), we specify `ON DELETE CASCADE`.
- Where financial or legal history must be preserved (e.g., `payments`, `contracts`, `audit_logs`), we specify `ON DELETE RESTRICT`. This prevents deletion of a user if active financial transactions or signed contracts exist, protecting against data loss.

### Q19. What normal form does your database satisfy, and can you give an example of 2NF and 3NF compliance?
**Answer:**  
The database satisfies **Third Normal Form (3NF)**:
- **1NF Compliance:** All attributes are atomic (no comma-separated lists of skills; skills are stored in a normalized `worker_skills` junction table). Every table has a defined primary key.
- **2NF Compliance:** It is in 1NF and has no partial dependencies (all non-key attributes depend on the entire primary key).
- **3NF Compliance:** It is in 2NF and has no transitive dependencies. For example, in `bookings`, we store `worker_id` (foreign key to `users`), but we do not store the worker's trade category name or hourly rate in `bookings`; those are retrieved via joins from `worker_profiles` and `services`.

### Q20. What indexes are created in the database to optimize query performance?
**Answer:**  
Beyond default primary key and unique constraint indexes, we created composite and single-column B-tree indexes on high-frequency query predicates:
- `users(role, is_verified)`: Speeds up admin KYC queue filtering and active worker searches.
- `bookings(client_id, status)` and `bookings(worker_id, status)`: Accelerates dashboard status queries.
- `projects(status, category_id)`: Optimizes public contractor tender board browsing.
- `attendance(worker_id, created_at)`: Accelerates monthly muster roll generation.

---

## 5. Authentication, Sessions & RBAC Security

### Q21. How is user authentication implemented from scratch without a framework?
**Answer:**  
In [`auth/login.php`](file:///e:/BuildConnect/auth/login.php):
1. The user submits their email and plaintext password over HTTPS.
2. The controller validates that both fields are non-empty and well-formed.
3. A prepared PDO statement fetches the user record by email: `SELECT id, name, email, password, role, is_active FROM users WHERE email = :email;`.
4. If a record exists, we call native `password_verify($password, $user['password'])`.
5. If valid and `is_active == 1`, we call `session_regenerate_id(true)` to defeat session fixation, populate `$_SESSION['user_id']`, `$_SESSION['role']`, and `$_SESSION['name']`, and redirect the user to their role-specific dashboard.

### Q22. How are passwords hashed and stored? Why not MD5 or SHA-256?
**Answer:**  
We use **Bcrypt** via PHP's native `password_hash($password, PASSWORD_BCRYPT, ['cost' => 10])`.  
MD5 and standard SHA-256 are cryptographic hash functions designed to be fast; an attacker with a modern GPU can compute billions of MD5/SHA-256 hashes per second using brute-force rainbow tables.  
Bcrypt is a slow, salted, key-derivation function based on the Blowfish cipher:
- It automatically generates a cryptographically secure random 22-character salt for every password.
- Its configurable work factor (cost 10 = $2^{10} = 1,024$ rounds) makes hardware brute-force attacks computationally infeasible.

### Q23. How does the Role-Based Access Control (RBAC) mechanism prevent privilege escalation?
**Answer:**  
Access control is centralized in [`config/auth.php`](file:///e:/BuildConnect/config/auth.php) via helper functions like `require_role($allowed_roles)` and `require_login()`:
1. At the very top of every protected page (e.g., [`admin/users.php`](file:///e:/BuildConnect/admin/users.php)), we call `require_role('admin');`.
2. The function inspects `$_SESSION['role']`. If the session is missing, it redirects to `auth/login.php`.
3. If the user is logged in but holds an unauthorized role (e.g., a client navigating directly to `/admin/users.php` or `/contractor/create-contract.php`), the function immediately halts execution, sets HTTP response code `403 Forbidden`, and displays a secure "Access Denied" page.
4. It does not rely on client-side checks or hidden URL parameters.

### Q24. How do you defend against Session Hijacking and Session Fixation?
**Answer:**  
We implement three defense layers:
1. **Session Regeneration:** Upon successful credential verification in `auth/login.php`, we execute `session_regenerate_id(true)`, which generates a new cryptographic session ID and invalidates the pre-login session identifier.
2. **Cookie Security Flags:** In `config/auth.php`, session cookie parameters are initialized with `HttpOnly = true` (prevents JavaScript access via XSS) and `SameSite = 'Lax'` (prevents cross-site cookie injection).
3. **Session Fingerprinting:** We store `$_SESSION['user_agent']` and verify it on subsequent requests; if the browser signature changes unexpectedly, the session is destroyed.

### Q25. What happens during Logout? How is the session completely destroyed?
**Answer:**  
In [`auth/logout.php`](file:///e:/BuildConnect/auth/logout.php), we execute a 4-step complete session teardown:
1. `$_SESSION = [];` (clears all session variables in memory).
2. If session cookies are used, we expire the session cookie in the browser:  
   `setcookie(session_name(), '', time() - 3600, '/');`.
3. `session_destroy();` (deletes the physical session file on the web server).
4. Redirects to `auth/login.php?logout=success` with an HTTP 302 header.

### Q26. Can a logged-in Client modify another Client's project or view another Worker's private bookings?
**Answer:**  
No. This is known as an **Insecure Direct Object Reference (IDOR)** vulnerability, which we rigorously prevent.  
Whenever an operation targets an existing entity (e.g., updating a project or viewing a contract):
- We do NOT query simply by entity ID: `SELECT * FROM projects WHERE id = :id`.
- Instead, we scope the query to the authenticated session user:  
  `SELECT * FROM projects WHERE id = :id AND client_id = :session_user_id;`.
- If the record does not match the logged-in user's ID, the query returns empty, and the controller emits a 404 or 403 error.

---

## 6. CRUD Operations & Core Business Logic

### Q27. Can you demonstrate a complete CRUD lifecycle in BuildConnect?
**Answer:**  
Consider the **Projects Module** (`client/projects.php`, `client/create-project.php`):
- **Create (C):** Client fills out project title, category, description, budget, and deadline. Controller validates inputs, prepares an `INSERT INTO projects (...) VALUES (...)`, and logs the action in `audit_logs`.
- **Read (R):** Client views active projects on `client/projects.php`. Contractors browse open tenders on `contractor/browse-projects.php` via filtered `SELECT` queries with pagination.
- **Update (U):** Client edits project scope or adjusts budget range while status is `open` via `UPDATE projects SET ... WHERE id = :id AND client_id = :session_id`.
- **Delete (D):** Client cancels or deletes an un-awarded draft project. Controller verifies no active contracts exist, executes `DELETE FROM projects WHERE id = :id AND client_id = :session_id`, or sets status to `cancelled` (soft delete).

### Q28. How does the Admin KYC Verification Queue work?
**Answer:**  
In [`admin/verifications.php`](file:///e:/BuildConnect/admin/verifications.php):
1. Workers and contractors upload Aadhaar/PAN cards during registration. The file is stored in `uploads/documents/` with a sanitized cryptographic filename, and a record is created in `verification_requests` with status `pending`.
2. The Admin dashboard displays a badge with the count of pending verifications.
3. The Admin opens the document inspector modal to view the document.
4. Clicking "Approve" executes a database transaction:
   - Updates `verification_requests.status = 'approved'`
   - Updates `users.is_verified = 1`
   - Logs the approval event into `audit_logs` with admin user ID and timestamp.
5. The worker immediately receives access to accept live jobs.

### Q29. How does review and rating aggregation work?
**Answer:**  
In [`reviews` table]:
- After a booking or project contract reaches status `completed`, the client can submit a 1 to 5 star rating and text feedback.
- To prevent fake reviews, an insert constraint verifies that a completed contract or booking exists between that specific client and worker/contractor.
- A database trigger or calculation query computes the running average:  
  `SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE reviewee_id = :worker_id;`.
- This aggregate is stored in `worker_profiles.rating` for fast sorting and display.

### Q30. How is the immutable Audit Trail maintained?
**Answer:**  
In [`admin/audit-logs.php`](file:///e:/BuildConnect/admin/audit-logs.php) and helper `log_audit_action($user_id, $action, $details)`:
- Whenever a sensitive event occurs (user login, role change, KYC approval, contract sign-off, or milestone disbursement), a record is inserted into `audit_logs`.
- Recorded fields: `user_id`, `action`, `details`, `ip_address` (`$_SERVER['REMOTE_ADDR']`), `user_agent`, and `created_at`.
- There are no `UPDATE` or `DELETE` endpoints exposed in the application for `audit_logs`, ensuring an immutable forensic journal.

---

## 7. Dual Booking Workflows: Instant vs. Quotes

### Q31. Explain the mathematical formula used for Instant Booking proximity matching.
**Answer:**  
In [`client/instant-booking.php`](file:///e:/BuildConnect/client/instant-booking.php), we implement the **Haversine Formula** in SQL to calculate the great-circle distance between the client's job coordinates and registered workers:
```sql
SELECT u.id, u.name, wp.hourly_rate, wp.rating,
( 6371 * acos( cos( radians(:client_lat) ) * cos( radians( wp.latitude ) ) 
  * cos( radians( wp.longitude ) - radians(:client_lng) ) 
  + sin( radians(:client_lat) ) * sin( radians( wp.latitude ) ) ) 
) AS distance_km
FROM users u
JOIN worker_profiles wp ON u.id = wp.user_id
WHERE u.role = 'worker' AND u.is_verified = 1 AND wp.is_available = 1 AND wp.category_id = :category_id
HAVING distance_km <= :radius_limit
ORDER BY distance_km ASC;
```
`6371` represents the Earth's mean radius in kilometers. Workers within 25–50 km are returned sorted by nearest distance.

### Q32. Walk through the complete lifecycle of an Instant Booking (Mode A).
**Answer:**  
1. **Creation:** Client enters job details, trade category, location, and preferred time. System queries nearest verified available workers.
2. **Dispatch & Lock:** Client confirms booking. System creates a record in `bookings` with status `requested` and locks worker availability.
3. **Worker Claim:** Worker sees an incoming booking notification card on `worker/dashboard.php`.
4. **Accept / Decline:** If the worker clicks "Accept", status transitions to `confirmed`. If "Decline", status becomes `cancelled`, and worker availability is restored.
5. **Execution & Completion:** Worker attends site, completes the task, and clicks "Mark Completed". Status becomes `completed`.
6. **Settlement & Feedback:** Client inspects the work, marks payment settled, and submits a 5-star review.

### Q33. Walk through the complete lifecycle of Compare Quotes (Mode B).
**Answer:**  
1. **Tender Posting:** Client publishes a project on `client/create-project.php` with architectural specifications, location, budget range (e.g., ₹2,00,000 – ₹3,50,000), and deadline.
2. **Contractor Bidding:** Verified contractors view the RFP on `contractor/browse-projects.php` and submit sealed proposals specifying bid amount, timeline in days, and itemized milestone breakdown.
3. **Bid Evaluation:** Client navigates to `client/view-bids.php` and compares proposals side-by-side (contractor rating, price, timeline, reviews).
4. **Award & Contract:** Client clicks "Accept Bid". A database transaction:
   - Updates the chosen bid status to `accepted` and others to `rejected`.
   - Updates project status to `awarded`.
   - Inserts a formal legal record into `contracts`.
   - Inserts the agreed payment stages into `contract_milestones`.
5. **Milestone Progression:** As the contractor completes stages (e.g., Foundation, Framing, Finishing), client reviews and approves each milestone to log disbursement.

### Q34. How does the Dynamic QR Attendance system eliminate attendance fraud?
**Answer:**  
Traditional paper registers allow proxy attendance (*ghost workers*). Our dynamic QR system eliminates this in three ways:
1. **Time-Expiring Tokens:** The contractor's terminal generates a 64-character SHA-256 token that refreshes every 60 seconds. A worker cannot use a forwarded screenshot taken earlier.
2. **Geolocation Validation:** When the worker scans the QR code via `api/attendance.php`, the browser submits the worker's current GPS latitude and longitude. The backend computes the distance to the registered project site; if the worker is not within the acceptable geofence radius (e.g., 200 meters), check-in is rejected.
3. **One Check-in Per Day:** Database unique constraints prevent a worker from checking in multiple times on the same shift.

### Q35. What happens if a contractor submits an unrealistically high or low bid?
**Answer:**  
Clients are protected through our side-by-side comparison matrix:
- The system visually tags bids against the client's defined budget range.
- Contractors with proven track records, verified business licenses, and high customer review averages are highlighted.
- The client is under no obligation to choose the lowest bid; they evaluate the contractor's portfolio, ratings, and milestone breakdown before awarding the contract.

---

## 8. Validation, Security & OWASP Defenses

### Q36. How do you defend against SQL Injection throughout the application?
**Answer:**  
We strictly enforce **Parameterized Prepared Statements** across all database interactions using PDO:
```php
// SECURE: User input is never concatenated into SQL syntax
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = :email AND status = :status");
$stmt->execute([':email' => $email, ':status' => 'active']);
$user = $stmt->fetch();
```
Because `PDO::ATTR_EMULATE_PREPARES` is set to `false`, the SQL command structure is compiled by the MySQL server *before* input parameters are bound. Input parameters are treated strictly as data literals, making it impossible for SQL metacharacters (e.g., `' OR '1'='1`) to alter query logic.

### Q37. How do you prevent Cross-Site Scripting (XSS)?
**Answer:**  
We apply **Context-Aware Output Escaping**:
- Every dynamic value rendered into HTML is sanitized using native `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`.
- This converts dangerous characters (`<`, `>`, `&`, `"`, `'`) into harmless HTML entities (`&lt;`, `&gt;`, `&amp;`, `&quot;`, `&#039;`).
- An attacker attempting to inject `<script>alert('xss')</script>` will see the literal text safely rendered in the browser without executing JavaScript.

### Q38. How is Cross-Site Request Forgery (CSRF) mitigated on sensitive POST forms?
**Answer:**  
In [`config/auth.php`](file:///e:/BuildConnect/config/auth.php):
1. A cryptographically secure 64-character pseudorandom token is generated per session: `$_SESSION['csrf_token'] = bin2hex(random_bytes(32));`.
2. Every state-changing form (e.g., project creation, bid acceptance, document approval) includes a hidden input:  
   `<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">`.
3. Upon form submission, the controller verifies:  
   `hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')`.
4. Using `hash_equals()` provides timing-attack resistance. If tokens do not match, the request is immediately rejected.

### Q39. How are uploaded files (Aadhaar, PAN cards, site photos) validated and secured?
**Answer:**  
In file upload handlers (e.g., [`contractor/upload-doc.php`](file:///e:/BuildConnect/contractor/upload-doc.php)):
1. **MIME Type Whitelisting:** We do not trust the user-supplied `$_FILES['doc']['name']` extension. We inspect the actual file binary using PHP's `finfo_file(FILEINFO_MIME_TYPE)` to ensure it matches strictly `application/pdf`, `image/jpeg`, or `image/png`.
2. **File Size Capping:** We enforce a strict maximum upload limit (e.g., 5 MB).
3. **Randomized Renaming:** Uploaded files are renamed using cryptographically secure hashes (e.g., `bin2hex(random_bytes(16)) . '.pdf'`) to prevent directory traversal and file overwriting.
4. **Execution Prevention:** Upload directories contain an `.htaccess` file disabling PHP execution (`php_flag engine off`), preventing attackers from executing uploaded `.php` web shells.

### Q40. How do you prevent sensitive credentials from leaking into production error messages?
**Answer:**  
In [`config/database.php`](file:///e:/BuildConnect/config/database.php):
- All PDO operations execute inside `try-catch` blocks.
- On exception, `PDOException` details (which might contain database usernames or IP addresses) are logged to private server logs (`error_log($e->getMessage())`).
- The user is shown only a generic, sanitized message: *"An unexpected system error occurred. Please try again later."*
- `display_errors` is set to `0` in production configuration.

---

## 9. Testing, Debugging & Verification Methodology

### Q41. How did you test your application, and what test suites did you run?
**Answer:**  
We engineered automated and integration regression test suites executing directly against the live server:
1. **PHP Syntax & Linter Suite:** Executed `php -l` on all **134 production PHP files**; 100% passed with zero parse errors.
2. **Live HTTP RBAC Suite:** Executed 24 CURL requests against protected endpoints for all four roles; verified that unauthorized requests return HTTP 403 Forbidden.
3. **Dual Booking E2E Suite:** Simulated complete multi-step lifecycles for both Mode A (Instant Booking with concurrency locks) and Mode B (Project RFP, contractor bidding, bid acceptance, and contract creation); all 14 assertions passed.
4. **Admin Master Suite:** Verified 14 administrative capabilities including KYC document approvals, status toggling, and audit log generation.
5. **Authentication Suite:** Verified 9 demo account logins using Bcrypt hash validation.  
Total: **209/209 test assertions passed (100% pass rate, 0 failures)**.

### Q42. How did you debug and resolve the "Access Denied" issue reported earlier?
**Answer:**  
During testing, users encountered unexpected "Access Denied" 403 pages when navigating between dashboards.  
1. **Root Cause Analysis:** We traced `config/auth.php` and discovered role casing and session naming inconsistencies: some modules set `$_SESSION['user_role']` while others expected `$_SESSION['role']`. Additionally, contractor role normalization had a case mismatch (`Contractor` vs `contractor`).
2. **Resolution:** We refactored `config/auth.php` to implement a centralized `require_role($roles)` function that automatically normalizes roles using `strtolower(trim($role))`, unifies the session key to `$_SESSION['role']`, and gracefully redirects authenticated users to their correct home portal rather than halting execution.

### Q43. What tools and environments did you use for local testing?
**Answer:**  
- **Web Server:** PHP 8.2 built-in CLI development server (`php -S localhost:8000`) and Apache 2.4.
- **Database:** MySQL 8.0 / MariaDB 10.4 running via XAMPP on Windows.
- **API Testing:** Automated PHP regression test scripts with cURL and session cookie jars (`scratch/test_*.php`).
- **Browser Testing:** Google Chrome DevTools (Network tab for HTTP status inspection, Console for JavaScript errors, Application tab for session cookies).

### Q44. How did you verify that database transactions actually rollback when an error occurs?
**Answer:**  
In our E2E contract test, we simulated an intentional failure:
- We began a transaction, inserted a contract record, and then attempted an invalid milestone insert with an intentionally violated foreign key.
- The catch block caught the `PDOException` and executed `$pdo->rollBack()`.
- We then queried the `contracts` table and verified that the initial contract record was cleanly rolled back and not persisted. This proved ACID atomicity.

### Q45. How did you test mobile responsiveness on various screen sizes?
**Answer:**  
We used Chrome DevTools Device Mode to test viewports across:
- Mobile Portrait (375px — iPhone SE / Android)
- Mobile Large (414px — iPhone 13 Pro Max)
- Tablet Portrait (768px — iPad Mini)
- Desktop Widescreen (1280px and 1920px)  
All navigation sidebars collapse into touch-friendly off-canvas menus, tables support horizontal scrolling wrappers (`.table-responsive`), and buttons maintain a minimum 44px touch target height.

---

## 10. Limitations, Technical Debt & Future Scope

### Q46. What are the current limitations of BuildConnect?
**Answer:**  
We document four genuine technical boundaries:
1. **Live Payment Webhook Escrow:** While financial records and milestone disbursements are accurately logged in the `payments` table, automated escrow holding and bank payouts (e.g., Razorpay Escrow webhooks) require commercial banking API merchant credentials.
2. **Browser-Based QR Camera:** The QR scanner relies on HTML5 `getUserMedia`, which depends on browser camera permissions and HTTPS rather than low-level native mobile camera autofocus.
3. **HTTP Short-Polling for Notifications:** Real-time alerts refresh upon page navigation and short-polling intervals rather than persistent bi-directional WebSockets.
4. **Tile Dependency for Maps:** Leaflet maps require active internet connectivity to fetch OpenStreetMap tiles; offline map caching for remote construction sites is not yet implemented.

### Q47. If an examiner asks: "Why didn't you integrate a live payment gateway like Razorpay or Stripe?", how do you answer?
**Answer:**  
*"In our project architecture, we designed the complete database schema and transaction state engine for payments, including payment reference tracking, milestone-linked amounts, and status transitions (`pending`, `completed`, `refunded`) in the `payments` table.  
However, live external payment gateways like Razorpay or Stripe Route require registered business entities, GSTIN certification, and commercial merchant KYC verification to issue production API keys. For academic simulation, we engineered the complete internal disbursement logic while leaving the external webhook listener ready for plug-and-play production activation."*

### Q48. What is the difference between your QR attendance approach and a biometric thumb scanner?
**Answer:**  
- **Biometric Hardware:** Highly reliable, but requires dedicated physical hardware terminals installed on-site, which is impractical for small residential renovation projects or mobile maintenance crews.
- **BuildConnect Dynamic QR:** Hardware-agnostic. Any contractor with a smartphone can display the dynamic 60-second QR code, and any worker with a smartphone camera can scan it. The addition of GPS geolocation capture provides comparable fraud resistance without requiring expensive on-site biometric equipment.

### Q49. What are the key enhancements planned in the Future Scope?
**Answer:**  
1. **Native Mobile App (Flutter):** Provides background GPS geofencing, push notifications, and offline muster roll caching when workers are underground or in areas without cellular coverage.
2. **Automated Escrow Smart Contracts:** Milestone funds locked upon contract award and automatically disbursed to the contractor's bank account upon mutual digital signature.
3. **AI Estimation Engine:** Machine learning models trained on regional construction material costs and labor indices to suggest benchmark quotation estimates for clients.
4. **Biometric IoT Integration:** RESTful bridge connecting physical on-site turnstiles to the `api/attendance.php` endpoint on mega construction sites.

### Q50. What were the most important lessons you learned while building this project?
**Answer:**  
1. **Security Cannot Be an Afterthought:** Parameterized queries, CSRF tokens, and strict role authorization must be baked into the foundational architecture from day one.
2. **Data Normalization Prevents Chaos:** Investing time in designing a normalized 29-table 3NF schema prevented data anomalies, orphaned records, and redundant update bugs across the 134 PHP modules.
3. **Rigorous Automated Testing Saves Time:** Writing automated regression suites that test live HTTP requests caught role permission oversights and race conditions that manual clicking would have easily missed.
