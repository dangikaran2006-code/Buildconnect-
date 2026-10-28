# BuildConnect — Software Requirements Specification (SRS) & Tech Stack

## 1. System Requirements

### 1.1 Hardware Requirements

#### Minimum Server Hardware Configuration
* **Processor (CPU)**: Dual-Core 2.0 GHz or higher (x86_64 or ARM64 architecture).
* **Random Access Memory (RAM)**: 2 GB minimum (4 GB recommended for production workloads handling concurrent database connections).
* **Hard Disk Storage**: 20 GB available storage (SSD recommended for fast MySQL read/write operations and document storage).
* **Network Interface**: 100 Mbps / 1 Gbps Ethernet or high-speed broadband connection with dedicated IP or DNS routing.

#### Minimum Client Hardware Configuration
* **Desktop / Laptop**: Any modern personal computer (Intel Core i3 / AMD Ryzen 3 or equivalent, 2 GB RAM).
* **Mobile / Tablet**: Modern smartphone or tablet (Android 8.0+ / iOS 12.0+) with at least 1 GB RAM.
* **Display Resolution**: Responsive design supports viewports from 320px (mobile) up to 3840px (4K Ultra HD displays).
* **Camera / Hardware**: Optional integrated device camera for scanning physical QR code displays on construction sites.

---

### 1.2 Software Requirements

#### Server-Side Environment
* **Operating System**: Cross-platform compatibility; fully operational on **Microsoft Windows** (Windows 10/11, Windows Server 2019/2022) and **Linux** (Ubuntu 20.04/22.04 LTS, Debian 11/12, CentOS/RHEL 8/9).
* **Web Server**: Apache HTTP Server 2.4+ (with `mod_rewrite` enabled) or Nginx 1.18+ (with PHP-FPM fastcgi proxy). Built-in PHP CLI development server (`php -S localhost:8000`) supported for local evaluation.
* **Backend Runtime**: **PHP 7.4.x / 8.0.x / 8.1.x / 8.2.x**.
  * *Required PHP Extensions*: `pdo`, `pdo_mysql`, `session`, `json`, `mbstring`, `fileinfo`, `curl`, `openssl`.
* **Database Engine**: **MySQL 8.0+** or **MariaDB 10.4+** supporting InnoDB storage engine, foreign key constraints, and `utf8mb4` Unicode character collation.

#### Client-Side Environment
* **Web Browser**: Any standard standards-compliant modern browser:
  * Google Chrome 90+ (Desktop & Mobile)
  * Mozilla Firefox 88+
  * Microsoft Edge 90+
  * Apple Safari 14+
* **JavaScript**: Modern ES6+ JavaScript execution enabled in browser settings.

---

## 2. Technology Stack Architecture

| Layer | Component | Specification | Technical Rationale |
| :--- | :--- | :--- | :--- |
| **Backend Language** | PHP (Hypertext Preprocessor) | PHP 8.1 / 8.2 Native | Native web execution, zero heavyweight framework overhead, high execution speed, broad deployment support. |
| **Database Engine** | MySQL / MariaDB | MySQL 8.0 InnoDB (`utf8mb4`) | Relational integrity, ACID compliance, atomic transactions, row-level locking (`FOR UPDATE`), and indexing. |
| **Database Interface** | PHP Data Objects (PDO) | Prepared Statements Only | Complete prevention of SQL Injection vulnerabilities and native parameterized typing. |
| **Presentation Layer** | HTML5 & Vanilla CSS3 | Custom Design Tokens | Semantic HTML5 structure with custom CSS variables (`--bc-navy`, `--bc-amber`, `--bc-card`) avoiding rigid Tailwind churn. |
| **UI Framework** | Bootstrap 5.3 + FontAwesome 6 | Responsive Grid System | Modern responsive layouts, accessible navigation drawers, mobile offcanvas, and crisp vector iconography. |
| **Client-Side Scripting** | JavaScript (ES6+ Vanilla) | Asynchronous AJAX Fetch | Native DOM manipulation, dynamic form validation, modal state management, and real-time calculation without bloated dependencies. |
| **Geospatial Mapping** | Leaflet.js / OpenStreetMap | Interactive Coordinates Widget | Lightweight, free, high-performance mapping rendering project sites, worker locations, and radius bounds. |
| **Security Layer** | Native Cryptography | Bcrypt, SHA-256, CSRF Hex | Industry-standard password hashing (`password_hash`), cryptographically secure token generation (`bin2hex(random_bytes(32))`), and XSS sanitization. |

---

## 3. User Classes & Stakeholder Characteristics

BuildConnect establishes four distinct user classes governed by a centralized Role-Based Access Control (RBAC) engine:

