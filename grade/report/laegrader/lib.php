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


/**
 * File in which the grader_report class is defined.
 * @package gradebook
 */
 
require_once($CFG->dirroot . '/grade/report/lib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once $CFG->dirroot.'/grade/report/LAEgrader/locallib.php';
require_once $CFG->dirroot.'/grade/report/grader/lib.php';

/**
 * Class providing an API for the grader report building and displaying.
 * @uses grade_report
 * @package gradebook
 */
class grade_report_LAEgrader extends grade_report_grader {
    /**
     * The final grades.
     * @var array $grades
     */
    var $grades;

    /**
     * Array of errors for bulk grades updating.
     * @var array $gradeserror
     */
    var $gradeserror = array();

//// SQL-RELATED

    /**
     * The id of the grade_item by which this report will be sorted.
     * @var int $sortitemid
     */
    var $sortitemid;

    /**
     * Sortorder used in the SQL selections.
     * @var int $sortorder
     */
    var $sortorder;

    /**
     * An SQL fragment affecting the search for users.
     * @var string $userselect
     */
    var $userselect;

    /**
     * List of collapsed categories from user preference
     * @var array $collapsed
     */
    var $collapsed;

    /**
     * A count of the rows, used for css classes.
     * @var int $rowcount
     */
    var $rowcount = 0;

    /**
     * Capability check caching
     * */
    var $canviewhidden;

    var $preferences_page=false;

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param int $courseid
     * @param object $gpr grade plugin return tracking object
     * @param string $context
     * @param int $page The current page being viewed (when report is paged)
     * @param int $sortitemid The id of the grade_item by which to sort the table
     */
    function grade_report_LAEgrader($courseid, $gpr, $context, $page=null, $sortitemid=null) {
        global $CFG;
        parent::grade_report($courseid, $gpr, $context, $page);

        $this->canviewhidden = has_capability('moodle/grade:viewhidden', get_context_instance(CONTEXT_COURSE, $this->course->id));
        $this->frozennamesandheaders = isset($CFG->grade_report_grader_frozennamesandheaders) ? grade_get_setting($this->courseid,'grade_report_grader_frozennamesandheaders',$CFG->grade_report_grader_frozennamesandheaders) : 1;

        // load collapsed settings for this report
        // COMMENT OUT THESE LINES, NOT NEEDED
//        if ($collapsed = get_user_preferences('grade_report_grader_collapsed_categories')) {
//            $this->collapsed = unserialize($collapsed);
//        } else {
            $this->collapsed = array('aggregatesonly' => array(), 'gradesonly' => array());
//        }

        if (empty($CFG->enableoutcomes)) {
            $nooutcomes = false;
        } else {
            $nooutcomes = get_user_preferences('grade_report_shownooutcomes');
        }

        // if user report preference set or site report setting set use it, otherwise use course or site setting
        $switch = $this->get_pref('aggregationposition');
        if ($switch == '') {
            $switch = grade_get_setting($this->courseid, 'aggregationposition', $CFG->grade_aggregationposition);
        }

        // Grab the grade_tree for this course
//        $this->gtree = new grade_tree($this->courseid, true, $switch, $this->collapsed, $nooutcomes);
        $this->gtree = new grade_tree_local($this->courseid, false, $switch, $this->collapsed, $nooutcomes); // END OF HACK
        
        
        // HACK: Bob Puffer to store an array of items for which each depends upon in order to calculate
        // CHECK TO SEE IF THIS IS REALLY NEEDED SINCE WE NEED TO WORK BACKWARDS FROM THE ITEM'S PARENT NOT THE ITEM's CHILDREN
        // WE DO USE THIS FOR CALCULATING RANGES, WHICH IS EASIER THAN THE OTHER WAY AROUND
        foreach ($this->gtree->items as $useditem) {
        	$useditem =& $useditem->depends_on();
        } // END OF HACK
        
        $this->sortitemid = $sortitemid;

        // base url for sorting by first/last name
        $studentsperpage = $this->get_pref('studentsperpage');
        $perpage = '';
        $curpage = '';

        if (!empty($studentsperpage)) {
            $perpage = '&amp;perpage='.$studentsperpage;
            $curpage = '&amp;page='.$this->page;
        }
        $this->baseurl = 'index.php?id='.$this->courseid. $perpage.$curpage.'&amp;';

        $this->pbarurl = 'index.php?id='.$this->courseid.$perpage.'&amp;';

        $this->setup_groups();

        $this->setup_sortitemid();
    }

    /**
     * Processes the data sent by the form (grades and feedbacks).
     * Caller is reposible for all access control checks
     * @param array $data form submission (with magic quotes)
     * @return array empty array if success, array of warnings if something fails.
     */
    function process_data($data) {
        $warnings = array();

        $separategroups = false;
        $mygroups       = array();
        if ($this->groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $this->context)) {
            $separategroups = true;
            $mygroups = groups_get_user_groups($this->course->id);
            $mygroups = $mygroups[0]; // ignore groupings
            // reorder the groups fro better perf bellow
            $current = array_search($this->currentgroup, $mygroups);
            if ($current !== false) {
                unset($mygroups[$current]);
                array_unshift($mygroups, $this->currentgroup);
            }
        }

        // always initialize all arrays
        $queue = array();
        foreach ($data as $varname => $postedvalue) {

            $needsupdate = false;

            // skip, not a grade nor feedback
            if (strpos($varname, 'grade') === 0) {
                $data_type = 'grade';
            } else if (strpos($varname, 'feedback') === 0) {
                $data_type = 'feedback';
            } else {
                continue;
            }

            $gradeinfo = explode("_", $varname);
            $userid = clean_param($gradeinfo[1], PARAM_INT);
            $itemid = clean_param($gradeinfo[2], PARAM_INT);

            $oldvalue = $data->{'old'.$varname};

            // was change requested?
            if ($oldvalue == $postedvalue) { // string comparison
                continue;
            }

			// HACK: bob puffer call local object
//            if (!$grade_item = grade_item::fetch(array('id'=>$itemid, 'courseid'=>$this->courseid))) { // we must verify course id here!
            if (!$grade_item = grade_item_local::fetch(array('id'=>$itemid, 'courseid'=>$this->courseid))) { // we must verify course id here!
            	// END OF HACK
                error('Incorrect grade item id');
            }

            // Pre-process grade
            if ($data_type == 'grade') {
                $feedback = false;
                $feedbackformat = false;
                if ($grade_item->gradetype == GRADE_TYPE_SCALE) {
                    if ($postedvalue == -1) { // -1 means no grade
                        $finalgrade = null;
                    } else {
                        $finalgrade = $postedvalue;
                    }
                } else {
		    // HACK: bob puffer to allow calculating grades from input letters
                    $context = get_context_instance(CONTEXT_COURSE, $grade_item->courseid);
		    if ($letters = grade_get_letters($context)) {
			unset($lastitem);
                        foreach ($letters as $used=>$letter) {
                            if (strtoupper($postedvalue) == $letter) {
                                if (isset($lastitem)) {
                                    $postedvalue = $lastitem;
                                } else {
                                    $postedvalue = $grade_item->grademax;
                                }
                                break;
                            } else {
                                    $lastitem = ($used - 1) * .01 * $grade_item->grademax;
                            }
                        }
                    } // END OF HACK
                    $finalgrade = unformat_float($postedvalue);
                }

                $errorstr = '';
                // Warn if the grade is out of bounds.
                if (is_null($finalgrade)) {
                    // ok
                } else {
                    $bounded = $grade_item->bounded_grade($finalgrade);
                    if ($bounded > $finalgrade) {
                        $errorstr = 'lessthanmin';
                    } else if ($bounded < $finalgrade) {
                        $errorstr = 'morethanmax';
                    }
                }
                if ($errorstr) {
                    $user = get_record('user', 'id', $userid, '', '', '', '', 'id, firstname, lastname');
                    $gradestr = new object();
                    $gradestr->username = fullname($user);
                    $gradestr->itemname = $grade_item->get_name();
                    $warnings[] = get_string($errorstr, 'grades', $gradestr);
                }

            } else if ($data_type == 'feedback') {
                $finalgrade = false;
                $trimmed = trim($postedvalue);
                if (empty($trimmed)) {
                     $feedback = NULL;
                } else {
                     $feedback = stripslashes($postedvalue);
                }
            }

            // group access control
            if ($separategroups) {
                // note: we can not use $this->currentgroup because it would fail badly
                //       when having two browser windows each with different group
                $sharinggroup = false;
                foreach($mygroups as $groupid) {
                    if (groups_is_member($groupid, $userid)) {
                        $sharinggroup = true;
                        break;
                    }
                }
                if (!$sharinggroup) {
                    // either group membership changed or somebedy is hacking grades of other group
                    $warnings[] = get_string('errorsavegrade', 'grades');
                    continue;
                }
            }

            $grade_item->update_final_grade($userid, $finalgrade, 'gradebook', $feedback, FORMAT_MOODLE);
        }

        return $warnings;
    }


    function get_studentnameshtml() {
        global $CFG, $USER;
        $studentshtml = '';

        $showuserimage = $this->get_pref('showuserimage');
        $showuseridnumber = $this->get_pref('showuseridnumber');
        $fixedstudents = 0;  ///$this->is_fixed_students();

        $strsortasc   = $this->get_lang_string('sortasc', 'grades');
        $strsortdesc  = $this->get_lang_string('sortdesc', 'grades');
        $strfirstname = $this->get_lang_string('firstname');
        $strlastname  = $this->get_lang_string('lastname');

        if ($this->sortitemid === 'lastname') {
            if ($this->sortorder == 'ASC') {
                $lastarrow = print_arrow('up', $strsortasc, true);
            } else {
                $lastarrow = print_arrow('down', $strsortdesc, true);
            }
        } else {
            $lastarrow = '';
        }

        if ($this->sortitemid === 'firstname') {
            if ($this->sortorder == 'ASC') {
                $firstarrow = print_arrow('up', $strsortasc, true);
            } else {
                $firstarrow = print_arrow('down', $strsortdesc, true);
            }
        } else {
            $firstarrow = '';
        }

        if ($fixedstudents) {
            $studentshtml .= '<div class="left_scroller">
                <table id="fixed_column" class="fixed_grades_column">
                    <tbody class="leftbody">';

            $colspan = 'colspan="2"';
            if ($showuseridnumber) {
                $colspan = 'colspan="3"';
            }

            $levels = count($this->gtree->levels) - 1;


            for ($i = 0; $i < $levels; $i++) {
                $studentshtml .= '
                        <tr class="heading name_row">
                            <td '.$colspan.' class="fixedcolumn cell c0 topleft">&nbsp;</td>
                        </tr>
                        ';
            }

            $studentshtml .= '<tr class="heading"><th id="studentheader" colspan="2" class="header c0" scope="col"><a href="'.$this->baseurl.'&amp;sortitemid=firstname">'
                        . $strfirstname . '</a> '
                        . $firstarrow. '/ <a href="'.$this->baseurl.'&amp;sortitemid=lastname">' . $strlastname . '</a>'. $lastarrow .'</th>';

            if ($showuseridnumber) {
                if ('idnumber' == $this->sortitemid) {
                    if ($this->sortorder == 'ASC') {
                        $idnumberarrow = print_arrow('up', $strsortasc, true);
                    } else {
                        $idnumberarrow = print_arrow('down', $strsortdesc, true);
                    }
                } else {
                    $idnumberarrow = '';
                }
                $studentshtml .= '<th class="header c0 useridnumber" scope="col"><a href="'.$this->baseurl.'&amp;sortitemid=idnumber">'
                        . get_string('idnumber') . '</a> ' . $idnumberarrow . '</th>';
            }

            $studentshtml .= '</tr>';

            if ($USER->gradeediting[$this->courseid]) {
                $studentshtml .= '<tr class="controls"><th class="header c0 controls" scope="row" '.$colspan.'>'.$this->get_lang_string('controls','grades').'</th></tr>';
            }

            $row_classes = array(' even ', ' odd ');

            foreach ($this->users as $userid => $user) {

                $user_pic = null;
                if ($showuserimage) {
                    $user_pic = '<div class="userpic">' . print_user_picture($user, $this->courseid, NULL, 0, true) . "</div>\n";
                }

                //either add a th or a colspan to keep things aligned
                $userreportcell = '';
                $userreportcellcolspan = '';
                if (has_capability('gradereport/user:view', $this->context)) {
                    $a->user = fullname($user);
                    $strgradesforuser = get_string('gradesforuser', 'grades', $a);
                    $userreportcell = '<th class="userreport"><a href="'.$CFG->wwwroot.'/grade/report/user/index.php?id='.$this->courseid.'&amp;userid='.$user->id.'">'
                                    .'<img src="'.$CFG->pixpath.'/t/grades.gif" alt="'.$strgradesforuser.'" title="'.$strgradesforuser.'" /></a></th>';
                } else {
                    $userreportcellcolspan = 'colspan=2';
                }

                $studentshtml .= '<tr class="r'.$this->rowcount++ . $row_classes[$this->rowcount % 2] . '">'
                              .'<th class="c0 user" scope="row" onclick="set_row(this.parentNode.rowIndex);" '.$userreportcellcolspan.' >'.$user_pic
                              .'<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$this->course->id.'">'
                              .fullname($user)."</a>$userreportcell</th>\n";

                if ($showuseridnumber) {
                    $studentshtml .= '<th class="c0 useridnumber" onclick="set_row(this.parentNode.rowIndex);">'. $user->idnumber."</th>\n";
                }
                $studentshtml .= "</tr>\n";
            }

            if ($this->get_pref('showranges')) {
                $studentshtml .= '<tr class="range r'.$this->rowcount++.'">' . '<th class="header c0 range " '.$colspan.' scope="row">'.$this->get_lang_string('range','grades').'</th></tr>';
            }

            // Averages heading

            $straverage_group = get_string('groupavg', 'grades');
            $straverage = get_string('overallaverage', 'grades');
            $showaverages = $this->get_pref('showaverages');
            $showaverages_group = $this->currentgroup && $showaverages;

            if ($showaverages_group) {
                $studentshtml .= '<tr class="groupavg r'.$this->rowcount++.'"><th class="header c0" '.$colspan.'scope="row">'.$straverage_group.'</th></tr>';
            }

            if ($showaverages) {
                $studentshtml .= '<tr class="avg r'.$this->rowcount++.'"><th class="header c0" '.$colspan.'scope="row">'.$straverage.'</th></tr>';
            }

            $studentshtml .= '</tbody>
                </table>
            </div>
            <div class="right_scroller">
                <table id="user-grades" class="">
                    <tbody class="righttest">';

        } else {
//            $studentshtml .= '<table id="user-grades" width="800" style="width:800">
//                                <tbody>';
//            $studentshtml .= '<table id="user-grades" class="gradestable flexible boxaligncenter" width="800" style="width:800">
//                                <tbody>';
            $studentshtml .= '<br /><table id="user-grades" class="gradestable flexible boxaligncenter generaltable"><tbody>';
        }

        return $studentshtml;
    }

    /**
     * Builds and returns the HTML code for the headers.
     * @return string $headerhtml
     */
    function get_headerhtml() {
        global $CFG, $USER;

        $this->rowcount = 0;
        $fixedstudents = 0;  ///$this->is_fixed_students();

		$strsortasc   = $this->get_lang_string('sortasc', 'grades');
		$strsortdesc  = $this->get_lang_string('sortdesc', 'grades');
		$strfirstname = $this->get_lang_string('firstname');
		$strlastname  = $this->get_lang_string('lastname');
		$showuseridnumber = $this->get_pref('showuseridnumber');
		$showuserimage = $this->get_pref('showuserimage');

		if ($this->sortitemid === 'lastname') {
			if ($this->sortorder == 'ASC') {
				$lastarrow = print_arrow('up', $strsortasc, true);
			} else {
				$lastarrow = print_arrow('down', $strsortdesc, true);
			}
		} else {
			$lastarrow = '';
		}

		if ($this->sortitemid === 'firstname') {
			if ($this->sortorder == 'ASC') {
				$firstarrow = print_arrow('up', $strsortasc, true);
			} else {
				$firstarrow = print_arrow('down', $strsortdesc, true);
			}
		} else {
			$firstarrow = '';
		}


        // Prepare Table Headers
        $headerhtml = '';

        $numrows = count($this->gtree->levels);
        $columns_to_unset = array();

        $columncount = 0;

		$headerhtml .= '<tr class="heading r'.$this->rowcount++.'">';
		$colspan = '';
		if ($showuserimage) {
			$whiteline = '<br /><div class="whiteline">___________________________</div>';
		} else {
			$whiteline = '<br /><div class="whiteline">______________________</div>';
		}
		$user_pic = '';
		$name_header = ($columncount == 0) ? ' class="name-header"' : '';
		$output = '';
		if (! $USER->gradeediting[$this->courseid]) {
			$output = '<div class="inlinebutton">';
			// taking target out, will need to add later target="'.$target.'"
			$output .= '<form action="'. $CFG->wwwroot . '/grade/report/LAEgrader/index.php' .'" method="get">';
			$output .= '<div><input type="hidden" name="id" value="'. $this->courseid .'" />';
			$output .= '<div><input type="hidden" name="action" value="quick-dump" />';
			$output .= '<input type="submit" value="Copy to Excel" /></div></form></div>';
		}
		$options = array('id'=>$this->courseid,'action'=>'quick-dump');
		$headerhtml .= '<th class=" header c nameheader'.$columncount++ . '" scope="col" ' . $colspan . '>' . $user_pic
		. $output
		. '<a href="'.$this->baseurl.'&amp;sortitemid=firstname">'
					. $strfirstname . '</a> '
					. $firstarrow. '/ <a href="'.$this->baseurl.'&amp;sortitemid=lastname" width="100px"' . $name_header . '>' . $strlastname . '</a>'. $lastarrow
					. $whiteline
					. '</th><th style="background-color:#F3DFD0"></th>'; // '#F3DFD0' : '#D0DBF3'
		if ($showuseridnumber) {
			if ('idnumber' == $this->sortitemid) {
				if ($this->sortorder == 'ASC') {
					$idnumberarrow = print_arrow('up', $strsortasc, true);
				} else {
					$idnumberarrow = print_arrow('down', $strsortdesc, true);
				}
			} else {
				$idnumberarrow = '';
			}
			$headerhtml .= '<th class="header  c'.$columncount++.' useridnumber" scope="col"><a href="'.$this->baseurl.'&amp;sortitemid=idnumber">'
					. get_string('idnumber') . '</a> ' . $idnumberarrow . '</th>';
		}

		$catcount = 0;
		$catparent = 0;
		foreach ($this->gtree->items as $columnkey => $element) {
			$sort_link = '';
			if (isset($element->id)) {
				$sort_link = $this->baseurl.'&amp;sortitemid=' . $element->id;
			}

			$eid = 'i' . $element->id;
                        $object = $element;
			$type   = (stristr('courseitem,categoryitem',$element->itemtype)) ? $element->itemtype . 'item' : 'item';
			$element->type = $type;
			$itemmodule = null;
			$iteminstance = null;

			$columnclass = 'c' . $columncount++;
			$colspan = '';
			$catlevel = '';

                        $itemmodule = $object->itemmodule;
                        $iteminstance = $object->iteminstance;
			if ($element->id == $this->sortitemid) {
				if ($this->sortorder == 'ASC') {
					$arrow = $this->get_sort_arrow('up', $sort_link);
				} else {
					$arrow = $this->get_sort_arrow('down', $sort_link);
				}
			} else {
				$arrow = $this->get_sort_arrow('move', $sort_link);
			}

			$url = $this->baseurl . '&amp;action=display&target=' . $element->id . '&sesskey=' . sesskey();
			$hidden = '';
			if ($element->is_hidden()) {
				$hidden = ' hidden ';
				$catcolor = '#dfdfdf';
			} else if (isset($element->parent) AND $this->gtree->items[$element->parent]->itemtype <> 'course') {
				// same category as last
				if ($element->parent <> $catparent) {
					$catparent = $element->parent;
					$catcount++;
					$catcolor = ($catcount % 2) ? '#F3DFD0' : '#D0DBF3';
				}
			// category header for last category
			} else if($element->itemtype == 'category') {
			// course item
			} else {
				$catcolor = '#ffffff';
			}
			$headerlink = $this->gtree->get_element_headerLAEgrader($element, true, $this->get_pref('showactivityicons'), false);
			$headerhtml .= '<th class=" '.$columnclass.' '.$type.$catlevel.$hidden.'" style="background-color:' . $catcolor . '" scope="col" onclick="set_col(this.cellIndex)">'
						. $headerlink;

			$new_parent = $this->gtree->items[substr($eid,1,5)]->parent;
			if (isset($this->gtree->items[$new_parent]) AND $this->gtree->items[$new_parent]->agg_method == GRADE_AGGREGATE_WEIGHTED_MEAN) {
				$headerhtml .= '<br /><div class="gradeweight">W=' . number_format($element->aggregationcoef,2) . '	</div>';
			}

			$headerhtml .= $arrow;
			$headerhtml .= '</th>';

		}

		$headerhtml .= '</tr>';

        return $headerhtml;
    }

    /**
     * Builds and return the HTML row of ranges for each column (i.e. range).
     * @return string HTML
     */
    function get_iconshtml() {
        global $USER, $CFG;

        $iconshtml = '';
        if ($USER->gradeediting[$this->courseid]) {

            $iconshtml = '<tr class="controls">';

            $fixedstudents = 0;  /// $this->is_fixed_students();
            $showuseridnumber = $this->get_pref('showuseridnumber');
            $colspan = '';
            $columnadd = '<th></th>';
//            $colspan = 'colspan="2"';
            if ($showuseridnumber) {
                $columnadd = '<th></th><th></th>';
//                $colspan = 'colspan="3"';
            }

            if (!$fixedstudents) {
                $iconshtml .= '<th class="header c0 controls" scope="row" '.$colspan.'>'.$this->get_lang_string('controls','grades').'</th>' . $columnadd;
            }

            $columncount = 0;
            foreach ($this->gtree->items as $itemid=>$unused) {
                // emulate grade element
                $item =& $this->gtree->items[$itemid];

                $eid = $this->gtree->get_item_eid($item);
                $element = $this->gtree->locate_element($eid);

//                $iconshtml .= '<td class="controls cell c'.$columncount++.' icons">' . $this->get_icons($element) . '</td>';
                $iconshtml .= '<td class="controls c'.$columncount++.' icons">' . $this->get_icons($element) . '</td>';
            }
            $iconshtml .= '</tr>';
        }
        return $iconshtml;
    }


    /**
     * Given a grade_category, grade_item or grade_grade, this function
     * figures out the state of the object and builds then returns a div
     * with the icons needed for the grader report.
     *
     * @param object $object
     * @return string HTML
     */
    function get_icons($element) {
        global $CFG, $USER;

        if (!$USER->gradeediting[$this->courseid]) {
            return '<div class="grade_icons" />';
        }

        // Init all icons
        $edit_icon = '';

//        if ($element['type'] != 'categoryitem' && $element['type'] != 'courseitem') {
            $edit_icon             = $this->gtree->get_edit_icon($element, $this->gpr);
//        }

        $edit_calculation_icon = '';
        $show_hide_icon        = '';
        $lock_unlock_icon      = '';

        if (has_capability('moodle/grade:manage', $this->context)) {

            if ($this->get_pref('showcalculations')) {
                $edit_calculation_icon = $this->gtree->get_calculation_icon($element, $this->gpr);
            }

            if ($this->get_pref('showeyecons')) {
               $show_hide_icon = $this->gtree->get_hiding_icon($element, $this->gpr);
            }

            if ($this->get_pref('showlocks')) {
                $lock_unlock_icon = $this->gtree->get_locking_icon($element, $this->gpr);
            }
        }

        return '<div class="grade_icons">'.$edit_icon.$edit_calculation_icon.$show_hide_icon.$lock_unlock_icon.'</div>';
    }




    /**
     * Builds and return the HTML rows of the table (grades headed by student).
     * @return string HTML
     */
    function get_studentshtml() {
        global $CFG, $USER;

        $studentshtml = '';

        $strfeedback  = $this->get_lang_string("feedback");
        $strgrade     = $this->get_lang_string('grade');
        $gradetabindex = 1;
        $numusers      = count($this->users);
        $showuserimage = $this->get_pref('showuserimage');
        $showuseridnumber = $this->get_pref('showuseridnumber');
        $showquickfeedback = $this->get_pref('showquickfeedback');
        $fixedstudents = 0; ///$this->is_fixed_students();
        $quickgrading = $this->get_pref('quickgrading');

        // Preload scale objects for items with a scaleid
        $scales_list = '';
        $tabindices = array();

        foreach ($this->gtree->items as $item) {
            if (!empty($item->scaleid)) {
                $scales_list .= "$item->scaleid,";
            }

            $tabindices[$item->id]['grade'] = $gradetabindex;
            $tabindices[$item->id]['feedback'] = $gradetabindex + $numusers;
            $gradetabindex += $numusers * 2;
        }
        $scales_array = array();

        if (!empty($scales_list)) {
            $scales_list = substr($scales_list, 0, -1);
            $scales_array = get_records_list('scale', 'id', $scales_list);
        }

        $row_classes = array(' even ', ' odd ');

        foreach ($this->users as $userid => $user) {

            if ($this->canviewhidden) {
                $altered = array();
                $unknown = array();
            } else {
                $hiding_affected = grade_grade::get_hiding_affected($this->grades[$userid], $this->gtree->items);
                $altered = $hiding_affected['altered'];
                $unknown = $hiding_affected['unknown'];
                unset($hiding_affected);
            }

            $columncount = 0;
            if ($fixedstudents) {
                $studentshtml .= '<tr class="r'.$this->rowcount++ . $row_classes[$this->rowcount % 2] . '">';
            } else {
                // Student name and link
                $user_pic = null;
                if ($showuserimage) {
                    $user_pic = '<div class="userpic">' . print_user_picture($user, $this->courseid, null, 0, true) . '</div>';
                }

                //we're either going to add a th or a colspan to keep things aligned
                // REMOVING $colspan AS ANYTHING
                $userreportcell = '';
                $userreportcellcolspan = '';

                if (has_capability('gradereport/'.$CFG->grade_profilereport.':view', $this->context)) {
                    $a->user = fullname($user);
                    $strgradesforuser = get_string('gradesforuser', 'grades', $a);
                    $userreportcell = '<td><a href="'.$CFG->wwwroot.'/grade/report/'.$CFG->grade_profilereport.'/index.php?id='.$this->courseid.'&amp;userid='.$user->id.'">'
                                    .'<img class="userreport" src="'.$CFG->pixpath.'/t/grades.gif" alt="'.$strgradesforuser.'" style="align:center" title="'.$strgradesforuser.'" /></a></td>';
                } else {
                    $userreportcell = '<td class="userreport"></td>';
                }
                $studentshtml .= '<tr class="r'.$this->rowcount++ . $row_classes[$this->rowcount % 2] . '">'
                              .'<td class="c'.$columncount++.' user" scope="row" onclick="set_row(this.parentNode.rowIndex);" '.$userreportcellcolspan.' >'.$user_pic
                              .'<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$this->course->id.'">'
                              . $user->firstname . '<br /><div class="lastname">' . $user->lastname . "</a></td>$userreportcell";
//                              .fullname($user)."</a>$userreportcell</th>\n";


                if ($showuseridnumber) {

                    $studentshtml .= '<th class="c'.$columncount++.' useridnumber" onclick="set_row(this.parentNode.rowIndex);">'.
                            $user->idnumber.'</th>';
                }

            }

            // each loop does an item, entire cycle does a user's row
            foreach ($this->gtree->items as $itemid=>$unused) {
                $item =& $this->gtree->items[$itemid];
                $grade = $this->grades[$userid][$item->id];

                // Get the decimal points preference for this item
                $decimalpoints = $item->get_decimals();

                if (in_array($itemid, $unknown)) {
                    $gradeval = null;
                } else if (array_key_exists($itemid, $altered)) {
                    $gradeval = $altered[$itemid];
                } else {
                    $gradeval = $grade->finalgrade;
                }

                // MDL-11274
                // Hide grades in the grader report if the current grader doesn't have 'moodle/grade:viewhidden'
                if (!$this->canviewhidden and $grade->is_hidden()) {
                    if (!empty($CFG->grade_hiddenasdate) and $grade->get_datesubmitted() and !$item->is_category_item() and !$item->is_course_item()) {
                        // the problem here is that we do not have the time when grade value was modified, 'timemodified' is general modification date for grade_grades records
                        $studentshtml .= '<td class="cell c'.$columncount++.'"><span class="datesubmitted">'.userdate($grade->get_datesubmitted(),get_string('strftimedatetimeshort')).'</span></td>';
                    } else {
                        $studentshtml .= '<td class="cell c'.$columncount++.'">-</td>';
                    }
                    continue;
                }

                // emulate grade element
                $eid = $this->gtree->get_grade_eid($grade);
                $element = array('eid'=>$eid, 'object'=>$grade, 'type'=>'grade');

                $cellclasses = 'grade cell c'.$columncount++;
                if ($item->is_category_item()) {
                    $cellclasses .= ' cat';
                    // BOB PUFFER 10/29/09: HACK TO ALLOW ACCURATE CALCULATION OF DISPLAY BASED ON ACCURATE MAXGRADE
                    $item->grademax = $grade->rawgrademax; // END OF HACK
                }
                if ($item->is_course_item()) {
                    $cellclasses .= ' course';
                    // BOB PUFFER 10/29/09: HACK TO ALLOW ACCURATE CALCULATION OF DISPLAY BASED ON ACCURATE MAXGRADE
					$item->grademax = $grade->rawgrademax; // END OF HACK
                }
                if ($grade->is_overridden()) {
                    $cellclasses .= ' overridden';
                }

                if ($grade->is_excluded()) {
                    $cellclasses .= ' excluded';
                }

                $grade_title = '<div class="fullname">'.fullname($user).'</div>';
                $grade_title .= '<div class="itemname">'.$item->get_name(true).'</div>';

                if (!empty($grade->feedback) && !$USER->gradeediting[$this->courseid]) {
                    $grade_title .= '<div class="feedback">'
                                 .wordwrap(trim(format_string($grade->feedback, $grade->feedbackformat)), 34, '<br/ >') . '</div>';
                } else {

                }

                $studentshtml .= '<td class="'.$cellclasses.'" title="'.s($grade_title).'">';

                if ($grade->is_excluded()) {
                    $studentshtml .= '<span class="excludedfloater">'.get_string('excluded', 'grades') . '</span> ';
                }

                // Do not show any icons if no grade (no record in DB to match)
                if (!$item->needsupdate and $USER->gradeediting[$this->courseid]) {
                    $studentshtml .= $this->get_icons($element);
                }

                $hidden = '';
                if ($grade->is_hidden()) {
                    $hidden = ' hidden ';
                }

                $gradepass = ' gradefail ';
                if ($grade->is_passed($item)) {
                    $gradepass = ' gradepass ';
                } elseif (is_null($grade->is_passed($item))) {
                    $gradepass = '';
                }

                // if in editting mode, we need to print either a text box
                // or a drop down (for scales)
                // grades in item of type grade category or course are not directly editable
                if ($item->needsupdate) {
                    $studentshtml .= '<span class="gradingerror'.$hidden.'">'.get_string('error').'</span>';

                } else if ($USER->gradeediting[$this->courseid]) {

                    if ($item->scaleid && !empty($scales_array[$item->scaleid])) {
                        $scale = $scales_array[$item->scaleid];
                        $gradeval = (int)$gradeval; // scales use only integers
                        $scales = explode(",", $scale->scale);
                        // reindex because scale is off 1

                        // MDL-12104 some previous scales might have taken up part of the array
                        // so this needs to be reset
                        $scaleopt = array();
                        $i = 0;
                        foreach ($scales as $scaleoption) {
                            $i++;
                            $scaleopt[$i] = $scaleoption;
                        }

                        if ($quickgrading and $grade->is_editable()) {
                            $oldval = empty($gradeval) ? -1 : $gradeval;
                            if (empty($item->outcomeid)) {
                                $nogradestr = $this->get_lang_string('nograde');
                            } else {
                                $nogradestr = $this->get_lang_string('nooutcome', 'grades');
                            }
                            $studentshtml .= '<input type="hidden" name="oldgrade_'.$userid.'_'
                                          .$item->id.'" value="'.$oldval.'"/>';
                            $studentshtml .= choose_from_menu($scaleopt, 'grade_'.$userid.'_'.$item->id,
                                                              $gradeval, $nogradestr, '', '-1',
                                                              true, false, $tabindices[$item->id]['grade']);
                        } elseif(!empty($scale)) {
                            $scales = explode(",", $scale->scale);

                            // invalid grade if gradeval < 1
                            if ($gradeval < 1) {
                                $studentshtml .= '<span class="gradevalue'.$hidden.$gradepass.'">-</span>';
                            } else {
                                $gradeval = $grade->grade_item->bounded_grade($gradeval); //just in case somebody changes scale
                                $studentshtml .= '<span class="gradevalue'.$hidden.$gradepass.'">'.$scales[$gradeval-1].'</span>';
                            }
                        } else {
                            // no such scale, throw error?
                        }

                    } else if ($item->gradetype != GRADE_TYPE_TEXT) { // Value type
                        if ($quickgrading and $grade->is_editable()) {
                            $value = format_float($gradeval, $decimalpoints);
                            $studentshtml .= '<input type="hidden" name="oldgrade_'.$userid.'_'.$item->id.'" value="'.$value.'" />';
                            $studentshtml .= '<input size="6" tabindex="' . $tabindices[$item->id]['grade']
                                          . '" type="text" title="'. $strgrade .'" name="grade_'
                                          .$userid.'_' .$item->id.'" value="'.$value.'" />';
                        } else {
                            $studentshtml .= '<span class="gradevalue'.$hidden.$gradepass.'">'.format_float($gradeval, $decimalpoints).'</span>';
                        }
                    }


                    // If quickfeedback is on, print an input element
                    if ($showquickfeedback and $grade->is_editable()) {

                        $studentshtml .= '<input type="hidden" name="oldfeedback_'
                                      .$userid.'_'.$item->id.'" value="' . s($grade->feedback) . '" />';
                        $studentshtml .= '<input class="quickfeedback" tabindex="' . $tabindices[$item->id]['feedback']
                                      . '" size="6" title="' . $strfeedback . '" type="text" name="feedback_'
                                      .$userid.'_'.$item->id.'" value="' . s($grade->feedback) . '" />';
                    }

                } else { // Not editing
					
                	$gradedisplaytype = $item->get_displaytype();
                	
                	//HACK
					// Alter the item if the display isn't points
                	if ($gradedisplaytype <> GRADE_DISPLAY_TYPE_REAL) {
/*
                            if ($item->itemtype == 'category') {
                                // TRY THIS OUT AS A METHOD FOR ITEM
                                limit_item($item, $this->gtree->items,$cat_tree);

                            }
*/

                            if (isset($grade->weighted_grade) AND $grade->weighted_grade > 0) {
                                    $gradeval = $grade->weighted_grade * .01 * $item->grademax;
//                                    $gradeval = $item->newgrade / $item->aggtotal * $item->grademax;
//                                    $gradeval = $item->newgrade;
                            }
                        } // END OF HACK
                	
                	
                    // If feedback present, surround grade with feedback tooltip: Open span here

                    if ($item->needsupdate) {
                        $studentshtml .= '<span class="gradingerror'.$hidden.$gradepass.'">'.get_string('error').'</span>';

                    } else {
                        $studentshtml .= '<span class="gradevalue'.$hidden.$gradepass.'">'.grade_format_gradevalue($gradeval, $item, true, $gradedisplaytype, null).'</span>';
                    }
                }

                if (!empty($this->gradeserror[$item->id][$userid])) {
                    $studentshtml .= $this->gradeserror[$item->id][$userid];
                }

                $studentshtml .=  '</td>' . "\n";
            }
            $studentshtml .= '</tr>';
        }
        return $studentshtml;
    }

    /**
     * Builds and return the HTML row of ranges for each column (i.e. range).
     * @return string HTML
     */
    function get_rangehtml() {
        global $USER, $CFG;

        $rangehtml = '';
        if ($this->get_pref('showranges')) {
            $rangesdisplaytype   = $this->get_pref('rangesdisplaytype');
            $rangesdecimalpoints = $this->get_pref('rangesdecimalpoints');

            $columncount=0;
            $rangehtml = '<tr class="range r'.$this->rowcount++.' heading">';

            $fixedstudents = 0;  ///$this->is_fixed_students();
            if (!$fixedstudents) {
                $colspan='colspan="2" ';
                if ($this->get_pref('showuseridnumber')) {
                    $colspan = 'colspan="3" ';
                }
                $rangehtml .= '<th class="header c0 range" '.$colspan.' scope="row">'.$this->get_lang_string('range','grades').'</th>';
            }

            foreach ($this->gtree->items as $itemid=>$unused) {
                $item =& $this->gtree->items[$itemid];

                // HACK: 11/2/09 Bob Puffer to acquire accurate ranges for categories and course total in the grader report 
                // (these are stored accurately in grade_items but that's not normally accessed for displaying ranges in grader (reason unknown)
			
                if ($item->itemtype == 'category' or $item->itemtype == 'course') {
                    // ignore dependson-cache if collapsed category
                    if (!empty($this->collapsed['aggregatesonly']) AND in_array($item->iteminstance,$this->collapsed['aggregatesonly'])) {
                    } else {
                        $item->grademax = 0;
                        foreach ($item->dependson_cache as $dependent) {
                            // HACK to exclude extra credit items from being included in ranges
                            if (isset($this->gtree->items[$dependent]->grademax)
                                AND ! ($unused->agg_method <> GRADE_AGGREGATE_WEIGHTED_MEAN AND $this->gtree->items[$dependent]->aggregationcoef > 0)) {
                                $item->grademax += $this->gtree->items[$dependent]->grademax; // END OF HACK
                            }
                        }
                    }
                } // END OF HACK
                
                $hidden = '';
                if ($item->is_hidden()) {
                    $hidden = ' hidden ';
                }

                $formatted_range = $item->get_formatted_range($rangesdisplaytype, $rangesdecimalpoints);

                $rangehtml .= '<th class="header c'.$columncount++.' range"><span class="rangevalues'.$hidden.'">'. $formatted_range .'</span></th>';

            }
            $rangehtml .= '</tr>';
        }
        return $rangehtml;
    }

    /**
     * we supply the userids in this query, and get all the grades
     * pulls out all the grades, this does not need to worry about paging
     */
     /*
    function load_final_grades() {
        global $CFG;

        // please note that we must fetch all grade_grades fields if we want to contruct grade_grade object from it!
        $sql = "SELECT g.*
                  FROM {$CFG->prefix}grade_items gi,
                       {$CFG->prefix}grade_grades g
                 WHERE g.itemid = gi.id AND gi.courseid = {$this->courseid} {$this->userselect}";

        $userids = array_keys($this->users);


        if ($grades = get_records_sql($sql)) {
            foreach ($grades as $graderec) {
                if (in_array($graderec->userid, $userids) and array_key_exists($graderec->itemid, $this->gtree->items)) { // some items may not be present!!
//                if (in_array($graderec->userid, $userids)) { // some items may not be present!!
                    $this->grades[$graderec->userid][$graderec->itemid] = new grade_grade_local($graderec, false);
                    $this->grades[$graderec->userid][$graderec->itemid]->grade_item =& $this->gtree->items[$graderec->itemid]; // db caching
                }
            }
        }

        // THIS HANDLES EXCLUDED OR DROPPED GRADES (handled in loadstudentshtml()
        // prefil grades that do not exist yet
        foreach ($this->gtree->items as $itemid=>$unused) {
            $parent = $unused->parent;

            // IMPORTANT TO NOTE, we've previously stored agg method for each item with its own record so we must go get the agg method for the item's parent
            $aggmeth = $this->gtree->items[$parent]->agg_method;
            foreach ($userids as $userid) {
                if (!isset($this->grades[$userid][$itemid])) {
                    $this->grades[$userid][$itemid] = new grade_grade();
                    $this->grades[$userid][$itemid]->itemid = $itemid;
                    $this->grades[$userid][$itemid]->userid = $userid;
                    $this->grades[$userid][$itemid]->grade_item =& $unused; // db caching
                // non-excluded items and categories
                } else if(! $this->grades[$userid][$itemid]->excluded) {
                    // if item is weighted then store the weighted "contribution" to the parent's weighted grade
                    // we don't do that with empty grades
                    $weight = ($aggmeth == GRADE_AGGREGATE_WEIGHTED_MEAN) ? $unused->aggregationcoef : (isset($this->grades[$userid][$itemid]->grademax)) ? $this->grades[$userid][$itemid]->grademax : 0;
                    // categoryitem or courseitem
                    if (isset($this->grades[$userid][$itemid]->weighted_grade) AND $this->grades[$userid][$itemid]->weighted_grade > 0) {
                        // consider drop low and keep high
                        limit_item($itemid, $this->gtree->items, $this->grades[$userid]);
                        // scale weighted grade to 100
                        $this->grades[$userid][$itemid]->weighted_grade *= 100 / $this->grades[$userid][$itemid]->totalcoef;

                        $this->grades[$userid][$itemid]->contribution = $this->grades[$userid][$itemid]->weighted_grade *
                                $weight / 100;
                    // item
                    } else {
                        $this->grades[$userid][$itemid]->contribution = $this->grades[$userid][$itemid]->finalgrade/
                                $this->grades[$userid][$itemid]->rawgrademax *
                                $unused->aggregationcoef;
                    }
                    // store the contribution to the parent's weighted_grade value
                    $this->grades[$userid][$parent]->weighted_grade += $this->grades[$userid][$itemid]->contribution;
                    // used for scaling total contributions to 100
                    $this->grades[$userid][$parent]->totalcoef += $weight;
                    // courseitem doesn't have a parent with an agg method
                }
            }
        }
    
}
*/

    /**
     * pulls out the userids of the users to be display, and sorts them
     */
    function load_users() {
        global $CFG;

        if (is_numeric($this->sortitemid)) {
            // the MAX() magic is required in order to please PG
            $sort = "MAX(g.finalgrade) $this->sortorder";

            $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.imagealt, u.picture, u.idnumber
                      FROM {$CFG->prefix}user u
                           JOIN {$CFG->prefix}role_assignments ra ON ra.userid = u.id
                           $this->groupsql
                           LEFT JOIN {$CFG->prefix}grade_grades g ON (g.userid = u.id AND g.itemid = $this->sortitemid)
                     WHERE ra.roleid in ($this->gradebookroles) AND u.deleted = 0
                           $this->groupwheresql
                           AND ra.contextid ".get_related_contexts_string($this->context)."
                  GROUP BY u.id, u.firstname, u.lastname, u.imagealt, u.picture, u.idnumber
                  ORDER BY $sort";

        } else {
            switch($this->sortitemid) {
                case 'lastname':
                    $sort = "u.lastname $this->sortorder, u.firstname $this->sortorder"; break;
                case 'firstname':
                    $sort = "u.firstname $this->sortorder, u.lastname $this->sortorder"; break;
                case 'idnumber':
                default:
                    $sort = "u.idnumber $this->sortorder"; break;
            }

            $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.imagealt, u.picture, u.idnumber
                      FROM {$CFG->prefix}user u
                           JOIN {$CFG->prefix}role_assignments ra ON u.id = ra.userid
                           $this->groupsql
                     WHERE ra.roleid in ($this->gradebookroles)
                           $this->groupwheresql
                           AND ra.contextid ".get_related_contexts_string($this->context)."
                  ORDER BY $sort";
        }


        $this->users = get_records_sql($sql, $this->get_pref('studentsperpage') * $this->page,
                            $this->get_pref('studentsperpage'));

        if (empty($this->users)) {
            $this->userselect = '';
            $this->users = array();
        } else {
            $this->userselect = 'AND g.userid in ('.implode(',', array_keys($this->users)).')';
        }

        return $this->users;
    }


    function quick_dump() {
        global $CFG;
        require_once($CFG->dirroot.'/lib/excellib.class.php');

//        $export_tracking = $this->track_exports();

        $strgrades = get_string('grades');

    /// Calculate file name
        $downloadfilename = clean_filename("{$this->course->shortname} $strgrades.xls");
    /// Creating a workbook
        $workbook = new MoodleExcelWorkbook("-");
    /// Sending HTTP headers
        $workbook->send($downloadfilename);
    /// Adding the worksheet
        $myxls =& $workbook->add_worksheet($strgrades);

    /// Print names of all the fields
        $myxls->write_string(0,0,get_string("firstname"));
        $myxls->write_string(0,1,get_string("lastname"));
        $myxls->write_string(0,2,get_string("idnumber"));
        $myxls->write_string(0,3,get_string("email"));
        $pos=4;
        
        // write out column headers
        foreach ($this->gtree->items as $grade_item) {
//            $myxls->write_string(0, $pos++, $this->format_column_name($grade_item));
            switch ($grade_item->itemtype) {
                    case 'category':
                        $myxls->write_string(0, $pos++, $grade_item->item_category->fullname . ' Category total');
                        break;
                    case 'course':
                        $myxls->write_string(0, $pos++, 'Course total');
                        break;
                    default:
                        $myxls->write_string(0, $pos++, $grade_item->itemname);
            }

            /// add a column_feedback column
            if (isset($this->export_feedback) AND $this->export_feedback) {
//                $myxls->write_string(0, $pos++, $this->format_column_name($grade_item, true));
                $myxls->write_string(0, $pos++, $grade_item->itemname);
            }
        }

        // write out range row
        $myxls->write_string(1, 2, 'Maximum grade->');
        $pos=4;
        foreach ($this->gtree->items as $grade_item) {
//            $myxls->write_string(0, $pos++, $this->format_column_name($grade_item));
            $myxls->write_string(1, $pos++, format_float($grade_item->grademax,0));

            /// add a column_feedback column
            if (isset($this->export_feedback) AND $this->export_feedback) {
//                $myxls->write_string(0, $pos++, $this->format_column_name($grade_item, true));
                $myxls->write_string(0, $pos++, $grade_item->name);
            }
        }

    /// Print all the lines of data.
        $i = 1;
//        $geub = new grade_export_update_buffer();
//        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
//        $gui->init();
        foreach ($this->users as $key=>$user) {
            $i++;
//            $user = $userdata->user;

            $myxls->write_string($i,0,$user->firstname);
            $myxls->write_string($i,1,$user->lastname);
            $myxls->write_string($i,2,$user->idnumber);
            $myxls->write_string($i,3,$user->email);
//           $myxls->write_string($i,3,$user->institution);
//            $myxls->write_string($i,4,$user->department);
//            $myxls->write_string($i,3,$user->email);
            $j=4;
            foreach ($this->gtree->items as $itemid => $item) {
//                if ($export_tracking) {
//                    $status = $geub->track($grade);
//                }
                $grade = $this->grades[$key][$itemid];
                $item->grademax = $this->grades[$key][$itemid]->rawgrademax;
                $item->display = ($item->display == GRADE_DISPLAY_TYPE_DEFAULT) ? GRADE_DISPLAY_TYPE_REAL : $item->display;
                $gradeval = ($item->display <> GRADE_DISPLAY_TYPE_REAL AND $grade->weighted_grade > 0) ? $grade->weighted_grade * $grade->rawgrademax / 100 : $grade->finalgrade;
                $gradestr = grade_format_gradevalue($gradeval, $item, true, $item->display,  2); 
                if (is_numeric($gradestr)) {
                    $myxls->write_number($i,$j++,$gradestr);
                }
                else {
                    $myxls->write_string($i,$j++,$gradestr);
                }
/*
                // writing feedback if requested
                if ($this->export_feedback) {
                    $myxls->write_string($i, $j++, $this->format_feedback($userdata->feedbacks[$itemid]));
                }
 * 
 */
            }
        }
//        $gui->close();
//        $geub->close();

    /// Close the workbook
        $workbook->close();

        exit;
    }

    function grade_format_local($value,$grade_item, $displaytype, $localized, $decimals) {
        switch ($displaytype) {
            case GRADE_DISPLAY_TYPE_REAL:
//                return format_float($value, $decimals, $localized);
                return grade_format_gradevalue_real($value, $grade_item, $decimals, $localized);

            case GRADE_DISPLAY_TYPE_PERCENTAGE:
//                return format_float($value, $decimals, $localized) .'%';
                return grade_format_gradevalue_percentage($value, $grade_item, $decimals, $localized);

            case GRADE_DISPLAY_TYPE_LETTER:
                return grade_format_gradevalue_letter($value, $grade_item);

            case GRADE_DISPLAY_TYPE_REAL_PERCENTAGE:
//                return format_float($value, $decimals, $localized) .'('
//                    . format_float($value, $decimals, $localized) .'%)';

                return grade_format_gradevalue_real($value, $grade_item, $decimals, $localized) . ' (' .
                        grade_format_gradevalue_percentage($value, $grade_item, $decimals, $localized) . ')';

            case GRADE_DISPLAY_TYPE_REAL_LETTER:
//                return format_float($value, $decimals, $localized) .'('
//                    . grade_format_gradevalue_letter($value, $grade_item) .')';
                return grade_format_gradevalue_real($value, $grade_item, $decimals, $localized) . ' (' .
                        grade_format_gradevalue_letter($value, $grade_item) . ')';

            case GRADE_DISPLAY_TYPE_PERCENTAGE_REAL:
//                return format_float($value, $decimals, $localized) .'% ('
//                    . format_float($value, $decimals, $localized) .')';
                return grade_format_gradevalue_percentage($value, $grade_item, $decimals, $localized) . ' (' .
                        grade_format_gradevalue_real($value, $grade_item, $decimals, $localized) . ')';

            case GRADE_DISPLAY_TYPE_LETTER_REAL:
//                return grade_format_gradevalue_letter($value, $grade_item) .'('
//                    . format_float($value, $decimals, $localized) .')';
                return grade_format_gradevalue_letter($value, $grade_item) . ' (' .
                        grade_format_gradevalue_real($value, $grade_item, $decimals, $localized) . ')';

            case GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE:
//                return grade_format_gradevalue_letter($value, $grade_item) .'('
//                    . format_float($value, $decimals, $localized) .'%)';
                return grade_format_gradevalue_letter($value, $grade_item) . ' (' .
                        grade_format_gradevalue_percentage($value, $grade_item, $decimals, $localized) . ')';

            case GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER:
//                return format_float($value, $decimals, $localized) .'% ('
//                    . grade_format_gradevalue_letter($value, $grade_item) .')';
                return grade_format_gradevalue_percentage($value, $grade_item, $decimals, $localized) . ' (' .
                        grade_format_gradevalue_letter($value, $grade_item) . ')';
            default:
                return '';
        }
    }


}

// CLAMP # 194 2010-06-23 end


?>
