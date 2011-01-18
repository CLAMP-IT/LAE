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
require_once $CFG->dirroot.'/grade/report/laegrader/locallib.php';
require_once $CFG->dirroot.'/grade/report/user/lib.php'; 


/**
 * Class providing an API for the user report building and displaying.
 * @uses grade_report
 * @package gradebook
 */
class grade_report_laeuser extends grade_report_user {

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
    function grade_report_laeuser($courseid, $gpr, $context, $userid) {
        global $CFG;
        parent::grade_report($courseid, $gpr, $context);

        $this->showrank        = isset($CFG->grade_report_user_showrank) ? grade_get_setting($this->courseid, 'report_user_showrank', $CFG->grade_report_user_showrank) : 1;
        $this->showpercentage  = isset($CFG->grade_report_user_showpercentage) ? grade_get_setting($this->courseid, 'report_user_showpercentage', $CFG->grade_report_user_showpercentage) : 1;
        $this->showhiddenitems = isset($CFG->grade_report_user_showhiddenitems) ? grade_get_setting($this->courseid, 'report_user_showhiddenitems', $CFG->grade_report_user_showhiddenitems) : 1;
		// HACK to include extra settings in user report
//        $this->showpoints		= isset($CFG->grade_report_laeuser_showpoints) ? grade_get_setting($this->courseid, 'report_laeuser_showpoints', $CFG->grade_report_laeuser_showpoints) : 1;
//        $this->showrange = isset($CFG->grade_report_laeuser_showrange) ? grade_get_setting($this->courseid, 'report_laeuser_showrange', $CFG->grade_report_laeuser_showrange) : 0;
//        $this->showfeedback = isset($CFG->grade_report_laeuser_showfeedback_col) ? grade_get_setting($this->courseid, 'report_laeuser_showfeedback_col', $CFG->grade_report_laeuser_showfeedback_col) : 1;
//        $this->showweight = isset($CFG->grade_report_laeuser_showweight) ? grade_get_setting($this->courseid, 'report_laeuser_showweight', $CFG->grade_report_laeuser_showweight) : 1;
//        $this->showlettergrade = isset($CFG->grade_report_laeuser_showlettergrade) ? grade_get_setting($this->courseid, 'report_laeuser_showlettergrade', $CFG->grade_report_laeuser_showlettergrade) : 1; // END OF HACK
        $this->showpoints		= ($temp = grade_get_setting($this->courseid, 'report_laeuser_showpoints', $CFG->grade_report_laeuser_showpoints)) ? $temp : 0;
        $this->showrange		= ($temp = grade_get_setting($this->courseid, 'report_laeuser_showrange', $CFG->grade_report_laeuser_showrange)) ? $temp : 0;
        $this->showfeedback_col		= ($temp = grade_get_setting($this->courseid, 'report_laeuser_showfeedback_col', $CFG->grade_report_laeuser_showfeedback_col)) ? $temp : 0;
        $this->showweight		= ($temp = grade_get_setting($this->courseid, 'report_laeuser_showweight', $CFG->grade_report_laeuser_showweight)) ? $temp : 0;
        $this->showlettergrade		= ($temp = grade_get_setting($this->courseid, 'report_laeuser_showlettergrade', $CFG->grade_report_laeuser_showlettergrade)) ? $temp : 0;
        $this->showtotalsifcontainhidden = grade_get_setting($this->courseid, 'report_user_showtotalsifcontainhidden', $CFG->grade_report_user_showtotalsifcontainhidden);
        $this->accuratetotals		= ($temp = grade_get_setting($this->courseid, 'report_laegrader_accuratetotals', $CFG->grade_report_laegrader_accuratetotals)) ? $temp : 0;
        
        // HACK to eliminate this being automatically set
//        $this->showrange = true;  // END OF HACK

        $this->switch = grade_get_setting($this->courseid, 'aggregationposition', $CFG->grade_aggregationposition);

        $this->gtree = new grade_tree($this->courseid, false, $this->switch, false, !$CFG->enableoutcomes); // END OF HACK

        // Fill items with parent information needed later
        $this->gtree->parents = array();
        fill_parents($this->gtree->parents, $this->gtree->items, $this->gtree->top_element, $this->gtree->top_element['object']->grade_item->id, $this->accuratetotals, false);

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
        global $CFG, $USER;

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
//            return false;
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
                $data['itemname']['content'] = shorten_text($fullname, 70);
                $data['itemname']['class'] = $class;
			   // left-align the names of items
                $data['itemname']['class'] .= ' left';
     
                $data['itemname']['colspan'] = ($this->maxdepth - $depth);

                /// Actual Grade -- we don't use this in the laeuser report
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
                if ($this->showpoints || $this->showrange) {
                	if (isset($this->gtree->parents[$eid]) && ($type == 'categoryitem' || $type == 'courseitem')) {
						// set up variables that are used in this inserted limit_rules scrap
						$items = $this->gtree->items;
	               		$grade_values = $this->gtree->parents[$eid]->cat_item;
	               		$grade_maxes = $this->gtree->parents[$eid]->cat_max;
	               		$this_cat = $this->gtree->items[$eid]->get_item_category();
	               		$extraused = $this_cat->is_extracredit_used();
				        if (!empty($this_cat->droplow)) {
				            asort($grade_values, SORT_NUMERIC);
				            $dropped = 0;
				            foreach ($grade_values as $itemid=>$value) {
				                if ($dropped < $this_cat->droplow) {
									if ($extraused and $items[$itemid]->aggregationcoef > 0) {
				                        // no drop low for extra credits
				                    } else {
				                        unset($grade_values[$itemid]);
				                        unset($grade_maxes[$itemid]);
				                        $dropped++;
				                    }
				                } else {
				                    // we have dropped enough
				                    break;
				                }
				            }
				
				        } else if (!empty($this_cat->keephigh)) {
				            arsort($grade_values, SORT_NUMERIC);
				            $kept = 0;
				            foreach ($grade_values as $itemid=>$value) {
								if ($extraused and $items[$itemid]->aggregationcoef > 0) {
				                    // we keep all extra credits
				                } else if ($kept < $this_cat->keephigh) {
				                    $kept++;
				                } else {
				                    unset($grade_values[$itemid]);
				                    unset($grade_maxes[$itemid]);
				                }
				            }
			       		}
			       		// never aggregate hidden categories into their parents
						if (! $grade_grade->is_hidden()) {
			       			$this->gtree->parents[$this->gtree->parents[$eid]->id]->cat_item[$eid] = array_sum($grade_values);
							// if we have a point value or if viewing an empty report
			       			if (isset($gradeval) || $this->user->id == $USER->id) {
								$this->gtree->parents[$this->gtree->parents[$eid]->id]->cat_max[$eid] = array_sum($grade_maxes);
			       			}
			       		}
                	} elseif ($type == 'item') {
	                	// if grade isn't hidden and has a value include the item gradeval in for summing (empty reports have no grades)
	              		if (! $grade_grade->is_hidden() && $gradeval <> null && isset($this->gtree->parents[$eid])) {
                			$this->gtree->parents[$this->gtree->parents[$eid]->id]->cat_item[$eid] = $gradeval;
						}
	                	// if grade isn't hidden or null or if viewing an empty report include the item grademax in for summing 
						if ((! $grade_grade->is_hidden() && $gradeval <> null && isset($this->gtree->parents[$eid])) || $this->user->id == $USER->id) {
							$this->gtree->parents[$this->gtree->parents[$eid]->id]->cat_max[$eid] = $grade_grade->grade_item->grademax;
	              		}
	              	}
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
                    } elseif (is_null($gradeval)) {
                        $data['points']['class'] = $class;
                        $data['points']['content'] = '-';
                    } else {
                        $data['points']['class'] = $class;
                        // if a value in the earned_total indicating a category or course item with values accumulated from its children
                        // earned_total never gets created if $this->accuratetotals isn't on
                        if (isset($this->gtree->items[$grade_object->id]->earned_total)) {
                            $tempmax = $grade_grade->grade_item->grademax;
//                            $grade_grade->grade_item->grademax = array_sum($grade_values);
                            $grade_grade->grade_item->grademax = $this->gtree->items[$grade_object->id]->max_earnable;
                            // display based on accumulated total not stored total
                            $data['points']['content'] = grade_format_gradevalue(array_sum($grade_values), $grade_grade->grade_item, true,GRADE_DISPLAY_TYPE_REAL);
//                            $data['points']['content'] = grade_format_gradevalue($this->gtree->items[$grade_object->id]->earned_total, $grade_grade->grade_item, true,GRADE_DISPLAY_TYPE_REAL);
                            $grade_grade->grade_item->grademax = $tempmax;
                            // store points to containing category or course
                            // this isn't done in fill_parents because the laeuser report needs to only figure items marked
                            if ($this->accuratetotals && isset($this->gtree->parents[$grade_object->id]->id)) {
                            	if ($grade_grade->grade_item->aggregationcoef <> 1 || $this->gtree->parents[$grade_object->id]->agg == GRADE_AGGREGATE_WEIGHTED_MEAN) {
                            		$this->gtree->items[$this->gtree->parents[$grade_object->id]->id]->max_earnable += $this->gtree->items[$grade_object->id]->max_earnable;
                            	}
                            	$this->gtree->items[$this->gtree->parents[$grade_object->id]->id]->earned_total += $this->gtree->items[$grade_object->id]->earned_total;
                                // to be included later if we need to perform the calculations for the ultimate grade
/*
                                if ($this->gtree->parents[$grade_object->id]->agg == GRADE_AGGREGATE_WEIGHTED_MEAN) {
                                    $this->gtree->items[$this->gtree->parents[$grade_object->id]->id]->weighted_grade += $this->gtree->items[$grade_object->id]->weighted_grade;
                                } else {
                                    $this->gtree->items[$this->gtree->parents[$grade_object->id]->id]->weighted_grade += $this->gtree->items[$grade_object->id]->weighted_grade;
                                }
 * 
 */
                            }
                        } else {
                            // store points to containing category or course
                            $data['points']['content'] = grade_format_gradevalue($gradeval, $grade_grade->grade_item, true,GRADE_DISPLAY_TYPE_REAL);
                            if ($this->accuratetotals && isset($this->gtree->parents[$grade_object->id]->id)) {
                            	if ($grade_grade->grade_item->aggregationcoef <> 1 || $this->gtree->parents[$grade_object->id]->agg == GRADE_AGGREGATE_WEIGHTED_MEAN) {
	                                $this->gtree->items[$this->gtree->parents[$grade_object->id]->id]->max_earnable += $grade_grade->grade_item->grademax;
                            	}
	                            $this->gtree->items[$this->gtree->parents[$grade_object->id]->id]->earned_total += $gradeval;
                            }
                        }
                    }
                }

