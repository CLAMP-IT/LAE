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

require_once '../../../config.php';
require_once 'lib.php';
require_once 'grade_import_form.php';

$id = required_param('id', PARAM_INT); // course id

if (!$course = get_record('course', 'id', $id)) {
    print_error('nocourseid');
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $id);
require_capability('moodle/grade:import', $context);
require_capability('gradeimport/xml:view', $context);

// print header
$strgrades = get_string('grades', 'grades');
$actionstr = get_string('modulename', 'gradeimport_xml');

if (!empty($CFG->gradepublishing)) {
    $CFG->gradepublishing = has_capability('gradeimport/xml:publish', $context);
}

$mform = new grade_import_form();

if ($data = $mform->get_data()) {
    // Large files are likely to take their time and memory. Let PHP know
    // that we'll take longer, and that the process should be recycled soon
    // to free up memory.
    @set_time_limit(0);
    @raise_memory_limit("256M");
    if (function_exists('apache_child_terminate')) {
        @apache_child_terminate();
    }

    if ($text = $mform->get_file_content('userfile')) {

        print_grade_page_head($COURSE->id, 'import', 'xml', get_string('importxml', 'grades'));

        $error = '';
        $importcode = import_xml_grades($text, $course, $error);
        if ($importcode) {
            grade_import_commit($id, $importcode, $data->feedback, true);
            print_footer();
            die;
        } else {
            notify($error);
            print_continue($CFG->wwwroot.'/grade/index.php?id='.$course->id);
            print_footer();
            die;
        }

    } else if (empty($data->key)) {
        redirect('import.php?id='.$id.'&amp;feedback='.(int)($data->feedback).'&url='.urlencode($data->url));

    } else {
        if ($data->key == 1) {
            $data->key = create_user_key('grade/import', $USER->id, $course->id, $data->iprestriction, $data->validuntil);
        }

        print_grade_page_head($COURSE->id, 'import', 'xml', get_string('importxml', 'grades'));

        echo '<div class="gradeexportlink">';
        $link = $CFG->wwwroot.'/grade/import/xml/fetch.php?id='.$id.'&amp;feedback='.(int)($data->feedback).'&amp;url='.urlencode($data->url).'&amp;key='.$data->key;
        echo get_string('import', 'grades').': <a href="'.$link.'">'.$link.'</a>';
        echo '</div>';
        print_footer();
        die;
    }
}

print_grade_page_head($COURSE->id, 'import', 'xml', get_string('importxml', 'grades'));

$mform->display();

print_footer();

?>
