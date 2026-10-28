# BUILDCONNECT — ARCHITECTURAL BRAIN & DEVELOPER GUIDE
> **Permanent Development Instruction, Architectural Reference & Module Dependency Map**

---

## 1. Project Identity & Overview
* **Application Name**: BuildConnect
* **Classification**: Centralized Construction Workforce & Project Management Platform
* **Architecture**: Three-Tier Monolithic Web Architecture (Native PHP 8.x + MySQL 8.0 InnoDB via PDO + Semantic HTML5 / Custom CSS3 Variables + Bootstrap 5.3 + JavaScript ES6+ Fetch)
* **Primary Actors**:
  1. `admin`: Platform governance, worker verification, audit logs, dispute moderation.
  2. `contractor`: Projects, milestones, jobs, candidate hiring, tasks, QR attendance sessions, contracts.
  3. `worker`: Profile, trade skills, document uploads, job applications, QR check-in, instant dispatches, earnings.
  4. `client`: Project monitoring, instant emergency bookings (Mode A), quote requests (Mode B), ratings.

---

## 2. Directory Structure & Key Folders Map

```text
BuildConnect/
├── admin/                  # System Administrator governance & audit controllers (19 files)
│   ├── index.php           # Executive admin KPI dashboard
│   ├── users.php           # User account search, filter, edit, and suspension
│   ├── verification.php    # Worker document inspection and verification queue
│   ├── bookings.php        # Service booking and quotation oversight
│   ├── projects.php        # Global projects monitoring
│   ├── jobs.php            # Global job postings monitoring
│   ├── applications.php    # Global job applications review
│   ├── attendance.php      # Global site attendance monitor
│   ├── reviews.php         # Review moderation & dispute flag resolution
│   ├── audit-logs.php      # Immutable security activity audit trail
│   └── reports.php         # Platform analytics and CSV report generation
│
├── client/                 # Client project monitoring & booking wizards (22 files)
│   ├── index.php           # Client executive dashboard
│   ├── projects.php        # Active construction project overview
│   ├── project-details.php # Read-only milestone progress & map coordinates
│   ├── create-booking.php  # Unified wizard for Instant Booking (Mode A) & Quotes (Mode B)
│   ├── bookings.php        # Client service bookings tracker
│   ├── booking-details.php # Booking details, matched worker, and quote comparison matrix
│   ├── contracts.php       # Client digital agreements overview
│   ├── milestones.php      # Milestone completion percentage view
│   └── reviews.php         # Two-way contractor/worker star rating submission
│
├── contractor/             # Contractor project & workforce management (34 files)
│   ├── index.php           # Contractor command center dashboard
│   ├── projects.php        # Project portfolio (CRUD with budget & coordinates)
│   ├── create-project.php  # Project creation wizard
│   ├── project-details.php # Milestone, task, and team management per project
│   ├── jobs.php            # Job listings manager tied to projects
│   ├── create-job.php      # Job creation form with trade, wage rate, spots
│   ├── applications.php    # Applicant review pipeline (AI match score display)
│   ├── application-details.php # One-click hiring and automated project enrollment
│   ├── tasks.php           # Daily task management with priority levels
│   ├── generate-attendance.php # Dynamic QR session code generator
│   ├── attendance.php      # Daily site check-in roster
│   ├── contracts.php       # Digital contract issuance and tracking
│   ├── quote-requests.php  # Browse open Mode B requests & submit itemized bids
│   └── project-insights.php# AI project schedule risk and delay forecasting
│
├── worker/                 # Worker profile, attendance & job search (27 files)
│   ├── index.php           # Worker executive dashboard
│   ├── profile.php         # Trade profile (rates, bio, service radius)
│   ├── skills.php          # Trade skills matrix (27 categorized skills)
│   ├── documents.php       # Government ID & trade certification upload queue
│   ├── jobs.php            # Search and browse published jobs with filters
│   ├── job-details.php     # Job specifications & pay rate details
│   ├── apply-job.php       # Submit job application with custom expected pay
│   ├── scan-attendance.php # Mobile-friendly QR code check-in/out interface
│   ├── contracts.php       # Digital contract review and electronic signature
│   ├── nearby-requests.php # View & accept Mode A instant repair dispatches
│   ├── my-bookings.php     # Management of accepted bookings
│   └── earnings.php        # Calculated total earnings from hours and contracts
│
├── api/                    # RESTful JSON endpoints (5 files)
│   ├── attendance.php      # QR check-in/out authentication and logging
│   ├── ai.php              # AI candidate matching & schedule risk calculations
│   ├── export-report.php   # Dynamic CSV generation for projects & attendance
│   ├── analytics.php       # JSON metric feeds for dynamic dashboard charts
│   └── push-notifications.php # Web Push API endpoint handler
│
├── auth/                   # External authentication helpers (2 files)
│   ├── google-login.php    # Initiates Google OAuth 2.0 flow
│   └── google-callback.php # Handles Google OAuth token exchange & user sign-in
│
├── config/                 # Environment & Database Configuration (2 files)
│   ├── constants.php       # Global URL paths, roles, and status definitions
│   └── database.php        # PDO connection wrapper with prepared statement defaults
│
├── database/               # Database Schemas & Seed Data (6 files)
│   ├── schema.sql          # Master DDL creating all 29 relational tables
│   ├── seed_demo_presentation.sql # Comprehensive presentation demo records
│   └── seed_presentation_demo.php # Automated PHP database seeder script
│
├── docs/                   # Complete Final-Year Project Report & Documentation Suite
│   ├── diagrams/           # Raw editable Mermaid diagram source files (.mmd)
│   ├── 01_PROJECT_ABSTRACT_AND_INTRODUCTION.md
│   ├── 02_REQUIREMENTS_SPECIFICATION_SRS.md
│   ├── 03_SYSTEM_ARCHITECTURE_AND_DESIGN.md
│   ├── 04_DATABASE_DESIGN_AND_DATA_DICTIONARY.md
│   ├── 05_MODULE_DESCRIPTIONS_AND_WORKFLOWS.md
│   ├── 06_SECURITY_AND_AUTHORIZATION_DESIGN.md
│   ├── 07_TEST_SUITE_AND_VERIFICATION_RESULTS.md
│   ├── 08_INSTALLATION_AND_DEPLOYMENT_GUIDE.md
│   ├── 09_LIMITATIONS_FUTURE_SCOPE_AND_CONCLUSION.md
│   └── FINAL_YEAR_PROJECT_REPORT.md # Unified Master 19-Section Report
│
├── includes/               # Shared logic, layout components & security guards (9 files)
│   ├── auth.php            # RBAC enforcement: require_login(), require_role()
│   ├── booking-functions.php # Dual booking state machine, Haversine proximity, atomic locks
│   ├── ai.php              # Rule-based candidate matching & risk scoring engine
│   ├── functions.php       # Input sanitization, flash messages, currency/date formatters
│   ├── header.php          # Standardized HTML5 header & CSS links
│   ├── footer.php          # Standardized footer & JS bundles
│   ├── navbar.php          # Centralized navigation bar with notifications dropdown
│   └── sidebar.php         # Role-aware collapsible sidebar navigation
│
├── uploads/                # Hardened upload storage sandbox
├── index.php               # Public marketing landing page
├── login.php               # Multi-role authentication entry point
├── register.php            # Role-based user registration wizard
├── notifications.php       # Centralized notification center with role URL guard
├── contracts.php           # Central contracts router dispatching to role contracts
├── reviews.php             # Central reviews router dispatching to role reviews
├── unauthorized.php        # HTTP 403 Access Denied containment view
├── README.md               # Repository showcase documentation
└── BRAINME.md              # Architectural & development reference (this file)
```

