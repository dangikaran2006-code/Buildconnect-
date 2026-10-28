# BuildConnect — Module Descriptions & Implemented Workflows

## 1. Overview of Application Modules

BuildConnect comprises **134 verified PHP production scripts** structured into 9 modular subsystems:
1. **Authentication & Access Gateway** (`auth/`, `login.php`, `register.php`, `logout.php`, `unauthorized.php`, `includes/auth.php`)
2. **Admin Governance & Verification Module** (`admin/` — 19 files)
3. **Contractor Project & Workforce Module** (`contractor/` — 34 files)
4. **Worker Profile & Marketplace Module** (`worker/` — 27 files)
5. **Client Project Monitoring Module** (`client/` — 22 files)
6. **Dual Booking Engine** (Mode A Instant Booking & Mode B Compare Quotes — `includes/booking-functions.php`, `client/create-booking.php`, `client/booking-details.php`, `worker/nearby-requests.php`, `worker/my-bookings.php`, `contractor/quote-requests.php`, `admin/bookings.php`)
7. **Dynamic QR Code Site Attendance Module** (`contractor/generate-attendance.php`, `worker/scan-attendance.php`, `api/attendance.php`, `admin/attendance.php`)
8. **Digital Contracts & Electronic Signatures Module** (`contractor/create-contract.php`, `contractor/contracts.php`, `worker/contracts.php`, `worker/contract-details.php`, `client/contracts.php`, `contracts.php`)
9. **AI Matching, Risk Analysis & Analytics API Module** (`includes/ai.php`, `api/ai.php`, `api/export-report.php`, `api/push-notifications.php`)

---

## 2. Core Functional Modules

