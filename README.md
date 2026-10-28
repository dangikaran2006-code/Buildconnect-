# BuildConnect 🏗️
> **Centralized Construction Workforce & Project Management Platform**

BuildConnect is a full-featured web application engineered to unite the four pivotal stakeholders of the construction industry—**System Administrators, Contractors, Skilled Construction Workers, and Real Estate Clients**—into a single, cohesive digital platform.

Built with native PHP 8.x, MySQL relational database architecture with 100% prepared PDO statements, a custom CSS token-driven industrial UI design system, Leaflet.js geospatial mapping, and tokenized QR attendance processing, BuildConnect delivers end-to-end operational governance for modern construction sites.

---

## 📊 Project Highlights & Metrics

* **Backend Engine**: Native PHP 7.4+ / 8.1 / 8.2 (Modular architecture, zero framework bloat).
* **Database Architecture**: 29 Relational Tables in MySQL 8.0 / MariaDB 10.4 (InnoDB, Foreign Keys, 100% Prepared Statements).
* **Application Scope**: 134 verified production PHP controllers across 9 discrete modules.
* **Security & RBAC**: Strict 4-role isolation (`admin`, `contractor`, `worker`, `client`) with standardized HTTP 403 Access Denied containment, 64-character CSRF tokens, and BCrypt password encryption.
* **Verification Rate**: 100% pass rate across 209 automated unit, integration, and live HTTP test cases.

---

## 🌟 Key Verified Features

### 1. Dual Booking Engine (`includes/booking-functions.php`)
* **Mode A (Instant Booking — Emergency Repairs)**:
  - Clients post emergency repair requests with trade categories, address, coordinates, urgency, and budget.
  - Automatically identifies nearby verified and available workers within a 5–10 km radius using the spherical Haversine distance formula.
  - Workers receive instant dispatches on `worker/nearby-requests.php` and claim jobs with atomic concurrency locking (`SELECT ... FOR UPDATE`), preventing race conditions and double-bookings.
* **Mode B (Compare Quotes — Construction Projects)**:
  - Clients post large-scale renovation or construction scopes with specifications and target timelines.
  - Multiple contractors and workers submit itemized bids with price, scope of work, exclusions, and duration.
  - Clients compare bids in a structured matrix; accepting a winning quote automatically syncs project records and issues polite rejection notifications to competing bidders.

### 2. Tokenized QR Code Site Attendance
* Contractors generate dynamic, project-bound daily QR attendance sessions with 64-character cryptographic tokens.
* Workers scan the physical QR display or enter the session token via `worker/scan-attendance.php`.
* The system verifies active project membership, records timestamped check-in (`verified_by_qr = 1`), and automatically calculates shift work hours upon check-out, eliminating timecard falsification.

### 3. Verified Trade Profiles & Skills Taxonomy
* 27 standardized trade skills categorized across Civil Construction, MEP (Mechanical, Electrical, Plumbing), Finishing, and Heavy Machinery.
* Workers upload government identity cards, trade licenses, and safety certificates.
* Administrators inspect uploaded documents in a dedicated queue (`admin/verification.php`), granting verified badges upon approval.

### 4. Digital Contracts & Electronic Signatures
* Contractors draft formal digital contracts specifying scopes of work, hourly/daily/fixed pay rates, and legal clauses.
* Workers review terms on `worker/contract-details.php` and sign electronically with audit timestamping.
* Milestone disbursements and direct transfer logs are recorded in the `payments` table.

### 5. Client Project Monitoring & Interactive Maps
* Real-time milestone progress tracking (0–100%) and budget allocation for property developers.
* Interactive Leaflet.js mapping rendering project sites, worker service radiuses, and geographic coordinates.

### 6. AI Matching & Schedule Risk Engine (`includes/ai.php`)
* Evaluates worker trade titles, experience years, verified status, and ratings against job requirements to compute a 0–100% Match Score.
* Analyzes project milestone schedules and task progress to forecast schedule delay risks.

---

## 👥 Verified Pre-Configured Demo Accounts

All demo accounts come pre-configured in the presentation database seed (`database/seed_demo_presentation.sql`):

