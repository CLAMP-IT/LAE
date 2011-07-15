===Moodle 1.9.12+Liberal Arts Edition v1.3 Beta Release Notes===

Welcome to the Moodle 1.9.12+Liberal Arts Edition v1.3 Beta. The goal of LAE is to provide a coherent package for modules,  patches,  and code developed (or improved) by the Collaborative Liberal Arts Moodle Project. 

This package consists of the code that the developers and instructional technologists at CLAMP schools have deemed essential to their operation of Moodle. A number of other recommend add-ons for Moodle are available through CLAMP web site (http://www.clamp-it.org). These recommended add-ons,  however,  have certain caveats that you should be aware of, and it's imperative that you read their respective lae_readme.txt files before installing them.

===LEGAL===
The LAE is offered "as is", with no warranty. The institutions that comprise CLAMP have done their best to test this code, but we're offering it strictly as a connivence to our members. 

===CONTACT===
Questions about the LAE can be sent to Ken Newquist at newquisk@lafayette.edu or 610-330-5759. Member organizations can participate in the development o
CLAMP members can participate in the development of the LAE by joining the Development Project in Redmine (our collaboration web site) at:

http://redmine.clamp-it.org/projects/show/development

===BROWSER COMPATIBILITY===
We recommend using Firefox 3.x with Moodle: Liberal Arts Edition. It has excellent support for web standards and works best with the LAE's built-in TinyMCE text editor. Other browsers work, but we have seen occasional quicks in how they interact with TinyMCE.

===CONTENTS===

Moodle 1.9.12+LAEv1.3 consists of Moodle 1.9.12+ (20110623) as well as a number of CLAMP-developed features and bug fixes.

The following features are included:

* Anonymous Forums
* Simple File Upload
* Assignment ZIP
* CLAMP Grader Report
* Re-organized Files Upload UI
* Value-input for Assignment Grading Interface

The following features have been added in the v1.3 release:

* Moodle 1.9.12+ (20110623) merged into LAE

The following bug fixes (with their CLAMP tracking number) were added as part of Moodle Hack/Doc Fest, Summer 2011 at Hampshire College:

* CLAMP-12: Student cannot see reponse file for Advanced uploading of assignment with No Grade
* CLAMP-183: Wiki won't display images with CamelCase-like names
* CLAMP-307: Forum: Q&A allows students to start discussions
* CLAMP-316: Bad HTML code in section header may disallow editing it
* CLAMP-318: Grader Preference not saved, value inserted into DB to long for user_preferences.value field

One new feature was added: if groupings are enabled, LAE will automatically great a new grouping whenever a group is created. This streamlines the process of using groupings to control access to specific resources with a Moodle course.

* CLAMP-333: Auto-adding of grouping (if enabled) for each created group in a course

====Anonymous Forums====
A completely new version of the Anonymous Forums option in Moodle. This version introduces a new "anonymous user" who is attached to forum posts, allowing faculty to back up and restore a forum without losing anonymity. There is an upgrade tool that automatically runs when LAE v1.1.1 is installed to convert the previous version of the Anonymous forums to the new format. Note: This feature is disabled by default.

====Assignment ZIP====
This tool allows faculty to download all of the files associated with an assignment as a single ZIP file, greatly simplifying the download process.

====CLAMP Grader Report====
We've created new, easier-to-use Grader and User Reports for Moodle. These are now the default reports for the Gradebook; they allow faculty to scroll through grades vertically and horizontally. We need this tested with existing gradebooks to verify that everything displays properly.

====Filtered Course List Block====
This block addresses a problem many campuses that are into their second or third year of Moodle encounter: filtering the current term's courses from those of previous terms. 

This block allows you to specify a current term and a future term based on whatever term-based naming convention you use in your Moodle courses' shortname field (e.g. FA11, SP12). It also allows you to specify a course category instead.

====Quickmail====
A block used to quickly send emails to members of a class, replicating similar functionality found in other learning management systems.

====Simple File Upload===
Simple File Upload adds the resource option of "Upload a File" to the "Resources" dropdown menu

=====SimpleRSS=====
Moodle sometimes fails to parse RSS feeds. This is because it relies on the unmaintained and buggy Magpie RSS library with SimplePie RSS, a current and well-maintained PHP library for parsing RSS feeds. This CLAMP-created fix is scheduled for inclusion in Moodle 2.0 (http://tracker.moodle.org/browse/MDL-7946)

====TinyMCE 3.37====
This is an upgrade from TinyMCE 2.x., our replacement editor for HTML Area. TinyMCE offers a superior feature set, and far better support for cutting and pasting from Microsoft Word. We need to verify that it works properly and upgrades cleanly from previous versions of LAE. Note: TinyMCE uses different HTML syntax from HTML area, so certain formating -- like bold or italics -- can't be undone using TinyMCE, and have to be changed in HTML view. 

====Re-organized Files Upload UI====
Keeps everything on one page, less clicks, more intuitive

====Value-input for Assignment Grading Interface====
Substitutes text input for asssignment grading dropdowns allowing decimal grades.  If $CFG-wipealloverrides is set
in config.php, will wipe any overrides on assignment grades allowing freedom to grade from Assignment UI or Gradebook
UI.

====Bug Fixes====
* Fixed a limitation the code that presizes course backups to determine if they're too large (default Moodle goes through the whole backup process before it determines the course is too large). Was limiting backed up courses to 5MB -- now allows courses up to 1/2 Gig.

* Can't edit a wiki with a # (hash, number sign, pound sign) in it's name.

http://redmine.clamp-it.org/issues/show/11
http://tracker.moodle.org/browse/MDL-17237

* Q and A forum allows for editing after submission

Students could submit a dummy response, see everyone else's answers, and then go back and edit their response accordingly. This prevents that.

http://redmine.clamp-it.org/issues/show/221
http://tracker.moodle.org/browse/MDL-9376

* Q and A forum subscriptions: The Question and Answer forum allowed a subscription even if the student hadn't answered the question yet. (and thus the chance of prematurely being able to look at other students answer)

http://redmine.clamp-it.org/issues/show/230
http://tracker.moodle.org/browse/MDL-9376

* Wiki throws "Page not found" error in group mode

http://redmine.clamp-it.org/issues/show/219

*  Wiki does not handle forward slashes in the top-level page correctly

http://redmine.clamp-it.org/issues/show/216
http://tracker.moodle.org/browse/MDL-22933

* SSL Publishing a Moodle site with ISA Server and no SSL Bridging (Browser--ssl--> ISA Server --nonssl --> Moodle) breaks Quiz View

http://tracker.moodle.org/browse/MDL-11061

* Move quiz timer to right side of page: In its default placement, the quiz time can obscure parts of the quiz interface. This offsets the timer to avoid that problem.

http://redmine.clamp-it.org/issues/show/114
(part of "Common Hacks" integration)

* Backup skips courses larger than half gig: Moodle can get hung up attempting to backup courses larger than half a gig. This tweaks skips over-sized courses (and logs the missed courses to the Moodle log)

http://redmine.clamp-it.org/issues/show/114
(part of "Common Hacks" integration)

* Cleans up ghost resources from bad import: On rare occasions, a course restore/import import can cause empty "ghost" resources to appear. This fix removes those ghosts.

http://redmine.clamp-it.org/issues/show/114
(part of "Common Hacks" integration)

* Fixes certain latex characters filterings

http://redmine.clamp-it.org/issues/show/114
(part of "Common Hacks" integration)

* Suppresses the display of essay answers in the Quiz Item Analysis Report, since the report doesn't handle them correctly by design

http://tracker.moodle.org/browse/MDL-21493
http://redmine.clamp-it.org/issues/show/182

* Disables the LAMS course format if LAMS is not configured on the site

http://tracker.moodle.org/browse/MDL-12847
http://redmine.clamp-it.org/issues/show/242

* CLAMP: Student cannot see reponse file for Advanced uploading of assignment with No Grade

http://redmine.clamp-it.org/issues/show/12
http://tracker.moodle.org/browse/MDL-16553

* Wiki won't display images with CamelCase-like names

http://redmine.clamp-it.org/issues/show/183
http://tracker.moodle.org/browse/MDL-14372

* Bad HTML code in section header may disallow editing it

http://redmine.clamp-it.org/issues/show/316
http://tracker.moodle.org/browse/MDL-1458

* Auto-adding of grouping (if enabled) for each created group in a course

http://redmine.clamp-it.org/issues/show/333
http://tracker.moodle.org/browse/MDL-28082

* Forum: Q&A allows students to start discussions

http://redmine.clamp-it.org/issues/show/307
http://tracker.moodle.org/browse/MDL-27735

===Tweaks and Enhancements===

* Assignment Max Grade increased to 250 (from 100): Moodle defaults its max grade value to 100; LAE changes that default to 250. 

http://redmine.clamp-it.org/issues/show/114

* AJAX editing now on by default: The LAE edition defaults to on for "Use AJAX with course editing"; we feel the feature is now solid enough to be used in production. 

http://redmine.clamp-it.org/issues/show/209

* Gradebook Default Navigation Changed: The default navigation mode for Gradebook has been set to use tabs and the dropdown; Moodle was defaulting to just the dropdown, but we found the tabs are easier for faculty to use.

http://redmine.clamp-it.org/issues/show/211

* On Category settings page, hide "Grade Item" section

The fact that the category total settings are called "Grade Item" is  confusing to  faculty. The term "Grade Item" is used elsewhere to mean "an item to be graded," so when faculty see "Grade Item" on the Category creation page, they think it is a shortcut to add a grade item inside of that category. This hides the "grade Item" langauge.

http://redmine.clamp-it.org/issues/show/225
http://tracker.moodle.org/browse/MDL-22931

* Hide "role renaming" options in course settings

The "role renaming" options at the bottom of the course settings page are confusing for many instructors. This change hides them behind a "Show Advanced" button.

http://tracker.moodle.org/browse/MDL-22928
http://redmine.clamp-it.org/issues/show/218

* Files Upload UI

The "Restore" option is now available only for .zip files.

http://redmine.clamp-it.org/issues/show/287

Background colors have been set to transparent and columns for folders and files have been set to line up.

http://redmine.clamp-it.org/issues/show/289

* Stylesheet Conflict Between Grader and LAE Grader

Fixed an css issue that caused rows to mis-aligned when both the Grader and LAE Grader are enabled.

http://redmine.clamp-it.org/issues/show/300

* Course Backup Enahncements

If you have ZipArchive installed on your system, then the Moodle backup code will now take advantage of it. This allows for larger archives when running scheduled backups.

Backup File Size Option: Moodle can hang during backups if there is too much content in the course. We've added a web interface option (under Administration > Performance) where admins can specify a max file size for course backups. The default file size is 512 MB.

====LAE Recommended Add-ons====

* Census Report: An administrative report that audits Moodle and displays a statistics about active courses, students and faculty. Note: Census report now lets you specify a date range when running the report.

* Scheduler: A module that allows faculty to setup meeting times for students, who can then sign up for those times individually or in groups.

===DOWNLOADING THE LAE===
You can get the LAE in two ways:

* Download the tar and zip packages from the CLAMP web site:
http://www.clamp-it.org/code/

* Download the current release branch from the CLAMP Subversion repository:

svn co svn+ssh://[CLAMP username]@www.clamp-it.org/var/svn/moodle/tags/1.9.12-LAE1.2.1

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

3) If you have a more current version of Moodle installed (one later than 1.9.12 (20110510), do not attempt to install LAE v1.2.1, as it will cause a conflict with your newer database, and the installation will fail. You can find your current version by logging into Moodle as an administrator and then going to Administration > Notifications and looking at the bottom of the page for the Moodle version.
