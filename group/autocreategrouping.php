<?php

//require_once('../config.php');
//require_once('lib.php');


// create grouping with same name as group

$autodata->courseid = $data->courseid;
$autodata->name = $data->name; 
$autodata->timecreated = time();
$autodata->descriptionformat=1; // HTML format


if ($groupingid = groups_create_grouping($autodata))
  echo "Grouping $groupingid created.<br/>"; 
else echo "I can't create that grouping, Bob."; 


// assign group to grouping

$groupid = $data->id;

if (groups_assign_grouping($groupingid, $groupid))
  echo "Group $groupid assigned to grouping $groupingid.<br/>";
else echo "Couldn't assign group $groupid to grouping $groupingid.<br/>"; 




?>
