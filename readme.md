

##### 1. Apache web server (needs to be running)
##### 2. MySQL database.  
##### 3. PHP 5.2 and higher
##### 4. The following PHP modules installed and enabled:
mysql, imap, mcrypt, imagick, gd, cli, curl, soap, pear
##### 5. PERL
##### 6. Imagemagick
##### 7. PDF ToolKit (pdftk)
##### 8. cURL


## Update Notes for Version 2.0:
##### 1. Phaxio is now the only supported fax program.
##### 2. Standard Medical Templates v1 and v2 are now depreciated and replaced my Medical Template
##### 3. CPT database is now coming from Medicare and subsequently royalty-free.
##### 4. ICD database update (Internal database is now depreciated).
##### 5. Medication database update (Internal database is now depreciated with move to RXNorm API)
##### 6. CVX database update (Internal database is now depreciated).
##### 7. Patient education materials using VivaCare is now depreciated.
##### 8. SMS sending using TextBelt
##### 9. Supplements database update (Internal database is now depreciated).
##### 10. Guardian roles database update (Internal database is now depreciated).
##### 11. Sessions table depreciated (file based sessions currently in use with Laravel 5).

# Notes
## Lab order templates:
Labs are set up in such a way: Name of Test [OrderCode,CPTCode,LOINCCode,ResultCode]

# How the files are organized.

NOSH is built around the Laravel 5 PHP Framework, which is a models/controllers/views (MCV) framework.
Documentation for the entire framework can be found on the [Laravel website](http://laravel.com/docs).

## Routes
The routes.php file dictate where the URL command goes to.  Looking at the file, you'll notice that the controllers are
categorized by an access control list (ACL) based on the type of user priviledges a user has when he/she is logged in to NOSH.

## Controllers
As is standard with the Laravel framework, main guts of the system lie in the ../app/Http/Controllers directory.  Looking at the
routes.php file, you'll notice that the type of controllers are categorized between AJAX and non-AJAX functions (hence they
are named with a prefix of Ajax).  Furthermore, the main controller files (CoreController.php, ChartController.php) are determined based on whether the functions are patient related or practice related.  As is also standard with the Laravel framework, middleware in the ../app/Http/Middleware directory govern any checks/filters prior to calling a function in each controller.

## Views
The view files, PDF, and email template files are in the ../resources/views directory.  The view files are essentially "modules" that
are added on depending on the needs of the view layout.
The corresponding javascript files (named the same as the view file, but with a .js extension) are in the ../public/assets/js directory.
If you see the javascript, you will notice that jQuery is used heavily here.  There are numerous plugins for jQuery that are
referenced in the header file.  Below is a list of the major jQuery plugins that are used:
##### Javascript library: [JQuery](https://jquery.com/)
##### Bootstrap user interface: [Bootstrap](https://getbootstrap.com)
##### Calendar system: [FullCalendar](https://fullcalendar.io)
##### Signature capture: [Signature Pad](http://thomasjbradley.ca/lab/signature-pad)
##### Graphs and Charts: [Highcharts](https://www.highcharts.com)
##### Form input masking: [Masked Input](https://github.com/digitalBush/jquery.maskedinput)
##### Image editing: [jCanvas](https://projects.calebevans.me/jcanvas)
##### Family Tree: [Sigma](https://sigmajs.org)

## Resources
In addition to views, other resources such as CPT codes, supplements list, CVX codes, immunizations recommendations, and growth chart plotting data are stored in either CSV or YAML format.  These files will or can be updated from time to time based on available updates.

## Assets
Images indicated in the view files reside in the ../public/assets/images directory.
CSS files reside in the ../public/assets/cs directory
Imported files are usually downloaded via script in the ../import directory.

## Database schema
Below are the list of active database tables that are installed for NOSH.  Some table names are self explainatory, but those that are not
will be explained here.  Some tables are depreciated if you happen to see the database schemas.
	addressbook
	alerts
	allergies
	assessment - Assessment of a patient encounter.
	audit - This is a log of all database commands (add, edit, delete) by users of NOSH.
	billing - List of all fields in a HCFA-1500 form for each patient encounter.
	billing-core - List of all charges and payments for a patient encounter.
	calendar - List of all visit types and their duration for the patient scheduler.
	demographics - List of all patients (active or inactive) in the system.
	demographics_notes - Additional demographics information irrespective of practice.
	demographics_relate - Reference table associating patient to practices and users.
 	documents - List of all PDF documents saved in the documents folder (default is /noshdocuments) on the server that pertain to a
		given patient.
	encounters - List of all patient encounters for a given patient.
	forms - List of filled out forms.
	groups - List of user groups (provider, admin, assistant, billing, patient).
	hippa - List of all release of information requests for a given patient.
	hpi - History of Present Illness of a patient encounter.
	immunizations - List of immunizations for a given patient.
	insurance - List of all insurance information for a given patient.
	issues - List of all medical issues (active or inactive) for a given patient.
	labs - List of all lab results for a given patient.
	messaging - Intraoffice messaging.
	migrations - Internal use for Laravel.
	orders - This table lists all physician orders for a given patient.
	orderslists - This table lists all templates for physician orders.
	other_history - Past Medical History, Past Surgical History, Family History. Social History, Tobacco Use History, Alcohol Use
		History, and Illicit Drug Use History
	pages - List of documents being sent by fax.
	pe - Physical Examination of a patient encounter.
	plan - Plan of a patient encounter.
	pos - Place of Service codes
	practiceinfo - Practice information
	procedure - Procedures done in a patient encounter.
	procedurelist - Procedure templates.
	providers - Provider information
	received - List of documents received by fax.
	recepients - List of recepients of faxes sent.
	repeat_schedule - List of repeated calendar events.
	ros - Review of System of a patient encounter.
	rx - List of all medications (active or inactive) for a given patient.
	rx_list - List of all medications prescribed by a provider.
	scans - List of all documents scanned into the system.
	schedule - Patient scheduling
	sessions - Internal use
	sendfax - List of all sent faxes.
	sup_list - List of all ordered supplements by physician.
	template - List of forms save
	tests - List of test results for a given patient.
	t_messages - List of all telephone messages for a given patient.
	users - List of all system users.
	vaccine_inventory - Vaccine inventory
	vaccine_temp - Vaccine temperature log
	vitals - List of vital signs in a patient encounter.


