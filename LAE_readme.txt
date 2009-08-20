h1. Moodle Liberal Arts Edition v1 Release Notes

Welcome to the Moodle: Liberal Arts Edition v1.0. The goal of LAE is to provide a coherent package for modules, patches, and code developed (or improved) by the Collaborative Liberal Arts Moodle Project. 

This package consists of the code that the developers and instructional technologists at CLAMP schools have deemed essential to their operation of Moodle. A number of other recommend add-ons for Moodle are available through CLAMP web site (http://www.clamp-it.org). These recommended add-ons, however, have certain caveats that you should be aware of, and it's imperative that you read their respective lae_readme.txt files before installing them.

===LEGAL===
The LAE is offered "as is", with no warranty. The institutions that comprise CLAMP have done their best to test this code, but we're offering it strictly as a connivence to our members. 

===CONTACT===
Questions about the LAE can be sent to Ken Newquist at newquisk@lafayette.edu or 610-330-5759. Member organizations can participate in the development o
CLAMP members can participate in the development of the LAE by joining the Development Project in Redmine (our collaboration web site) at:

http://redmine.clamp-it.org/projects/show/development

===BROWSER COMPATIBILITY===
We recommend using Firefox 3.x with Moodle: Liberal Arts Edition. It has excellent support for web standards and works best with the LAE's built-in TinyMCE text editor. Other browsers work, but we have seen occasional quicks in how they interact with TinyMCE.

===CONTENTS===

The LAE v1.0 consists of the following components:

====Moodle 1.9.5+ (20090729)====
This is the Moodle 1.9.5+ weekly build from late July.

====TinyMCE v2.x====
TinyMCE is a new WYSIWYG editor for Moodle that replaces the older, out-of-date HTMLArea editor that ships with Moodle. HTMLArea is scheduled to be removed in Moodle 2.0 (to be replaced by TinyMCE or CKEditor) but we decided to address it immediately because of a usability problem involving cutting and pasting text from Microsoft Word into Moodle. This problem results in extraneous, hidden code would be copied along with the intended text. This hidden text would then become visible when the posts arrived via email.

====Filtered Course List Block====
This block addresses a problem many campuses that are into their second or third year of Moodle encounter: filtering the current term's courses from those of previous terms. 

This block allows you to specify a current term and a future term based on whatever term-based naming convention you use in your Moodle courses' shortname field (e.g. FA09, SP10). It also allows you to specify a course category instead.

=====SimpleRSS=====
Moodle sometimes fails to parse RSS feeds. This is because it relies on the unmaintained and buggy Magpie RSS library with SimplePie RSS, a current and well-maintained PHP library for parsing RSS feeds. This CLAMP-created fix is scheduled for inclusion in Moodle 2.0 (http://tracker.moodle.org/browse/MDL-7946)

====Quickmail====
A block used to quickly send emails to members of a class, replicating similar functionality found in other learning management systems.

====LAE Recommended Add-ons====
The following add-ons are not included in the LAE, but are available for download from the CLAMP web site (http://www.clamp-it.org/code/)

* Assignment Zip+: A tool that allows the bulk download of assignment files as a ZIP file. Note: You must have PHP compiled with ZIP support for this to work.

* Anonymous Forums: Anonymizes forum posts so that the author name is not publicly shown. Note: Currently works only with new installs. If you have an existing install, you'll need to manually create the needed database columns to support Anonymous Forums.

* Census Report: An administrative report that audits Moodle and displays a statistics about active courses, students and faculty.

* Common Moodle Patches+: A collection of commonly used patches (including the max grade patch, a bug fix for tex, moving the 'course search' form to the top of the courses page) for improving Moodle usability.

* Gradebook Max Grade patch: Increases the maximum allowable grade from 100 to 250 (included in the "Common Moodle Patches" file; this is a standalone version).

* Simple File Upload+: An add-on that offers a straight-forward upload option for adding files to a Moodle course that bypasses the normal (and more cumbersome) file upload procedure. Note: Simple File Upload currently allow users to upload a file, but they can’t update the resource associated with that file without reloading it.

* Scheduler: A module that allows faculty to setup meeting times for students, who can then sign up for those times individually or in groups.

Add-ons marked with a plus (+) indicate code we hope to include in LAE v1.1 once we've completed development and testing on them.

===DOWNLOADING THE LAE===
You can get the LAE in two ways:

* Download the tar package from the CLAMP web site:
http://www.clamp-it.org/code/

You can also download the individual components that make up the LAE from that URL.

* Download the current release branch from the CLAMP Subversion repository:

svn co svn+ssh://[CLAMP username]@www.clamp-it.org/var/svn/moodle/branches/1.9.5-LAE1.0/Release

===INSTALLING THE LAE===
If you are installing Moodle for the first time, you can follow the standard Moodle installation instructions (substituting the LAE Moodle package for the regular Moodle one)

http://docs.moodle.org/en/Installing_Moodle

===UPGRADING TO THE LAE===
If you are upgrading an existing installation, you can follow your normal procedure for doing an "in-place" upgrade (replacing your old Moodle files with the new LAE ones, then copying over any additional modules or blocks you might have from the old install into the new one)

You will then need to edit your config.php file and include the following two lines of code in order to enable TinyMCE support (these lines are added automatically if you are doing a new installation).

$CFG->validateforms = 'server';
$CFG->defaulthtmleditor='tinymce';

You should place them just above these lines:

// MAKE SURE WHEN YOU EDIT THIS FILE THAT THERE ARE NO SPACES, BLANK LINES,
// RETURNS, OR ANYTHING ELSE AFTER THE TWO CHARACTERS ON THE NEXT LINE.

A few notes:

1) Always backup your original Moodle files and database before doing an upgrade.

2) We *strongly* recommend doing a test upgrade on a development Moodle instance before upgrading your production instance.

3) If you have a more current version of Moodle installed (one later than 1.9.5+ (20090729), do not attempt to install LAE v1.0, as it will likely cause problems. You can find your current version by logging into Moodle as an administrator and then going to Administration > Notifications and looking at the bottom of the page for the Moodle version.