| User Role | Demo Email Address | Development Password | Primary Dashboard Route |
| :--- | :--- | :--- | :--- |
| **System Admin** | `admin@demo.com` | `Admin@123` | [admin/index.php](file:///e:/BuildConnect/admin/index.php) |
| **Contractor** | `contractor@demo.com` | `Contractor@123` | [contractor/index.php](file:///e:/BuildConnect/contractor/index.php) |
| **Worker** | `worker@demo.com` | `Worker@123` | [worker/index.php](file:///e:/BuildConnect/worker/index.php) |
| **Client** | `client@demo.com` | `Client@123` | [client/index.php](file:///e:/BuildConnect/client/index.php) |

*(Note: In production deployments, change development passwords immediately via account settings).*

---

## 💻 Installation & Quick Start

### 1. Requirements
* PHP 7.4+ or 8.1+ (with extensions: `pdo`, `pdo_mysql`, `session`, `json`, `mbstring`, `fileinfo`, `curl`)
* MySQL 8.0+ or MariaDB 10.4+
* Apache 2.4+ (with `mod_rewrite` enabled) or PHP built-in CLI server

### 2. Database Setup
1. Start your MySQL database service (e.g., via XAMPP or native MySQL).
2. Create the database:
   ```sql
   CREATE DATABASE buildconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the master schema and presentation demo data:
   ```bash
   mysql -u root -p buildconnect < database/schema.sql
   mysql -u root -p buildconnect < database/seed_demo_presentation.sql
   ```

### 3. Environment Configuration
Copy `.env.example` to `.env` and verify database credentials:
```env
APP_NAME=BuildConnect
APP_ENV=development
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=buildconnect
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Running the Application
Launch the built-in development server from the project directory:
```bash
php -S localhost:8000
```
Open your web browser and navigate to: **`http://localhost:8000`**

---

## 📁 Repository Directory Structure

```text
BuildConnect/
├── admin/               # Administrator governance, verification, and audit controllers (19 files)
├── client/              # Client portal, project monitoring, and booking wizards (22 files)
├── contractor/          # Contractor projects, jobs, QR attendance, and contracts (34 files)
├── worker/              # Worker profiles, trade skills, QR check-in, and dispatches (27 files)
├── api/                 # RESTful AJAX endpoints (attendance, AI scores, CSV exports) (5 files)
├── auth/                # Google OAuth callback controllers (2 files)
├── assets/              # Custom CSS stylesheets, icons, avatars, and JavaScript libraries
├── config/              # Central configuration constants and PDO database connection
├── database/            # Master schema (schema.sql) and presentation database seeds
├── docs/                # Comprehensive Final-Year Project Report & Documentation Suite
│   ├── diagrams/        # Raw editable Mermaid diagram source files (.mmd)
│   ├── 01_PROJECT_ABSTRACT_AND_INTRODUCTION.md
│   ├── 02_REQUIREMENTS_SPECIFICATION_SRS.md
│   ├── 03_SYSTEM_ARCHITECTURE_AND_DESIGN.md
│   ├── 04_DATABASE_DESIGN_AND_DATA_DICTIONARY.md
│   ├── 05_MODULE_DESCRIPTIONS_AND_WORKFLOWS.md
│   ├── 06_SECURITY_AND_AUTHORIZATION_DESIGN.md
│   ├── 07_TEST_SUITE_AND_VERIFICATION_RESULTS.md
│   ├── 08_INSTALLATION_AND_DEPLOYMENT_GUIDE.md
│   ├── 09_LIMITATIONS_FUTURE_SCOPE_AND_CONCLUSION.md
│   └── FINAL_YEAR_PROJECT_REPORT.md  # Unified Master 19-Section Academic Report
├── includes/            # Core business logic, RBAC auth guard, booking engine, navbar, sidebar
├── uploads/             # Hardened storage sandbox for worker documents and blueprints
├── BRAINME.md           # Developer architectural guide & dependency documentation
├── index.php            # Public marketing landing page
├── login.php            # Secure multi-role user sign-in
├── register.php         # Public role-based user registration
└── unauthorized.php     # HTTP 403 Access Denied containment view
```

---

## 🛡️ Enterprise Security & Standards Compliance

* **SQL Injection**: 100% prepared PDO statements (`$stmt->prepare()` and `$stmt->execute()`). Zero concatenated raw SQL inputs.
* **Cross-Site Scripting (XSS)**: Sanitized via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
* **Cross-Site Request Forgery (CSRF)**: Cryptographically secure 64-character tokens enforced on all state-changing POST operations.
* **Insecure Direct Object References (IDOR)**: Server-side ownership verification enforced on all queries (`contractor_id = ?`, `client_id = ?`, `worker_id = ?`).
* **Session Security**: Session ID regeneration upon login (`session_regenerate_id(true)`), `HttpOnly = true`, and `SameSite = Lax` cookie flags.
* **File Upload Sandbox**: Whitelist extension inspection (`.pdf`, `.jpg`, `.jpeg`, `.png`), MIME inspection (`finfo`), 5 MB filesize limit, randomized filenames, and `.htaccess` direct execution blocking.

---

## 📚 Complete Project Documentation
For the complete 19-section academic thesis report and modular system design specifications, refer to the [docs/](file:///e:/BuildConnect/docs/) directory:
* **[Master Final-Year Project Report](file:///e:/BuildConnect/docs/FINAL_YEAR_PROJECT_REPORT.md)** (Full 19-section comprehensive thesis)
* **[SRS & Requirements Specification](file:///e:/BuildConnect/docs/02_REQUIREMENTS_SPECIFICATION_SRS.md)**
* **[System Architecture & Design Diagrams](file:///e:/BuildConnect/docs/03_SYSTEM_ARCHITECTURE_AND_DESIGN.md)**
* **[Database Design & Data Dictionary (29 Tables)](file:///e:/BuildConnect/docs/04_DATABASE_DESIGN_AND_DATA_DICTIONARY.md)**
* **[Security & Authorization Design](file:///e:/BuildConnect/docs/06_SECURITY_AND_AUTHORIZATION_DESIGN.md)**
* **[Test Suite & Verification Results (209 Tests)](file:///e:/BuildConnect/docs/07_TEST_SUITE_AND_VERIFICATION_RESULTS.md)**
