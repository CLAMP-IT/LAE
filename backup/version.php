<?php  //$Id: version.php,v 1.114.2.6 2009/01/28 23:44:23 stronk7 Exp $

/// This file defines the current version of the
/// backup/restore code that is being used.  This can be
/// compared against the values stored in the 
/// database (backup_version) to determine whether upgrades should
/// be performed (see db/backup_*.php)

    $backup_version = 2008030301;   // The current version is a date (YYYYMMDDXX)
    $backup_release = '1.9.4';     // User-friendly version number

?>
