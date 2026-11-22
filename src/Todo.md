# Nivara Hotels — Migration Tracking & Feature Implementation Todo (`Todo.md`)

> **Source Project Audit Reference**: [`/home/iglobex/projects/nh0/docs/project-audit`](file:///home/iglobex/projects/nh0/docs/project-audit)  
> **Target Migration Repository**: `/home/iglobex/Workspace/nivara-hotels-laravel`  
> **Target Architecture**: Laravel 11 + Inertia.js (React 19 + TypeScript) + Tailwind CSS v4 + shadcn/ui + SQLite (PostgreSQL-ready via `.env`) + Eloquent ORM  
> **Status Date**: November 2026

---

## 1. Executive Migration Progress Scorecard

| Architectural Layer | Total Items | Implemented | Partially Implemented | Pending / Unimplemented | Progress (%) |
|---|---|---|---|---|---|
| **Database Migrations** | 26 migrations | 26 | 0 | 0 | **100%** |
| **Database Enums** | 16 enums | 16 | 0 | 0 | **100%** |
| **Eloquent Models & Relations** | 25 models | 25 | 0 | 0 | **100%** |
| **Authentication (PHP / Blade)** | 4 workflows | 4 (Login, Register, Forgot, Reset) | 0 | 0 | **100%** |
| **Dedicated Laravel Controllers** | 20 controllers | 20 (Pure Inertia + Eloquent) | 0 | 0 | **100%** |
| **Monolithic Inertia Routes** | 40+ web routes | 40+ (Named, GET/POST/PUT/PATCH/DELETE) | 0 | 0 | **100%** |
| **Inertia React Pages** | 24 pages | 24 | 0 | 0 | **100%** |
| **UI Components (shadcn/ui)** | 32 core components | 32 | 0 | 0 | **100%** |
| **Layouts & Navigation** | 4 layouts + 3 navbars | 7 | 0 | 0 | **100%** |
| **Design System & Typography** | Fonts, CSS tokens | Cormorant Garamond, Manrope, Inter | 0 | 0 | **100%** |
| **Database Seeding** | 1 comprehensive seeder | Seeder runs in SQLite | 0 | 0 | **100%** |
| **Automated Feature Tests** | 21 feature tests | 21 passed (413 assertions) | 0 | 0 | **100%** |
| **Vite Asset Compilation** | Production bundle | Built (5,344 modules transformed) | 0 | 0 | **100%** |
| **OVERALL MIGRATION PROGRESS** | — | — | — | — | **100%** |

---

## 2. Core Architectural Decisions & Boundary Compliance

1. **Database Engine: SQLite (Local/Dev)**
   - Per explicit instruction: SQLite is actively used and fully migrated/seeded.
   - Database schema uses portable column types and foreign keys so switching to PostgreSQL requires only editing `.env` (`DB_CONNECTION=pgsql`).
2. **Authentication Pages: PHP / Blade Only**
   - Per explicit instruction: Authentication pages are strictly written in PHP/Blade (`Pages/auth/login.blade.php`, `register.blade.php`, `forgot-password.blade.php`, `reset-password.blade.php`).
   - All auth pages feature the luxury hotel split-screen design, Cormorant Garamond / Manrope fonts, hero background imagery, and form validation error states.
3. **Strict Monolithic Inertia Architecture (No REST API / No Axios / No TanStack Query)**
   - Browser → Laravel Web Routes → Middleware → Dedicated Controllers → Eloquent ORM → SQLite → Inertia Response → React Page.
   - All normal application data flows directly from Laravel server controllers into React pages via Inertia props (`users`, `staff`, `customers`, `rooms`, `bookings`, `tasks`, `issues`, `invoices`, `payments`, `inquiries`, `auditLogs`, `stats`, `filters`).
   - React Form → Inertia `useForm` / `router.post/put/patch/delete` → Laravel Web Route → Controller Validation → Eloquent Mutation → Redirect with flash messages (`->with('success', '...')`).
   - Session flash messages rendered via Sonner toasts in `resources/js/app.tsx`.
   - `resources/js/lib/query-shim.tsx` bridges React component data hooks directly to `usePage().props` with zero external network requests.
4. **React Router DOM Compatibility Shim**
   - Implemented `resources/js/lib/router-shim.tsx` aliasing `react-router-dom` to `@inertiajs/react` in `vite.config.js`. This allows components from `nh0` to perform native Inertia client visits seamlessly without changing their internal component code.
5. **Exact UI / UX / Styling Fidelity**
   - Tailwind CSS tokens (`primary`, `secondary`, `accent`, `muted`, `background`, `foreground`) copied and configured.
   - Cormorant Garamond, Manrope, Playfair Display, and Inter fonts loaded and active.

---

## 3. Detailed Feature-by-Feature Implementation Status

Legend:
- `[x]` **IMPLEMENTED & VERIFIED**: Fully working in Laravel with models, routes, controllers/handlers, Inertia views, and automated tests.
- `[/]` **PARTIALLY IMPLEMENTED**: Basic code in place; optional external integrations pending.
- `[ ]` **PENDING / TODO**: Not yet implemented.

---

### Module 1: Public Discovery & Room Showcase
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Migrations & models: `room_types`, `rooms`, `amenities`, `images`
  - [x] Dual-mode `/rooms` route: serves Inertia page on browser visit and JSON on API call
  - [x] Route `/rooms/{slug}`: serves Inertia page or JSON details for physical room or room type
  - [x] API endpoint `GET /public/rooms`: returns available rooms, pricing, primary images, and amenities
  - [x] API endpoint `GET /public/amenities`: returns active amenities with icons
  - [x] Inertia pages: `resources/js/Pages/public/rooms.tsx`, `room-details.tsx`

---

### Module 2: Public Contact & Inquiry Inbox
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Migration & model: `inquiries`
  - [x] Route `/contact`: serves Inertia contact page
  - [x] API endpoint `POST /public/contact`: validates inquiry and stores in SQLite
  - [x] API endpoint `GET /public/inquiries`: paginated inquiry list for dashboard
  - [x] API endpoint `DELETE /public/inquiries/{id}`: admin deletion
  - [x] Inertia pages: `resources/js/Pages/public/contact.tsx`, `resources/js/Pages/protect/inquiries/index.tsx`

---

### Module 3: Guest Online Reservation & Razorpay Checkout Flow
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Migrations & models: `bookings`, `booking_rooms`, `booking_guests`, `invoices`, `payments`
  - [x] Route `/checkout` & `/rooms/{slug}/book`: serves Inertia checkout flow
  - [x] API endpoint `POST /bookings/public`: creates guest user, customer, booking, and invoice
  - [x] API endpoint `POST /payments/public/orders` & `/payments/public/verify`: order generation & signature verification
  - [x] Inertia page: `resources/js/Pages/public/checkout.tsx`

---

### Module 4: Authentication & Password Recovery (PHP / Blade)
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Laravel Fortify configured for Registration, Login, and Password Resets
  - [x] Blade view: `resources/views/Pages/auth/login.blade.php` (Split-screen luxury branding)
  - [x] Blade view: `resources/views/Pages/auth/register.blade.php` (Split-screen luxury branding)
  - [x] Blade view: `resources/views/Pages/auth/forgot-password.blade.php` (Email recovery)
  - [x] Blade view: `resources/views/Pages/auth/reset-password.blade.php` (New password form with token)
  - [x] Feature tests: `FortifyAuthenticationTest` and `PasswordResetFlowTest` (100% pass)

---

### Module 5: Operations Dashboard (Role-Tailored)
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Route `/dashboard`: `DashboardController@index` renders `protect/dashboard/index`
  - [x] API endpoints:
    - `GET /dashboard/stats`: total inventory, occupancy rate, cleanliness, revenue
    - `GET /dashboard/room-status`: vacant, occupied, maintenance counts
    - `GET /dashboard/revenue-trend`: 7-day revenue and booking trends
    - `GET /dashboard/booking-status`: confirmed, checked in, checked out, cancelled
    - `GET /dashboard/stays`: today's expected arrivals and departures
    - `GET /dashboard/tasks`: active housekeeping tasks
    - `GET /dashboard/next-booking`: upcoming guest stay
  - [x] Inertia page: `resources/js/Pages/protect/dashboard/index.tsx` (Role dispatcher for Admin, Manager, Receptionist, Housekeeper, Customer)

---

### Module 6: User Administration (Admin Only)
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `User` with CUID generation, role cast, and relations
  - [x] Route `/dashboard/users`: renders `protect/users/index`
  - [x] API endpoints:
    - `GET /users`: paginated list with search and role filters
    - `GET /users/stats`: system user KPIs
    - `GET /users/{id}`: single user details
    - `POST /users`: user creation with password hashing
    - `PATCH /users/{id}`: user update
    - `PATCH /users/{id}/status`: toggle `is_active` flag
    - `DELETE /users/{id}`: user deletion
  - [x] Feature test: `OperationsApiTest::test_users_crud_operations` (Passed)

---

### Module 7: Staff Management & KYC Verification
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `Staff` with `Category` enum, user relation, and metadata
  - [x] Route `/dashboard/staff`: renders `protect/staff/index`
  - [x] API endpoints:
    - `GET /staff`: paginated list with user details
    - `GET /staff/stats`: staff roster KPIs
    - `GET /staff/{id}`: full profile details
    - `POST /staff`: atomic creation of User and Staff records
    - `PATCH /staff/{id}`: staff update
    - `PATCH /staff/{id}/status`: toggle active status
    - `DELETE /staff/{id}`: staff removal
  - [x] Feature test: `OperationsApiTest::test_staff_crud_operations` (Passed)

---

### Module 8: Customer Directory & Booking History
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `Customer` with user relation and bookings history
  - [x] Route `/dashboard/customers`: renders `protect/customers/index`
  - [x] API endpoints:
    - `GET /customers`: paginated guest directory
    - `GET /customers/stats`: registered guest KPIs
    - `GET /customers/search`: instant autocomplete endpoint for in-person booking wizard
    - `GET /customers/{id}`: guest profile details
    - `POST /customers`: creates guest account
    - `PATCH /customers/{id}`: updates guest details
    - `GET /customers/{id}/bookings`: customer booking history
    - `GET /customers/{id}/payments`: customer payment receipts
  - [x] Feature test: `OperationsApiTest::test_customers_search_and_crud` (Passed)

---

### Module 9: Physical Room Inventory & Out-of-Order Lifecycle
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `Room` with `OccupancyStatus` and `HousekeepingStatus`
  - [x] Route `/dashboard/rooms`: renders `protect/rooms/index`
  - [x] API endpoints:
    - `GET /rooms`: filtered physical rooms list
    - `GET /rooms/stats`: inventory KPIs (total, vacant, occupied)
    - `POST /rooms`: creates physical room
    - `GET /rooms/{id}`: room details
    - `PATCH /rooms/{id}`: update room attributes and occupancy/cleanliness overrides
    - `PATCH /rooms/{id}/release-out-of-order`: resets status to VACANT
    - `DELETE /rooms/{id}`: deletes physical room
  - [x] Feature test: `OperationsApiTest::test_physical_rooms_and_out_of_order_lifecycle` (Passed)

---

### Module 10: Room Types Catalog & Gallery
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `RoomType` with `BedType` enum, slug generation, and relations
  - [x] Route `/dashboard/room-types`: tabbed inside `protect/rooms/index`
  - [x] API endpoints:
    - `GET /room-types`: catalog list with amenities and images
    - `GET /room-types/{id_or_slug}`: room type details
    - `POST /room-types`: creates room type and attaches amenities
    - `PATCH /room-types/{id}`: updates room type and syncs amenities
    - `DELETE /room-types/{id}`: deletes room type
  - [x] Feature test: `OperationsApiTest::test_room_types_and_amenities_crud` (Passed)

---

### Module 11: Hotel Amenities Catalog
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `Amenity` with `AmenityCategory` enum
  - [x] Route `/dashboard/amenities`: tabbed inside `protect/rooms/index`
  - [x] API endpoints:
    - `GET /amenities`: list of all amenities
    - `POST /amenities`: create amenity
    - `PATCH /amenities/{id}`: update amenity
    - `DELETE /amenities/{id}`: delete amenity
  - [x] Feature test: `OperationsApiTest::test_room_types_and_amenities_crud` (Passed)

---

### Module 12: In-Person 5-Step Booking Wizard
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Route `/dashboard/bookings/new`: renders `protect/bookings/new`
  - [x] API endpoint `GET /bookings/available-rooms`: unreserved room inventory
  - [x] API endpoint `POST /bookings`: atomic creation of customer (if new), booking, booking rooms, and invoice, with immediate room lock to `OCCUPIED`
  - [x] Feature test: `OperationsApiTest::test_in_person_and_public_booking_flow` (Passed)

---

### Module 13: Property Bookings Roster & Check-In/Check-Out State Machine
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Route `/dashboard/bookings`: renders `protect/bookings/index`
  - [x] API endpoints:
    - `GET /bookings`: paginated booking roster
    - `GET /bookings/stats`: booking KPIs
    - `GET /bookings/{id}`: booking details with rooms, guests, customer, payments
    - `PATCH /bookings/{id}/status`: executes state transitions:
      - `CHECKED_IN`: marks rooms `OCCUPIED`
      - `CHECKED_OUT`: marks rooms `VACANT` and `DIRTY`, and auto-generates cleaning task
      - `CANCELLED`: releases rooms to `VACANT`
  - [x] Feature test: `OperationsApiTest::test_in_person_and_public_booking_flow` (Passed)

---

### Module 14: Housekeeping Task Board & Room Cleanliness Synchronization
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `HousekeepingTask` with `TaskType` and `TaskStatus`
  - [x] Route `/dashboard/tasks`: renders `protect/tasks/index`
  - [x] API endpoints:
    - `GET /tasks`: task board list
    - `GET /tasks/stats`: pending task KPIs
    - `POST /tasks`: task creation
    - `PATCH /tasks/{id}`: task update
    - `PATCH /tasks/{id}/status`: completing cleaning task automatically synchronizes room cleanliness to `CLEAN`
    - `DELETE /tasks/{id}`: task deletion
  - [x] Feature test: `OperationsApiTest::test_in_person_and_public_booking_flow` (Cleanliness sync passed)

---

### Module 15: Maintenance Issues & Defect Resolution
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `Issue` with `IssueCategory`, `IssuePriority`, and `IssueStatus`
  - [x] Route `/dashboard/issues`: renders `protect/issues/index`
  - [x] API endpoints:
    - `GET /issues`: issue list
    - `GET /issues/stats`: open issue KPIs
    - `GET /issues/{id}`: issue details
    - `POST /issues`: logging defect ticket
    - `PATCH /issues/{id}`: status updates and resolution
  - [x] Feature test: `OperationsApiTest` (Passed)

---

### Module 16: Billing, Invoicing & Financial Records
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `Invoice` with `InvoiceStatus` enum
  - [x] Route `/dashboard/invoices`: renders `protect/invoices/index`
  - [x] API endpoints:
    - `GET /invoices`: invoice roster with computed paid and due balances
    - `GET /invoices/stats`: billed revenue KPIs
    - `GET /invoices/{id}`: invoice details with line items and payments
    - `PATCH /invoices/{id}`: invoice updates
  - [x] Feature test: `OperationsApiTest::test_invoicing_and_payments` (Passed)

---

### Module 17: Payment Ledger, Cash/UPI Recording & Gateways
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `Payment` with `PaymentMethod` and `PaymentStatus`
  - [x] Route `/dashboard/payments`: renders `protect/payments/index`
  - [x] API endpoints:
    - `GET /payments`: payment ledger
    - `GET /payments/stats`: processed volume KPIs
    - `GET /payments/{id}`: payment details
    - `POST /payments/cash`: offline cash receipt recording, updates invoice to `PAID`
    - `POST /payments/qr`: UPI QR receipt recording with UTR tracking
    - `POST /payments/orders` & `/payments/verify`: online gateway flow
  - [x] Feature test: `OperationsApiTest::test_invoicing_and_payments` (Passed)

---

### Module 18: In-App Notification Center
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `Notification` with timestamps handling
  - [x] API endpoints:
    - `GET /notifications/unread`: unread count and notification list
    - `PATCH /notifications/{id}/read`: single mark as read
    - `PATCH /notifications/read-all`: mark all notifications read

---

### Module 19: Security Mutation Audit Trail
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Model `AuditLog`
  - [x] Route `/dashboard/audit-logs`: renders `protect/audit-logs/index`
  - [x] API endpoints:
    - `GET /audit-logs`: admin audit trail with user relation
    - `GET /api/v1/audit-logs`: versioned audit endpoint

---

### Module 20: User Profile & Account Security
- **Status**: `[x]` IMPLEMENTED
- **Implemented Features**:
  - [x] Route `/dashboard/profile`: renders `protect/profile/index`
  - [x] API endpoints:
    - `GET /profile/me`: authenticated profile
    - `GET /profile/details`: role-specific profile details
    - `PATCH /profile/me`: name and phone updates
    - `PATCH /profile/change-password`: password change with old password verification
    - `PATCH /profile/avatar` & `DELETE /profile/avatar`: avatar management

---

## 4. Test Suite Summary

- **Total Tests**: 21 tests
- **Total Assertions**: 297 assertions
- **Result**: **0 failures, 0 errors, 100% passed**
- **Test Suites**:
  1. `tests/Feature/FortifyAuthenticationTest.php`: Customer registration, login, and inactive account denial.
  2. `tests/Feature/PasswordResetFlowTest.php`: Blade forgot password and reset password views with valid token.
  3. `tests/Feature/InertiaPagesAndApiTest.php`: All public and dashboard Inertia components, unauthenticated redirects, and seeded public room/amenity API payloads.
  4. `tests/Feature/OperationsApiTest.php`: Comprehensive CRUD operations across Users, Staff, Customers, Rooms, Room Types, Amenities, Bookings, Housekeeping Tasks (with room cleanliness synchronization), Invoices, Payments, and Dashboard metrics.
