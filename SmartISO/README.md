# SmartISO - Document Management & Workflow System

## About SmartISO

SmartISO is a comprehensive document management and approval workflow system built with CodeIgniter 4. It facilitates the creation, submission, approval, and processing of various organizational forms with role-based access control and digital signature capabilities.

### Key Features
- **Dynamic Form Builder** with drag-and-drop interface
- **Role-based Workflow Management** (Requestor → Approver → Service Staff)
- **Digital Signature Integration** for document authentication  
- **PDF/Word Document Generation** with professional templates
- **Analytics & Reporting Dashboard** with Chart.js visualizations
- **Department-based Organization** structure
- **Real-time Status Tracking** throughout the workflow process

### User Roles
- **Superuser**: Full system administration
- **Admin**: User and form management
- **Requestor**: Form submission and tracking
- **Approving Authority**: Form review and approval
- **Service Staff**: Request processing and completion

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
