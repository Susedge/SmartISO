# SmartISO - Intelligent Service Request & Workflow Management System

## About SmartISO

SmartISO is a comprehensive service request management and approval workflow system built with CodeIgniter 4. It facilitates the complete lifecycle of organizational service requests from creation to completion, featuring automated scheduling, real-time notifications, and comprehensive feedback collection.

### Key Features

#### 🔄 **Complete Workflow Management**
- **Dynamic Form Builder** with drag-and-drop interface
- **Role-based Workflow Process** (Admin → Requestor → Approver → Service Staff)
- **Automated Service Scheduling** with conflict detection
- **Real-time Notifications** for all workflow stages
- **Digital Signature Integration** for document authentication

#### 📊 **Advanced Management Tools**
- **Office-based Organization** (replaces departments)
- **Service Scheduling Module** with calendar integration
- **Comprehensive Feedback System** with analytics
- **PDF/Word Template Downloads** (clean, fillable forms)
- **Analytics & Reporting Dashboard** with detailed metrics

#### 🔔 **Smart Notification System**
- **Automatic notifications** for new submissions, approvals, scheduling, and completion
- **Real-time status updates** throughout the entire process
- **Customizable notification preferences** by user role

#### 📝 **Enhanced Form Management**
- **Template downloads** without placeholders for offline completion
- **System date auto-fill** for all generated forms
- **Office selection** (not predefined) during account creation
- **Import capabilities** for completed offline forms

### Complete Workflow Process

#### 1. **Admin Setup Phase**
- ✅ Admin creates forms with templates using the dynamic form builder
- ✅ Admin sets approver staff for each form type
- ✅ Admin configures offices and assigns users
- ✅ System automatically tracks all changes with timestamps

#### 2. **Request Submission Phase**
- ✅ Requestor fills up forms online OR downloads fillable templates (PDF/Word)
- ✅ Requestor can download clean templates without placeholders
- ✅ System auto-fills current date and allows office selection
- ✅ Automatic notifications sent to designated approvers

#### 3. **Approval Phase**
- ✅ Approver receives notification of new request
- ✅ Approver reviews and approves/rejects the form
- ✅ Approver assigns service staff for approved requests
- ✅ Automatic notifications sent to requestor and service staff

#### 4. **Service Scheduling Phase**
- ✅ Service staff receives notification of assignment
- ✅ System provides scheduling module with conflict detection
- ✅ Service staff schedules the service with specific date/time
- ✅ Automatic notifications sent to requestor about schedule

#### 5. **Service Completion Phase**
- ✅ Service staff processes and completes the request
- ✅ System tracks completion with timestamps
- ✅ Automatic notifications sent requesting feedback
- ✅ Comprehensive feedback collection with analytics

### User Roles & Permissions
- **Admin**: Full system management, form creation, user management, office configuration
- **Requestor**: Form submission, status tracking, feedback provision, template downloads
- **Approving Authority**: Request review, approval/rejection, service staff assignment
- **Service Staff**: Request processing, scheduling, service completion

## 📋 Complete System Documentation

For detailed workflow processes, technical architecture, and API documentation, see:
**[SYSTEM_WORKFLOW.md](SYSTEM_WORKFLOW.md)**

## Quick Start

### System Requirements

PHP version 8.1 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) for MySQL database
- [libcurl](http://php.net/manual/en/curl.requirements.php) for HTTP requests
- json (enabled by default)

### Installation

1. **Clone the repository**
   ```bash
   git clone [repository-url] SmartISO
   cd SmartISO
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp env .env
   # Edit .env file with your database settings
   ```

4. **Set up database**
   ```bash
   php spark migrate
   ```

5. **Configure web server**
   Point your web server to the `public/` directory

6. **Create initial superuser**
   Access `/auth/register` to create the first superuser account

### Usage

1. **Login** with your credentials
2. **Configure departments** and users (Admin/Superuser)
3. **Create forms** using the drag-and-drop form builder
4. **Set up approval workflows** and assign signatories
5. **Start processing** forms through the workflow

### Support & Documentation

- **System Workflow**: See [SYSTEM_WORKFLOW.md](SYSTEM_WORKFLOW.md) for complete documentation
- **Technical Issues**: Check `writable/logs/` for error logs
- **Configuration**: Modify settings in `app/Config/` directory

---

## Framework Information

This application is built on **CodeIgniter 4.6.2**. For framework-specific documentation, visit the [CodeIgniter User Guide](https://codeigniter.com/user_guide/).

### Framework Requirements

## Running unit tests

Follow these steps to run the project's PHPUnit unit tests and save TestDox-style output to a text file with controller-level details.

1. Open PowerShell and change to the project root (where `composer.json` is):

```powershell
Set-Location -Path "C:\xampp\htdocs\SmartISO-3\SmartISO"
```

2. Run PHPUnit and write TestDox output to a text file (`build/logs/controllers_unit_tests.txt`) and JUnit XML (`build/logs/controllers_test_junit.xml`):

```powershell
# Create logs dir if missing
if (-not (Test-Path -Path "build/logs")) { New-Item -ItemType Directory -Path "build/logs" | Out-Null }

# Run tests (TestDox -> human readable text, and JUnit xml)
vendor\bin\phpunit --testdox > build\logs\controllers_unit_tests.txt; vendor\bin\phpunit --log-junit build\logs\controllers_test_junit.xml
```

3. Notes
- The first command writes TestDox (human-readable) output to `build/logs/controllers_unit_tests.txt`.
- The second command writes JUnit XML suitable for CI systems to `build/logs/controllers_test_junit.xml`.
- You can run a single `phpunit` invocation with multiple log options if desired; the example above separates them so the TestDox text file is easy to inspect.
- If your environment requires `php` prefix on Windows (sometimes `vendor\bin\phpunit` is a PHP file), use `php vendor\bin\phpunit` instead.

If you'd like, I can add a small npm-style script or a `Makefile` target to make this a single command.

Single-command to run tests (what I added)

You can run both TestDox and JUnit generation with a single composer command that was added to the project:

```powershell
composer run controllers:test
```

This executes `scripts/run_controllers_tests.php` and writes human-readable TestDox output to `build/logs/controllers_unit_tests.txt` and JUnit XML to `build/logs/controllers_test_junit.xml`.
