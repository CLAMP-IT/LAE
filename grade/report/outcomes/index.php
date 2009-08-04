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

include_once('../../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once $CFG->dirroot.'/grade/lib.php';

$courseid = required_param('id', PARAM_INT);                   // course id

if (!$course = get_record('course', 'id', $courseid)) {
    print_error('nocourseid');
}

require_login($course->id);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('gradereport/outcomes:view', $context);

//first make sure we have proper final grades
grade_regrade_final_grades($courseid);

// Grab all outcomes used in course
$report_info = array();
$outcomes = grade_outcome::fetch_all_available($courseid);

// Get grade_items that use each outcome
foreach ($outcomes as $outcomeid => $outcome) {
    $report_info[$outcomeid]['items'] = get_records_select('grade_items', "outcomeid = $outcomeid AND courseid = $courseid");
    $report_info[$outcomeid]['outcome'] = $outcome;

    // Get average grades for each item
    if (is_array($report_info[$outcomeid]['items'])) {
        foreach ($report_info[$outcomeid]['items'] as $itemid => $item) {
            $sql = "SELECT itemid, AVG(finalgrade) AS avg, COUNT(finalgrade) AS count
                      FROM {$CFG->prefix}grade_grades
                     WHERE itemid = $itemid
                  GROUP BY itemid";
            $info = get_records_sql($sql);

            if (!$info) {
                unset($report_info[$outcomeid]['items'][$itemid]);
                continue;
            } else {
                $info = reset($info);
                $avg = round($info->avg, 2);
                $count = $info->count;
            }

            $report_info[$outcomeid]['items'][$itemid]->avg = $avg;
            $report_info[$outcomeid]['items'][$itemid]->count = $count;
        }
    }
}

$html = '<table class="generaltable boxaligncenter" width="90%" cellspacing="1" cellpadding="5" summary="Outcomes Report">' . "\n";
$html .= '<tr><th class="header c0" scope="col">' . get_string('outcomename', 'grades') . '</th>';
$html .= '<th class="header c1" scope="col">' . get_string('courseavg', 'grades') . '</th>';
$html .= '<th class="header c2" scope="col">' . get_string('sitewide', 'grades') . '</th>';
$html .= '<th class="header c3" scope="col">' . get_string('activities', 'grades') . '</th>';
$html .= '<th class="header c4" scope="col">' . get_string('average', 'grades') . '</th>';
$html .= '<th class="header c5" scope="col">' . get_string('numberofgrades', 'grades') . '</th></tr>' . "\n";

$row = 0;
foreach ($report_info as $outcomeid => $outcomedata) {
    $rowspan = count($outcomedata['items']);
    // If there are no items for this outcome, rowspan will equal 0, which is not good
    if ($rowspan == 0) {
        $rowspan = 1;
    }

    $shortname_html = '<tr class="r' . $row . '"><td class="cell c0" rowspan="' . $rowspan . '">' . $outcomedata['outcome']->shortname . "</td>\n";

    $sitewide = get_string('no');
    if (empty($outcomedata['outcome']->courseid)) {
        $sitewide = get_string('yes');
    }

    $sitewide_html = '<td class="cell c2" rowspan="' . $rowspan . '">' . $sitewide . "</td>\n";

    $outcomedata['outcome']->sum = 0;
    $scale = new grade_scale(array('id' => $outcomedata['outcome']->scaleid), false);

    $print_tr = false;
    $items_html = '';

    if (!empty($outcomedata['items'])) {
        foreach ($outcomedata['items'] as $itemid => $item) {
            if ($print_tr) {
                $row++;
                $items_html .= "<tr class=\"r$row\">\n";
            }

            $grade_item = new grade_item($item, false);

            if ($item->itemtype == 'mod') {
                $cm = get_coursemodule_from_instance($item->itemmodule, $item->iteminstance, $item->courseid);
                $itemname = '<a href="'.$CFG->wwwroot.'/mod/'.$item->itemmodule.'/view.php?id='.$cm->id.'">'.$grade_item->get_name().'</a>';
            } else {
                $itemname = $grade_item->get_name();
            }

            $outcomedata['outcome']->sum += $item->avg;
            $gradehtml = $scale->get_nearest_item($item->avg);

            $items_html .= "<td class=\"cell c3\">$itemname</td>"
                         . "<td class=\"cell c4\">$gradehtml ($item->avg)</td>"
                         . "<td class=\"cell c5\">$item->count</td></tr>\n";
            $print_tr = true;
        }
    } else {
        $items_html .= "<td class=\"cell c3\"> - </td><td class=\"cell c4\"> - </td><td class=\"cell c5\"> 0 </td></tr>\n";
    }

    // Calculate outcome average
    if (is_array($outcomedata['items'])) {
        $count = count($outcomedata['items']);
        if ($count > 0) {
            $avg = $outcomedata['outcome']->sum / $count;
        } else {
            $avg = $outcomedata['outcome']->sum;
        }
        $avg_html = $scale->get_nearest_item($avg) . " (" . round($avg, 2) . ")\n";
    } else {
        $avg_html = ' - ';
    }

    $outcomeavg_html = '<td class="cell c1" rowspan="' . $rowspan . '">' . $avg_html . "</td>\n";

    $html .= $shortname_html . $outcomeavg_html . $sitewide_html . $items_html;
    $row++;
}



$html .= '</table>';

print_grade_page_head($courseid, 'report', 'outcomes');


echo $html;
print_footer($course);

?>
