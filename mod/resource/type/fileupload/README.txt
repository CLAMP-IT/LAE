Simplified File Upload add-on README
Updated: 2010-06-26 // CLAMP wheeler

==============================================================================================
Overview:

This add-on creates a new resource type, fileupload, which simplifies the addition of file resources 
to a Moodle course, replacing the file resource user interface. Fileupload files are stored with the 
course files and are accessible there as file resources. 

The fileupload resource appears on the "Add a resource…" menu as "Upload a file". Users are presented 
with an upload dialog directly from the "Add a resource dialog", rather than the Moodle files dialog. 
All fileupload files are uploaded to the root directory of the course files. 


==============================================================================================
Limitations:
- fileuploads files modified (moved or renamed) directly in a Moodle Files dialog are broken without 
  warning to the user 
- fileupload is not yet documented in Moodle help 

==============================================================================================
Installation instructions:

Move or copy the entire 'fileupload' directory to the 'mod/resources/types/' path within your Moodle install.
You should then move to the top level of your Moodle install and apply the patches: 

- 'fileupload.diff' - which will update the resource base class to recognize fileupload and add needed strings 
to the en_utf8 bundle in 'lang/en_utf8/resource.php' to support the module.

Then, when adding resources to a course, you should see a new item in the resource type drop-down list: 'Upload a file'. 

==============================================================================================
Notes: 

2010-01-26 - Dan Wheeler = CLAMP dwheeler - BUG // CLAMP #181 2010-06-24 
Advanced features missing for opening in a new window, resulting in major issues with certain browsers. - fixed

2010-01-06 - Sarah Ryder = CLAMP - BUG - The force download option was 
opening the file w/ a blank white page due to some trailing whitespace
after the end php tag. - fixed 01-06

2010-01-05 - Dan Wheeler = CLAMP - Possible bug in backup/restore for fileupload type (not seen everywhere). Fix 
under development (simple update to backup/restore to add fileupload type). 

This seems to work OK with Moodle v1.9.7 (developed on 1.9.5)

2009-07-30 - Dan Wheeler = CLAMP - BUG - The Fileupload update requires
a file upload so can not be used simply to update a resource name
or description- fixed 07-30. 

2009-06-08 - Dan Wheeler = CLAMP - Fileupload resources can now be
updated like other resources (click on the Update icon) but will
overwrite any existing files with the same name without warning the
user.

2009-06-05 Caroline Moore
Resources created with this utility are saved in the database as type
'fileupload'. If you discontinue using this utility in the future,
you'll need to run a query on your database to change all resources of
type 'fileupload' to type 'file'. They will then work properly with
the built-in file utility.
