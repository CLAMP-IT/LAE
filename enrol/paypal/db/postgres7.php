<?PHP  //$Id: postgres7.php,v 1.5 2006/10/26 22:46:06 stronk7 Exp $

// THIS FILE IS DEPRECATED!  PLEASE DO NOT MAKE CHANGES TO IT!
//
// IT IS USED ONLY FOR UPGRADES FROM BEFORE MOODLE 1.7, ALL 
// LATER CHANGES SHOULD USE upgrade.php IN THIS DIRECTORY.

// PostgreSQL commands for upgrading this enrolment module

function enrol_paypal_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    //////  DO NOT ADD NEW THINGS HERE!!  USE upgrade.php and the lib/ddllib.php functions.

    return $result;

}

?>
