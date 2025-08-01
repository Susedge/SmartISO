# SmartISO System Workflow Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [User Roles & Permissions](#user-roles--permissions)
3. [Complete Workflow Process](#complete-workflow-process)
4. [System Components](#system-components)
5. [Technical Architecture](#technical-architecture)
6. [Database Schema](#database-schema)
7. [API Endpoints](#api-endpoints)

---

## System Overview

**SmartISO** is a comprehensive document management and approval workflow system built with CodeIgniter 4. It facilitates the creation, submission, approval, and processing of various organizational forms with role-based access control and digital signature capabilities.

### Key Features
- Dynamic form builder with drag-and-drop interface
- Role-based workflow management
- Digital signature integration
- PDF/Word document generation
- Analytics and reporting dashboard
- Department-based organization structure

---

## User Roles & Permissions

### 1. **Superuser**
- **Highest level access**
- Can create, edit, and delete all user accounts (including other superusers)
- Full access to all system configurations
- Can manage all forms and submissions
- Access to all analytics and reports
- System administration capabilities

### 2. **Admin**
- **Administrative access**
- Can create and manage user accounts (except superusers)
- Form configuration and management
- Department management
- Access to analytics and reports
- Cannot edit superuser accounts
- **Priority**: Can set custom priority levels for form submissions

### 3. **Requestor**
- **Form submission role**
- Can fill out and submit forms
- View their own form submissions
- Sign approved forms digitally
- Limited to requestor-specific fields in forms
- **Priority**: Forms automatically assigned 'Normal' priority (cannot set custom priority)

### 4. **Approving Authority**
- **Approval workflow role**
- Review and approve/reject submitted forms
- Access to pending approval queue
- Can add approval comments and signatures
- View submission history for approved forms

### 5. **Service Staff**
- **Service processing role**
- Process approved forms
- Fill service-staff specific fields
- Provide service completion signatures
- Access to service queue
- **Priority**: Can set custom priority levels for form submissions

---

## Complete Workflow Process

### Phase 1: Form Creation & Configuration
```
Admin/Superuser → Form Builder → Dynamic Panel Creation → Field Configuration
```

1. **Form Setup**
   - Admin creates new form using form builder
   - Configures form metadata (name, description, panel associations)
   - Sets up approval workflow requirements

2. **Panel Configuration**
   - Create dynamic panels with drag-and-drop interface
   - Define field types (input, dropdown, textarea, datepicker, yes/no)
   - Set field roles (requestor-only, service-staff-only, both, readonly)
   - Configure field validation and requirements

3. **Signatory Setup**
   - Define required signatures for form completion
   - Assign approving authorities to forms
   - Set signature order and requirements

### Phase 2: Form Submission Workflow
```
Requestor → Fill Form → Submit → Pending Approval → Approval/Rejection
```

1. **Form Access**
   - Requestor logs in and accesses available forms
   - System displays forms based on user permissions
   - Form loads with requestor-specific fields

2. **Form Completion**
   - Requestor fills out visible fields
   - System validates required fields
   - Form data is temporarily stored
   - **Priority Assignment**: Only Service Staff and Admin can set custom priority levels
     - Requestors automatically get 'Normal' priority
     - Available priorities: Low (5 days SLA), Normal (3 days SLA), High (2 days SLA), Urgent (1 day SLA), Critical (same day SLA)
   - **Reference File Upload**: Optional file attachment for additional documentation

3. **Submission Process**
   - Requestor submits completed form
   - System creates submission record with status "submitted"
   - Form data is stored in submission_data table
   - Notification may be sent to approving authorities

### Phase 3: Approval Workflow
```
Approving Authority → Review Form → Approve/Reject → Assign Service Staff
```

1. **Review Queue**
   - Approving authority accesses pending approval queue
   - Reviews submitted form details and data
   - Can view requestor information and submission history

2. **Approval Decision**
   - **If Approved:**
     - Status changes to "approved"
     - Form moves to service queue
     - Service staff is assigned (if configured)
     - Approval timestamp and signature recorded
   
   - **If Rejected:**
     - Status changes to "rejected"
     - Rejection reason is recorded
     - Requestor is notified
     - Form can be resubmitted after corrections

### Phase 4: Service Processing
```
Service Staff → Process Request → Complete Service → Digital Signature
```

1. **Service Queue**
   - Service staff accesses their assigned forms
   - Reviews approved requests requiring processing
   - Can update service-specific fields

2. **Service Completion**
   - Service staff fills out service-related fields
   - Updates form status to reflect service progress
   - Provides service completion signature
   - Status changes to "pending_requestor_signature"

### Phase 5: Final Completion
```
Requestor → Review Completed Service → Final Signature → Completed
```

1. **Final Review**
   - Requestor reviews completed service
   - Verifies service details and outcomes
   - Provides final acknowledgment signature

2. **Form Completion**
   - Status changes to "completed"
   - Final PDF/document is generated
   - Form is archived in completed submissions
   - Analytics data is updated

---

## System Components

### Core Controllers

1. **Auth Controller**
   - User authentication and session management
   - Login/logout functionality
   - Password reset capabilities

2. **Dashboard Controller**
   - Main dashboard for all user types
   - Role-specific widgets and statistics
   - Quick access to relevant functions

3. **Forms Controller**
   - Form display and submission handling
   - Workflow status management
   - Signature collection process

4. **Admin Controllers**
   - **DynamicForms**: Form builder and panel management
   - **Users**: User account management
   - **Departments**: Organizational structure
   - **Configurations**: System settings

5. **Analytics Controller**
   - Reporting and analytics dashboard
   - Data visualization with Chart.js
   - PDF/Word export capabilities

### Key Models

1. **UserModel**: User account and authentication data
2. **FormModel**: Form definitions and metadata
3. **DbpanelModel**: Dynamic form field configurations
4. **FormSubmissionModel**: Form submission tracking
5. **FormSubmissionDataModel**: Actual form field data
6. **DepartmentModel**: Organizational departments
7. **FormSignatoryModel**: Required signatures configuration

---

## Technical Architecture

### Technology Stack
- **Backend**: CodeIgniter 4.6.2 (PHP 8.1+)
- **Database**: MySQL
- **Frontend**: Bootstrap 5.2.3, Chart.js 4.4.0
- **PDF Generation**: DomPDF 3.1
- **Document Generation**: PhpOffice/PhpWord 1.4.0
- **Styling**: Custom pastel.css theme

### Security Features
- Session-based authentication
- Role-based access control (RBAC)
- CSRF protection
- Input validation and sanitization
- Secure password hashing

### File Structure
```
SmartISO/
├── app/
│   ├── Controllers/
│   │   ├── Admin/
│   │   └── Main Controllers
│   ├── Models/
│   ├── Views/
│   │   ├── admin/
│   │   ├── auth/
│   │   ├── forms/
│   │   ├── layouts/
│   │   └── analytics/
│   ├── Config/
│   └── Database/
│       └── Migrations/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── uploads/
└── vendor/
```

---

## Database Schema

### Core Tables

1. **users**
   - User accounts and authentication
   - Fields: id, username, email, password_hash, full_name, user_type, department_id

2. **forms**
   - Form definitions
   - Fields: id, code, description, panel_name, created_at, updated_at

3. **dbpanel**
   - Dynamic form field configurations
   - Fields: id, panel_name, field_name, field_label, field_type, field_role, required, width

4. **form_submissions**
   - Form submission tracking
   - Fields: id, form_id, submitted_by, status, approver_id, service_staff_id, timestamps

5. **form_submission_data**
   - Actual form field data
   - Fields: id, submission_id, field_name, field_value

6. **departments**
   - Organizational structure
   - Fields: id, code, description, active

### Status Flow
```
submitted → approved → pending_service → pending_requestor_signature → completed
                   ↘
                    rejected
```

---

## API Endpoints

### Authentication
- `POST /auth/login` - User login
- `POST /auth/logout` - User logout
- `POST /auth/register` - User registration (admin only)

### Forms
- `GET /forms` - List available forms
- `GET /forms/view/{code}` - Display form for completion
- `POST /forms/submit` - Submit completed form
- `GET /forms/my-submissions` - User's submissions
- `GET /forms/pending-approval` - Forms awaiting approval
- `GET /forms/pending-service` - Forms awaiting service

### Administration
- `GET /admin/users` - User management
- `GET /admin/dynamicforms` - Form builder
- `GET /admin/departments` - Department management
- `GET /admin/configurations` - System configurations

### Analytics
- `GET /analytics` - Analytics dashboard
- `POST /analytics/export` - Export reports (PDF/Word)

---

## Deployment & Configuration

### System Requirements
- PHP 8.1 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer for dependency management

### Installation Steps
1. Clone repository
2. Run `composer install`
3. Configure database in `.env`
4. Run migrations: `php spark migrate`
5. Set up web server to point to `public/` directory
6. Create initial superuser account

### Configuration Files
- `.env` - Environment configuration
- `app/Config/Database.php` - Database settings
- `app/Config/Routes.php` - URL routing
- `app/Config/Filters.php` - Authentication filters

---

## Maintenance & Support

### Regular Tasks
- Database backups
- Log file rotation
- Security updates
- Performance monitoring

### Troubleshooting
- Check log files in `writable/logs/`
- Verify database connections
- Ensure proper file permissions
- Monitor server resources

---

*Last Updated: August 1, 2025*
*SmartISO Version: 1.0*
