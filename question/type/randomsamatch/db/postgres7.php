<?php  // $Id: postgres7.php,v 1.4.8.1 2007/11/02 16:20:36 tjhunt Exp $

// THIS FILE IS DEPRECATED!  PLEASE DO NOT MAKE CHANGES TO IT!
//
// IT IS USED ONLY FOR UPGRADES FROM BEFORE MOODLE 1.7, ALL 
// LATER CHANGES SHOULD USE upgrade.php IN THIS DIRECTORY.

// PostgreSQL commands for upgrading this question type

function qtype_randomsamatch_upgrade($oldversion=0) {
    global $CFG;

    if ($oldversion < 2006042800) {
        // This is a random questiontype and therefore answers are always shuffled, no need for this field
        modify_database('', 'ALTER TABLE prefix_question_randomsamatch DROP shuffleanswers');
    }

    //////  DO NOT ADD NEW THINGS HERE!!  USE upgrade.php and the lib/ddllib.php functions.

    return true;
}

?>
