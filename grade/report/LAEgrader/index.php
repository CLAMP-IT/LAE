<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
// CLAMP # 194 2010-06-23 bobpuffer

require_once '../../../config.php';
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/LAEgrader/lib.php';
require_once $CFG->dirroot.'/grade/report/LAEgrader/locallib.php'; // END OF HACK

require_js(array('yui_yahoo', 'yui_dom', 'yui_event', 'yui_container', 'yui_connection', 'yui_dragdrop', 'yui_element'));


$courseid      = required_param('id', PARAM_INT);        // course id
$page          = optional_param('page', 0, PARAM_INT);   // active page
$perpageurl    = optional_param('perpage', 0, PARAM_INT);
$edit          = optional_param('edit', -1, PARAM_BOOL); // sticky editting mode

$sortitemid    = optional_param('sortitemid', 0, PARAM_ALPHANUM); // sort by which grade item
$action        = optional_param('action', 0, PARAM_ALPHAEXT);
$move          = optional_param('move', 0, PARAM_INT);
$type          = optional_param('type', 0, PARAM_ALPHA);
$target        = optional_param('target', 0, PARAM_ALPHANUM);
$toggle        = optional_param('toggle', NULL, PARAM_INT);
$toggle_type   = optional_param('toggle_type', 0, PARAM_ALPHANUM);

/// basic access checks
if (!$course = get_record('course', 'id', $courseid)) {
    print_error('nocourseid');
}
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('gradereport/LAEgrader:view', $context);
require_capability('moodle/grade:viewall', $context);

/// return tracking object
$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'LAEgrader', 'courseid'=>$courseid, 'page'=>$page));

/// last selected report session tracking
if (!isset($USER->grade_last_report)) {
    $USER->grade_last_report = array();
}
$USER->grade_last_report[$course->id] = 'LAEgrader';

/// Build editing on/off buttons

if (!isset($USER->gradeediting)) {
    $USER->gradeediting = array();
}

if (has_capability('moodle/grade:edit', $context)) {
    if (!isset($USER->gradeediting[$course->id])) {
        $USER->gradeediting[$course->id] = 0;
    }

    if (($edit == 1) and confirm_sesskey()) {
        $USER->gradeediting[$course->id] = 1;
    } else if (($edit == 0) and confirm_sesskey()) {
        $USER->gradeediting[$course->id] = 0;
    }

    // page params for the turn editting on
    $options = $gpr->get_options();
    $options['sesskey'] = sesskey();

    if ($USER->gradeediting[$course->id]) {
        $options['edit'] = 0;
        $string = get_string('turneditingoff');
    } else {
        $options['edit'] = 1;
        $string = get_string('turneditingon');
    }

    $buttons = print_single_button('index.php', $options, $string, 'get', '_self', true);

} else {
    $USER->gradeediting[$course->id] = 0;
    $buttons = '';
}

$gradeserror = array();

// Handle toggle change request
if (!is_null($toggle) && !empty($toggle_type)) {
    set_user_preferences(array('grade_report_show'.$toggle_type => $toggle));
}

//first make sure we have proper final grades - this must be done before constructing of the grade tree
grade_regrade_final_grades($courseid);

// Perform actions
if (!empty($target) && !empty($action) && confirm_sesskey()) {
	grade_report_grader::process_action($target, $action); // END OF HACK
}

$reportname = get_string('modulename', 'gradereport_LAEgrader');
// Initialise the grader report object
$report = new grade_report_LAEgrader($courseid, $gpr, $context, $page, $sortitemid); // END OF HACK

// make sure separate group does not prevent view
if ($report->currentgroup == -2) {
    print_grade_page_head_local($COURSE->id, 'report', 'LAEgrader', $reportname, false, null, $buttons);
    print_heading(get_string("notingroup"));
    print_footer($course);
    exit;
}

/// processing posted grades & feedback here
if ($data = data_submitted() and confirm_sesskey() and has_capability('moodle/grade:edit', $context)) {
    $warnings = $report->process_data($data);
} else {
    $warnings = array();
}


// final grades MUST be loaded after the processing
$report->load_users();
$numusers = $report->get_numusers();
$report->load_final_grades();

// AT THIS POINT WE HAVE ACCURATE GRADES FOR DISPLAY

if ($action === 'quick-dump') {
    $report->quick_dump();
}

/// Print header
print_grade_page_head($COURSE->id, 'report', 'LAEgrader', $reportname, false, null, $buttons);

echo $report->group_selector;
echo '<div class="clearer"></div>';
// echo $report->get_toggles_html();

//show warnings if any
foreach($warnings as $warning) {
    notify($warning);
}

$studentsperpage = $report->get_pref('studentsperpage');
// Don't use paging if studentsperpage is empty or 0 at course AND site levels
if (!empty($studentsperpage)) {
    print_paging_bar($numusers, $report->page, $studentsperpage, $report->pbarurl);
}

$reporthtml = '<script src="functions.js" type="text/javascript"></script>';
//$reporthtml .= '<div class="gradeparent">';
$reporthtml .= $report->get_studentnameshtml();
$reporthtml .= $report->get_headerhtml();
$reporthtml .= str_replace('iconsmall','iconsmall iconcenter',$report->get_iconshtml());
//$reporthtml .= $report->get_iconshtml();
$reporthtml .= $report->get_studentshtml();
$reporthtml .= $report->get_rangehtml();
$reporthtml .= $report->get_avghtml(true);
$reporthtml .= $report->get_avghtml();
$reporthtml .= $report->get_endhtml();
//$reporthtml .= '</div>';

//if ($report->frozennamesandheaders) {
    $headerrows = ($USER->gradeediting[$courseid]) ? 2 : 1;
    $headercols = ($report->get_pref('showuseridnumber')) ? 3 : 2;
    $headerinit = "fxheaderInit('user-grades',380," . $headerrows . ',' . $headercols . ');';
    $reporthtml .=
            '<script src="' . $CFG->wwwroot . '/grade/report/LAEgrader/fxHeader_0.3.min.js" type="text/javascript"></script>
            <script type="text/javascript">' .$headerinit . 'fxheader(); </script>';
//}

// print submit button
if ($USER->gradeediting[$course->id]) {
    echo '<form action="index.php" method="post">';
    echo '<div>';
    echo '<input type="hidden" value="'.$courseid.'" name="id" />';
    echo '<input type="hidden" value="'.sesskey().'" name="sesskey" />';
    echo '<input type="hidden" value="grader" name="report"/>';
}

echo $reporthtml;

// print submit button
if ($USER->gradeediting[$course->id] && ($report->get_pref('showquickfeedback') || $report->get_pref('quickgrading'))) {
    echo '<div class="submit"><input type="submit" value="'.get_string('update').'" /></div>';
    echo '</div></form>';
}

// prints paging bar at bottom for large pages
if (!empty($studentsperpage) && $studentsperpage >= 20) {
    print_paging_bar($numusers, $report->page, $studentsperpage, $report->pbarurl);
}

print_footer($course);

// CLAMP # 194 2010-06-23 end
?>
