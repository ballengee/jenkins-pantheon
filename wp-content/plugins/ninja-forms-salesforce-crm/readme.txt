=== Ninja Forms - SalesForce CRM===

== Changelog ==
= 3.3.2 (19 October 2022)
* Bug Fixes *
- Fix date interval error
- Fix debug data not displaying on settings page

= 3.3.1 (11 October 2021) =
* Bug Fixes *
- correct errant upgrade function name

* Enhancements *
- Add debug diagnostics to support

= 3.3.0 ( 08 July 2021) =
* Enhancements *
- Add Advanced Command that sets sandbox login URL

* Bug Fixes *
- Prevent error in which multiple Salesforce actions cause redeclaration errors

= 3.2.0 =
Add check for empty date field and remove

Refactor duplicate check as separate method (also fixes duplicate check error)

Add context to response analysis for better handling create vs getrecords

Markup Debug HTML for easier reading on screen

= 3.1.0 =
2018.04.26
Add contextual help to support form designers connect with Salesforce

Add Format for Salesforce Currency special instruction

Add filter to modify array delimiter for multiselect and equivalent data

Add Special Instructions to replace commas with semi-colon in strings

Change SSLVerify default to true, was false, and add filter and advanced command
to override new default

Add listener for key change - auto update merge tags in field map form_field

Separate pre-3.0 files and classes to enable ongoing 3.0 development.  Shared
functionality with 2.9 hinders the ability to develop new features; separate
the files so that 3.0 development can continue without breaking 2.9

Add check for comm_data['status'] is set in Settings

= 3.0.6 =
2017.10.26
Remove integer 0 from boolean data handling array and specifically set true value

= 3.0.5 =
2017.10.20
Correct typo in special instructions
Special instructions option to preserve ampersand and quote marks
Scrub action settings to remove field option dropdown values during save

= 3.0.4 =
2017.09.23
Add boolean for data handling

= 3.0.3 =
2017.05.19
Add Campaign Linking
Remove HTML tags that appear in text areas
Remove builder template action hooks
Use htmlentities for settings display - avoid html output from response


= 3.0.2 =
2017.04.14
Add status message for Salesforce 403  Forbidden error
This is for Salesforce accounts which do not have API access enabled

= 3.0.1 =
2017.03.20
Change slug and name constants for auto update

= 3.0 =
Add handling for extracting file from file upload in NF3

Add file upload special instructions

Move duplicate check array to shared functions for NF3 use

Correct Field Map Upgrade lookup

Upgrade to NF3


= 1.2.2 = 
Add date formatting function so that form designer can use local date format
on form and date will converted to Salesforce-required date format prior
to submisssion

= 1.2.1 =
Fix is_array code in validate_raw_form_value method in build request

= 1.2 =
Enable Sandbox mode by way of a filter
Correct duplicate filter name for child object array modifications
Add support for file uploads into Salesforce
Add json request to communication details when Salesforce rejects the request


= 1.1.1 =
2015.05.12
Add status update when duplicate check is not requested; before, if no
duplicate check is performed, status does not update.  This change will
enhance support

= 1.1 =
2015.03.23
Add feature - check for duplicate values and create task to validate duplication


= 1.0.7 =
2015.01.22
Check for available fields before refreshing object;
Before would throw a warning if settings were unable to
retrieve an object.  After checks for null and sets
a descriptive error

= 1.0.5 =
2015.01.19
Add automatic connection of Note to Lead, Contact, Account

= 1.0 =
Begin