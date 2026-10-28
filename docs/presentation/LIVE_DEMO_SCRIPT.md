# BuildConnect — Step-by-Step Live Project Demonstration Script
**Project Name:** BuildConnect: Digital Construction Workforce & Project Management Platform  
**Target Audience:** Final-Year Engineering / MCA Examination Board & External Viva Examiners  
**Estimated Demonstration Time:** 10 to 12 Minutes (with Optional 5-Minute Fast Track)  
**Local Test Server URL:** `http://localhost:8000` (or `http://localhost/BuildConnect`)  
**Verified Testing Status:** 209/209 Automated Test Assertions Passing  

---

## 📋 Pre-Demo Preparation Checklist (Do this 5 minutes before the examiner enters)
1. Ensure your local server is running:
   ```powershell
   # In terminal:
   php -S localhost:8000 -t e:\BuildConnect
   ```
2. Open your web browser (Chrome or Edge) with four separate tabs or two side-by-side windows:
   - **Tab 1:** `http://localhost:8000/auth/login.php` (for live UI walkthrough)
   - **Tab 2:** `http://localhost:8000/docs/presentation/presentation_slides.html` (Presentation slides)
   - **Tab 3:** MySQL CLI or phpMyAdmin (`http://localhost/phpmyadmin` targeting database `buildconnect_db`)
   - **Tab 4:** Terminal window at `e:\BuildConnect` (to execute verification test suites)
3. Verified Demo Accounts Reference Card:

| Role | Demo Email | Demo Password | Purpose in Demonstration |
| :--- | :--- | :--- | :--- |
| **👑 Admin** | `admin@demo.com` | `Admin@123` | System oversight, KYC document verification, audit logs |
| **👤 Client** | `client@demo.com` | `Client@123` | Instant booking dispatch, project RFP creation, quote evaluation |
| **🔨 Worker** | `worker@demo.com` | `Worker@123` | Claiming instant jobs, availability toggle, QR site attendance |
| **🏢 Contractor** | `contractor@demo.com` | `Contractor@123` | Tender bidding, milestone drafting, dynamic QR code generation |

---

## Phase 1: Problem & Solution Pitch (1 Minute)

### 🎙️ Student Speaking Script:
> *"Respected examiners, the construction and home maintenance sector in India suffers from severe informality. Homeowners struggle to find verified tradespersons, oral contracts cause constant payment disputes, and contractors lose up to 18% of their payroll to paper muster roll attendance fraud.  
> We built **BuildConnect** to solve these problems. It is a full-stack PHP 8.2 and MySQL application with 29 normalized tables, featuring four isolated user roles, a Dual Booking Engine for both emergency repairs and large tenders, and a dynamic QR attendance system.  
> Let me now demonstrate the live application across each role."*

---

## Phase 2: System Administrator Command Center (2.5 Minutes)

### Action Steps:
1. Navigate to: `http://localhost:8000/auth/login.php`
2. Enter Credentials:
   - **Email:** `admin@demo.com`
   - **Password:** `Admin@123`