                /// Range
                if ($this->showrange) {
                	// include for limit rules, if needed
                	$data['range']['class'] = $class;
                    // if a category or course item
                   	$tempmax = $grade_grade->grade_item->grademax;
                    	
                   	if (isset($grade_maxes) && ($type == 'categoryitem' or $type == 'courseite')) {
                   		$grade_grade->grade_item->grademax = array_sum($grade_maxes);
                   	}
/*
					if (isset($this->gtree->items[$grade_object->id]->max_earnable)) {
				        // imported course from previous term where grademaxes in grade_item have been manipulated
*/
/*
				        if ($gradeval == null) {
                            // if no grades yet the range should reflect the whole category
                            $grade_grade->grade_item->grademax = $this->gtree->items[$grade_object->id]->max_earnable;
                        } elseif ($grade_grade->grade_item->grademax <> $grade_grade->rawgrademax) {
                            // leave $grade_grade->grade_item->grademax alone to be used in the computation
                            $grade_grade->grade_item->grademax = $this->gtree->items[$grade_object->id]->earned_total *
                                    $grade_grade->grade_item->grademax/ $grade_grade->finalgrade;
 * 
                        } else {
                            // if theres already at least one grade marked we want the range to reflect only those items marked
                            $grade_grade->grade_item->grademax = $this->gtree->items[$grade_object->id]->max_earnable;
//                            $grade_grade->grade_item->grademax = $this->gtree->items[$grade_object->id]->earned_total *
//                                    $grade_grade->finalgrade / $grade_grade->grade_item->grademax;
                                    $grade_grade->rawgrademax/ $grade_grade->finalgrade;
//                                    $grade_grade->rawgrademax / $grade_grade->finalgrade;
                        }
 */
/*
					// display based on accumulated total not stored total
                        $data['range']['content'] = $grade_grade->grade_item->get_formatted_range();
                        $grade_grade->grade_item->grademax = $tempmax;
                    }  else {
                        $data['range']['content'] = $grade_grade->grade_item->get_formatted_range();
                    }
*/
                    $data['range']['content'] = $grade_grade->grade_item->get_formatted_range(GRADE_DISPLAY_TYPE_REAL);
                    $grade_grade->grade_item->grademax = $tempmax;
                }