### 2.1 Authentication & Security Gateway Module
* **Entry Routes**: [login.php](file:///e:/BuildConnect/login.php), [register.php](file:///e:/BuildConnect/register.php), [logout.php](file:///e:/BuildConnect/logout.php), [unauthorized.php](file:///e:/BuildConnect/unauthorized.php), [auth/google-login.php](file:///e:/BuildConnect/auth/google-login.php), [auth/google-callback.php](file:///e:/BuildConnect/auth/google-callback.php).
* **Core Logic**: [includes/auth.php](file:///e:/BuildConnect/includes/auth.php).
* **Key Operations**:
  - Handles new user account provisioning across roles (`client`, `contractor`, `worker`).
  - Verifies passwords via BCrypt hashing (`password_verify`).
  - Regenerates session ID upon login (`session_regenerate_id(true)`) to prevent fixation attacks.
  - Enforces role authorization via `require_role(...)` and `requireRole(...)`.
  - Normalizes role strings to prevent case-mismatch false positives.
  - Emits HTTP 403 Forbidden on [unauthorized.php](file:///e:/BuildConnect/unauthorized.php) with functional "Return to Dashboard" and "Logout" actions.

---

### 2.2 Admin Governance & Verification Module (`admin/`)
* **Key Routes**:
  - `admin/index.php`: Executive KPI dashboard rendering real-time metrics (users, active projects, jobs, verified workers, system activity).
  - `admin/users.php`: Central user management table supporting search, filtering by role/status, editing, and suspension/activation.
  - `admin/verification.php`: Worker document verification queue; administrators review uploaded ID cards, trade licenses, and issue approval badges.
  - `admin/projects.php`: Global project monitoring displaying progress percentages, budgets, and contractor/client links.
  - `admin/jobs.php` & `admin/applications.php`: Global job postings oversight and application review.
  - `admin/bookings.php`: Bookings & quotes inspection dashboard with cancellation and dispute oversight.
  - `admin/attendance.php`: Global site attendance monitor tracking QR check-ins across all projects.
  - `admin/reviews.php`: Two-way review moderation and dispute resolution.
  - `admin/audit-logs.php`: Immutable audit log table recording user actions, entity IDs, IP addresses, and timestamps.
  - `admin/reports.php`: Global report generation with CSV download functionality.

---

### 2.3 Contractor Project & Workforce Module (`contractor/`)
* **Key Routes**:
  - `contractor/index.php`: Contractor command center displaying active projects, pending applications, active tasks, and recent check-ins.
  - `contractor/projects.php`, `create-project.php`, `project-details.php`: Multi-project portfolio management with coordinates, budgets, and milestone configuration.
  - `contractor/jobs.php`, `create-job.php`, `job-details.php`: Job publishing tied to active projects, defining daily/hourly pay rates and required trades.
  - `contractor/applications.php`, `application-details.php`: Review job applicants with AI match score visualization; single-click hire automatically enrolls workers into `project_members`.
  - `contractor/tasks.php`, `create-task.php`, `task-details.php`: Workforce task delegation with status tracking (`todo`, `in_progress`, `completed`) and priority levels.
  - `contractor/generate-attendance.php`: Generates dynamic, tokenized QR codes on site for daily worker check-ins.
  - `contractor/contracts.php`, `create-contract.php`, `contract-details.php`: Issues legally binding digital contracts with custom terms and compensation rates.
  - `contractor/quote-requests.php`: Browses open Mode B client quote requests and submits itemized bids.
  - `contractor/project-insights.php`: AI-driven schedule risk analysis highlighting project milestones at risk of delay.

---

### 2.4 Worker Profile & Marketplace Module (`worker/`)
* **Key Routes**:
  - `worker/index.php`: Worker dashboard displaying active contract assignments, pending tasks, recent attendance, and earnings summaries.
  - `worker/profile.php`, `edit-profile.php`: Professional trade profile management (trade title, experience years, hourly/daily wage rates, service radius).
  - `worker/skills.php`: Trade skills matrix across 27 categorized skills with proficiency levels.
  - `worker/documents.php`: Secure identity document and trade license upload queue.
  - `worker/jobs.php`, `job-details.php`, `apply-job.php`: Job discovery with trade filters and application submission with expected pay notes.
  - `worker/scan-attendance.php`: Mobile-friendly QR scanner and token entry interface logging timestamped site check-in/out records.
  - `worker/contracts.php`, `contract-details.php`: Digital contract review interface allowing workers to inspect terms and sign electronically.
  - `worker/nearby-requests.php`: Displays nearby Mode A instant job dispatches with distance calculations (km) and accept/reject actions.
  - `worker/my-bookings.php`: Management dashboard for accepted service dispatches and job execution.
  - `worker/earnings.php`: Financial summary calculating total earnings from attendance hours and completed contracts.

---

### 2.5 Client Project Monitoring Module (`client/`)
* **Key Routes**:
  - `client/index.php`: Client portal featuring active project summaries, budget tracking, and quick booking access.
  - `client/projects.php`, `project-details.php`: Read-only milestone completion monitoring and site coordinates viewing.
  - `client/create-booking.php`: Central wizard allowing clients to book an **Instant Service (Mode A)** or post a project for **Quotes (Mode B)**.
  - `client/bookings.php`, `booking-details.php`: Service booking tracker displaying matched worker status, quote comparison tables, and job completion approval.
  - `client/contracts.php`: Overview of digital agreements tied to client projects.
  - `client/reviews.php`: Feedback interface allowing clients to rate service providers upon completion.

---

## 3. End-to-End Implemented Workflows

### 3.1 Workflow 1: Instant Booking (Mode A — Small Emergency Jobs)

```text
[Client]                            [BuildConnect Engine]                       [Nearby Worker]
   |                                          |                                        |
   |-- 1. Submits Emergency Repair ---------->|                                        |
   |   (Trade, Lat/Lng, Address, Budget)      |-- 2. Calculates Proximity ------------>|
   |                                          |   (Haversine within 5-10 km radius)    |
   |                                          |-- 3. Filters Verified & Available ---->|
   |                                          |-- 4. Dispatches Notification --------->|
   |                                          |                                        |-- 5. Views Request Details
   |                                          |<-- 6. Accepts Booking (Atomic Lock) ---|
   |                                          |    (SELECT ... FOR UPDATE)             |
   |                                          |-- 7. Confirms Assignment & Updates ----|
   |<-- 8. Receives Worker Acceptance Alert --|                                        |
   |                                          |<-- 9. Updates Status (On The Way) -----|
   |                                          |<-- 10. Updates Status (Work Started) --|
   |-- 11. Approves & Marks Completed ------->|                                        |
   |-- 12. Submits Star Rating & Review ----->|                                        |
```

1. **Job Posting**: Client navigates to `client/create-booking.php`, selects "Instant Booking", chooses category (e.g. Electrical Fault), specifies address, coordinates (`location_lat`, `location_lng`), urgency, and budget in INR.
2. **Proximity Calculation**: The system executes `find_matching_workers()` in `includes/booking-functions.php`, computing distance using the spherical Haversine formula:
   $$\text{Distance} = 2R \cdot \arcsin\left(\sqrt{\sin^2\left(\frac{\Delta \text{lat}}{2}\right) + \cos(\text{lat}_1)\cos(\text{lat}_2)\sin^2\left(\frac{\Delta \text{lng}}{2}\right)}\right)$$
3. **Dispatch Notification**: Qualified workers within the radius receive a high-priority system notification and see the job on `worker/nearby-requests.php`.
4. **Concurrency-Safe Acceptance**: When a worker clicks "Accept Job", `accept_instant_booking()` begins a database transaction and executes `SELECT ... FOR UPDATE` on the booking row. If another worker claims it concurrently, the transaction rolls back cleanly with a friendly notification.
5. **Job Execution & Status Updates**: The assigned worker updates progress (`on_the_way` $\rightarrow$ `work_started`).
6. **Completion & Review**: Client marks the job as `completed`, which unlocks the two-way review prompt to rate the worker.

---

### 3.2 Workflow 2: Compare Quotes (Mode B — Large Construction Scopes)

```text
[Client]                           [BuildConnect Platform]                [Contractors & Workers]
   |                                          |                                        |
   |-- 1. Posts Renovation Scope ------------>|                                        |
   |   (Budget range, timeline, blueprints)   |-- 2. Publishes to Quote Board -------->|
   |                                          |                                        |-- 3. Reviews Specifications
   |                                          |<-- 4. Submits Itemized Quotation ------|
   |                                          |    (Price, Scope, Exclusions, Duration)|
   |<-- 5. Receives Bids Notification --------|                                        |
   |-- 6. Compares Quotes in Matrix --------->|                                        |
   |-- 7. Accepts Winning Quote ------------->|                                        |
   |                                          |-- 8. Auto-Rejects Competing Quotes --->|
   |                                          |-- 9. Creates/Syncs Project Record ---->|
   |                                          |-- 10. Notifies Selected Provider ----->|
```

1. **Request Creation**: Client posts scope via `client/create-booking.php` selecting "Compare Quotes", detailing architectural scope, timeline, and budget parameters.
2. **Quote Submission**: Qualified contractors view the request on `contractor/quote-requests.php` and submit structured bids on `contractor/quote-requests.php` detailing proposed price, scope of work, exclusions, and estimated duration.
3. **Revision Support**: Bidders can revise their terms prior to client selection.
4. **Winning Quote Acceptance**: Client views comparison table on `client/booking-details.php?id=...` and clicks "Accept This Quote".
5. **Automatic Synchronization**: `accept_booking_quote()` in `includes/booking-functions.php` executes an atomic transaction:
   - Marks the selected quote as `accepted`.
   - Marks all other competing bids as `rejected`.
   - Automatically synchronizes or creates a linked project in the `projects` table.
   - Dispatches role-appropriate notifications to all participating bidders.

---

### 3.3 Workflow 3: QR Code Site Attendance

```text
[Contractor]                      [BuildConnect Server]                       [Worker]
     |                                      |                                    |
     |-- 1. Generates Daily QR Session ---->|                                    |
     |   (Tied to project_id, 24h token)    |                                    |
     |<-- 2. Displays QR Code on Site ------|                                    |
     |                                      |                                    |
     |                                      |<-- 3. Scans QR or Enters Token ----|
     |                                      |    (worker/scan-attendance.php)    |
     |                                      |-- 4. Validates Token & Coordinates |
     |                                      |-- 5. Logs Check-In Record -------->|
     |                                      |    (verified_by_qr = 1)            |
     |                                      |                                    |
     |                                      |<-- 6. Scans Out at Shift End ------|
     |                                      |-- 7. Calculates Hours Worked ----->|
     |<-- 8. Views Verified Attendance -----|                                    |
```

1. **Session Generation**: Contractor visits `contractor/generate-attendance.php`, selects active project, and generates a dynamic QR session with a 64-character token (`BC-PROJ-...`) stored in `attendance_sessions`.
2. **On-Site Scan**: Worker opens `worker/scan-attendance.php` on their mobile device and scans the physical QR display or inputs the token.
3. **Authentication & Validation**: Server verifies that the worker is an authenticated project member and that the session token is active and unexpired.
4. **Attendance Logging**: Creates a record in `attendance` with `check_in_time = NOW()`, `verified_by_qr = 1`, and `status = 'present'`.
5. **Shift Completion**: Upon shift conclusion, a second scan records `check_out_time` and computes exact `hours_worked`.
