<?php  //$Id$

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
require_once $CFG->dirroot.'/grade/report/lib.php';
require_once 'category_form.php';

$courseid = required_param('courseid', PARAM_INT);
$id       = optional_param('id', 0, PARAM_INT); // grade_category->id

if (!$course = get_record('course', 'id', $courseid)) {
    print_error('nocourseid');
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('moodle/grade:manage', $context);

// default return url
$gpr = new grade_plugin_return();
$returnurl = $gpr->get_return_url('index.php?id='.$course->id);


$heading = get_string('categoryedit', 'grades');

if ($id) {
    if (!$grade_category = grade_category::fetch(array('id'=>$id, 'courseid'=>$course->id))) {
        error('Incorrect category id!');
    }
    $grade_category->apply_forced_settings();
    $category = $grade_category->get_record_data();
    // set parent
    $category->parentcategory = $grade_category->parent;
    $grade_item = $grade_category->load_grade_item();
    // nomalize coef values if needed
    $parent_category = $grade_category->get_parent_category();

    foreach ($grade_item->get_record_data() as $key => $value) {
        $category->{"grade_item_$key"} = $value;
    }

    $decimalpoints = $grade_item->get_decimals();

    $category->grade_item_grademax   = format_float($category->grade_item_grademax, $decimalpoints);
    $category->grade_item_grademin   = format_float($category->grade_item_grademin, $decimalpoints);
    $category->grade_item_gradepass  = format_float($category->grade_item_gradepass, $decimalpoints);
    $category->grade_item_multfactor = format_float($category->grade_item_multfactor, 4);
    $category->grade_item_plusfactor = format_float($category->grade_item_plusfactor, 4);

    if (!$parent_category) {
        // keep as is
    } else if ($parent_category->aggregation == GRADE_AGGREGATE_SUM or $parent_category->aggregation == GRADE_AGGREGATE_WEIGHTED_MEAN2) {
        $category->grade_item_aggregationcoef = $category->grade_item_aggregationcoef == 0 ? 0 : 1;
    } else {
        $category->grade_item_aggregationcoef = format_float($category->grade_item_aggregationcoef, 4);
    }

} else {
    $heading = get_string('newcategory', 'grades');
    $grade_category = new grade_category(array('courseid'=>$courseid), false);
    $grade_category->apply_default_settings();
    $grade_category->apply_forced_settings();

    $category = $grade_category->get_record_data();
}

$mform = new edit_category_form(null, array('current'=>$category, 'gpr'=>$gpr));

if ($mform->is_cancelled()) {
    redirect($returnurl);

} else if ($data = $mform->get_data(false)) {
    // If no fullname is entered for a course category, put ? in the DB
    if (!isset($data->fullname) || $data->fullname == '') {
        $data->fullname = '?';
    }

    if (!isset($data->aggregateonlygraded)) {
        $data->aggregateonlygraded = 0;
    }
    if (!isset($data->aggregateoutcomes)) {
        $data->aggregateoutcomes = 0;
    }
    grade_category::set_properties($grade_category, $data);

    /// CATEGORY
    if (empty($grade_category->id)) {
        $grade_category->insert();

    } else {
        $grade_category->update();
    }

    /// GRADE ITEM
    // grade item data saved with prefix "grade_item_"
    $itemdata = new stdClass();
    foreach ($data as $k => $v) {
        if (preg_match('/grade_item_(.*)/', $k, $matches)) {
            $itemdata->{$matches[1]} = $v;
        }
    }

    if (!isset($itemdata->aggregationcoef)) {
        $itemdata->aggregationcoef = 0;
    }

    $hidden      = empty($itemdata->hidden) ? 0: $itemdata->hidden;
    $hiddenuntil = empty($itemdata->hiddenuntil) ? 0: $itemdata->hiddenuntil;
    unset($itemdata->hidden);
    unset($itemdata->hiddenuntil);

    $locked   = empty($itemdata->locked) ? 0: $itemdata->locked;
    $locktime = empty($itemdata->locktime) ? 0: $itemdata->locktime;
    unset($itemdata->locked);
    unset($itemdata->locktime);

    $convert = array('grademax', 'grademin', 'gradepass', 'multfactor', 'plusfactor', 'aggregationcoef');
    foreach ($convert as $param) {
        if (array_key_exists($param, $itemdata)) {
            $itemdata->$param = unformat_float($itemdata->$param);
        }
    }

    // When creating a new category, a number of grade item fields are filled out automatically, and are required.
    // If the user leaves these fields empty during creation of a category, we let the default values take effect
    // Otherwise, we let the user-entered grade item values take effect
    $grade_item = $grade_category->load_grade_item();
    $grade_item_copy = fullclone($grade_item);
    grade_item::set_properties($grade_item, $itemdata);

    if (empty($grade_item->id)) {
        $grade_item->id = $grade_item_copy->id;
    }
    if (empty($grade_item->grademax)) {
        $grade_item->grademax = $grade_item_copy->grademax;
    }
    if (empty($grade_item->grademin)) {
        $grade_item->grademin = $grade_item_copy->grademin;
    }
    if (empty($grade_item->gradepass)) {
        $grade_item->gradepass = $grade_item_copy->gradepass;
    }
    if (empty($grade_item->aggregationcoef)) {
        $grade_item->aggregationcoef = $grade_item_copy->aggregationcoef;
    }

    // Handle null decimals value - must be done before update!
    if (!array_key_exists('decimals', $itemdata) or $itemdata->decimals < 0) {
        $grade_item->decimals = null;
    }

    $grade_item->outcomeid = null;

    // update hiding flag
    if ($hiddenuntil) {
        $grade_item->set_hidden($hiddenuntil, false);
    } else {
        $grade_item->set_hidden($hidden, false);
    }

    $grade_item->set_locktime($locktime); // locktime first - it might be removed when unlocking
    $grade_item->set_locked($locked, false, true);

    $grade_item->update(); // We don't need to insert it, it's already created when the category is created

    // set parent if needed
    if (isset($data->parentcategory)) {
        $grade_category->set_parent($data->parentcategory, 'gradebook');
    }

    redirect($returnurl);
}


print_grade_page_head($courseid, 'edittree', null, $heading);

$mform->display();

print_footer($course);
die;