```text
                               +-------------------+
                               |    users table    |
                               | (role enum check) |
                               +---------+---------+
                                         |
         +-----------------+-------------+-------------+-----------------+
         |                 |                           |                 |
         v                 v                           v                 v
+-----------------+ +-------------------+     +------------------+ +-----------------+
|   ROLE_ADMIN    | |  ROLE_CONTRACTOR  |     |   ROLE_WORKER    | |   ROLE_CLIENT   |
|   Directory:    | |    Directory:     |     |    Directory:    | |   Directory:    |
|    admin/       | |   contractor/     |     |     worker/      | |     client/     |
| (Governance &   | | (Projects, Jobs,  |     | (Skills, Search, | | (Monitoring,    |
|  Verification)  | |  Team, Contracts) |     |  Scan, Dispatch) | |  Bids, Reviews) |
+-----------------+ +-------------------+     +------------------+ +-----------------+
```

1. **System Administrator (`admin`)**: Responsible for platform governance, identity document verification, user suspension/activation, dispute moderation, system settings, and security audit log inspection.
2. **Contractor (`contractor`)**: Construction firms and independent builders who manage multi-site projects, post job openings, evaluate applicants, assign work tasks, generate QR attendance sessions, issue binding contracts, and submit quotes.
3. **Construction Worker (`worker`)**: Trade professionals (Masons, Electricians, Welders, Carpenters, Plumbers, Crane Operators) who maintain trade skill credentials, search and apply for jobs, check in via QR codes, sign contracts, and complete emergency dispatches.
4. **Client (`client`)**: Real estate developers, commercial property owners, and homeowners who post construction projects, request competitive bids, order instant repair services, track milestone progress, and review contractors.

---

## 4. Functional Requirements (FR)

### Module 1: Authentication & Access Control
* **FR-AUTH-01**: The system shall permit users to register under one of three public roles: Client, Contractor, or Worker.
* **FR-AUTH-02**: The system shall authenticate users using email address and password with Bcrypt hashing verification (`password_verify`).
* **FR-AUTH-03**: The system shall regenerate session IDs (`session_regenerate_id(true)`) upon successful authentication to eliminate session fixation.
* **FR-AUTH-04**: The system shall enforce role containment on all endpoints; any attempt by an unauthorized role to access restricted directories (`admin/`, `client/`, `contractor/`, `worker/`) shall be blocked with an HTTP 403 status code and directed to `unauthorized.php`.
* **FR-AUTH-05**: The system shall validate a 64-character CSRF token on all POST requests before executing any state-changing operations.
* **FR-AUTH-06**: The system shall securely destroy session cookies and session files upon invocation of `logout.php`.

### Module 2: Administrative Governance
* **FR-ADM-01**: The administrator shall view global metrics including total users, active projects, jobs posted, verified workers, and system activity logs.
* **FR-ADM-02**: The administrator shall inspect pending worker verification documents and approve or reject credentials with audit recording.
* **FR-ADM-03**: The administrator shall be empowered to toggle user status between `active`, `inactive`, and `suspended`.
* **FR-ADM-04**: The administrator shall inspect all platform bookings, quotes, and contract records with moderator override capabilities.
* **FR-ADM-05**: The administrator shall view and filter immutable activity logs capturing user ID, action name, IP address, and timestamp.

### Module 3: Contractor Project & Workforce Management
* **FR-CON-01**: Contractors shall create construction projects with title, location, coordinates (latitude/longitude), budget, timeline, and description.
* **FR-CON-02**: Contractors shall define project milestones with completion percentages and target dates.
* **FR-CON-03**: Contractors shall post trade jobs specifying required trade, pay rate, pay type (hourly/daily/fixed), available spots, and dates.
* **FR-CON-04**: Contractors shall review submitted job applications, view candidate profiles and AI match scores, and hire candidates with automated enrollment into `project_members`.
* **FR-CON-05**: Contractors shall create, update, and reassign tasks with priorities (`low`, `medium`, `high`, `urgent`, `critical`).
* **FR-CON-06**: Contractors shall generate dynamic QR attendance sessions with unique 64-character tokens and expiry timestamps.
* **FR-CON-07**: Contractors shall draft, sign, and issue digital contracts to hired workers.
* **FR-CON-08**: Contractors shall browse open Mode B quote requests and submit itemized quotations with price, scope, and estimated duration.

### Module 4: Worker Profile, Jobs & Attendance
* **FR-WRK-01**: Workers shall maintain trade profile information including primary trade title, experience years, hourly/daily rates, bio, and service radius.
* **FR-WRK-02**: Workers shall manage their skills taxonomy across 27 predefined trade categories with proficiency levels.
* **FR-WRK-03**: Workers shall upload identity documents (Aadhaar/ID card, trade license, safety cert) in PDF, PNG, or JPG formats up to 5 MB.
* **FR-WRK-04**: Workers shall browse published jobs, view match recommendations, and submit applications with custom expected pay.
* **FR-WRK-05**: Workers shall scan or input active QR session tokens to submit timestamped check-in and check-out attendance records.
* **FR-WRK-06**: Workers shall review pending digital contracts, read legal clauses, and electronically sign or reject contracts.
* **FR-WRK-07**: Workers shall receive instant booking dispatches, view customer location and budget, and accept or reject requests.
* **FR-WRK-08**: Workers shall monitor their work history, calculated earnings, task assignments, and performance reviews.

