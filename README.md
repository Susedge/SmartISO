# SmartISO
 Smart ISO Form Generator

## Installation
- Uncomment `extension=intl` on php.ini
- Uncomment `extension=gd` on php.ini
- Uncomment `extension=zip` on php.ini
- Uncomment `extension=openssl` on php.ini (required for email notifications)
- Run - composer install
- Create 'smartiso' db in phpMyAdmin
- Run - php spark migrate
- Run - php spark db:seed DepartmentAdminSeeder (optional - creates sample department admin)
- Run - php spark serve

## Email Notifications Setup (Optional - For Testing)
SmartISO now supports Gmail email notifications for all system events.

### Testing Email Notifications
1. **Test email configuration:**
   ```bash
   php test_email.php
   ```

2. **Override user emails for testing (sends all emails to test address):**
   ```bash
   php override_user_emails.php
   ```

3. **Restore original user emails after testing:**
   ```bash
   php restore_user_emails.php
   ```

For detailed information, see: `GMAIL_NOTIFICATIONS_GUIDE.md`

ADMIN 
- Create accounts for “APPROVING AUTHORITIES” and “SERVICE STAFF”.
- Add and create forms
- Add and create panels for forms
- Add departments
- Edit and delete users.

REQUESTOR
- Input data “DIGITAL SIGNATURE”.
- Generation request form.
- Submit to Approving Authorities.

APPROVING AUTHORITIES
- Input data “DIGITAL SIGNATURE”.
- View all submitted request forms by the Requestors.
- If approved send it to the Service Staff, else it will notify the Requestor that the submitted request form is denied.
- View all completed ISO request form for record

SERVICE STAFF
- Input data “DIGITAL SIGNATURE”.
- View and edit approved request form.
Send the completed request form back to the requestor for signature that the work is done by the Service staff. “If the Requestor already has signed the completed request form it will notify both Approving Authorities and Service Staff”.
- View all completed ISO request form for record


# Test Accounts
password: password123
- admin_user (Global Admin)
- approver_user (Approving Authority)
- requestor_user (Requestor)
- service_user (Service Staff)
- dept_admin_it (Department Admin - IT Department)

# Email Configuration
- SMTP: Gmail (smtp.gmail.com:587)
- From: chesspiecedum2@gmail.com
- Test Email: chesspiecedum2@gmail.com
- App Password: nyvu scnm vnnv dafa
- php override_user_emails.php
- php restore_user_emails.php

# For unit testing
 - run: composer run controllers:test
 - then navigate to: SmartISO/app/build/logs/

# Run codeigniter app
- run: composer install
- run: php spark migrate
- run: php spark serve