                /// Percentage
                if ($this->showpercentage) {
                    if ($grade_grade->grade_item->needsupdate) {
                        $data['percentage']['class'] = $class.' gradingerror';
                        $data['percentage']['content'] = get_string('error');
                    } elseif ($grade_grade->is_hidden()) {
                        $data['percentage']['class'] = $class.' hidden';
                        $data['percentage']['content'] = '-';
                    } elseif ($grade_object->scaleid <> null) {
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
                    if ($grade_object->aggregationcoef > 0 && $type <> 'courseitem') {
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
                    } elseif ($grade_object->scaleid <> null) {
                        $data['percentage']['class'] = $class.' hidden';
                        $data['percentage']['content'] = '-';
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
                    } elseif (is_null($gradeval)) {
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

            // HACK to display keephigh and droplow category elements, if present
            if ($grade_object->keephigh > 0) {
	            $data['itemname']['content'] .= '<span style="color: red"> (keep highest ' . $grade_object->keephigh . ' scores)</span>';        	
            }
            if ($grade_object->droplow > 0) {
	            $data['itemname']['content'] .= '<span style="color: red"> (drop lowest ' . $grade_object->droplow . ' scores)</span>';        	
            } // END OF HACK
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

    /**
     * Prints or returns the HTML from the flexitable.
     * @param bool $return Whether or not to return the data instead of printing it directly.
     * @return string
     */
    function print_table($return=false) {
         $maxspan = $this->maxdepth;

        /// Build table structure
        $html = "
            <table cellspacing='0' cellpadding='0' class='boxaligncenter generaltable user-grade'>
            <thead>
                <tr>
                    <th class=\"header\" colspan='$maxspan'>".$this->tableheaders[0]."</th>\n";

        for ($i = 1; $i < count($this->tableheaders); $i++) {
        	if(! isset($this->tablehidden[$i])) {
        		$this->tablehidden[$i] = '';
        	}
            $html .= "<th class=\"header" . $this->tablehidden[$i] . "\">".$this->tableheaders[$i]."</th>\n";
        }
/*
        $html .= '</tr>';
        if ($this->canviewhidden) {
            $html .= '<tr>';
            for ($i = 1; $i < count($this->tableheaders); $i++) {
                $html .= "<th class=\"header" . $this->tablehidden[$i] . "\">".$this->tableheaders[$i]."</th>\n";
            }
        }
 * 
 */
        $html .= "
                </tr>
            </thead>
            <tbody>\n";

        /// Print out the table data
        for ($i = 0; $i < count($this->tabledata); $i++) {
            $html .= "<tr>\n";
            if (isset($this->tabledata[$i]['leader'])) {
                $rowspan = $this->tabledata[$i]['leader']['rowspan'];
                $class = $this->tabledata[$i]['leader']['class'];
                $html .= "<td class='$class' rowspan='$rowspan'></td>\n";
            }
            for ($j = 0; $j < count($this->tablecolumns); $j++) {
                $name = $this->tablecolumns[$j];
                $class = (isset($this->tabledata[$i][$name]['class'])) ? $this->tabledata[$i][$name]['class'] : '';
                $colspan = (isset($this->tabledata[$i][$name]['colspan'])) ? "colspan='".$this->tabledata[$i][$name]['colspan']."'" : '';
                $content = (isset($this->tabledata[$i][$name]['content'])) ? $this->tabledata[$i][$name]['content'] : null;
                if (isset($content)) {
                    $html .= "<td class='$class' $colspan>$content</td>\n";
                }
            }
            $html .= "</tr>\n";
        }

        $html .= "</tbody></table>";

        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }



}
    
function grade_report_laeuser_settings_definition(&$mform) {
    global $CFG;

    $options = array(-1 => get_string('default', 'grades'),
                      0 => get_string('hide'),
                      1 => get_string('show'));
    if (empty($CFG->grade_report_laeuser_showpoints)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_laeuser_showpoints', get_string('showpoints', 'gradereport_laeuser'), $options);
    
    if (empty($CFG->grade_report_laeuser_showfeedback_col)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_laeuser_showfeedback_col', get_string('showfeedback_col', 'gradereport_laeuser'), $options);
    
    if (empty($CFG->grade_report_laeuser_showrange)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }
    $mform->addElement('select', 'report_laeuser_showrange', get_string('showrange', 'gradereport_laeuser'), $options);
    
    if (empty($CFG->grade_report_laeuser_showweight)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }
    $mform->addElement('select', 'report_laeuser_showweight', get_string('showweight', 'gradereport_laeuser'), $options);

    if (empty($CFG->grade_report_laeuser_showlettergrade)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }
    $mform->addElement('select', 'report_laeuser_showlettergrade', get_string('showlettergrade', 'gradereport_laeuser'), $options);
}

function grade_report_laeuser_profilereport($course, $user) {
    if (!empty($course->showgrades)) {

        $context = get_context_instance(CONTEXT_COURSE, $course->id);

        //first make sure we have proper final grades - this must be done before constructing of the grade tree
        grade_regrade_final_grades($course->id);

        /// return tracking object
        $gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'laeuser', 'courseid'=>$course->id, 'userid'=>$user->id));
        // Create a report instance
        $report = new grade_report_user($course->id, $gpr, $context, $user->id);

        // print the page
        echo '<div class="grade-report-user">'; // css fix to share styles with real report page
        print_heading(get_string('modulename', 'gradereport_laeuser'). ' - '.fullname($report->user));

        if ($report->fill_table()) {
            echo $report->print_table(true);
        }
        echo '</div>';
    }
}
?>