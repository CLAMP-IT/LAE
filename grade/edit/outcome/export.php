<?php // $Id$
      // Exports selected outcomes in CSV format. 

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->libdir.'/gradelib.php';

$courseid = optional_param('id', 0, PARAM_INT);
$action   = optional_param('action', '', PARAM_ALPHA);

/// Make sure they can even access this course
if ($courseid) {
    if (!$course = get_record('course', 'id', $courseid)) {
        print_error('nocourseid');
    }
    require_login($course);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('moodle/grade:manage', $context);

    if (empty($CFG->enableoutcomes)) {
        redirect('../../index.php?id='.$courseid);
    }

} else {
    require_once $CFG->libdir.'/adminlib.php';
    admin_externalpage_setup('outcomes');
}

if (!confirm_sesskey()) {
    break;
}
// $outcome = grade_outcome::fetch(array('id'=>$outcomeid));

$systemcontext = get_context_instance(CONTEXT_SYSTEM);

header("Content-Type: text/csv; charset=utf-8");
// TODO: make the filename more useful, include a date, a specific name, something... 
header('Content-Disposition: attachment; filename=outcomes.csv');

// sending header with clear names, to make 'what is what' as easy as possible to understand
$header = array('outcome_name', 'outcome_shortname', 'outcome_description', 'scale_name', 'scale_items', 'scale_description');
echo format_csv($header, ';', '"');

$outcomes = array();
if ( $courseid ) {
    $outcomes = array_merge(grade_outcome::fetch_all_global(), grade_outcome::fetch_all_local($courseid));
} else { 
    $outcomes = grade_outcome::fetch_all_global();
}

foreach($outcomes as $outcome) {

    $line = array();

    $line[] = $outcome->get_name();
    $line[] = $outcome->get_shortname();
    $line[] = $outcome->description;
    
    $scale = $outcome->load_scale();
    $line[] = $scale->get_name();
    $line[] = $scale->compact_items();
    $line[] = $scale->description;
    
    echo format_csv($line, ';', '"');
}

/**
 * Formats and returns a line of data, in CSV format. This code
 * is from http://au2.php.net/manual/en/function.fputcsv.php#77866
 *
 * @params array-of-string $fields data to be exported
 * @params char $delimiter char to be used to separate fields
 * @params char $enclosure char used to enclose strings that contains newlines, spaces, tabs or the delimiter char itself
 * @returns string one line of csv data
 */
function format_csv($fields = array(), $delimiter = ';', $enclosure = '"') {
    $str = '';
    $escape_char = '\\';
    foreach ($fields as $value) {
        if (strpos($value, $delimiter) !== false ||
                strpos($value, $enclosure) !== false ||
                strpos($value, "\n") !== false ||
                strpos($value, "\r") !== false ||
                strpos($value, "\t") !== false ||
                strpos($value, ' ') !== false) {
            $str2 = $enclosure;
            $escaped = 0;
            $len = strlen($value);
            for ($i=0;$i<$len;$i++) {
                if ($value[$i] == $escape_char) {
                    $escaped = 1;
                } else if (!$escaped && $value[$i] == $enclosure) {
                    $str2 .= $enclosure;
                } else {
                    $escaped = 0;
                }
                $str2 .= $value[$i];
            }
            $str2 .= $enclosure;
            $str .= $str2.$delimiter;
        } else {
            $str .= $value.$delimiter;
        }
    }
    $str = substr($str,0,-1);
    $str .= "\n";

    return $str;
}

