<?php  //$Id: index.php,v 1.35.2.5 2009/05/04 13:14:17 skodak Exp $

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
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_xml.php';

$id = required_param('id', PARAM_INT); // course id

if (!$course = get_record('course', 'id', $id)) {
    print_error('nocourseid');
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/xml:view', $context);

print_grade_page_head($COURSE->id, 'export', 'xml', get_string('exportto', 'grades') . ' ' . get_string('modulename', 'gradeexport_xml'));

if (!empty($CFG->gradepublishing)) {
    $CFG->gradepublishing = has_capability('gradeexport/xml:publish', $context);
}

$mform = new grade_export_form(null, array('idnumberrequired'=>true, 'publishing'=>true, 'updategradesonly'=>true));

$groupmode    = groups_get_course_groupmode($course);   // Groups are being used
$currentgroup = groups_get_course_group($course, true);
if ($groupmode == SEPARATEGROUPS and !$currentgroup and !has_capability('moodle/site:accessallgroups', $context)) {
    print_heading(get_string("notingroup"));
    print_footer($course);
    die;    
}

// process post information
if ($data = $mform->get_data()) {
    $export = new grade_export_xml($course, $currentgroup, '', false, $data->updatedgradesonly, $data->display, $data->decimals);

    // print the grades on screen for feedbacks
    $export->process_form($data);
    $export->print_continue();
    $export->display_preview(true);
    print_footer($course);
    exit;
}

groups_print_course_menu($course, 'index.php?id='.$id);
echo '<div class="clearer"></div>';

$mform->display();

print_footer();
?>
