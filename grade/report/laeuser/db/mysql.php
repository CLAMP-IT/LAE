<?php
/* 
 * updates laeuser report to current syntax )lower-case_
 * in case its previously been installed using old syntax (LAE)
 */

// trick the dang thing into thinking this isn't the first install, which it isn't except the plugin is renamed (only changing case)
// something you never want to have to do
$sql = 'update ' . $CFG->prefix . "config set name = replace(name, 'LAEuser','laeuser')";
$result = mysql_query($sql);
$sql = 'update ' . $CFG->prefix . "capabilities set name = replace(name, 'LAEuser','laeuser'), component = replace(component, 'LAEuser', 'laeuser')";
$result = mysql_query($sql);
$sql = 'update ' . $CFG->prefix . "role_capabilities set capability = replace(capability, 'LAEuser','laeuser')";
$result = mysql_query($sql);
?>