---

## 3. Database Dependencies per Module

| Module Name | Primary Controller Files | Direct Database Tables Read / Written |
| :--- | :--- | :--- |
| **Authentication & Users** | `login.php`, `register.php`, `includes/auth.php` | `users`, `workers`, `contractors`, `clients`, `activity_logs` |
| **Worker Profile & Skills**| `worker/profile.php`, `worker/skills.php` | `workers`, `skills`, `worker_skills`, `worker_experience` |
| **Worker Verification** | `admin/verification.php`, `worker/documents.php` | `worker_documents`, `workers`, `users`, `activity_logs` |
| **Projects & Milestones** | `contractor/projects.php`, `client/projects.php` | `projects`, `milestones`, `project_documents`, `project_members` |
| **Tasks Delegation** | `contractor/tasks.php`, `worker/tasks.php` | `tasks`, `milestones`, `projects`, `users` |
| **Job Marketplace & Hiring**| `contractor/jobs.php`, `worker/jobs.php` | `jobs`, `job_applications`, `project_members`, `users` |
| **QR Site Attendance** | `contractor/generate-attendance.php`, `worker/scan-attendance.php` | `attendance_sessions`, `attendance`, `projects`, `users` |
| **Digital Contracts** | `contractor/contracts.php`, `worker/contracts.php` | `contracts`, `projects`, `jobs`, `users`, `payments` |
| **Dual Booking Engine** | `includes/booking-functions.php`, `client/booking-details.php` | `bookings`, `booking_quotes`, `workers`, `users`, `projects` |
| **Reviews & Ratings** | `client/reviews.php`, `admin/reviews.php` | `reviews`, `review_reports`, `workers`, `contractors`, `users` |
| **Notifications** | `notifications.php`, `includes/navbar.php` | `notifications`, `users` |
| **AI Matching & Risk** | `includes/ai.php`, `contractor/job-matches.php` | `ai_match_results`, `ai_usage_logs`, `jobs`, `workers`, `skills` |

