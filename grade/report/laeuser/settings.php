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

/// Add settings for this module to the $settings object (it's already defined)

$settings->add(new admin_setting_configcheckbox('grade_report_laeuser_showpoints', get_string('showpoints', 'gradereport_laeuser'), get_string('configshowpoints', 'gradereport_laeuser'), 2, PARAM_INT));
$settings->add(new admin_setting_configcheckbox('grade_report_laeuser_showfeedback_col', get_string('showfeedback', 'gradereport_laeuser'), get_string('configshowfeedback_col', 'gradereport_laeuser'), 2, PARAM_INT));
$settings->add(new admin_setting_configcheckbox('grade_report_laeuser_showrange', get_string('showrange', 'gradereport_laeuser'), get_string('configshowrange', 'gradereport_laeuser'), 2, PARAM_INT));
$settings->add(new admin_setting_configcheckbox('grade_report_laeuser_showweight', get_string('showweight', 'gradereport_laeuser'), get_string('configshowweight', 'gradereport_laeuser'), 2, PARAM_INT));
$settings->add(new admin_setting_configcheckbox('grade_report_laeuser_showlettergrade', get_string('showlettergrade', 'gradereport_laeuser'), get_string('configshowlettergrade', 'gradereport_laeuser'), 2, PARAM_INT));
?>
