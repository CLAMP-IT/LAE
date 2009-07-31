<?php  // $Id$

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 1999 onwards  Martin Dougiamas  http://moodle.com       //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/lib.php';

$courseid = required_param('id', PARAM_INT);
$action   = required_param('action', PARAM_ALPHA);
$eid      = required_param('eid', PARAM_ALPHANUM);

/// Make sure they can even access this course
if (!$course = get_record('course', 'id', $courseid)) {
    print_error('nocourseid');
}
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

// default return url
$gpr = new grade_plugin_return();
$returnurl = $gpr->get_return_url($CFG->wwwroot.'/grade/edit/tree/index.php?id='.$course->id);

// get the grading tree object
$gtree = new grade_tree($courseid, false, false);

// what are we working with?
if (!$element = $gtree->locate_element($eid)) {
    error('Incorrect element id!', $returnurl);
}
$object = $element['object'];
$type   = $element['type'];


switch ($action) {
    case 'hide':
        if ($eid and confirm_sesskey()) {
            if (!has_capability('moodle/grade:manage', $context) and !has_capability('moodle/grade:hide', $context)) {
                error('No permission to hide!', $returnurl);
            }
            if ($type == 'grade' and empty($object->id)) {
                $object->insert();
            }
            $object->set_hidden(1, true);
        }
        break;

    case 'show':
        if ($eid and confirm_sesskey()) {
            if (!has_capability('moodle/grade:manage', $context) and !has_capability('moodle/grade:hide', $context)) {
                error('No permission to show!', $returnurl);
            }
            if ($type == 'grade' and empty($object->id)) {
                $object->insert();
            }
            $object->set_hidden(0, true);
        }
        break;

    case 'lock':
        if ($eid and confirm_sesskey()) {
            if (!has_capability('moodle/grade:manage', $context) and !has_capability('moodle/grade:lock', $context)) {
                error('No permission to lock!', $returnurl);
            }
            if ($type == 'grade' and empty($object->id)) {
                $object->insert();
            }
            $object->set_locked(1, true, true);
        }
        break;

    case 'unlock':
        if ($eid and confirm_sesskey()) {
            if (!has_capability('moodle/grade:manage', $context) and !has_capability('moodle/grade:unlock', $context)) {
                error('No permission to unlock!', $returnurl);
            }
            if ($type == 'grade' and empty($object->id)) {
                $object->insert();
            }
            $object->set_locked(0, true, true);
        }
        break;
}

redirect($returnurl);
//redirect($returnurl, 'debug delay', 5);

?>