---

## 4. Fundamental Architectural & Development Rules

### Rule 1: Zero Breaking Changes to Working Features
* **Never rewrite existing functional code** when adding enhancements.
* First understand the existing workflow, identify dependencies across the 29 database tables, implement the smallest safe change, and verify via automated test suites.

### Rule 2: Strict Server-Side Role Enforcement (RBAC)
* Every protected route MUST begin with:
  ```php
  require_once __DIR__ . '/../includes/auth.php';
  require_role(ROLE_EXPECTED); // or require_role(['role_a', 'role_b'])
  ```
* Never rely solely on client-side hiding of buttons or sidebar links. Unauthorized requests must be trapped by `require_role()` and redirected to [unauthorized.php](file:///e:/BuildConnect/unauthorized.php) with an HTTP 403 Forbidden header.

### Rule 3: 100% Parameterized Prepared PDO Statements
* Zero concatenated variables or dynamic string interpolations inside SQL queries:
  ```php
  // CORRECT:
  $stmt = $db->prepare("SELECT * FROM projects WHERE contractor_id = ? AND status = ?");
  $stmt->execute([(int)$user_id, $status]);

  // STRICTLY FORBIDDEN:
  $db->query("SELECT * FROM projects WHERE contractor_id = " . $user_id);
  ```

### Rule 4: Output Escaping (XSS Prevention)
* All dynamic database values rendered into HTML attributes or text nodes MUST pass through `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` or the centralized `e()` / `sanitize()` helper.

### Rule 5: State-Changing CSRF Protection
* All POST requests must include `<?= csrf_field() ?>` and validate `verify_csrf_token($_POST['csrf_token'] ?? '')` before mutating any database record.

### Rule 6: Atomic Multi-Table Operations & Concurrency Locks
* Any operation altering multiple relational tables (e.g. quote acceptance syncing to `projects` and updating competing `booking_quotes`) must use atomic transactions (`beginTransaction()`, `commit()`, `rollBack()`).
* First-come-first-served resource claims (e.g. Mode A instant booking acceptance) MUST enforce exclusive row locks (`SELECT ... FOR UPDATE`) to prevent double claims.

---

## 5. Testing & Verification Standard

Before declaring any enhancement complete, the following test suites must be executed from the project root:

1. **PHP Static Linting**:
   ```bash
   php -l [modified_file.php]
   ```
   Must return `No syntax errors detected`.
2. **Access Control & Authorization Suite**:
   ```bash
   php scratch/test_live_access_denied_suite.php
   ```
   Must return `24 / 24 tests passed (100%)`.
3. **Dual Booking Engine Suite**:
   ```bash
   php scratch/test_dual_booking_system.php
   ```
   Must return `14 / 14 tests passed (100%)`.
4. **Admin Route Verification Suite**:
   ```bash
   php scratch/test_step14_master.php
   ```
   Must return `14 / 14 Admin pages verified clean`.

---

## 6. Current System Status

* **Status**: **PRODUCTION-READY & VERIFIED (Step 15 Dual Booking + RBAC Hardening)**
* **Active Relational Tables**: 29
* **Production PHP Controllers**: 134
* **Total Passing Tests**: 209 / 209 (100%)
* **Documentation Package**: Complete 19-section academic thesis report in `docs/FINAL_YEAR_PROJECT_REPORT.md` and 9 modular specifications in `docs/`.
