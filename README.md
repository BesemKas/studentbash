# Connectra - Event Management and Ticketing System

## Table of Contents
1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Core Features](#core-features)
4. [Technical Stack](#technical-stack)
5. [Database Schema](#database-schema)
6. [Authentication & Authorization](#authentication--authorization)
7. [Ticket Management System](#ticket-management-system)
8. [Event Management](#event-management)
9. [QR Code System](#qr-code-system)
10. [Payment Verification](#payment-verification)
11. [File Storage](#file-storage)
12. [Email Notifications](#email-notifications)
13. [Queue System](#queue-system)
14. [Security Features](#security-features)
15. [API Routes](#api-routes)
16. [Installation & Setup](#installation--setup)
17. [Configuration](#configuration)
18. [Testing](#testing)
19. [Deployment](#deployment)

---

## Overview

Connectra is a comprehensive event management and ticketing system built with Laravel 12 and Livewire. The system enables event organizers to create events, manage ticket types, and handle ticket sales with QR code-based validation. It supports both full-pass and day-pass tickets, VIP options, age verification, and manual payment verification workflows.

### Key Capabilities

- **Event Management**: Create and manage multi-day events with custom ticket types
- **Ticket Generation**: Generate secure QR code-based tickets with unique identifiers
- **Payment Verification**: Manual payment verification workflow with email notifications
- **Gate Validation**: Real-time QR code scanning and ticket validation at event gates
- **User Management**: Role-based access control (Admin and User roles)
- **Age Verification**: Automatic age calculation and minor detection
- **File Management**: Secure file storage and serving for event thumbnails

---

## System Architecture

### High-Level Architecture

Connectra follows a **Model-View-Controller (MVC)** architecture pattern with **Livewire Volt** components for reactive frontend interactions. The system is built on Laravel 12, utilizing:

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Livewire Volt (server-side reactive components)
- **UI Framework**: Livewire Flux (component library) with Tailwind CSS 4
- **Database**: SQLite (default) or any Laravel-supported database
- **Queue System**: Database queue driver for asynchronous job processing
- **Authentication**: Laravel Fortify for authentication flows

### Component Architecture

#### 1. **Models Layer** (`app/Models/`)
- `User`: User authentication and profile management
- `Event`: Event information and metadata
- `EventDate`: Individual dates within multi-day events
- `EventTicketType`: Ticket type definitions (VIP, Full Pass, Day Pass)
- `Ticket`: Individual ticket instances with QR codes

#### 2. **Controllers Layer** (`app/Http/Controllers/`)
- `AdminEventController`: Handles event creation/updates with file uploads
- `StorageController`: Serves files from storage (workaround for symlink blocking)
- `ProfileController`: User profile management
- `QueueProcessorController`: Queue processing endpoint for cron jobs

#### 3. **Livewire Volt Components** (`resources/views/livewire/`)
- `ticket-generator`: Ticket creation interface
- `scanner-validator`: QR code scanning and gate validation
- `verification-manager`: Admin payment verification interface
- `admin-events`: Event management interface
- `admin-event-ticket-types`: Ticket type management
- `my-tickets`: User ticket viewing
- `events`: Public event browsing
- `event-detail`: Event detail page

#### 4. **Utilities** (`app/Utilities/`)
- `TicketIdGenerator`: Generates secure ticket IDs and payment references

#### 5. **Notifications** (`app/Notifications/`)
- `TicketVerifiedNotification`: Email notification when ticket is verified

### Request Flow

1. **User Request** → Routes (`routes/web.php`)
2. **Middleware** → Authentication, Role checks
3. **Controller/Volt Component** → Business logic
4. **Model** → Database interaction
5. **View** → Blade template rendering
6. **Response** → HTML/JSON response

### Data Flow for Ticket Generation

1. User selects event and ticket type
2. User enters holder information (name, DOB, email)
3. System generates payment reference (`P-{INITIALS}-{RANDOM}`)
4. System generates secure ticket ID (`TYPE-R3DDR2MMR2YYR2C-I`)
5. QR code is generated with URL: `/gate?ticket={TICKET_ID}`
6. Ticket is saved with `is_verified = false`
7. Admin verifies payment manually
8. System sends email notification to ticket holder
9. Ticket becomes valid for gate entry

---

## Core Features

### 1. Event Management
- **Multi-Day Events**: Support for events spanning multiple days with automatic date generation
- **Event Thumbnails**: Image upload and management for event thumbnails
- **Event Status**: Active/inactive event status control
- **Event Details**: Name, location, start date, end date management

### 2. Ticket Type Management
- **Full Pass Tickets**: Valid for all days of the event
- **Day Pass Tickets**: Valid for specific selected days
- **VIP Tickets**: Special VIP designation with custom armband colors
- **Adult-Only Tickets**: Age-restricted ticket types (18+)
- **Custom Pricing**: Price per ticket type
- **Armband Colors**: Visual identification system for different ticket types

### 3. Ticket Generation
- **Secure ID Generation**: Cryptographically secure ticket IDs
- **QR Code Generation**: Automatic QR code creation with embedded ticket URLs
- **Holder Information**: Name, date of birth, email collection
- **Payment Reference**: Auto-generated payment references for external payment systems
- **Age Detection**: Automatic minor/adult classification
- **Email Preferences**: Option to send verification emails to ticket holder

### 4. Payment Verification
- **Manual Verification**: Admin-controlled payment verification workflow
- **Payment Reference Search**: Search tickets by payment reference
- **Bulk Verification**: Toggle verification status for multiple tickets
- **Email Notifications**: Automatic email when ticket is verified
- **Verification Status**: Clear visual indicators for verified/unverified tickets

### 5. Gate Validation
- **QR Code Scanning**: Real-time QR code scanning using device camera
- **Manual Entry**: Manual ticket ID entry for validation
- **Multi-Event Support**: Event filtering for gate staff
- **Security Checks**:
  - Payment verification status
  - Ticket usage status (one-time use)
  - Event date validation (for day passes)
  - Age verification (for adult-only tickets)
- **Check-In Process**: Mark tickets as used after validation
- **Visual Feedback**: Color-coded status messages (success/error/warning)

### 6. User Management
- **Role-Based Access**: Admin and User roles with Spatie Permission
- **User Profiles**: Name and email management
- **Password Management**: Secure password updates
- **Account Settings**: Profile and appearance settings

### 7. Public Event Browsing
- **Event Listing**: Public event catalog
- **Event Details**: Detailed event information pages
- **Active Event Filtering**: Only active events displayed

### 8. My Tickets
- **Ticket Viewing**: Users can view all their tickets
- **Ticket Details**: Full ticket information including QR codes
- **Verification Status**: Clear indication of payment verification status

---

## Technical Stack

### Backend Technologies

#### Laravel 12
- **Framework**: Laravel 12 (PHP 8.2+)
- **Architecture**: MVC with Livewire Volt
- **ORM**: Eloquent ORM
- **Migrations**: Database version control
- **Factories**: Model factories for testing
- **Seeders**: Database seeding for roles and initial data

#### Authentication & Authorization
- **Laravel Fortify**: Authentication system (login, registration, password reset)
- **Spatie Permission**: Role and permission management
- **Roles**: `admin` and `user` roles
- **Middleware**: Role-based route protection

#### Queue System
- **Driver**: Database queue driver
- **Jobs**: Queued email notifications
- **Processing**: Cron-based queue processing endpoint

#### File Storage
- **Storage Driver**: Local filesystem
- **Public Storage**: Symlinked public storage (with fallback route)
- **File Serving**: Custom route for serving files when symlinks are blocked

### Frontend Technologies

#### Livewire Volt
- **Framework**: Livewire Volt 1.7.0
- **Components**: Single-file components with embedded PHP logic
- **Reactivity**: Server-side reactive components without JavaScript framework
- **Real-time Updates**: Live DOM updates via AJAX

#### UI Framework
- **Livewire Flux**: Component library (buttons, inputs, modals, etc.)
- **Tailwind CSS 4**: Utility-first CSS framework
- **Vite**: Build tool for assets
- **Responsive Design**: Mobile-first responsive design

#### JavaScript Libraries
- **html5-qrcode**: QR code scanning library
- **Axios**: HTTP client for AJAX requests
- **Alpine.js**: Lightweight JavaScript framework (via Livewire)

### Database

#### Default Database
- **SQLite**: Default database (development)
- **Migration Support**: Full Laravel migration support for any database

#### Database Tables
- `users`: User accounts
- `events`: Event information
- `event_dates`: Individual event dates
- `event_ticket_types`: Ticket type definitions
- `tickets`: Individual ticket records
- `roles`: User roles
- `permissions`: System permissions
- `model_has_roles`: User-role assignments
- `jobs`: Queue jobs
- `failed_jobs`: Failed queue jobs
- `cache`: Cache storage
- `telescope_entries`: Laravel Telescope debugging data

### Development Tools

#### Testing
- **Pest PHP**: Modern PHP testing framework
- **Laravel Testing**: Full Laravel testing suite
- **Feature Tests**: Authentication, Livewire components, settings
- **Unit Tests**: Model and utility tests

#### Code Quality
- **Laravel Pint**: Code style fixer
- **PHPStan**: Static analysis (optional)

#### Debugging
- **Laravel Telescope**: Application debugging and monitoring
- **Logging**: Comprehensive logging throughout application

### Dependencies

#### Core PHP Packages
- `laravel/framework`: ^12.0
- `laravel/fortify`: ^1.30
- `laravel/telescope`: ^5.15
- `livewire/flux`: ^2.1.1
- `livewire/volt`: ^1.7.0
- `spatie/laravel-permission`: ^6.23

#### Development Packages
- `pestphp/pest`: ^4.1
- `pestphp/pest-plugin-laravel`: ^4.0
- `laravel/pint`: ^1.24
- `laravel/sail`: ^1.41
- `mockery/mockery`: ^1.6

#### Frontend Packages
- `@tailwindcss/vite`: ^4.1.11
- `tailwindcss`: ^4.0.7
- `vite`: ^7.0.4
- `axios`: ^1.7.4
- `laravel-vite-plugin`: ^2.0

---

## Database Schema

### Entity Relationship Diagram

```
users
  ├── tickets (one-to-many)
  └── roles (many-to-many via model_has_roles)

events
  ├── event_dates (one-to-many)
  ├── event_ticket_types (one-to-many)
  └── tickets (one-to-many)

event_dates
  └── tickets (one-to-many)

event_ticket_types
  └── tickets (one-to-many)
```

### Table Definitions

#### `users` Table
Standard Laravel users table with role support.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string(255) | User's full name |
| `email` | string(255) | Unique email address |
| `email_verified_at` | timestamp | Email verification timestamp |
| `password` | string(255) | Hashed password |
| `remember_token` | string(100) | Remember me token |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

**Relationships:**
- `hasMany(Ticket::class)` - User's tickets
- `belongsToMany(Role::class)` - User roles (via Spatie Permission)

**Indexes:**
- Primary key on `id`
- Unique index on `email`

#### `events` Table
Stores event information and metadata.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string(255) | Event name |
| `location` | string(255) | Event location |
| `start_date` | date | Event start date |
| `end_date` | date | Event end date |
| `is_active` | boolean | Event active status (default: true) |
| `thumbnail_path` | string(255) | Path to event thumbnail image |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

**Relationships:**
- `hasMany(EventDate::class)` - Event dates
- `hasMany(EventTicketType::class)` - Ticket types
- `hasMany(Ticket::class)` - Tickets for this event

**Indexes:**
- Primary key on `id`
- Index on `is_active`
- Index on `start_date`
- Index on `end_date`
- Composite index on `[is_active, start_date]`

**Methods:**
- `generateEventDates()` - Creates EventDate records for each day in range
- `getDateRange()` - Returns formatted date range string
- `isActive()` - Checks if event is active
- `getThumbnailUrlAttribute()` - Returns full URL for thumbnail
- `getThumbnailUrlOrPlaceholder()` - Returns thumbnail URL or placeholder

#### `event_dates` Table
Individual dates within multi-day events.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `event_id` | bigint | Foreign key to events |
| `date` | date | Specific event date |
| `day_number` | integer | Day number (1, 2, 3, etc.) |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

**Relationships:**
- `belongsTo(Event::class)` - Parent event
- `hasMany(Ticket::class)` - Tickets for this date

**Indexes:**
- Primary key on `id`
- Foreign key on `event_id`
- Index on `date`
- Composite index on `[event_id, date]`

**Constraints:**
- Foreign key constraint: `event_id` references `events(id)` ON DELETE CASCADE

#### `event_ticket_types` Table
Ticket type definitions for events.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `event_id` | bigint | Foreign key to events |
| `name` | string(255) | Ticket type name (e.g., "VIP", "FULL", "D4") |
| `description` | text | Ticket type description |
| `is_vip` | boolean | VIP status (default: false) |
| `is_adult_only` | boolean | Adult-only restriction (default: false) |
| `allowed_dates` | json | Array of event_date_ids (null = full pass, [] = day pass) |
| `armband_color` | string(255) | Armband color identifier |
| `price` | decimal(10,2) | Ticket price |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

**Relationships:**
- `belongsTo(Event::class)` - Parent event
- `hasMany(Ticket::class)` - Tickets of this type

**Indexes:**
- Primary key on `id`
- Foreign key on `event_id`
- Index on `name`
- Index on `is_vip`
- Index on `is_adult_only`
- Composite index on `[event_id, is_vip]`

**Constraints:**
- Foreign key constraint: `event_id` references `events(id)` ON DELETE CASCADE

**Methods:**
- `isValidForDate(int $eventDateId)` - Checks if ticket type is valid for specific date
- `getValidDates()` - Returns valid EventDate records
- `isFullPass()` - Checks if this is a full pass (null allowed_dates)
- `getArmbandColor()` - Returns armband color
- `isVip()` - Checks if VIP ticket
- `isAdultOnly()` - Checks if adult-only ticket

**Special Values:**
- `allowed_dates = null` - Full pass (valid for all dates)
- `allowed_dates = []` - Day pass (requires date selection)
- `allowed_dates = [1, 2, 3]` - Specific dates only

#### `tickets` Table
Individual ticket records with QR codes and holder information.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | bigint | Foreign key to users (nullable) |
| `event_id` | bigint | Foreign key to events (nullable) |
| `event_date_id` | bigint | Foreign key to event_dates (nullable, for day passes) |
| `event_ticket_type_id` | bigint | Foreign key to event_ticket_types (nullable) |
| `qr_code_text` | string(100) | Unique QR code identifier |
| `holder_name` | string(255) | Ticket holder's name |
| `email` | string(255) | Ticket holder's email |
| `send_email_to_holder` | boolean | Send verification email to holder (default: false) |
| `dob` | date | Date of birth |
| `is_minor` | boolean | Minor status (auto-calculated, default: false) |
| `payment_ref` | string(255) | Payment reference (unique, nullable) |
| `is_verified` | boolean | Payment verification status (default: false) |
| `is_vip` | boolean | VIP status (default: false) |
| `used_at` | timestamp | Check-in timestamp (nullable) |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

**Relationships:**
- `belongsTo(User::class)` - Ticket purchaser (nullable)
- `belongsTo(Event::class)` - Associated event
- `belongsTo(EventDate::class)` - Specific event date (for day passes)
- `belongsTo(EventTicketType::class)` - Ticket type

**Indexes:**
- Primary key on `id`
- Unique index on `qr_code_text`
- Unique index on `payment_ref`
- Index on `user_id`
- Index on `event_id`
- Index on `event_date_id`
- Index on `event_ticket_type_id`
- Index on `is_verified`
- Index on `used_at`
- Index on `is_vip`
- Index on `is_minor`
- Index on `email`
- Composite index on `[user_id, is_verified]`
- Composite index on `[event_id, is_verified]`
- Composite index on `[event_id, used_at]`

**Constraints:**
- Foreign key constraint: `user_id` references `users(id)` ON DELETE CASCADE
- Foreign key constraint: `event_date_id` references `event_dates(id)` ON DELETE CASCADE

**Methods:**
- `isUsed()` - Checks if ticket has been used
- `markAsUsed()` - Marks ticket as used (sets used_at)
- `getArmbandInfo()` - Returns armband color from ticket type
- `age()` - Calculates age from DOB
- `isAdult()` - Checks if holder is 18 or older
- `isMinor()` - Checks if holder is under 18

**Auto-Behavior:**
- `is_minor` is automatically set when `dob` is saved (via model boot method)

### Spatie Permission Tables

#### `roles` Table
User roles (admin, user).

#### `permissions` Table
System permissions (if used).

#### `model_has_roles` Table
User-role assignments.

#### `model_has_permissions` Table
User-permission assignments (if used).

#### `role_has_permissions` Table
Role-permission assignments (if used).

### Laravel System Tables

#### `jobs` Table
Queued jobs for asynchronous processing.

#### `failed_jobs` Table
Failed queue jobs with error information.

#### `cache` Table
Cache storage (if using database cache driver).

#### `telescope_entries` Table
Laravel Telescope debugging data.

---

## Authentication & Authorization

### Authentication System

Connectra uses **Laravel Fortify** for authentication, providing a complete authentication system without requiring scaffolding.

#### Authentication Features

1. **User Registration**
   - Email and password registration
   - Automatic role assignment (`user` role)
   - Email verification support (optional)
   - Password validation rules

2. **User Login**
   - Email/password authentication
   - Remember me functionality
   - Rate limiting protection
   - Session management

3. **Password Reset**
   - Forgot password flow
   - Email-based password reset links
   - Secure token generation

4. **Email Verification**
   - Optional email verification
   - Verification email sending
   - Protected routes requiring verification

#### Authentication Routes

- `GET /register` - Registration form
- `POST /register` - Process registration
- `GET /login` - Login form
- `POST /login` - Process login
- `POST /logout` - User logout
- `GET /forgot-password` - Password reset request form
- `POST /forgot-password` - Send password reset email
- `GET /reset-password/{token}` - Password reset form
- `POST /reset-password` - Process password reset

#### User Registration Flow

1. User fills registration form (name, email, password)
2. `CreateNewUser` action validates input
3. User record created with hashed password
4. User assigned `user` role automatically
5. User redirected to dashboard
6. Email verification sent (if enabled)

#### Password Requirements

- Minimum length: 8 characters (configurable)
- Must contain letters and numbers (configurable)
- Confirmation required

### Authorization System

Connectra uses **Spatie Laravel Permission** for role-based access control (RBAC).

#### Roles

1. **Admin Role** (`admin`)
   - Full system access
   - Event management
   - Ticket verification
   - Gate validation
   - User management

2. **User Role** (`user`)
   - Ticket creation
   - View own tickets
   - Profile management
   - Event browsing

#### Role Assignment

- New users automatically assigned `user` role
- Admin role must be assigned manually via database or seeder
- Roles stored in `roles` table
- User-role relationships in `model_has_roles` table

#### Permission System

- Permissions can be assigned to roles
- Permissions can be assigned directly to users
- Currently uses role-based checks (`role:admin` middleware)
- Can be extended with granular permissions

#### Route Protection

Routes are protected using middleware:

```php
// Authentication required
->middleware(['auth'])

// Admin role required
->middleware(['auth', 'role:admin'])

// Email verification required
->middleware(['auth', 'verified'])
```

#### Authorization Checks

**In Controllers:**
```php
if (!auth()->user()->hasRole('admin')) {
    abort(403);
}
```

**In Blade Templates:**
```blade
@role('admin')
    <!-- Admin content -->
@endrole
```

**In Routes:**
```php
Route::middleware('role:admin')->group(function () {
    // Admin routes
});
```

### User Profile Management

#### Profile Settings

- **Profile Information**: Name and email updates
- **Password Management**: Secure password changes
- **Appearance Settings**: Theme preferences
- **Account Deletion**: User account deletion

#### Profile Routes

- `GET /settings/profile` - Profile edit form
- `PUT /settings/profile` - Update profile
- `GET /settings/password` - Password change form
- `PUT /settings/password` - Update password
- `GET /settings/appearance` - Appearance settings

#### Profile Update Flow

1. User accesses profile settings
2. User updates name/email
3. If email changed, verification reset
4. Profile updated in database
5. Success message displayed

---

## Ticket Management System

### Ticket Generation Process

The ticket generation system creates secure, unique tickets with QR codes for event entry.

#### Ticket Generation Flow

1. **User Selection**
   - User selects event from dropdown
   - User selects ticket type (Full Pass, Day Pass, VIP)
   - For day passes, user selects specific event date

2. **Holder Information Collection**
   - Holder name (required)
   - Date of birth (required, for age verification)
   - Email address (required)
   - Payment reference (auto-generated)
   - Email preference (send to holder or purchaser)

3. **Secure ID Generation**
   - Ticket type name extracted (e.g., "VIP", "FULL", "D4")
   - Date of birth parsed (YYYY-MM-DD)
   - Holder initials extracted (first letter of each word)
   - Secure random blocks generated
   - Final ID format: `TYPE-R3DDR2MMR2YYR2C-I`
     - `TYPE`: Ticket type name (uppercase)
     - `R3DD`: 3 random digits + 2-digit day
     - `R2MM`: 2 random digits + 2-digit month
     - `R2YY`: 2 random digits + 2-digit year
     - `R2C`: 2 random check digits
     - `I`: Holder initials

4. **Payment Reference Generation**
   - Format: `P-{INITIALS}-{RANDOM}`
   - Example: `P-KL-8592`
   - Unique per ticket

5. **QR Code Generation**
   - QR code contains URL: `/gate?ticket={TICKET_ID}`
   - SVG format for display
   - Generated using `bacon/bacon-qr-code` library
   - 400x400 pixel size

6. **Ticket Creation**
   - Ticket saved with `is_verified = false`
   - Age automatically calculated and `is_minor` set
   - VIP status copied from ticket type
   - Event date ID set (for day passes)

#### Ticket ID Format Details

**Example Ticket ID**: `VIP-15415112501391-JMD`

- `VIP`: Ticket type name
- `154`: 3 random digits
- `15`: Day (15th)
- `11`: 2 random digits
- `25`: Month (December)
- `01`: 2 random digits
- `39`: Year (2039, last 2 digits)
- `1`: 2 random check digits
- `JMD`: Initials (John Michael Doe)

#### Ticket Validation Rules

1. **Required Fields**
   - Event selection
   - Ticket type selection
   - Holder name
   - Date of birth
   - Email address

2. **Day Pass Validation**
   - Event date must be selected
   - Event date must belong to selected event
   - Event date must be in ticket type's allowed dates

3. **Age Validation**
   - Age calculated from DOB
   - `is_minor` automatically set (< 18 years)
   - Adult-only tickets require age >= 18

4. **Email Validation**
   - Valid email format required
   - Can be different from user's email

### Ticket Types

#### Full Pass Tickets
- `allowed_dates = null` in database
- Valid for all days of the event
- No date selection required
- Example: "FULL" ticket type

#### Day Pass Tickets
- `allowed_dates = []` or specific array in database
- Valid for specific selected day(s)
- Date selection required during ticket creation
- Example: "D4", "D5", "D6" ticket types

#### VIP Tickets
- `is_vip = true` in ticket type
- Can be full pass or day pass
- Custom armband colors
- Special handling at gate

#### Adult-Only Tickets
- `is_adult_only = true` in ticket type
- Requires ticket holder to be 18+
- Age checked at gate validation

### Ticket Status

#### Verification Status

1. **Unverified** (`is_verified = false`)
   - Default state when ticket created
   - Payment not yet verified
   - Cannot be used for gate entry
   - Admin must verify payment manually

2. **Verified** (`is_verified = true`)
   - Payment verified by admin
   - Email notification sent to holder
   - Valid for gate entry
   - Can be checked in

#### Usage Status

1. **Unused** (`used_at = null`)
   - Ticket not yet checked in
   - Can be used for entry

2. **Used** (`used_at = timestamp`)
   - Ticket already checked in
   - One-time use only
   - Cannot be used again

### Ticket Viewing

#### My Tickets Page

- Lists all tickets for authenticated user
- Shows ticket details:
  - Event name
  - Ticket type
  - Holder name
  - QR code
  - Verification status
  - Payment reference
- Filtering and sorting options

#### Ticket Details

Each ticket displays:
- QR code (SVG)
- Ticket ID (QR code text)
- Holder information
- Event information
- Ticket type
- Payment reference
- Verification status
- Usage status

### Ticket Search

#### By Payment Reference
- Admin can search tickets by payment reference
- Partial matching supported
- Used in verification manager

#### By QR Code
- Gate staff can search by QR code text
- Exact matching required
- Used in scanner validator

---

## Event Management

### Event Creation

#### Event Information

1. **Basic Details**
   - Event name (required, alphanumeric + spaces/hyphens)
   - Location (required, alphanumeric + spaces/hyphens)
   - Start date (required, date format)
   - End date (required, must be >= start date)
   - Active status (boolean checkbox)

2. **Thumbnail Upload**
   - Image file (JPG, JPEG, PNG, WebP)
   - Maximum size: 5MB
   - Stored in `storage/app/public/events/`
   - Unique filename generated: `event_{uniqid}.{extension}`
   - Old thumbnail deleted when updating event

#### Event Creation Flow

1. Admin fills event form
2. File upload validated (if provided)
3. Event record created
4. Event dates automatically generated
5. Thumbnail saved (if provided)
6. Success message displayed

#### Event Date Generation

When an event is created or updated, the system automatically generates `EventDate` records:

- One record per day from `start_date` to `end_date`
- `day_number` assigned sequentially (1, 2, 3, etc.)
- Old event dates deleted and regenerated on update
- Used for day pass ticket validation

**Example:**
- Start: 2025-12-15
- End: 2025-12-17
- Generates:
  - Day 1: 2025-12-15
  - Day 2: 2025-12-16
  - Day 3: 2025-12-17

### Event Updates

- Same validation as creation
- Existing thumbnail preserved if no new upload
- Event dates regenerated (old ones deleted)
- All related tickets remain valid

### Event Status

#### Active Events (`is_active = true`)
- Visible in public event listing
- Available for ticket generation
- Shown in event dropdowns

#### Inactive Events (`is_active = false`)
- Hidden from public listing
- Still accessible via direct URL
- Can be reactivated

### Event Thumbnails

#### Storage
- Path: `storage/app/public/events/`
- Filename format: `event_{uniqid}.{extension}`
- Database stores relative path: `events/{filename}`

#### URL Generation
- Primary: Route-based URL (`/files/events/{filename}`)
- Fallback: Asset URL (`/storage/events/{filename}`)
- Handles servers that block symlink access

#### Thumbnail Display
- Event listing: Thumbnail shown
- Event detail: Large thumbnail
- Placeholder: Default image if no thumbnail

### Ticket Type Management

#### Creating Ticket Types

1. **Basic Information**
   - Name (e.g., "VIP", "FULL", "D4")
   - Description (optional)
   - Price (optional, decimal)

2. **Ticket Type Options**
   - VIP status (boolean)
   - Adult-only restriction (boolean)
   - Armband color (string, optional)

3. **Date Restrictions**
   - Full Pass: Leave `allowed_dates` empty/null
   - Day Pass: Select specific event dates
   - Can select multiple dates for multi-day passes

#### Ticket Type Configuration

**Full Pass:**
- `allowed_dates = null`
- Valid for all event dates
- No date selection during ticket creation

**Day Pass:**
- `allowed_dates = [1, 2, 3]` (array of event_date_ids)
- Valid only for selected dates
- Date selection required during ticket creation

**VIP:**
- `is_vip = true`
- Can be full pass or day pass
- Custom armband color

**Adult-Only:**
- `is_adult_only = true`
- Requires ticket holder to be 18+
- Age checked at gate

### Event Browsing

#### Public Event Listing
- Shows only active events
- Displays event thumbnails
- Shows date ranges
- Links to event detail pages

#### Event Detail Page
- Full event information
- All ticket types listed
- Ticket generation link (if authenticated)
- Event dates displayed

---

## QR Code System

### QR Code Generation

#### Generation Process

1. **Input Data**
   - Ticket ID (secure ID from `TicketIdGenerator`)
   - Gate route URL

2. **QR Code Content**
   - Format: `/gate?ticket={TICKET_ID}`
   - Full URL can be generated if needed
   - URL-encoded ticket ID

3. **QR Code Format**
   - SVG format (vector, scalable)
   - 400x400 pixel size
   - Error correction level: Default
   - Generated using `bacon/bacon-qr-code` library

#### QR Code Library

- **Package**: `bacon/bacon-qr-code`
- **Renderer**: `ImageRenderer` with `SvgImageBackEnd`
- **Style**: `RendererStyle(400)` - 400 pixel size
- **Output**: SVG string stored in component property

### QR Code Scanning

#### Scanner Implementation

- **Library**: `html5-qrcode` (JavaScript)
- **Method**: Camera-based scanning
- **Platform**: Web browser (mobile/desktop)
- **Fallback**: Manual entry available

#### Scanning Flow

1. User clicks "Scan QR Code" button
2. Camera permission requested
3. Scanner modal opens
4. Camera feed displayed
5. QR code detected automatically
6. Ticket ID extracted from URL
7. Scanner stops
8. Ticket search triggered
9. Results displayed

#### QR Code Parsing

The scanner extracts ticket ID from QR code URL:

- Pattern: `?ticket={TICKET_ID}` or `&ticket={TICKET_ID}`
- URL decoding applied
- Whitespace trimmed
- Validated before search

#### Manual Entry

- Text input field available
- Real-time sanitization
- Supports paste operations
- Search button triggers validation

### QR Code Validation

#### Validation Process

1. **Ticket Lookup**
   - Search by `qr_code_text` (exact match)
   - Eager load relationships (event, ticketType)
   - Return ticket or null

2. **Security Checks**
   - Payment verification (`is_verified = true`)
   - Usage status (`used_at = null`)
   - Event date validation (for day passes)
   - Age verification (for adult-only tickets)

3. **Validation Results**
   - Success: Ticket valid, can check in
   - Error: Payment unverified, already used, invalid date, age restriction
   - Warning: Additional information displayed

#### Validation Status Messages

**Success:**
- "✅ TICKET VALID - READY FOR ENTRY"
- Shows ticket details
- Check-in button available

**Errors:**
- "❌ DENIED: PAYMENT UNVERIFIED"
- "❌ DENIED: TICKET ALREADY USED"
- "❌ DENIED: INVALID EVENT DATE"
- "❌ DENIED: AGE RESTRICTION"

**Warnings:**
- "⚠️ MINOR DETECTED"
- "⚠️ VIP TICKET"

### Check-In Process

#### Check-In Flow

1. Ticket validated successfully
2. User clicks "Check In" button
3. `used_at` timestamp set
4. Ticket marked as used
5. Success message displayed
6. Ticket cannot be used again

#### One-Time Use

- Each ticket can only be checked in once
- `used_at` timestamp records check-in time
- Prevents duplicate entry
- Cannot be reversed (security feature)

### Gate Interface

#### Features

- **Event Filtering**: Select event to filter tickets
- **Auto-Selection**: Event auto-selected from ticket
- **QR Scanner**: Camera-based scanning
- **Manual Entry**: Text input for ticket ID
- **Real-Time Validation**: Instant feedback
- **Visual Status**: Color-coded messages
- **Armband Info**: Display armband color for ticket type

#### Status Display

- **Green**: Valid ticket, ready for entry
- **Red**: Invalid ticket, denied entry
- **Yellow**: Warning information

---

## Payment Verification

### Verification Workflow

#### Manual Verification Process

1. **Ticket Creation**
   - Ticket created with `is_verified = false`
   - Payment reference generated
   - User makes external payment

2. **Payment Received**
   - Admin receives payment confirmation
   - Admin searches ticket by payment reference
   - Admin verifies payment details

3. **Verification Action**
   - Admin toggles verification status
   - `is_verified` set to `true`
   - Email notification queued

4. **Email Notification**
   - Email sent to ticket holder (if `send_email_to_holder = true`)
   - Email sent to purchaser (if `send_email_to_holder = false`)
   - Notification includes ticket details

### Verification Manager Interface

#### Search Functionality

- **Payment Reference Search**
  - Partial matching supported
  - Case-insensitive
  - Input sanitization applied
  - Returns first matching ticket

#### Ticket Display

- Shows ticket details:
  - Holder name
  - Email
  - Payment reference
  - Verification status
  - Event information
  - Ticket type

#### Verification Actions

- **Toggle Verification**: Click to verify/unverify
- **Bulk Operations**: Verify multiple tickets
- **Status Indicators**: Visual verification status

### Verification Status

#### Unverified Tickets
- `is_verified = false`
- Cannot be used for gate entry
- Payment pending
- Red indicator

#### Verified Tickets
- `is_verified = true`
- Valid for gate entry
- Payment confirmed
- Green indicator
- Email notification sent

### Email Notifications

#### Notification Trigger

- Sent when ticket verified (`is_verified` changes from `false` to `true`)
- Queued for asynchronous processing
- Uses database queue driver

#### Email Content

- Subject: "Your Ticket Has Been Verified - Connectra"
- Greeting: Personalized with holder name
- Ticket details:
  - Ticket type
  - Event name
  - Payment reference
  - QR code text
- Action button: Link to "My Tickets" page
- Footer: Thank you message

#### Email Recipients

- **To Holder**: If `send_email_to_holder = true`
- **To Purchaser**: If `send_email_to_holder = false`
- Email address from ticket record

### Payment Reference Format

- **Format**: `P-{INITIALS}-{RANDOM}`
- **Example**: `P-KL-8592`
- **Uniqueness**: Unique constraint in database
- **Search**: Partial matching supported

### Verification Security

#### Access Control
- Only admin users can verify tickets
- Route protected with `role:admin` middleware
- Verification actions logged

#### Audit Trail
- Verification status changes logged
- Timestamps recorded
- User ID tracked (who verified)

---

## File Storage

### Storage Configuration

#### Storage Driver
- **Default**: Local filesystem
- **Public Storage**: `storage/app/public/`
- **Private Storage**: `storage/app/private/`
- **Symlink**: `public/storage` → `storage/app/public`

### Event Thumbnails

#### Storage Location
- **Path**: `storage/app/public/events/`
- **Filename Format**: `event_{uniqid}.{extension}`
- **Database Storage**: Relative path `events/{filename}`

#### File Upload Process

1. **File Validation**
   - Allowed types: JPG, JPEG, PNG, WebP
   - Maximum size: 5MB
   - MIME type validation

2. **File Processing**
   - Unique filename generation
   - Directory creation (if needed)
   - File moved to storage
   - Path saved to database

3. **Old File Cleanup**
   - Old thumbnail deleted when updating event
   - Direct file system operations
   - Error handling for missing files

#### File Serving

**Problem**: Some servers block symlink access to `public/storage`

**Solution**: Custom route `/files/{path}` serves files through Laravel

**Implementation**:
- `StorageController::serve()` method
- Security checks:
  - Directory traversal prevention
  - Path validation
  - File existence checks
- Proper MIME type headers
- Cache headers (1 year)

**URL Generation**:
- Primary: `route('storage.serve', ['path' => $thumbnail_path])`
- Fallback: `asset('storage/' . $thumbnail_path)`

### Storage Security

#### Path Validation
- Prevents directory traversal (`..` checks)
- Ensures files are within `storage/app/public`
- Real path resolution

#### File Type Validation
- MIME type checking
- Extension validation
- File size limits

#### Access Control
- Public files: Accessible via route
- Private files: Require authentication (if implemented)

---

## Email Notifications

### Email Configuration

#### Mail Driver
- **Default**: SMTP
- **Configurable**: Any Laravel-supported mail driver
- **Settings**: `config/mail.php`

#### SMTP Configuration
- Host, port, encryption
- Username, password
- From address and name
- Environment variables

### Notification System

#### Notification Class
- **Class**: `TicketVerifiedNotification`
- **Implements**: `ShouldQueue` (queued)
- **Channel**: Mail only

#### Notification Flow

1. **Trigger**: Ticket verification status changes to `true`
2. **Queue**: Notification queued for processing
3. **Processing**: Queue worker processes job
4. **Email**: Mail sent via configured driver
5. **Logging**: Success/failure logged

### Email Content

#### Template
- Uses Laravel MailMessage
- Markdown support
- Action buttons
- Customizable

#### Content Structure
1. **Subject**: "Your Ticket Has Been Verified - Connectra"
2. **Greeting**: Personalized with holder name
3. **Body**: Ticket details
4. **Action**: Link to "My Tickets" page
5. **Footer**: Thank you message

### Email Recipients

#### Recipient Selection
- **Holder Email**: If `send_email_to_holder = true`
- **Purchaser Email**: If `send_email_to_holder = false`
- Email from ticket record

#### Email Addresses
- Holder email: From ticket `email` field
- Purchaser email: From user account

### Email Logging

#### Logging Points
- Notification queued
- Email generation
- Email sent
- Errors logged

#### Log Information
- Ticket ID
- Email address
- Mail configuration
- Success/failure status

### Email Testing

#### Test Route
- **Route**: `/mail/test?token={QUEUE_PROCESSOR_TOKEN}`
- **Method**: GET
- **Purpose**: Test email configuration
- **Features**:
  - SMTP connection test
  - Test email sending
  - Configuration display
  - Error diagnostics

---

## Queue System

### Queue Configuration

#### Queue Driver
- **Default**: Database
- **Table**: `jobs`
- **Failed Jobs**: `failed_jobs`
- **Config**: `config/queue.php`

#### Queue Connection
- Environment variable: `QUEUE_CONNECTION`
- Default: `database`
- Alternative: `sync` (immediate processing)

### Queue Jobs

#### Job Types
- **Email Notifications**: `TicketVerifiedNotification`
- **Queued**: All notifications implement `ShouldQueue`

#### Job Processing

**Development**:
```bash
php artisan queue:work
```

**Production**:
- Cron-based processing
- Queue processor route
- Continuous processing

### Queue Processor Route

#### Endpoint
- **Route**: `/queue/process?token={QUEUE_PROCESSOR_TOKEN}`
- **Method**: GET
- **Security**: Token-protected
- **Purpose**: Process queued jobs via cron

#### Implementation
- Token validation
- Job processing
- Error handling
- Response logging

#### Cron Setup
```bash
* * * * * curl "https://yourdomain.com/queue/process?token=YOUR_TOKEN"
```

### Queue Diagnostics

#### Diagnostic Route
- **Route**: `/queue/diagnose?token={QUEUE_PROCESSOR_TOKEN}`
- **Method**: GET
- **Purpose**: Debug queue issues

#### Diagnostic Information
- Queue connection status
- Pending jobs count
- Failed jobs list
- Recent jobs
- Configuration details

### Failed Jobs

#### Failure Handling
- Jobs retry on failure
- Failed jobs stored in `failed_jobs` table
- Error information logged
- Manual retry available

#### Failed Job Information
- Job ID
- Queue name
- Failure timestamp
- Exception message
- Stack trace

---

## Security Features

### Input Sanitization

#### Sanitization Strategy
- **Location**: All user inputs
- **Method**: Regex-based sanitization
- **Pattern**: `/[^a-zA-Z0-9\-]/` (alphanumeric + hyphens only)
- **Applied To**: QR codes, payment references, ticket IDs

#### Implementation
- Automatic sanitization in Livewire components
- `updated{Property}()` hooks
- Before database queries
- Logging of sanitization actions

### SQL Injection Prevention

#### Protection Methods
- **Eloquent ORM**: Parameterized queries
- **Query Builder**: Bound parameters
- **No Raw Queries**: Avoided where possible

#### Best Practices
- Use Eloquent relationships
- Parameter binding
- Input validation
- Type casting

### XSS Prevention

#### Protection
- **Blade Escaping**: Automatic in Blade templates
- **Livewire**: Server-side rendering
- **No Raw HTML**: User input never rendered raw

### CSRF Protection

#### Laravel CSRF
- **Token**: Included in all forms
- **Middleware**: Automatic validation
- **Exceptions**: API routes (if needed)

### Authentication Security

#### Password Security
- **Hashing**: Bcrypt (default)
- **Validation**: Strong password rules
- **Storage**: Never stored in plain text

#### Session Security
- **Session Driver**: Database/file
- **Session Timeout**: Configurable
- **Remember Me**: Secure tokens

### Authorization Security

#### Role-Based Access
- **Middleware**: `role:admin` checks
- **Route Protection**: Admin routes protected
- **Component Checks**: Server-side validation

#### Access Control
- **Public Routes**: Event browsing, policies
- **Authenticated Routes**: Ticket generation, my tickets
- **Admin Routes**: Event management, verification, gate

### File Upload Security

#### Validation
- **File Type**: Allowed extensions only
- **File Size**: Maximum limits
- **MIME Type**: Content validation
- **Path Security**: Directory traversal prevention

#### Storage Security
- **Public Files**: Event thumbnails only
- **Private Files**: Protected (if implemented)
- **Path Validation**: Real path checks

### Rate Limiting

#### Fortify Rate Limiting
- **Login**: Rate limited
- **Registration**: Rate limited
- **Password Reset**: Rate limited

#### Custom Rate Limiting
- Can be added to routes
- Per-IP limiting
- Per-user limiting

### Logging

#### Security Logging
- **Authentication**: Login/logout events
- **Authorization**: Access denied events
- **Verification**: Payment verification actions
- **Gate Operations**: Check-in events

#### Log Locations
- `storage/logs/laravel.log`
- `storage/logs/connectra.log`
- `storage/logs/synapse-events.log`

### Secure Ticket IDs

#### ID Generation
- **Cryptographically Secure**: `random_int()`
- **Unique**: Database unique constraint
- **Format**: Structured but unpredictable
- **Length**: 100 characters maximum

#### ID Validation
- **Exact Match**: Required for lookup
- **Sanitization**: Before database queries
- **No Guessing**: Random components prevent prediction

---

## API Routes

### Public Routes

#### Home
- **Route**: `GET /`
- **View**: Welcome page
- **Access**: Public

#### Events
- **Route**: `GET /events`
- **Component**: `events`
- **Access**: Public
- **Purpose**: Event listing

#### Event Detail
- **Route**: `GET /events/{event}`
- **Component**: `event-detail`
- **Access**: Public
- **Purpose**: Event details

#### Policies
- **Route**: `GET /age-verification-policy`
- **Route**: `GET /privacy-policy`
- **Route**: `GET /terms-conditions`
- **Access**: Public
- **Purpose**: Legal pages

#### How to Pay
- **Route**: `GET /how-to-pay`
- **Component**: `how-to-pay`
- **Access**: Public
- **Purpose**: Payment instructions

### Authenticated Routes

#### Dashboard
- **Route**: `GET /dashboard`
- **View**: Dashboard
- **Middleware**: `auth`, `verified`
- **Purpose**: User dashboard

#### Ticket Generation
- **Route**: `GET /tickets/new`
- **Component**: `ticket-generator`
- **Middleware**: `auth`
- **Purpose**: Create tickets

#### My Tickets
- **Route**: `GET /my-tickets`
- **Component**: `my-tickets`
- **Middleware**: `auth`
- **Purpose**: View user tickets

#### Settings
- **Route**: `GET /settings/profile`
- **Route**: `PUT /settings/profile`
- **Route**: `GET /settings/password`
- **Route**: `GET /settings/appearance`
- **Middleware**: `auth`
- **Purpose**: User settings

### Admin Routes

#### Gate Validation
- **Route**: `GET /gate`
- **Component**: `scanner-validator`
- **Middleware**: `auth`, `role:admin`
- **Purpose**: QR code scanning and validation

#### Verification Manager
- **Route**: `GET /admin/verify`
- **Component**: `verification-manager`
- **Middleware**: `auth`, `role:admin`
- **Purpose**: Payment verification

#### Event Management
- **Route**: `GET /admin/events`
- **Route**: `POST /admin/events`
- **Component**: `admin-events`
- **Middleware**: `auth`, `role:admin`
- **Purpose**: Create/edit events

#### Ticket Type Management
- **Route**: `GET /admin/events/{event}/ticket-types`
- **Component**: `admin-event-ticket-types`
- **Middleware**: `auth`, `role:admin`
- **Purpose**: Manage ticket types

### Utility Routes

#### File Serving
- **Route**: `GET /files/{path}`
- **Controller**: `StorageController::serve`
- **Access**: Public
- **Purpose**: Serve storage files

#### Queue Processing
- **Route**: `GET /queue/process?token={token}`
- **Controller**: `QueueProcessorController::process`
- **Access**: Token-protected
- **Purpose**: Process queued jobs

#### Queue Diagnostics
- **Route**: `GET /queue/diagnose?token={token}`
- **Access**: Token-protected
- **Purpose**: Debug queue issues

#### Mail Testing
- **Route**: `GET /mail/test?token={token}`
- **Access**: Token-protected
- **Purpose**: Test email configuration

---

## Installation & Setup

### Prerequisites

#### System Requirements
- **PHP**: 8.2 or higher
- **Composer**: Latest version
- **Node.js**: 18+ and npm
- **Database**: SQLite (default) or MySQL/PostgreSQL
- **Web Server**: Apache/Nginx or PHP built-in server

#### PHP Extensions
- `pdo`
- `pdo_sqlite` (or `pdo_mysql`/`pdo_pgsql`)
- `mbstring`
- `xml`
- `ctype`
- `json`
- `openssl`
- `tokenizer`
- `fileinfo` (optional, for MIME type detection)

### Installation Steps

#### 1. Clone Repository
```bash
git clone <repository-url>
cd studentbash
```

#### 2. Install PHP Dependencies
```bash
composer install
```

#### 3. Install Node Dependencies
```bash
npm install
```

#### 4. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

#### 5. Database Setup
```bash
# Create SQLite database (if using SQLite)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed roles
php artisan db:seed --class=RoleSeeder
```

#### 6. Storage Setup
```bash
# Create storage link
php artisan storage:link

# Or use custom script if symlink fails
php public/create-storage-link.php
```

#### 7. Build Assets
```bash
npm run build
```

### Quick Setup Script

The `composer.json` includes a setup script:

```bash
composer run setup
```

This runs:
1. `composer install`
2. Creates `.env` if missing
3. Generates application key
4. Runs migrations
5. Installs npm dependencies
6. Builds assets

### Development Server

#### Start Development Environment
```bash
composer run dev
```

This starts:
- Laravel development server
- Queue worker
- Vite dev server

#### Individual Commands
```bash
# Laravel server
php artisan serve

# Queue worker
php artisan queue:listen

# Vite dev server
npm run dev
```

### First Admin User

#### Create Admin User
```bash
php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
]);

$user->assignRole('admin');
```

---

## Configuration

### Environment Variables

#### Application
- `APP_NAME`: Application name
- `APP_ENV`: Environment (local, production)
- `APP_KEY`: Application encryption key
- `APP_DEBUG`: Debug mode (true/false)
- `APP_URL`: Application URL

#### Database
- `DB_CONNECTION`: Database driver (sqlite, mysql, pgsql)
- `DB_DATABASE`: Database name/path
- `DB_USERNAME`: Database username (if needed)
- `DB_PASSWORD`: Database password (if needed)

#### Mail
- `MAIL_MAILER`: Mail driver (smtp, sendmail)
- `MAIL_HOST`: SMTP host
- `MAIL_PORT`: SMTP port
- `MAIL_USERNAME`: SMTP username
- `MAIL_PASSWORD`: SMTP password
- `MAIL_ENCRYPTION`: Encryption (tls, ssl)
- `MAIL_FROM_ADDRESS`: From email address
- `MAIL_FROM_NAME`: From name

#### Queue
- `QUEUE_CONNECTION`: Queue driver (database, sync)
- `QUEUE_PROCESSOR_TOKEN`: Token for queue processing route

#### Session
- `SESSION_DRIVER`: Session driver (database, file)
- `SESSION_LIFETIME`: Session lifetime in minutes

### Configuration Files

#### `config/app.php`
- Application settings
- Timezone
- Locale

#### `config/database.php`
- Database connections
- Default connection

#### `config/mail.php`
- Mail configuration
- SMTP settings

#### `config/queue.php`
- Queue configuration
- Connection settings

#### `config/permission.php`
- Spatie Permission settings
- Cache configuration

#### `config/fortify.php`
- Fortify authentication settings
- Feature flags

### Role Configuration

#### Default Roles
- `admin`: Full system access
- `user`: Standard user access

#### Creating Roles
```php
use Spatie\Permission\Models\Role;

Role::create(['name' => 'admin']);
Role::create(['name' => 'user']);
```

#### Assigning Roles
```php
$user->assignRole('admin');
```

---

## Testing

### Test Framework

#### Pest PHP
- Modern PHP testing framework
- Laravel integration
- Feature and unit tests

### Running Tests

#### All Tests
```bash
php artisan test
```

#### Specific Test
```bash
php artisan test --filter TestName
```

#### With Coverage
```bash
php artisan test --coverage
```

### Test Structure

#### Feature Tests
- `tests/Feature/Auth/`: Authentication tests
- `tests/Feature/Livewire/`: Livewire component tests
- `tests/Feature/Settings/`: Settings tests
- `tests/Feature/DashboardTest.php`: Dashboard tests

#### Unit Tests
- `tests/Unit/`: Unit tests
- Model tests
- Utility tests

### Test Database

#### Configuration
- Uses separate test database
- RefreshDatabase trait
- Factory usage

#### Test Data
- Factories for all models
- Seeders for roles
- Test users

### Writing Tests

#### Example Test
```php
test('user can create ticket', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    
    $this->actingAs($user)
        ->post('/tickets/new', [
            // ticket data
        ])
        ->assertRedirect();
});
```

---

## Deployment

### Production Checklist

#### Pre-Deployment
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate application key
- [ ] Configure database
- [ ] Set up mail configuration
- [ ] Configure queue connection
- [ ] Set `QUEUE_PROCESSOR_TOKEN`
- [ ] Run migrations
- [ ] Seed roles
- [ ] Create storage link
- [ ] Build assets (`npm run build`)
- [ ] Optimize application (`php artisan optimize`)

#### Server Configuration
- [ ] Web server configured (Apache/Nginx)
- [ ] PHP 8.2+ installed
- [ ] Required PHP extensions installed
- [ ] Database server running
- [ ] Storage directory writable
- [ ] Queue worker running (or cron configured)

#### Security
- [ ] `.env` file secured
- [ ] Storage permissions set
- [ ] Admin user created
- [ ] HTTPS enabled
- [ ] CSRF protection enabled

### Deployment Steps

#### 1. Server Setup
```bash
# Clone repository
git clone <repository-url>
cd studentbash

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Configure environment
cp .env.example .env
# Edit .env with production settings
php artisan key:generate
```

#### 2. Database Setup
```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder
```

#### 3. Storage Setup
```bash
php artisan storage:link
# Or use custom script if symlink fails
php public/create-storage-link.php
```

#### 4. Optimization
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

#### 5. Queue Worker
```bash
# Option 1: Supervisor (recommended)
# Configure supervisor to run: php artisan queue:work

# Option 2: Cron
# Add to crontab:
# * * * * * curl "https://yourdomain.com/queue/process?token=YOUR_TOKEN"
```

### cPanel Deployment

#### Special Considerations
- Symlink access may be blocked
- Use `/files/{path}` route for file serving
- Queue processing via cron endpoint
- Storage permissions

#### cPanel Steps
1. Upload files via File Manager or FTP
2. Set permissions (755 for directories, 644 for files)
3. Configure database in cPanel
4. Set environment variables
5. Run migrations via SSH or Artisan Tinker
6. Create storage link or use custom script
7. Configure cron job for queue processing

### Maintenance

#### Regular Tasks
- Monitor queue for failed jobs
- Check logs for errors
- Backup database regularly
- Update dependencies (security patches)
- Clear cache if needed

#### Commands
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Queue management
php artisan queue:work
php artisan queue:failed
php artisan queue:retry {job-id}
```

---

## Additional Resources

### Documentation Files
- `DEPLOYMENT.md`: Deployment guide
- `QUEUE-SETUP.md`: Queue configuration
- `STORAGE-403-FIX-TUTORIAL.md`: Storage symlink fix
- `LIVEWIRE-405-FIX.md`: Livewire fixes
- `TROUBLESHOOTING.md`: Common issues
- `UPLOAD-GUIDE.md`: File upload guide
- `docs/age-verification.md`: Age verification policy
- `docs/privacyPolicy.md`: Privacy policy
- `docs/TermsConditions.md`: Terms and conditions
- `docs/INPUT-SANITIZATION.md`: Input sanitization guide

### Support
For issues, questions, or contributions, please refer to the project repository.

---

**Last Updated**: 2025-01-XX
**Version**: 1.0.0
**License**: MIT

