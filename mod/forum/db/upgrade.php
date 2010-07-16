<?php  //$Id$

require_once("$CFG->dirroot/mod/forum/lib.php");
// This file keeps track of upgrades to 
// the forum module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_forum_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

    if ($result && $oldversion < 2007101000) {

    /// Define field timemodified to be added to forum_queue
        $table = new XMLDBTable('forum_queue');
        $field = new XMLDBField('timemodified');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'postid');

    /// Launch add field timemodified
        $result = $result && add_field($table, $field);
    }

//===== 1.9.0 upgrade line ======//

    if ($result and $oldversion < 2007101511) {
        notify('Processing forum grades, this may take a while if there are many forums...', 'notifysuccess');
        //MDL-13866 - send forum ratins to gradebook again
        require_once($CFG->dirroot.'/mod/forum/lib.php');
        // too much debug output
        $db->debug = false;
        forum_update_grades();
        $db->debug = true;
    }

    if ($result && $oldversion < 2007101512) {

    /// Cleanup the forum subscriptions
        notify('Removing stale forum subscriptions', 'notifysuccess');

        $roles = get_roles_with_capability('moodle/course:view', CAP_ALLOW);
        $roles = array_keys($roles);
        $roles = implode(',', $roles);

        $sql = "SELECT fs.userid, f.id AS forumid
                  FROM {$CFG->prefix}forum f
                       JOIN {$CFG->prefix}course c                 ON c.id = f.course
                       JOIN {$CFG->prefix}context ctx              ON (ctx.instanceid = c.id AND ctx.contextlevel = ".CONTEXT_COURSE.")
                       JOIN {$CFG->prefix}forum_subscriptions fs   ON fs.forum = f.id
                       LEFT JOIN {$CFG->prefix}role_assignments ra ON (ra.contextid = ctx.id AND ra.userid = fs.userid AND ra.roleid IN ($roles))
                 WHERE ra.id IS NULL";

        if ($rs = get_recordset_sql($sql)) {
            $db->debug = false;
            while ($remove = rs_fetch_next_record($rs)) {
                delete_records('forum_subscriptions', 'userid', $remove->userid, 'forum', $remove->forumid);
                echo '.';
            }
            $db->debug = true;
            rs_close($rs);
        }
    }

    if ($result && $oldversion < 2007101513) {
        delete_records('forum_ratings', 'post', 0); /// Clean existing wrong rates. MDL-18227
    }

    /// CLAMP #175 2010-06-22 cfulton
    /// Support for Anonymous User:Anonymous Forums
    if ($result && $oldversion < 2010010700) {
        /// New anonymous forum patch
	/// Add anonymous user
	forum_add_anon_user();	        

        /// add hooks to _forum and _forum_posts
        $table = new XMLDBTable('forum');
        $field = new XMLDBField('anonymous');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'blockperiod');
        $result = $result && add_field($table, $field);
    }
	
    /// add the hiddenuserid to the forum posts field
    if ($result && $oldversion < 2010010702) {
        $table = new XMLDBTable('forum_posts');
        $field = new XMLDBField('hiddenuserid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $result = $result && add_field($table, $field);

        /// Check for old anonymous forums patch and cleanup if necessary
        if (isset($CFG->anonymous_name)) {
            // The settings in mdl_forum are equivalent and can be ignored. We need to update mdl_forum_posts
            $sql = "UPDATE ".$CFG->prefix."forum_posts SET hiddenuserid=userid WHERE anonymous=1";
            if(!execute_sql($sql)) {
                error("Failed to update old anonymous posts");
            }
            $sql = "UPDATE ".$CFG->prefix."forum_posts SET userid='".$CFG->anonymous_userid."' WHERE anonymous=1";
            if(!execute_sql($sql)) {
                error("Failed to update old anonymous posts");
            }
        }
    }
    /// end additions by cfulton

    return $result;
}

?>
