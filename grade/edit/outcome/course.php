<?php // $Id$
      // Allows a creator to edit custom outcomes, and also display help about outcomes

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
require_once $CFG->libdir.'/gradelib.php';

$courseid = required_param('id', PARAM_INT);

/// Make sure they can even access this course
if (!$course = get_record('course', 'id', $courseid)) {
    print_error('nocourseid');
}
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('moodle/course:update', $context);

/// return tracking object
$gpr = new grade_plugin_return(array('type'=>'edit', 'plugin'=>'outcomes', 'courseid'=>$courseid));

// first of all fix the state of outcomes_course table
$standardoutcomes    = grade_outcome::fetch_all_global();
$co_custom           = grade_outcome::fetch_all_local($courseid);
$co_standard_used    = array();
$co_standard_notused = array();

if ($courseused = get_records('grade_outcomes_courses', 'courseid', $courseid, '', 'outcomeid')) {
    $courseused = array_keys($courseused);
} else {
    $courseused = array();
}

// fix wrong entries in outcomes_courses
foreach ($courseused as $oid) {
    if (!array_key_exists($oid, $standardoutcomes) and !array_key_exists($oid, $co_custom)) {
        delete_records('grade_outcomes_courses', 'outcomeid', $oid, 'courseid', $courseid);
    }
}

// fix local custom outcomes missing in outcomes_course
foreach($co_custom as $oid=>$outcome) {
    if (!in_array($oid, $courseused)) {
        $courseused[$oid] = $oid;
        $goc = new object();
        $goc->courseid = $courseid;
        $goc->outcomeid = $oid;
        insert_record('grade_outcomes_courses', $goc);
    }
}

// now check all used standard outcomes are in outcomes_course too
$sql = "SELECT DISTINCT outcomeid
          FROM {$CFG->prefix}grade_items
         WHERE courseid=$courseid and outcomeid IS NOT NULL";
if ($realused = get_records_sql($sql)) {
    $realused = array_keys($realused);
    foreach ($realused as $oid) {
        if (array_key_exists($oid, $standardoutcomes)) {

            $co_standard_used[$oid] = $standardoutcomes[$oid];
            unset($standardoutcomes[$oid]);

            if (!in_array($oid, $courseused)) {
                $courseused[$oid] = $oid;
                $goc = new object();
                $goc->courseid = $courseid;
                $goc->outcomeid = $oid;
                insert_record('grade_outcomes_courses', $goc);
            }
        }
    }
}

// find all unused standard course outcomes - candidates for removal
foreach ($standardoutcomes as $oid=>$outcome) {
    if (in_array($oid, $courseused)) {
        $co_standard_notused[$oid] = $standardoutcomes[$oid];
        unset($standardoutcomes[$oid]);
    }
}


/// form processing
if ($data = data_submitted()) {
    require_capability('moodle/grade:manageoutcomes', $context);
    if (!empty($data->add) && !empty($data->addoutcomes)) {
    /// add all selected to course list
        foreach ($data->addoutcomes as $add) {
            $add = clean_param($add, PARAM_INT);
            if (!array_key_exists($add, $standardoutcomes)) {
                continue;
            }
            $goc = new object();
            $goc->courseid = $courseid;
            $goc->outcomeid = $add;
            insert_record('grade_outcomes_courses', $goc);
        }

    } else if (!empty($data->remove) && !empty($data->removeoutcomes)) {
    /// remove all selected from course outcomes list
        foreach ($data->removeoutcomes as $remove) {
            $remove = clean_param($remove, PARAM_INT);
            if (!array_key_exists($remove, $co_standard_notused)) {
                continue;
            }
            delete_records('grade_outcomes_courses', 'courseid', $courseid, 'outcomeid', $remove);
        }
    }
    redirect('course.php?id='.$courseid); // we must redirect to get fresh data
}

/// Print header
print_grade_page_head($COURSE->id, 'outcome', 'course');

check_theme_arrows();
require('course_form.html');

print_footer($course);
?>
