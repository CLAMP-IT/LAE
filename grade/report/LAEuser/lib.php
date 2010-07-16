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

/**
 * File in which the user_report class is defined.
 * @package gradebook
 */

require_once($CFG->dirroot . '/grade/report/lib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once $CFG->dirroot.'/grade/report/LAEgrader/locallib.php';
require_once $CFG->dirroot.'/grade/report/user/lib.php'; 


/**
 * Class providing an API for the user report building and displaying.
 * @uses grade_report
 * @package gradebook
 */
class grade_report_LAEuser extends grade_report_user {

    /**
     * The user.
     * @var object $user
     */
    var $user;

    /**
     * A flexitable to hold the data.
     * @var object $table
     */
    var $table;

    var $gtree;

    /**
     * Flat structure similar to grade tree
     */
    var $gseq;

    /**
     * show student ranks
     */
    var $showrank;

    /**
     * show grade percentages
     */
    var $showpercentage;

    /**
     * Show range
     */
    var $showrange;

    /**
     * HACK Show points
     */
    var $showpoints; // END OF HACK
    
    var $tableheaders;
    var $tablecolumns;

    var $maxdepth;
    var $evenodd;

    var $tabledata;
    var $canviewhidden;

    var $switch;

    /**
     * Show hidden items even when user does not have required cap
     */
    var $showhiddenitems;

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param int $courseid
     * @param object $gpr grade plugin return tracking object
     * @param string $context
     * @param int $userid The id of the user
     */
    function grade_report_LAEuser($courseid, $gpr, $context, $userid) {
        global $CFG;
        parent::grade_report($courseid, $gpr, $context);

        $this->showrank        = isset($CFG->grade_report_user_showrank) ? grade_get_setting($this->courseid, 'report_user_showrank', $CFG->grade_report_user_showrank) : 1;
        $this->showpercentage  = isset($CFG->grade_report_user_showpercentage) ? grade_get_setting($this->courseid, 'report_user_showpercentage', $CFG->grade_report_user_showpercentage) : 1;
        $this->showhiddenitems = isset($CFG->grade_report_user_showhiddenitems) ? grade_get_setting($this->courseid, 'report_user_showhiddenitems', $CFG->grade_report_user_showhiddenitems) : 1;
		// HACK to include extra settings in user report
//        $this->showpoints		= isset($CFG->grade_report_LAEuser_showpoints) ? grade_get_setting($this->courseid, 'report_LAEuser_showpoints', $CFG->grade_report_LAEuser_showpoints) : 1;
//        $this->showrange = isset($CFG->grade_report_LAEuser_showrange) ? grade_get_setting($this->courseid, 'report_LAEuser_showrange', $CFG->grade_report_LAEuser_showrange) : 0;
//        $this->showfeedback = isset($CFG->grade_report_LAEuser_showfeedback_col) ? grade_get_setting($this->courseid, 'report_LAEuser_showfeedback_col', $CFG->grade_report_LAEuser_showfeedback_col) : 1;
//        $this->showweight = isset($CFG->grade_report_LAEuser_showweight) ? grade_get_setting($this->courseid, 'report_LAEuser_showweight', $CFG->grade_report_LAEuser_showweight) : 1;
//        $this->showlettergrade = isset($CFG->grade_report_LAEuser_showlettergrade) ? grade_get_setting($this->courseid, 'report_LAEuser_showlettergrade', $CFG->grade_report_LAEuser_showlettergrade) : 1; // END OF HACK
        $this->showpoints		= ($temp = grade_get_setting($this->courseid, 'report_LAEuser_showpoints', $CFG->grade_report_LAEuser_showpoints)) ? $temp : 0;
        $this->showrange		= ($temp = grade_get_setting($this->courseid, 'report_LAEuser_showrange', $CFG->grade_report_LAEuser_showrange)) ? $temp : 0;
        $this->showfeedback_col		= ($temp = grade_get_setting($this->courseid, 'report_LAEuser_showfeedback_col', $CFG->grade_report_LAEuser_showfeedback_col)) ? $temp : 0;
        $this->showweight		= ($temp = grade_get_setting($this->courseid, 'report_LAEuser_showweight', $CFG->grade_report_LAEuser_showweight)) ? $temp : 0;
        $this->showlettergrade		= ($temp = grade_get_setting($this->courseid, 'report_LAEuser_showlettergrade', $CFG->grade_report_LAEuser_showlettergrade)) ? $temp : 0;
        $this->showtotalsifcontainhidden = grade_get_setting($this->courseid, 'report_user_showtotalsifcontainhidden', $CFG->grade_report_user_showtotalsifcontainhidden);
        
        // HACK to eliminate this being automatically set
//        $this->showrange = true;  // END OF HACK

        $this->switch = grade_get_setting($this->courseid, 'aggregationposition', $CFG->grade_aggregationposition);

        // Grab the grade_tree for this course
        // HACK instantiate local grade tree
//        $this->gtree = new grade_tree($this->courseid, false, $this->switch, false, !$CFG->enableoutcomes);
        $this->gtree = new grade_tree($this->courseid, false, $this->switch, false, !$CFG->enableoutcomes); // END OF HACK

        // Determine the number of rows and indentation
        $this->maxdepth = 1;
        $this->inject_rowspans($this->gtree->top_element);
        $this->maxdepth++; // Need to account for the lead column that spans all children
        for ($i = 1; $i <= $this->maxdepth; $i++) {
            $this->evenodd[$i] = 0;
        }

        $this->tabledata = array();

        $this->canviewhidden = has_capability('moodle/grade:viewhidden', get_context_instance(CONTEXT_COURSE, $this->courseid));

        // get the user (for full name)
        $this->user = get_record('user', 'id', $userid);

        // base url for sorting by first/last name
        $this->baseurl = $CFG->wwwroot.'/grade/report?id='.$courseid.'&amp;userid='.$userid;
        $this->pbarurl = $this->baseurl;

        // no groups on this report - rank is from all course users
        $this->setup_table();
    }

    /**
     * Prepares the headers and attributes of the flexitable.
     */
    function setup_table() {
        global $CFG;
        /*
         * Table has 5-6 columns
         *| itemname/description | final grade | percentage final grade | rank (optional) | feedback |
         */

        // setting up table headers
        $this->tablecolumns = array('itemname');
        $this->tableheaders = array($this->get_lang_string('gradeitem', 'grades'));

        if ($this->showweight) {
            $this->tablecolumns[] = 'weight';
            $this->tableheaders[] = $this->get_lang_string('weightuc', 'grades');
        }
        if ($this->showpoints) {
            $this->tablecolumns[] = 'points';
            $this->tableheaders[] = $this->get_lang_string('pointsuc', 'grades');
        }
        if ($this->showrange) {
            $this->tablecolumns[] = 'range';
            $this->tableheaders[] = $this->get_lang_string('range', 'grades');
        }

        if ($this->showpercentage) {
            $this->tablecolumns[] = 'percentage';
            $this->tableheaders[] = $this->get_lang_string('percentage', 'grades');
        }

        if ($this->showlettergrade) {
            $this->tablecolumns[] = 'lettergrade';
            $this->tableheaders[] = $this->get_lang_string('lettergrade', 'grades');
        }
        
        if ($this->showrank) {
            // TODO: this is broken if hidden grades present!!
            $this->tablecolumns[] = 'rank';
            $this->tableheaders[] = $this->get_lang_string('rank', 'grades');
        }
        if ($this->showfeedback_col) {
            $this->tablecolumns[] = 'feedback';
            $this->tableheaders[] = $this->get_lang_string('feedback', 'grades');
        }
    }

	function fill_table() {
        //print "<pre>";
        //print_r($this->gtree->top_element);
        $this->fill_table_recursive($this->gtree->top_element);
        //print_r($this->tabledata);
        //print "</pre>";
        return true;
    }

    function fill_table_recursive(&$element) {
        global $CFG;

        $type = $element['type'];
        $depth = $element['depth'];
        $grade_object = $element['object'];
        $eid = $grade_object->id;
        $fullname = $this->gtree->get_element_header($element, true, true, true);
        $data = array();
        $hidden = '';
        $excluded = '';
        $class = '';

        // If this is a hidden grade category, hide it completely from the user
        if ($type == 'category' && $grade_object->is_hidden() && !$this->canviewhidden && (
                $this->showhiddenitems == GRADE_REPORT_USER_HIDE_HIDDEN ||
                ($this->showhiddenitems == GRADE_REPORT_USER_HIDE_UNTIL && !$grade_object->is_hiddenuntil()))) {
            return false;
        }

        if ($type == 'category') {
            $this->evenodd[$depth] = (($this->evenodd[$depth] + 1) % 2);
        }
        $alter = ($this->evenodd[$depth] == 0) ? 'even' : 'odd';

        /// Process those items that have scores associated
        if ($type == 'item' or $type == 'categoryitem' or $type == 'courseitem') {
            if (! $grade_grade = grade_grade::fetch(array('itemid'=>$grade_object->id,'userid'=>$this->user->id))) {
                $grade_grade = new grade_grade();
                $grade_grade->userid = $this->user->id;
                $grade_grade->itemid = $grade_object->id;
            }

            $grade_grade->load_grade_item();

            /// Hidden Items
            if ($grade_grade->grade_item->is_hidden()) {
                $hidden = ' hidden';
            }

            // If this is a hidden grade item, hide it completely from the user.
            if ($grade_grade->is_hidden() && !$this->canviewhidden && (
                    $this->showhiddenitems == GRADE_REPORT_USER_HIDE_HIDDEN ||
                    ($this->showhiddenitems == GRADE_REPORT_USER_HIDE_UNTIL && !$grade_grade->is_hiddenuntil()))) {
                // return false;
            } else {

                /// Excluded Item
                if ($grade_grade->is_excluded()) {
                    $fullname .= ' ['.get_string('excluded', 'grades').']';
                    $excluded = ' excluded';
                }

                /// Other class information
                $class = "$hidden $excluded";
                if ($this->switch) { // alter style based on whether aggregation is first or last
                   $class .= ($type == 'categoryitem' or $type == 'courseitem') ? " ".$alter."d$depth baggt b2b" : " item b1b";
                } else {
                   $class .= ($type == 'categoryitem' or $type == 'courseitem') ? " ".$alter."d$depth baggb" : " item b1b";
                }

                /// Name
                $data['itemname']['content'] = $fullname;
                $data['itemname']['class'] = $class;
			   // left-align the names of items
                $data['itemname']['class'] .= ' left';
     
                $data['itemname']['colspan'] = ($this->maxdepth - $depth);

                /// Actual Grade
                $gradeval = $grade_grade->finalgrade;
                if ($grade_grade->grade_item->needsupdate) {
                    $data['grade']['class'] = $class.' gradingerror';
                    $data['grade']['content'] = get_string('error');
                } else if (!empty($CFG->grade_hiddenasdate) and $grade_grade->get_datesubmitted() and !$this->canviewhidden and $grade_grade->is_hidden()
                       and !$grade_grade->grade_item->is_category_item() and !$grade_grade->grade_item->is_course_item()) {
                    // the problem here is that we do not have the time when grade value was modified, 'timemodified' is general modification date for grade_grades records
                    $class .= ' datesubmitted';
                    $data['grade']['class'] = $class;
                    $data['grade']['content'] = get_string('submittedon', 'grades', userdate($grade_grade->get_datesubmitted(), get_string('strftimedatetimeshort')));

                } elseif ($grade_grade->is_hidden()) {
                    $data['grade']['class'] = $class.' hidden';
                    $data['grade']['content'] = '-';
                } else {
                    $data['grade']['class'] = $class;
                    $gradeval = $this->blank_hidden_total($this->courseid, $grade_grade->grade_item, $gradeval);
                    $data['grade']['content'] = grade_format_gradevalue($gradeval, $grade_grade->grade_item, true);
                }

                if ($this->showpoints) {
                    if ($grade_grade->grade_item->needsupdate) {
                        $data['points']['class'] = $class.' gradingerror';
                        $data['points']['content'] = get_string('error');
                    } elseif (!empty($CFG->grade_hiddenasdate) and $grade_grade->get_datesubmitted() and !$this->canviewhidden and $grade_grade->is_hidden()
                           and !$grade_grade->grade_item->is_category_item() and !$grade_grade->grade_item->is_course_item()) {
                        // the problem here is that we do not have the time when grade value was modified, 'timemodified' is general modification date for grade_grades records
                        $class .= ' datesubmitted';
                        $data['points']['class'] = $class;
                        $data['points']['content'] = get_string('submittedon', 'grades', userdate($grade_grade->get_datesubmitted(), get_string('strftimedatetimeshort')));

                    } elseif ($grade_grade->is_hidden()) {
                        $data['points']['class'] = $class.' hidden';
                        if (!$this->canviewhidden) {
                            $data['points']['content'] = '-';
                        } else {
                            $data['points']['content'] = grade_format_gradevalue($gradeval, $grade_grade->grade_item, true,GRADE_DISPLAY_TYPE_REAL);
                        }
                    } else {
                        $data['points']['class'] = $class;
                        $data['points']['content'] = grade_format_gradevalue($gradeval, $grade_grade->grade_item, true,GRADE_DISPLAY_TYPE_REAL);
                    }
                }

                /// Range
                if ($this->showrange) {
                    $data['range']['class'] = $class;
                    $data['range']['content'] = $grade_grade->grade_item->get_formatted_range();
                }

                /// Percentage
                if ($this->showpercentage) {
                    if ($grade_grade->grade_item->needsupdate) {
                        $data['percentage']['class'] = $class.' gradingerror';
                        $data['percentage']['content'] = get_string('error');
                    } elseif ($grade_grade->is_hidden()) {
                        $data['percentage']['class'] = $class.' hidden';
                        $data['percentage']['content'] = '-';
                    } else {
                        $data['percentage']['class'] = $class;
                        $data['percentage']['content'] = grade_format_gradevalue($gradeval, $grade_grade->grade_item, true, GRADE_DISPLAY_TYPE_PERCENTAGE);
                    }
                }

				if ($this->showweight) {               
					$data['weight']['class'] = $class;
					$data['weight']['content'] = '-';
					// has a weight assigned, might be extra credit
					if ($grade_object->aggregationcoef > 0) { 
//						if($parent_agg == GRADE_AGGREGATE_WEIGHTED_MEAN) {
							// print out the input weight for the category
							$data['weight']['content'] = number_format($grade_object->aggregationcoef,2).'%';
//						}
					}
				}

				/// Lettergrade
                if ($this->showlettergrade) {
                    if ($grade_grade->grade_item->needsupdate) {
                        $data['lettergrade']['class'] = $class.' gradingerror';
                        $data['lettergrade']['content'] = get_string('error');
                    } elseif ($grade_grade->is_hidden()) {
                        $data['lettergrade']['class'] = $class.' hidden';
                        if (!$this->canviewhidden) {
                            $data['lettergrade']['content'] = '-';
                        } else {
                            $data['lettergrade']['content'] = grade_format_gradevalue($gradeval, $grade_grade->grade_item, true, GRADE_DISPLAY_TYPE_LETTER);
                        }
                    } else {
                    	$data['lettergrade']['class'] = $class;
                        $data['lettergrade']['content'] = grade_format_gradevalue($gradeval, $grade_grade->grade_item, true, GRADE_DISPLAY_TYPE_LETTER);
                    }
                } 

                /// Rank
                if ($this->showrank) {
                    // TODO: this is broken if hidden grades present!!
                    if ($grade_grade->grade_item->needsupdate) {
                        $data['rank']['class'] = $class.' gradingerror';
                        $data['rank']['content'] = get_string('error');
                    } elseif ($grade_grade->is_hidden()) {
                        $data['rank']['class'] = $class.' hidden';
                        $data['rank']['content'] = '-';
                    } else if (is_null($gradeval)) {
                        // no grade, no rank
                        $data['rank']['class'] = $class;
                        $data['rank']['content'] = '-';

                    } else {
                        /// find the number of users with a higher grade
                        $sql = "SELECT COUNT(DISTINCT(userid))
                                  FROM {$CFG->prefix}grade_grades
                                 WHERE finalgrade > {$gradeval}
                                       AND itemid = {$grade_grade->grade_item->id}";
                        $rank = count_records_sql($sql) + 1;

                        $data['rank']['class'] = $class;
                        $data['rank']['content'] = "$rank/".$this->get_numusers(false); // total course users
                    }
                }

                /// Feedback
                if ($this->showfeedback_col) { // END OF HACK
                    if ($grade_grade->overridden > 0 AND ($type == 'categoryitem' OR $type == 'courseitem')) {
                        $data['feedback']['class'] = $class.' feedbacktext';
                        $data['feedback']['content'] = 'OVERRIDDEN: ' . format_text($grade_grade->feedback, $grade_grade->feedbackformat);
                    } else if (empty($grade_grade->feedback) or (!$this->canviewhidden and $grade_grade->is_hidden())) {
                        $data['feedback']['class'] = $class.' feedbacktext';
                        $data['feedback']['content'] = '&nbsp;';

                    } else {
                        $data['feedback']['class'] = $class.' feedbacktext';
                        $data['feedback']['content'] = format_text($grade_grade->feedback, $grade_grade->feedbackformat);
                    }
                } 

            }
        }

        /// Category
        if ($type == 'category') {
            $data['leader']['class'] = $class.' '.$alter."d$depth b1t b2b b1l";
            $data['leader']['rowspan'] = $element['rowspan'];

            if ($this->switch) { // alter style based on whether aggregation is first or last
               $data['itemname']['class'] = $class.' '.$alter."d$depth b1b b1t";
            } else {
               $data['itemname']['class'] = $class.' '.$alter."d$depth b2t";
            }
            $data['itemname']['colspan'] = ($this->maxdepth - $depth + count($this->tablecolumns) - 1);
            $data['itemname']['content'] = $fullname;
        }

        /// Add this row to the overall system
        $this->tabledata[] = $data;

        /// Recursively iterate through all child elements
        if (isset($element['children'])) {
            foreach ($element['children'] as $key=>$child) {
                $this->fill_table_recursive($element['children'][$key]);
            }
        }
    }
}
    
function grade_report_LAEuser_settings_definition(&$mform) {
    global $CFG;

    $options = array(-1 => get_string('default', 'grades'),
                      0 => get_string('hide'),
                      1 => get_string('show'));
/*
    if (empty($CFG->grade_report_user_showrank)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_user_showrank', get_string('showrank', 'grades'), $options);
    $mform->setHelpButton('report_user_showrank', array('showrank', get_string('showrank', 'grades'), 'grade'));

    if (empty($CFG->grade_report_user_showpercentage)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_user_showpercentage', get_string('showpercentage', 'grades'), $options);
    $mform->setHelpButton('report_user_showpercentage', array('showpercentage', get_string('showpercentage', 'grades'), 'grade'));
*/
    if (empty($CFG->grade_report_LAEuser_showpoints)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_LAEuser_showpoints', get_string('showpoints', 'grades'), $options);
    
    if (empty($CFG->grade_report_LAEuser_showfeedback_col)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_LAEuser_showfeedback_col', get_string('showfeedback_col', 'grades'), $options);
    
    if (empty($CFG->grade_report_LAEuser_showrange)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }
    $mform->addElement('select', 'report_LAEuser_showrange', get_string('showrange', 'grades'), $options);
    
    if (empty($CFG->grade_report_LAEuser_showweight)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }
    $mform->addElement('select', 'report_LAEuser_showweight', get_string('showweight', 'grades'), $options);

    if (empty($CFG->grade_report_LAEuser_showlettergrade)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }
    $mform->addElement('select', 'report_LAEuser_showlettergrade', get_string('showlettergrade', 'grades'), $options);
/*
    $options = array(-1 => get_string('default', 'grades'),
                      0 => get_string('shownohidden', 'grades'),
                      1 => get_string('showhiddenuntilonly', 'grades'),
                      2 => get_string('showallhidden', 'grades'));

    if (empty($CFG->grade_report_user_showhiddenitems)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[$CFG->grade_report_user_showhiddenitems]);
    }

    $mform->addElement('select', 'report_user_showhiddenitems', get_string('showhiddenitems', 'grades'), $options);
    $mform->setHelpButton('report_user_showhiddenitems', array('showhiddenitems', get_string('showhiddenitems', 'grades'), 'grade'));
*/
}
?>