3. Click **Sign In**. The system authenticates via Bcrypt, regenerates session ID, and redirects to [`/admin/dashboard.php`](file:///e:/BuildConnect/admin/dashboard.php).

### What to Point Out & Speak to Examiners:
- **1. High-Contrast KPI Cards:**
  > *"As you can see on the Admin Dashboard, we have real-time KPI metric cards displaying Total Registered Users, Pending KYC Verifications, Active Projects, and Platform Transaction Volume. All card values are calculated directly via indexed queries from MySQL."*
- **2. KYC Verification Drawer:**
  - Click on **Verifications** in the left sidebar or the "Review Documents" link.
  - Show the list of workers awaiting identity approval.
  - Click **Inspect Document** to open the modal previewing the uploaded government ID (Aadhaar/PAN).
  - Click **Approve**.
  > *"When a worker registers, their profile remains inactive. Here in the Verification Queue, the administrator inspects the uploaded government document. Clicking 'Approve' triggers an atomic transaction updating `users.is_verified = 1`, which enables the worker to accept live client bookings."*
- **3. Immutable Audit Journal:**
  - Click on **Audit Logs** (`/admin/audit-logs.php`).
  - Show the recent log entries.
  > *"Every sensitive action—logins, role elevations, and document approvals—is recorded in this immutable audit log along with the user ID, client IP address, user agent, and timestamp. There are no delete or update endpoints for this table."*

---

## Phase 3: Client Dual Booking Workflows (3 Minutes)

### Action Steps:
1. Log out of Admin by clicking the user avatar in the top-right and selecting **Logout**.
2. Log in as **Client**:
   - **Email:** `client@demo.com`
   - **Password:** `Client@123`
3. Land on [`/client/dashboard.php`](file:///e:/BuildConnect/client/dashboard.php).

### Workflow A: Instant Emergency Booking (Mode A)
1. Click **Instant Booking** (`/client/instant-booking.php`).
2. Select Category: **Plumbing** or **Electrical**.
3. Point to the UI:
   > *"In Mode A, a client facing an emergency leak or electrical fault selects the trade category and location. The backend calculates geographic distance using the SQL Haversine formula against registered workers within a 50 km radius who have marked themselves available."*
4. Click **Book Now** on an available worker (e.g., Demo Worker).
5. Enter emergency job details:
   - **Job Title:** Emergency Pipe Leakage Repair
   - **Preferred Date:** Today / Tomorrow
   - **Offered Amount:** ₹850
6. Click **Confirm Booking**.
   > *"Behind the scenes, the controller executes a `SELECT ... FOR UPDATE` row lock on this worker in MySQL. This guarantees atomicity—no two clients can book the same worker for the same time slot."*

### Workflow B: Compare Quotes for Large Projects (Mode B)
1. Click **Projects** → **Post New Project** (`/client/create-project.php`).
2. Fill out project RFP:
   - **Title:** Master Bedroom Renovation & Flooring
   - **Category:** Carpentry & Masonry
   - **Budget Range:** ₹75,000 to ₹1,20,000
   - **Estimated Timeline:** 21 Days
   - **Description:** Complete demolition of existing tile flooring and installation of vitrified tiles with custom wardrobe carpentry.
3. Click **Publish Tender**.
   > *"The project is now published on the public tender board. Let's switch to the Contractor account to review and bid on it."*

---

## Phase 4: Contractor Bidding & Dynamic QR Attendance (3 Minutes)

### Action Steps:
1. Log out of Client.
2. Log in as **Contractor**:
   - **Email:** `contractor@demo.com`
   - **Password:** `Contractor@123`
3. Land on [`/contractor/dashboard.php`](file:///e:/BuildConnect/contractor/dashboard.php).

### Workflow A: Bidding on the Client Tender
1. Click **Browse Projects** (`/contractor/browse-projects.php`).
2. Locate the project just created: *"Master Bedroom Renovation & Flooring"*.
3. Click **Submit Proposal**.
4. Enter Bid Details:
   - **Bid Amount:** ₹88,000
   - **Completion Days:** 18 Days
   - **Milestone Breakdown:**
     - Milestone 1: Demolition & Site Prep (₹25,000)
     - Milestone 2: Tile Laying & Grouting (₹35,000)
     - Milestone 3: Carpentry & Final Polish (₹28,000)
5. Click **Submit Bid**.
   > *"The contractor's bid is saved in the `bids` table. When the client reviews and accepts it, a formal contract is instantiated in the `contracts` table and milestone stages are generated."*

### Workflow B: Dynamic QR Code Site Attendance Generator
1. In the contractor sidebar, click **QR Attendance Terminal** (`/contractor/attendance-terminal.php` or `attendance.php`).
2. Select Active Project Site.
3. Click **Generate Daily Site QR Code**.
4. Observe the live modal display:
   > *"This is our dynamic anti-fraud attendance system. Unlike static printed QR codes that workers can photograph and share over WhatsApp, this QR code encodes a 64-character SHA-256 token that auto-refreshes every 60 seconds via an animated countdown timer.*  
   > *When the worker scans this code on site, their mobile browser transmits the token along with their live GPS latitude and longitude to our `/api/attendance.php` endpoint.*  
   > *The system verifies that the worker is physically within 200 meters of the registered site before recording check-in."*

---

## Phase 5: Worker Job Claim & Attendance Verification (1.5 Minutes)

### Action Steps:
1. Log out of Contractor.
2. Log in as **Worker**:
   - **Email:** `worker@demo.com`
   - **Password:** `Worker@123`
3. Land on [`/worker/dashboard.php`](file:///e:/BuildConnect/worker/dashboard.php).

### What to Point Out:
1. Show the **Incoming Instant Booking Notification Card**:
   - Shows the *"Emergency Pipe Leakage Repair"* job booked in Phase 3.
   - Shows Client Name, Address, and Offered Wage (₹850).
2. Click **Accept Booking**.
   - Status instantly transitions from `requested` to `confirmed`.
   > *"The worker accepts the job in one click. Both the client and worker receive updated status indicators."*
3. Show the **Availability Toggle Switch**:
   - Flip "Available for Work" on/off.
   > *"Workers can toggle their availability at any time. When set to unavailable, the Haversine radius search excludes them from new instant booking dispatches."*

---

## Phase 6: Direct MySQL Database Record Inspection (1.5 Minutes)

### Action Steps:
Switch to **Tab 3** (phpMyAdmin or terminal MySQL CLI):
```sql
USE buildconnect_db;
```

### 1. Show the New Booking Record:
```sql
SELECT id, client_id, worker_id, status, total_amount, created_at 
FROM bookings 
ORDER BY id DESC LIMIT 1;
```
> *"Here is the live record in the `bookings` table created during our Mode A demonstration, showing exact foreign keys to `users(id)` and status transitioned to `confirmed`."*

### 2. Show the Published Project and Contractor Bid:
```sql
SELECT p.id, p.title, p.status, b.contractor_id, b.bid_amount, b.status as bid_status
FROM projects p
JOIN bids b ON p.id = b.project_id
ORDER BY b.id DESC LIMIT 1;
```
> *"Here in the relational schema, the `projects` table seamlessly links to `bids` via a 1-to-many relationship, maintaining 3NF normalization without redundant data."*

### 3. Show the Dynamic Attendance and Audit Records:
```sql
SELECT id, user_id, action, ip_address, created_at 
FROM audit_logs 
ORDER BY id DESC LIMIT 5;
```
> *"And here is the immutable forensic record in `audit_logs`, tracking our logins, booking creations, and administrative actions with client IP and timestamps."*

---

## Phase 7: Security Architecture, Test Results & Wrap-Up (1 Minute)

### Action Steps:
Switch to **Tab 4** (Terminal) and run the automated test suite:
```powershell
php scratch/run_full_verification.php
```
*(Or point to [`docs/07_TEST_SUITE_AND_VERIFICATION_RESULTS.md`](file:///e:/BuildConnect/docs/07_TEST_SUITE_AND_VERIFICATION_RESULTS.md))*

### 🎙️ Student Speaking Script:
> *"To ensure enterprise-grade stability, we wrote comprehensive automated test suites:  
> - **134/134 PHP files** passed syntax validation with zero parse errors.  
> - **24/24 Live HTTP RBAC tests** verified that unauthorized cross-role access is strictly blocked with HTTP 403 Forbidden.  
> - **14/14 Dual Booking E2E tests** verified complete state progression.  
> Across all suites, **209 out of 209 test assertions passed with 100% success**.*  
>  
> *Regarding technical boundaries:  
> 1. Payment transactions are fully tracked in our database with milestone references, but external banking escrow webhooks require commercial merchant KYC.  
> 2. QR scanning uses the HTML5 browser camera; a native Android mobile application with offline muster roll caching represents our immediate future scope.*  
>  
> *Thank you. I am now pleased to answer any questions from the examination board."*

---

## 🎯 Quick Troubleshooting Guide for Live Demonstration

| Potential Issue | Quick Fix |
| :--- | :--- |
| **"Access Denied" appears unexpectedly** | Clear browser session cookies or use an Incognito window. Ensure you are logging in with the designated role account. |
| **Localhost server not responding** | Verify the background server is active: run `php -S localhost:8000 -t e:\BuildConnect` in a new PowerShell window. |
| **Camera permission blocked for QR scanner** | Click the lock/tune icon beside the browser URL bar, ensure "Camera" is set to "Allow", and reload the page. |
| **Database connection error** | Ensure MySQL is running in XAMPP / Windows Services on port 3306 with credentials in `config/database.php`. |
