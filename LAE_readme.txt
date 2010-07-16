===Moodle 1.9.9+Liberal Arts Edition v1.1 Release Notes===

Welcome to the Moodle 1.9.9+Liberal Arts Edition v1.1. The goal of LAE is to provide a coherent package for modules,  patches,  and code developed (or improved) by the Collaborative Liberal Arts Moodle Project. 

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

Moodle 1.9.9+LAE v1.1 consists of Moodle 1.9.9 (20100609) as well as a number of CLAMP-developed features and bug fixes. The following features have been added to the v1.1 release:

* Anonymous Forums
* Simple File Upload
* Assignment ZIP
* CLAMP Grader Report

====Anonymous Forums====
A completely new version of the Anonymous Forums option in Moodle. This version introduces a new "anonymous user" who is attached to forum posts, allowing faculty to back up and restore a forum without losing anonymity. There is an upgrade tool that automatically runs when LAE v1.1 is installed to convert the previous version of the Anonymous forums to the new format. Note: This feature is disabled by default.

====Assignment ZIP====
This tool allows faculty to download all of the files associated with an assignment as a single ZIP file, greatly simplifying the download process.

====CLAMP Grader Report====
We've created new, easier-to-use Grader and User Reports for Moodle. These are now the default reports for the Gradebook; they allow faculty to scroll through grades vertically and horizontally. We need this tested with existing gradebooks to verify that everything displays properly.

====Filtered Course List Block====
This block addresses a problem many campuses that are into their second or third year of Moodle encounter: filtering the current term's courses from those of previous terms. 

This block allows you to specify a current term and a future term based on whatever term-based naming convention you use in your Moodle courses' shortname field (e.g. FA09, SP10). It also allows you to specify a course category instead.

====Quickmail====
A block used to quickly send emails to members of a class, replicating similar functionality found in other learning management systems.

====Simple File Upload===
Simple File Upload adds the resource option of "Upload a File" to the "Resources" dropdown menu

=====SimpleRSS=====
Moodle sometimes fails to parse RSS feeds. This is because it relies on the unmaintained and buggy Magpie RSS library with SimplePie RSS, a current and well-maintained PHP library for parsing RSS feeds. This CLAMP-created fix is scheduled for inclusion in Moodle 2.0 (http://tracker.moodle.org/browse/MDL-7946)

====TinyMCE 3.37====
This is an upgrade from TinyMCE 2.x., our replacement editor for HTML Area. TinyMCE offers a superior feature set, and far better support for cutting and pasting from Microsoft Word. We need to verify that it works properly and upgrades cleanly from previous versions of LAE. Note: TinyMCE uses different HTML syntax from HTML area, so certain formating -- like bold or italics -- can't be undone using TinyMCE, and have to be changed in HTML view. 

====Bug Fixes====
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

* Move quiz timer to right side of page: In its default placement, the quiz time can obscure parts of the quiz interface. This offsets the timer to avoid that provlem.

http://redmine.clamp-it.org/issues/show/114

* Backup skips courses larger than half gig: Moodle can get hung up attempting to backup courses larger than half a gig. This tweaks skips over-sized courses (and logs the missed courses to the Moodle log)

http://redmine.clamp-it.org/issues/show/114

* Cleans up ghost resources from bad import: On rare occasions, a course restore/import import can cause empty "ghost" resources to appear. This fix removes those ghosts.

http://redmine.clamp-it.org/issues/show/114

* Fixes certain latex characters filterings

http://redmine.clamp-it.org/issues/show/114

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

====LAE Recommended Add-ons====

* Census Report: An administrative report that audits Moodle and displays a statistics about active courses, students and faculty. Note: Census report now lets you specify a date range when running the report.

* Scheduler: A module that allows faculty to setup meeting times for students, who can then sign up for those times individually or in groups.

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

3) If you have a more current version of Moodle installed (one later than 1.9.9+ (20100609), do not attempt to install LAE v1.0, as it will likely cause problems. You can find your current version by logging into Moodle as an administrator and then going to Administration > Notifications and looking at the bottom of the page for the Moodle version.