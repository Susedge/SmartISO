# SmartISO
 Smart ISO Form Generator

## Installation
- Uncomment `extension=intl` on php.ini

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