### Module 5: Client Project Monitoring & Dual Booking
* **FR-CLI-01**: Clients shall create construction projects and monitor assigned project milestones, progress percentages, and blueprints.
* **FR-CLI-02**: Clients shall initiate **Instant Booking (Mode A)** by specifying trade category, address, coordinates, preferred time, urgency, and budget.
* **FR-CLI-03**: The system shall calculate distances using the Haversine formula and identify nearby, available, skill-matched workers within the client's radius.
* **FR-CLI-04**: Clients shall initiate **Compare Quotes (Mode B)** by posting project scope and receiving competitive bids from contractors and workers.
* **FR-CLI-05**: Clients shall inspect submitted quotes, accept the optimal bid, and trigger automated rejections for competing submissions.
* **FR-CLI-06**: Clients shall approve and mark completed jobs, updating status and triggering two-way review prompts.
* **FR-CLI-07**: Clients shall submit star ratings (1–5) and feedback reviews for service providers.

### Module 6: Notification, Export & AI Subsystems
* **FR-SYS-01**: The system shall generate in-app notification records for key lifecycle triggers (job application, hire, QR check-in, contract issued, quote submitted, status update).
* **FR-SYS-02**: The notification center shall route users to role-appropriate destinations, rewriting cross-role links to avoid unauthorized redirects.
* **FR-SYS-03**: The system shall export project reports, worker lists, and attendance records into standard downloadable CSV files.
* **FR-SYS-04**: The AI matching engine shall evaluate candidate suitability against job trade titles, experience, verification status, and ratings, returning a normalized score from 0 to 100%.

---

## 5. Non-Functional Requirements (NFR)

### 5.1 Performance Requirements
* **Response Time**: Standard page loads shall execute within < 800 milliseconds on local servers and < 1.5 seconds under typical web hosting conditions.
* **Database Efficiency**: High-frequency queries (e.g., user authentication, nearby worker geospatial queries, attendance verification) shall execute in < 50 milliseconds using primary keys and composite indexes on `role`, `status`, `location_lat`, `location_lng`, and foreign keys.
* **Scalability**: Relational schema supports horizontal scaling with master-replica MySQL replication and session storage in Redis/Memcached.

### 5.2 Security Requirements
* **Password Protection**: Passwords must be hashed using PHP's native BCrypt algorithm (`PASSWORD_BCRYPT`, default cost factor 10 or 12). Plaintext passwords must never be stored, logged, or displayed.
* **SQL Injection Prevention**: All dynamic SQL queries must be executed via PDO prepared statements with parameter binding. Zero dynamic string interpolation into SQL queries is permitted.
* **XSS Prevention**: All user-supplied data must be sanitized via `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')` before outputting into HTML contexts.
* **CSRF Prevention**: All state-changing HTML forms and AJAX requests must validate a synchronized 64-character session CSRF token.
* **File Upload Hardening**: Uploaded files must be validated for allowed extensions (`.pdf`, `.jpg`, `.jpeg`, `.png`), checked via MIME type inspection (`finfo_file`), restricted to 5 MB maximum size, and stored under randomized filenames in a directory with PHP execution disabled.
* **Access Control Integrity**: Every protected script must enforce `require_login()` and `require_role(...)`. Direct object references must verify user ownership (`user_id = ?` or `contractor_id = ?`).

### 5.3 Reliability & Availability
* **Database Integrity**: All critical multi-table operations (such as quote acceptance with project synchronization or instant booking claims) must be wrapped in atomic database transactions (`$db->beginTransaction()`, `$db->commit()`, `$db->rollBack()`).
* **Concurrency Locking**: Critical resource claims (e.g. claiming an instant booking) must utilize row-level exclusive locks (`SELECT ... FOR UPDATE`) to prevent race conditions and double bookings.
* **Availability**: 99.9% uptime architecture with decoupled modular components.

### 5.4 Usability & Accessibility
* **Interface Clarity**: High-contrast, custom color-tailored UI ensuring WCAG 2.1 AA compliant contrast ratios across dark navy banners (`#0B132B`, `#111C2D`), dark charcoal cards, and crisp typography.
* **Responsive Layout**: Fluid layouts adapting seamlessly across mobile phones (360px+), tablets (768px+), laptops (1024px+), and large desktop monitors (1440px+).
* **Feedback Indicators**: Real-time visual feedback via Bootstrap alerts, badge status pills, modal confirmations, and loading indicators.
