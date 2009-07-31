<?php // $Id$

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
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

require_once('grade_object.php');

/**
 * Class representing a grade item. It is responsible for handling its DB representation,
 * modifying and returning its metadata.
 */
class grade_item extends grade_object {
    /**
     * DB Table (used by grade_object).
     * @var string $table
     */
    var $table = 'grade_items';

    /**
     * Array of required table fields, must start with 'id'.
     * @var array $required_fields
     */
    var $required_fields = array('id', 'courseid', 'categoryid', 'itemname', 'itemtype', 'itemmodule', 'iteminstance',
                                 'itemnumber', 'iteminfo', 'idnumber', 'calculation', 'gradetype', 'grademax', 'grademin',
                                 'scaleid', 'outcomeid', 'gradepass', 'multfactor', 'plusfactor', 'aggregationcoef',
                                 'sortorder', 'display', 'decimals', 'hidden', 'locked', 'locktime', 'needsupdate', 'timecreated',
                                 'timemodified');

    /**
     * The course this grade_item belongs to.
     * @var int $courseid
     */
    var $courseid;

    /**
     * The category this grade_item belongs to (optional).
     * @var int $categoryid
     */
    var $categoryid;

    /**
     * The grade_category object referenced $this->iteminstance (itemtype must be == 'category' or == 'course' in that case).
     * @var object $item_category
     */
    var $item_category;

    /**
     * The grade_category object referenced by $this->categoryid.
     * @var object $parent_category
     */
    var $parent_category;


    /**
     * The name of this grade_item (pushed by the module).
     * @var string $itemname
     */
    var $itemname;

    /**
     * e.g. 'category', 'course' and 'mod', 'blocks', 'import', etc...
     * @var string $itemtype
     */
    var $itemtype;

    /**
     * The module pushing this grade (e.g. 'forum', 'quiz', 'assignment' etc).
     * @var string $itemmodule
     */
    var $itemmodule;

    /**
     * ID of the item module
     * @var int $iteminstance
     */
    var $iteminstance;

    /**
     * Number of the item in a series of multiple grades pushed by an activity.
     * @var int $itemnumber
     */
    var $itemnumber;

    /**
     * Info and notes about this item.
     * @var string $iteminfo
     */
    var $iteminfo;

    /**
     * Arbitrary idnumber provided by the module responsible.
     * @var string $idnumber
     */
    var $idnumber;

    /**
     * Calculation string used for this item.
     * @var string $calculation
     */
    var $calculation;

    /**
     * Indicates if we already tried to normalize the grade calculation formula.
     * This flag helps to minimize db access when broken formulas used in calculation.
     * @var boolean
     */
    var $calculation_normalized;
    /**
     * Math evaluation object
     */
    var $formula;

    /**
     * The type of grade (0 = none, 1 = value, 2 = scale, 3 = text)
     * @var int $gradetype
     */
    var $gradetype = GRADE_TYPE_VALUE;

    /**
     * Maximum allowable grade.
     * @var float $grademax
     */
    var $grademax = 100;

    /**
     * Minimum allowable grade.
     * @var float $grademin
     */
    var $grademin = 0;

    /**
     * id of the scale, if this grade is based on a scale.
     * @var int $scaleid
     */
    var $scaleid;

    /**
     * A grade_scale object (referenced by $this->scaleid).
     * @var object $scale
     */
    var $scale;

    /**
     * The id of the optional grade_outcome associated with this grade_item.
     * @var int $outcomeid
     */
    var $outcomeid;

    /**
     * The grade_outcome this grade is associated with, if applicable.
     * @var object $outcome
     */
    var $outcome;

    /**
     * grade required to pass. (grademin <= gradepass <= grademax)
     * @var float $gradepass
     */
    var $gradepass = 0;

    /**
     * Multiply all grades by this number.
     * @var float $multfactor
     */
    var $multfactor = 1.0;

    /**
     * Add this to all grades.
     * @var float $plusfactor
     */
    var $plusfactor = 0;

    /**
     * Aggregation coeficient used for weighted averages
     * @var float $aggregationcoef
     */
    var $aggregationcoef = 0;

    /**
     * Sorting order of the columns.
     * @var int $sortorder
     */
    var $sortorder = 0;

    /**
     * Display type of the grades (Real, Percentage, Letter, or default).
     * @var int $display
     */
    var $display = GRADE_DISPLAY_TYPE_DEFAULT;

    /**
     * The number of digits after the decimal point symbol. Applies only to REAL and PERCENTAGE grade display types.
     * @var int $decimals
     */
    var $decimals = null;

    /**
     * 0 if visible, 1 always hidden or date not visible until
     * @var int $hidden
     */
    var $hidden = 0;

    /**
     * Grade item lock flag. Empty if not locked, locked if any value present, usually date when item was locked. Locking prevents updating.
     * @var int $locked
     */
    var $locked = 0;

    /**
     * Date after which the grade will be locked. Empty means no automatic locking.
     * @var int $locktime
     */
    var $locktime = 0;

    /**
     * If set, the whole column will be recalculated, then this flag will be switched off.
     * @var boolean $needsupdate
     */
    var $needsupdate = 1;

    /**
     * Cached dependson array
     */
    var $dependson_cache = null;

    /**
     * In addition to update() as defined in grade_object, handle the grade_outcome and grade_scale objects.
     * Force regrading if necessary, rounds the float numbers using php function,
     * the reason is we need to compare the db value with computed number to skip regrading if possible.
     * @param string $source from where was the object inserted (mod/forum, manual, etc.)
     * @return boolean success
     */
    function update($source=null) {
        // reset caches
        $this->dependson_cache = null;

        // Retrieve scale and infer grademax/min from it if needed
        $this->load_scale();

        // make sure there is not 0 in outcomeid
        if (empty($this->outcomeid)) {
            $this->outcomeid = null;
        }

        if ($this->qualifies_for_regrading()) {
            $this->force_regrading();
        }

        $this->timemodified = time();

        $this->grademin        = grade_floatval($this->grademin);
        $this->grademax        = grade_floatval($this->grademax);
        $this->multfactor      = grade_floatval($this->multfactor);
        $this->plusfactor      = grade_floatval($this->plusfactor);
        $this->aggregationcoef = grade_floatval($this->aggregationcoef);

        return parent::update($source);
    }

    /**
     * Compares the values held by this object with those of the matching record in DB, and returns
     * whether or not these differences are sufficient to justify an update of all parent objects.
     * This assumes that this object has an id number and a matching record in DB. If not, it will return false.
     * @return boolean
     */
    function qualifies_for_regrading() {
        if (empty($this->id)) {
            return false;
        }

        $db_item = new grade_item(array('id' => $this->id));

        $calculationdiff = $db_item->calculation != $this->calculation;
        $categorydiff    = $db_item->categoryid  != $this->categoryid;
        $gradetypediff   = $db_item->gradetype   != $this->gradetype;
        $scaleiddiff     = $db_item->scaleid     != $this->scaleid;
        $outcomeiddiff   = $db_item->outcomeid   != $this->outcomeid;
        $locktimediff    = $db_item->locktime    != $this->locktime;
        $grademindiff    = grade_floats_different($db_item->grademin,        $this->grademin);
        $grademaxdiff    = grade_floats_different($db_item->grademax,        $this->grademax);
        $multfactordiff  = grade_floats_different($db_item->multfactor,      $this->multfactor);
        $plusfactordiff  = grade_floats_different($db_item->plusfactor,      $this->plusfactor);
        $acoefdiff       = grade_floats_different($db_item->aggregationcoef, $this->aggregationcoef);

        $needsupdatediff = !$db_item->needsupdate &&  $this->needsupdate;    // force regrading only if setting the flag first time
        $lockeddiff      = !empty($db_item->locked) && empty($this->locked); // force regrading only when unlocking

        return ($calculationdiff || $categorydiff || $gradetypediff || $grademaxdiff || $grademindiff || $scaleiddiff
             || $outcomeiddiff || $multfactordiff || $plusfactordiff || $needsupdatediff
             || $lockeddiff || $acoefdiff || $locktimediff);
    }

    /**
     * Finds and returns a grade_item instance based on params.
     * @static
     *
     * @param array $params associative arrays varname=>value
     * @return object grade_item instance or false if none found.
     */
    function fetch($params) {
        return grade_object::fetch_helper('grade_items', 'grade_item', $params);
    }

    /**
     * Finds and returns all grade_item instances based on params.
     * @static
     *
     * @param array $params associative arrays varname=>value
     * @return array array of grade_item insatnces or false if none found.
     */
    function fetch_all($params) {
        return grade_object::fetch_all_helper('grade_items', 'grade_item', $params);
    }

    /**
     * Delete all grades and force_regrading of parent category.
     * @param string $source from where was the object deleted (mod/forum, manual, etc.)
     * @return boolean success
     */
    function delete($source=null) {
        $this->delete_all_grades($source);
        return parent::delete($source);
    }

    /**
     * Delete all grades
     * @param string $source from where was the object deleted (mod/forum, manual, etc.)
     * @return boolean success
     */
    function delete_all_grades($source=null) {
        if (!$this->is_course_item()) {
            $this->force_regrading();
        }

        if ($grades = grade_grade::fetch_all(array('itemid'=>$this->id))) {
            foreach ($grades as $grade) {
                $grade->delete($source);
            }
        }

        return true;
    }

    /**
     * In addition to perform parent::insert(), calls force_regrading() method too.
     * @param string $source from where was the object inserted (mod/forum, manual, etc.)
     * @return int PK ID if successful, false otherwise
     */
    function insert($source=null) {
        global $CFG;

        if (empty($this->courseid)) {
            error('Can not insert grade item without course id!');
        }

        // load scale if needed
        $this->load_scale();

        // add parent category if needed
        if (empty($this->categoryid) and !$this->is_course_item() and !$this->is_category_item()) {
            $course_category = grade_category::fetch_course_category($this->courseid);
            $this->categoryid = $course_category->id;

        }

        // always place the new items at the end, move them after insert if needed
        $last_sortorder = get_field_select('grade_items', 'MAX(sortorder)', "courseid = {$this->courseid}");
        if (!empty($last_sortorder)) {
            $this->sortorder = $last_sortorder + 1;
        } else {
            $this->sortorder = 1;
        }

        // add proper item numbers to manual items
        if ($this->itemtype == 'manual') {
            if (empty($this->itemnumber)) {
                $this->itemnumber = 0;
            }
        }

        // make sure there is not 0 in outcomeid
        if (empty($this->outcomeid)) {
            $this->outcomeid = null;
        }

        $this->timecreated = $this->timemodified = time();

        if (parent::insert($source)) {
            // force regrading of items if needed
            $this->force_regrading();
            return $this->id;

        } else {
            debugging("Could not insert this grade_item in the database!");
            return false;
        }
    }

    /**
     * Set idnumber of grade item, updates also course_modules table
     * @param string $idnumber (without magic quotes)
     * @return boolean success
     */
    function add_idnumber($idnumber) {
        if (!empty($this->idnumber)) {
            return false;
        }

        if ($this->itemtype == 'mod' and !$this->is_outcome_item()) {
            if (!$cm = get_coursemodule_from_instance($this->itemmodule, $this->iteminstance, $this->courseid)) {
                return false;
            }
            if (!empty($cm->idnumber)) {
                return false;
            }
            if (set_field('course_modules', 'idnumber', addslashes($idnumber), 'id', $cm->id)) {
                $this->idnumber = $idnumber;
                return $this->update();
            }
            return false;

        } else {
            $this->idnumber = $idnumber;
            return $this->update();
        }
    }

    /**
     * Returns the locked state of this grade_item (if the grade_item is locked OR no specific
     * $userid is given) or the locked state of a specific grade within this item if a specific
     * $userid is given and the grade_item is unlocked.
     *
     * @param int $userid
     * @return boolean Locked state
     */
    function is_locked($userid=NULL) {
        if (!empty($this->locked)) {
            return true;
        }

        if (!empty($userid)) {
            if ($grade = grade_grade::fetch(array('itemid'=>$this->id, 'userid'=>$userid))) {
                $grade->grade_item =& $this; // prevent db fetching of cached grade_item
                return $grade->is_locked();
            }
        }

        return false;
    }

    /**
     * Locks or unlocks this grade_item and (optionally) all its associated final grades.
     * @param int $locked 0, 1 or a timestamp int(10) after which date the item will be locked.
     * @param boolean $cascade lock/unlock child objects too
     * @param boolean $refresh refresh grades when unlocking
     * @return boolean true if grade_item all grades updated, false if at least one update fails
     */
    function set_locked($lockedstate, $cascade=false, $refresh=true) {
        if ($lockedstate) {
        /// setting lock
            if ($this->needsupdate) {
                return false; // can not lock grade without first having final grade
            }

            $this->locked = time();
            $this->update();

            if ($cascade) {
                $grades = $this->get_final();
                foreach($grades as $g) {
                    $grade = new grade_grade($g, false);
                    $grade->grade_item =& $this;
                    $grade->set_locked(1, null, false);
                }
            }

            return true;

        } else {
        /// removing lock
            if (!empty($this->locked) and $this->locktime < time()) {
                //we have to reset locktime or else it would lock up again
                $this->locktime = 0;
            }

            $this->locked = 0;
            $this->update();

            if ($cascade) {
                if ($grades = grade_grade::fetch_all(array('itemid'=>$this->id))) {
                    foreach($grades as $grade) {
                        $grade->grade_item =& $this;
                        $grade->set_locked(0, null, false);
                    }
                }
            }

            if ($refresh) {
                //refresh when unlocking
                $this->refresh_grades();
            }

            return true;
        }
    }

    /**
     * Lock the grade if needed - make sure this is called only when final grades are valid
     */
    function check_locktime() {
        if (!empty($this->locked)) {
            return; // already locked
        }

        if ($this->locktime and $this->locktime < time()) {
            $this->locked = time();
            $this->update('locktime');
        }
    }

    /**
     * Set the locktime for this grade item.
     *
     * @param int $locktime timestamp for lock to activate
     * @return void
     */
    function set_locktime($locktime) {
        $this->locktime = $locktime;
        $this->update();
    }

    /**
     * Set the locktime for this grade item.
     *
     * @return int $locktime timestamp for lock to activate
     */
    function get_locktime() {
        return $this->locktime;
    }

    /**
     * Returns the hidden state of this grade_item
     * @return boolean hidden state
     */
    function is_hidden() {
        return ($this->hidden == 1 or ($this->hidden != 0 and $this->hidden > time()));
    }

    /**
     * Check grade hidden status. Uses data from both grade item and grade.
     * @return boolean true if hiddenuntil, false if not
     */
    function is_hiddenuntil() {
        return $this->hidden > 1;
    }

    /**
     * Check grade item hidden status.
     * @return int 0 means visible, 1 hidden always, timestamp hidden until
     */
    function get_hidden() {
        return $this->hidden;
    }

    /**
     * Set the hidden status of grade_item and all grades, 0 mean visible, 1 always hidden, number means date to hide until.
     * @param int $hidden new hidden status
     * @param boolean $cascade apply to child objects too
     * @return void
     */
    function set_hidden($hidden, $cascade=false) {
        $this->hidden = $hidden;
        $this->update();

        if ($cascade) {
            if ($grades = grade_grade::fetch_all(array('itemid'=>$this->id))) {
                foreach($grades as $grade) {
                    $grade->grade_item =& $this;
                    $grade->set_hidden($hidden, $cascade);
                }
            }
        }
    }

    /**
     * Returns the number of grades that are hidden.
     * @param return int Number of hidden grades
     */
    function has_hidden_grades($groupsql="", $groupwheresql="") {
        global $CFG;
        return get_field_sql("SELECT COUNT(*) FROM {$CFG->prefix}grade_grades g LEFT JOIN "
                            ."{$CFG->prefix}user u ON g.userid = u.id $groupsql WHERE itemid = $this->id AND hidden = 1 $groupwheresql");
    }

    /**
     * Mark regrading as finished successfully.
     */
    function regrading_finished() {
        $this->needsupdate = 0;
        //do not use $this->update() because we do not want this logged in grade_item_history
        set_field('grade_items', 'needsupdate', 0, 'id', $this->id);
    }

    /**
     * Performs the necessary calculations on the grades_final referenced by this grade_item.
     * Also resets the needsupdate flag once successfully performed.
     *
     * This function must be used ONLY from lib/gradeslib.php/grade_regrade_final_grades(),
     * because the regrading must be done in correct order!!
     *
     * @return boolean true if ok, error string otherwise
     */
    function regrade_final_grades($userid=null) {
        global $CFG;

        // locked grade items already have correct final grades
        if ($this->is_locked()) {
            return true;
        }

        // calculation produces final value using formula from other final values
        if ($this->is_calculated()) {
            if ($this->compute($userid)) {
                return true;
            } else {
                return "Could not calculate grades for grade item"; // TODO: improve and localize
            }

        // noncalculated outcomes already have final values - raw grades not used
        } else if ($this->is_outcome_item()) {
            return true;

        // aggregate the category grade
        } else if ($this->is_category_item() or $this->is_course_item()) {
            // aggregate category grade item
            $category = $this->get_item_category();
            $category->grade_item =& $this;
            if ($category->generate_grades($userid)) {
                return true;
            } else {
                return "Could not aggregate final grades for category:".$this->id; // TODO: improve and localize
            }

        } else if ($this->is_manual_item()) {
            // manual items track only final grades, no raw grades
            return true;

        } else if (!$this->is_raw_used()) {
            // hmm - raw grades are not used- nothing to regrade
            return true;
        }

        // normal grade item - just new final grades
        $result = true;
        $grade_inst = new grade_grade();
        $fields = implode(',', $grade_inst->required_fields);
        if ($userid) {
            $rs = get_recordset_select('grade_grades', "itemid={$this->id} AND userid=$userid", '', $fields);
        } else {
            $rs = get_recordset('grade_grades', 'itemid', $this->id, '', $fields);
        }
        if ($rs) {
            while ($grade_record = rs_fetch_next_record($rs)) {
                $grade = new grade_grade($grade_record, false);

                if (!empty($grade_record->locked) or !empty($grade_record->overridden)) {
                    // this grade is locked - final grade must be ok
                    continue;
                }

                $grade->finalgrade = $this->adjust_raw_grade($grade->rawgrade, $grade->rawgrademin, $grade->rawgrademax);

                if (grade_floats_different($grade_record->finalgrade, $grade->finalgrade)) {
                    if (!$grade->update('system')) {
                        $result = "Internal error updating final grade";
                    }
                }
            }
            rs_close($rs);
        }

        return $result;
    }

    /**
     * Given a float grade value or integer grade scale, applies a number of adjustment based on
     * grade_item variables and returns the result.
     * @param float $rawgrade The raw grade value.
     * @param float $rawmin original rawmin
     * @param float $rawmax original rawmax
     * @return mixed
     */
    function adjust_raw_grade($rawgrade, $rawmin, $rawmax) {
        if (is_null($rawgrade)) {
            return null;
        }

        if ($this->gradetype == GRADE_TYPE_VALUE) { // Dealing with numerical grade

            if ($this->grademax < $this->grademin) {
                return null;
            }

            if ($this->grademax == $this->grademin) {
                return $this->grademax; // no range
            }

            // Standardise score to the new grade range
            // NOTE: this is not compatible with current assignment grading
            if ($this->itemmodule != 'assignment' and ($rawmin != $this->grademin or $rawmax != $this->grademax)) {
                $rawgrade = grade_grade::standardise_score($rawgrade, $rawmin, $rawmax, $this->grademin, $this->grademax);
            }

            // Apply other grade_item factors
            $rawgrade *= $this->multfactor;
            $rawgrade += $this->plusfactor;

            return $this->bounded_grade($rawgrade);

        } else if ($this->gradetype == GRADE_TYPE_SCALE) { // Dealing with a scale value
            if (empty($this->scale)) {
                $this->load_scale();
            }

            if ($this->grademax < 0) {
                return null; // scale not present - no grade
            }

            if ($this->grademax == 0) {
                return $this->grademax; // only one option
            }

            // Convert scale if needed
            // NOTE: this is not compatible with current assignment grading
            if ($this->itemmodule != 'assignment' and ($rawmin != $this->grademin or $rawmax != $this->grademax)) {
                $rawgrade = grade_grade::standardise_score($rawgrade, $rawmin, $rawmax, $this->grademin, $this->grademax);
            }

            return $this->bounded_grade($rawgrade);


        } else if ($this->gradetype == GRADE_TYPE_TEXT or $this->gradetype == GRADE_TYPE_NONE) { // no value
            // somebody changed the grading type when grades already existed
            return null;

        } else {
            debugging("Unknown grade type");
            return null;
        }
    }

    /**
     * Sets this grade_item's needsupdate to true. Also marks the course item as needing update.
     * @return void
     */
    function force_regrading() {
        $this->needsupdate = 1;
        //mark this item and course item only - categories and calculated items are always regraded
        $wheresql = "(itemtype='course' OR id={$this->id}) AND courseid={$this->courseid}";
        set_field_select('grade_items', 'needsupdate', 1, $wheresql);
    }

    /**
     * Instantiates a grade_scale object whose data is retrieved from the DB,
     * if this item's scaleid variable is set.
     * @return object grade_scale or null if no scale used
     */
    function load_scale() {
        if ($this->gradetype != GRADE_TYPE_SCALE) {
            $this->scaleid = null;
        }

        if (!empty($this->scaleid)) {
            //do not load scale if already present
            if (empty($this->scale->id) or $this->scale->id != $this->scaleid) {
                $this->scale = grade_scale::fetch(array('id'=>$this->scaleid));
                if (!$this->scale) {
                    debugging('Incorrect scale id: '.$this->scaleid);
                    $this->scale = null;
                    return null;
                }
                $this->scale->load_items();
            }

            // Until scales are uniformly set to min=0 max=count(scaleitems)-1 throughout Moodle, we
            // stay with the current min=1 max=count(scaleitems)
            $this->grademax = count($this->scale->scale_items);
            $this->grademin = 1;

        } else {
            $this->scale = null;
        }

        return $this->scale;
    }

    /**
     * Instantiates a grade_outcome object whose data is retrieved from the DB,
     * if this item's outcomeid variable is set.
     * @return object grade_outcome
     */
    function load_outcome() {
        if (!empty($this->outcomeid)) {
            $this->outcome = grade_outcome::fetch(array('id'=>$this->outcomeid));
        }
        return $this->outcome;
    }

    /**
    * Returns the grade_category object this grade_item belongs to (referenced by categoryid)
    * or category attached to category item.
    *
    * @return mixed grade_category object if applicable, false if course item
    */
    function get_parent_category() {
        if ($this->is_category_item() or $this->is_course_item()) {
            return $this->get_item_category();

        } else {
            return grade_category::fetch(array('id'=>$this->categoryid));
        }
    }

    /**
     * Calls upon the get_parent_category method to retrieve the grade_category object
     * from the DB and assigns it to $this->parent_category. It also returns the object.
     * @return object Grade_category
     */
    function load_parent_category() {
        if (empty($this->parent_category->id)) {
            $this->parent_category = $this->get_parent_category();
        }
        return $this->parent_category;
    }

    /**
    * Returns the grade_category for category item
    *
    * @return mixed grade_category object if applicable, false otherwise
    */
    function get_item_category() {
        if (!$this->is_course_item() and !$this->is_category_item()) {
            return false;
        }
        return grade_category::fetch(array('id'=>$this->iteminstance));
    }

    /**
     * Calls upon the get_item_category method to retrieve the grade_category object
     * from the DB and assigns it to $this->item_category. It also returns the object.
     * @return object Grade_category
     */
    function load_item_category() {
        if (empty($this->category->id)) {
            $this->item_category = $this->get_item_category();
        }
        return $this->item_category;
    }

    /**
     * Is the grade item associated with category?
     * @return boolean
     */
    function is_category_item() {
        return ($this->itemtype == 'category');
    }

    /**
     * Is the grade item associated with course?
     * @return boolean
     */
    function is_course_item() {
        return ($this->itemtype == 'course');
    }

    /**
     * Is this a manualy graded item?
     * @return boolean
     */
    function is_manual_item() {
        return ($this->itemtype == 'manual');
    }

    /**
     * Is this an outcome item?
     * @return boolean
     */
    function is_outcome_item() {
        return !empty($this->outcomeid);
    }

    /**
     * Is the grade item external - associated with module, plugin or something else?
     * @return boolean
     */
    function is_external_item() {
        return ($this->itemtype == 'mod');
    }

    /**
     * Is the grade item overridable
     * @return boolean
     */
    function is_overridable_item() {
        return !$this->is_outcome_item() and ($this->is_external_item() or $this->is_calculated() or $this->is_course_item() or $this->is_category_item());
    }

    /**
     * Is the grade item feedback overridable
     * @return boolean
     */
    function is_overridable_item_feedback() {
        return !$this->is_outcome_item() and $this->is_external_item();
    }

    /**
     * Returns true if grade items uses raw grades
     * @return boolean
     */
    function is_raw_used() {
        return ($this->is_external_item() and !$this->is_calculated() and !$this->is_outcome_item());
    }

    /**
     * Returns grade item associated with the course
     * @param int $courseid
     * @return course item object
     */
    function fetch_course_item($courseid) {
        if ($course_item = grade_item::fetch(array('courseid'=>$courseid, 'itemtype'=>'course'))) {
            return $course_item;
        }

        // first get category - it creates the associated grade item
        $course_category = grade_category::fetch_course_category($courseid);
        return $course_category->get_grade_item();
    }

    /**
     * Is grading object editable?
     * @return boolean
     */
    function is_editable() {
        return true;
    }

    /**
     * Checks if grade calculated. Returns this object's calculation.
     * @return boolean true if grade item calculated.
     */
    function is_calculated() {
        if (empty($this->calculation)) {
            return false;
        }

        /*
         * The main reason why we use the ##gixxx## instead of [[idnumber]] is speed of depends_on(),
         * we would have to fetch all course grade items to find out the ids.
         * Also if user changes the idnumber the formula does not need to be updated.
         */

        // first detect if we need to change calculation formula from [[idnumber]] to ##giXXX## (after backup, etc.)
        if (!$this->calculation_normalized and strpos($this->calculation, '[[') !== false) {
            $this->set_calculation($this->calculation);
        }

        return !empty($this->calculation);
    }

    /**
     * Returns calculation string if grade calculated.
     * @return mixed string if calculation used, null if not
     */
    function get_calculation() {
        if ($this->is_calculated()) {
            return grade_item::denormalize_formula($this->calculation, $this->courseid);

        } else {
            return NULL;
        }
    }

    /**
     * Sets this item's calculation (creates it) if not yet set, or
     * updates it if already set (in the DB). If no calculation is given,
     * the calculation is removed.
     * @param string $formula string representation of formula used for calculation
     * @return boolean success
     */
    function set_calculation($formula) {
        $this->calculation = grade_item::normalize_formula($formula, $this->courseid);
        $this->calculation_normalized = true;
        return $this->update();
    }

    /**
     * Denormalizes the calculation formula to [idnumber] form
     * @static
     * @param string $formula
     * @return string denormalized string
     */
    function denormalize_formula($formula, $courseid) {
        if (empty($formula)) {
            return '';
        }

        // denormalize formula - convert ##giXX## to [[idnumber]]
        if (preg_match_all('/##gi(\d+)##/', $formula, $matches)) {
            foreach ($matches[1] as $id) {
                if ($grade_item = grade_item::fetch(array('id'=>$id, 'courseid'=>$courseid))) {
                    if (!empty($grade_item->idnumber)) {
                        $formula = str_replace('##gi'.$grade_item->id.'##', '[['.$grade_item->idnumber.']]', $formula);
                    }
                }
            }
        }

        return $formula;

    }

    /**
     * Normalizes the calculation formula to [#giXX#] form
     * @static
     * @param string $formula
     * @return string normalized string
     */
    function normalize_formula($formula, $courseid) {
        $formula = trim($formula);

        if (empty($formula)) {
            return NULL;

        }

        // normalize formula - we want grade item ids ##giXXX## instead of [[idnumber]]
        if ($grade_items = grade_item::fetch_all(array('courseid'=>$courseid))) {
            foreach ($grade_items as $grade_item) {
                $formula = str_replace('[['.$grade_item->idnumber.']]', '##gi'.$grade_item->id.'##', $formula);
            }
        }

        return $formula;
    }

    /**
     * Returns the final values for this grade item (as imported by module or other source).
     * @param int $userid Optional: to retrieve a single final grade
     * @return mixed An array of all final_grades (stdClass objects) for this grade_item, or a single final_grade.
     */
    function get_final($userid=NULL) {
        if ($userid) {
            if ($user = get_record('grade_grades', 'itemid', $this->id, 'userid', $userid)) {
                return $user;
            }

        } else {
            if ($grades = get_records('grade_grades', 'itemid', $this->id)) {
                //TODO: speed up with better SQL
                $result = array();
                foreach ($grades as $grade) {
                    $result[$grade->userid] = $grade;
                }
                return $result;
            } else {
                return array();
            }
        }
    }

    /**
     * Get (or create if not exist yet) grade for this user
     * @param int $userid
     * @return object grade_grade object instance
     */
    function get_grade($userid, $create=true) {
        if (empty($this->id)) {
            debugging('Can not use before insert');
            return false;
        }

        $grade = new grade_grade(array('userid'=>$userid, 'itemid'=>$this->id));
        if (empty($grade->id) and $create) {
            $grade->insert();
        }

        return $grade;
    }

    /**
     * Returns the sortorder of this grade_item. This method is also available in
     * grade_category, for cases where the object type is not know.
     * @return int Sort order
     */
    function get_sortorder() {
        return $this->sortorder;
    }

    /**
     * Returns the idnumber of this grade_item. This method is also available in
     * grade_category, for cases where the object type is not know.
     * @return string idnumber
     */
    function get_idnumber() {
        return $this->idnumber;
    }

    /**
     * Returns this grade_item. This method is also available in
     * grade_category, for cases where the object type is not know.
     * @return string idnumber
     */
    function get_grade_item() {
        return $this;
    }

    /**
     * Sets the sortorder of this grade_item. This method is also available in
     * grade_category, for cases where the object type is not know.
     * @param int $sortorder
     * @return void
     */
    function set_sortorder($sortorder) {
        if ($this->sortorder == $sortorder) {
            return;
        }
        $this->sortorder = $sortorder;
        $this->update();
    }

    function move_after_sortorder($sortorder) {
        global $CFG;

        //make some room first
        $sql = "UPDATE {$CFG->prefix}grade_items
                   SET sortorder = sortorder + 1
                 WHERE sortorder > $sortorder AND courseid = {$this->courseid}";
        execute_sql($sql, false);

        $this->set_sortorder($sortorder + 1);
    }

    /**
     * Returns the most descriptive field for this object. This is a standard method used
     * when we do not know the exact type of an object.
     * @param boolean $fulltotal: if the item is a category total, returns $categoryname."total" instead of "Category total" or "Course total"
     * @return string name
     */
    function get_name($fulltotal=false) {
        if (!empty($this->itemname)) {
            // MDL-10557
            return format_string($this->itemname);

        } else if ($this->is_course_item()) {
            return get_string('coursetotal', 'grades');

        } else if ($this->is_category_item()) {
            if ($fulltotal) {
                $category = $this->load_parent_category();
                $a = new stdClass();
                $a->category = $category->get_name();
                return get_string('categorytotalfull', 'grades', $a);
            } else {
                return get_string('categorytotal', 'grades');
            }

        } else {
            return get_string('grade');
        }
    }

    /**
     * Sets this item's categoryid. A generic method shared by objects that have a parent id of some kind.
     * @param int $parentid
     * @return boolean success;
     */
    function set_parent($parentid) {
        if ($this->is_course_item() or $this->is_category_item()) {
            error('Can not set parent for category or course item!');
        }

        if ($this->categoryid == $parentid) {
            return true;
        }

        // find parent and check course id
        if (!$parent_category = grade_category::fetch(array('id'=>$parentid, 'courseid'=>$this->courseid))) {
            return false;
        }

        $this->force_regrading();

        // set new parent
        $this->categoryid = $parent_category->id;
        $this->parent_category =& $parent_category;

        return $this->update();
    }

    /**
     * Makes sure value is a valid grade value.
     * @param float $gradevalue
     * @return mixed float or int fixed grade value
     */
    function bounded_grade($gradevalue) {
        global $CFG;

        if (is_null($gradevalue)) {
            return null;
        }

        if ($this->gradetype == GRADE_TYPE_SCALE) {
            // no >100% grades hack for scale grades!
            // 1.5 is rounded to 2 ;-)
            return (int)bounded_number($this->grademin, round($gradevalue+0.00001), $this->grademax);
        }

        $grademax = $this->grademax;

        // NOTE: if you change this value you must manually reset the needsupdate flag in all grade items
        $maxcoef = isset($CFG->gradeoverhundredprocentmax) ? $CFG->gradeoverhundredprocentmax : 10; // 1000% max by default

        if (!empty($CFG->unlimitedgrades)) {
            // NOTE: if you change this value you must manually reset the needsupdate flag in all grade items
            $grademax = $grademax * $maxcoef;
        } else if ($this->is_category_item() or $this->is_course_item()) {
            $category = $this->load_item_category();
            if ($category->aggregation >= 100) {
                // grade >100% hack
                $grademax = $grademax * $maxcoef;
            }
        }

        return (float)bounded_number($this->grademin, $gradevalue, $grademax);
    }

    /**
     * Finds out on which other items does this depend directly when doing calculation or category agregation
     * @param bool $reset_cache
     * @return array of grade_item ids this one depends on
     */
    function depends_on($reset_cache=false) {
        global $CFG;

        if ($reset_cache) {
            $this->dependson_cache = null;
        } else if (isset($this->dependson_cache)) {
            return $this->dependson_cache;
        }

        if ($this->is_locked()) {
            // locked items do not need to be regraded
            $this->dependson_cache = array();
            return $this->dependson_cache;
        }

        if ($this->is_calculated()) {
            if (preg_match_all('/##gi(\d+)##/', $this->calculation, $matches)) {
                $this->dependson_cache = array_unique($matches[1]); // remove duplicates
                return $this->dependson_cache;
            } else {
                $this->dependson_cache = array();
                return $this->dependson_cache;
            }

        } else if ($grade_category = $this->load_item_category()) {
            //only items with numeric or scale values can be aggregated
            if ($this->gradetype != GRADE_TYPE_VALUE and $this->gradetype != GRADE_TYPE_SCALE) {
                $this->dependson_cache = array();
                return $this->dependson_cache;
            }

            $grade_category->apply_forced_settings();

            if (empty($CFG->enableoutcomes) or $grade_category->aggregateoutcomes) {
                $outcomes_sql = "";
            } else {
                $outcomes_sql = "AND gi.outcomeid IS NULL";
            }

            if (empty($CFG->grade_includescalesinaggregation)) {
                $gtypes = "gi.gradetype = ".GRADE_TYPE_VALUE;
            } else {
                $gtypes = "(gi.gradetype = ".GRADE_TYPE_VALUE." OR gi.gradetype = ".GRADE_TYPE_SCALE.")";
            }

            if ($grade_category->aggregatesubcats) {
                // return all children excluding category items
                $sql = "SELECT gi.id
                          FROM {$CFG->prefix}grade_items gi
                         WHERE $gtypes
                               $outcomes_sql
                               AND gi.categoryid IN (
                                  SELECT gc.id
                                    FROM {$CFG->prefix}grade_categories gc
                                   WHERE gc.path LIKE '%/{$grade_category->id}/%')";

            } else {
                $sql = "SELECT gi.id
                          FROM {$CFG->prefix}grade_items gi
                         WHERE gi.categoryid = {$grade_category->id}
                               AND $gtypes
                               $outcomes_sql

                        UNION

                        SELECT gi.id
                          FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_categories gc
                         WHERE (gi.itemtype = 'category' OR gi.itemtype = 'course') AND gi.iteminstance=gc.id
                               AND gc.parent = {$grade_category->id}
                               AND $gtypes
                               $outcomes_sql";
            }

            if ($children = get_records_sql($sql)) {
                $this->dependson_cache = array_keys($children);
                return $this->dependson_cache;
            } else {
                $this->dependson_cache = array();
                return $this->dependson_cache;
            }

        } else {
            $this->dependson_cache = array();
            return $this->dependson_cache;
        }
    }

    /**
     * Refetch grades from modules, plugins.
     * @param int $userid optional, one user only
     */
    function refresh_grades($userid=0) {
        if ($this->itemtype == 'mod') {
            if ($this->is_outcome_item()) {
                //nothing to do
                return;
            }

            if (!$activity = get_record($this->itemmodule, 'id', $this->iteminstance)) {
                debugging("Can not find $this->itemmodule activity with id $this->iteminstance");
                return;
            }

            if (!$cm = get_coursemodule_from_instance($this->itemmodule, $activity->id, $this->courseid)) {
                debugging('Can not find course module');
                return;
            }

            $activity->modname    = $this->itemmodule;
            $activity->cmidnumber = $cm->idnumber;

            grade_update_mod_grades($activity);
        }
    }

    /**
     * Updates final grade value for given user, this is a only way to update final
     * grades from gradebook and import because it logs the change in history table
     * and deals with overridden flag. This flag is set to prevent later overriding
     * from raw grades submitted from modules.
     *
     * @param int $userid the graded user
     * @param mixed $finalgrade float value of final grade - false means do not change
     * @param string $howmodified modification source
     * @param string $note optional note
     * @param mixed $feedback teachers feedback as string - false means do not change
     * @param int $feedbackformat
     * @return boolean success
     */
    function update_final_grade($userid, $finalgrade=false, $source=NULL, $feedback=false, $feedbackformat=FORMAT_MOODLE, $usermodified=null) {
        global $USER, $CFG;

        $result = true;

        // no grading used or locked
        if ($this->gradetype == GRADE_TYPE_NONE or $this->is_locked()) {
            return false;
        }

        $grade = new grade_grade(array('itemid'=>$this->id, 'userid'=>$userid));
        $grade->grade_item =& $this; // prevent db fetching of this grade_item

        if (empty($usermodified)) {
            $grade->usermodified = $USER->id;
        } else {
            $grade->usermodified = $usermodified;
        }

        if ($grade->is_locked()) {
            // do not update locked grades at all
            return false;
        }

        $locktime = $grade->get_locktime();
        if ($locktime and $locktime < time()) {
            // do not update grades that should be already locked, force regrade instead
            $this->force_regrading();
            return false;
        }

        $oldgrade = new object();
        $oldgrade->finalgrade     = $grade->finalgrade;
        $oldgrade->overridden     = $grade->overridden;
        $oldgrade->feedback       = $grade->feedback;
        $oldgrade->feedbackformat = $grade->feedbackformat;

        // changed grade?
        if ($finalgrade !== false) {
            if ($this->is_overridable_item()) {
                $grade->overridden = time();
            } else {
                $grade->overridden = 0;
            }

            $grade->finalgrade = $this->bounded_grade($finalgrade);
        }

        // do we have comment from teacher?
        if ($feedback !== false) {
            if ($this->is_overridable_item_feedback()) {
                // external items (modules, plugins) may have own feedback
                $grade->overridden = time();
            }

            $grade->feedback       = $feedback;
            $grade->feedbackformat = $feedbackformat;
        }

        if (empty($grade->id)) {
            $grade->timecreated  = null;   // hack alert - date submitted - no submission yet
            $grade->timemodified = time(); // hack alert - date graded
            $result = (boolean)$grade->insert($source);

        } else if (grade_floats_different($grade->finalgrade, $oldgrade->finalgrade)
                or $grade->feedback       !== $oldgrade->feedback
                or $grade->feedbackformat != $oldgrade->feedbackformat
                or $grade->overridden     != $oldgrade->overridden) {
            $grade->timemodified = time(); // hack alert - date graded
            $result = $grade->update($source);
        } else {
            // no grade change
            return $result;
        }

        if (!$result) {
            // something went wrong - better force final grade recalculation
            $this->force_regrading();

        } else if ($this->is_course_item() and !$this->needsupdate) {
            if (grade_regrade_final_grades($this->courseid, $userid, $this) !== true) {
                $this->force_regrading();
            }

        } else if (!$this->needsupdate) {
            $course_item = grade_item::fetch_course_item($this->courseid);
            if (!$course_item->needsupdate) {
                if (grade_regrade_final_grades($this->courseid, $userid, $this) !== true) {
                    $this->force_regrading();
                }
            } else {
                $this->force_regrading();
            }
        }

        return $result;
    }


    /**
     * Updates raw grade value for given user, this is a only way to update raw
     * grades from external source (modules, etc.),
     * because it logs the change in history table and deals with final grade recalculation.
     *
     * @param int $userid the graded user
     * @param mixed $rawgrade float value of raw grade - false means do not change
     * @param string $howmodified modification source
     * @param string $note optional note
     * @param mixed $feedback teachers feedback as string - false means do not change
     * @param int $feedbackformat
     * @param int $usermodified - user which did the grading
     * @param int $dategraded
     * @param int $datesubmitted
     * @param object $grade object - usefull for bulk upgrades
     * @return boolean success
     */
    function update_raw_grade($userid, $rawgrade=false, $source=NULL, $feedback=false, $feedbackformat=FORMAT_MOODLE, $usermodified=null, $dategraded=null, $datesubmitted=null, $grade=null) {
        global $USER;

        $result = true;

        // calculated grades can not be updated; course and category can not be updated  because they are aggregated
        if (!$this->is_raw_used() or $this->gradetype == GRADE_TYPE_NONE or $this->is_locked()) {
            return false;
        }

        if (is_null($grade)) {
            //fetch from db
            $grade = new grade_grade(array('itemid'=>$this->id, 'userid'=>$userid));
        }
        $grade->grade_item =& $this; // prevent db fetching of this grade_item

        if (empty($usermodified)) {
            $grade->usermodified = $USER->id;
        } else {
            $grade->usermodified = $usermodified;
        }

        if ($grade->is_locked()) {
            // do not update locked grades at all
            return false;
        }

        $locktime = $grade->get_locktime();
        if ($locktime and $locktime < time()) {
            // do not update grades that should be already locked and force regrade
            $this->force_regrading();
            return false;
        }

        $oldgrade = new object();
        $oldgrade->finalgrade     = $grade->finalgrade;
        $oldgrade->rawgrade       = $grade->rawgrade;
        $oldgrade->rawgrademin    = $grade->rawgrademin;
        $oldgrade->rawgrademax    = $grade->rawgrademax;
        $oldgrade->rawscaleid     = $grade->rawscaleid;
        $oldgrade->feedback       = $grade->feedback;
        $oldgrade->feedbackformat = $grade->feedbackformat;

        // use new min and max
        $grade->rawgrade    = $grade->rawgrade;
        $grade->rawgrademin = $this->grademin;
        $grade->rawgrademax = $this->grademax;
        $grade->rawscaleid  = $this->scaleid;

        // change raw grade?
        if ($rawgrade !== false) {
            $grade->rawgrade = $rawgrade;
        }

        // empty feedback means no feedback at all
        if ($feedback === '') {
            $feedback = null;
        }

        // do we have comment from teacher?
        if ($feedback !== false and !$grade->is_overridden()) {
            $grade->feedback       = $feedback;
            $grade->feedbackformat = $feedbackformat;
        }

        // update final grade if possible
        if (!$grade->is_locked() and !$grade->is_overridden()) {
            $grade->finalgrade = $this->adjust_raw_grade($grade->rawgrade, $grade->rawgrademin, $grade->rawgrademax);
        }

        // TODO: hack alert - create new fields for these in 2.0
        $oldgrade->timecreated  = $grade->timecreated;
        $oldgrade->timemodified = $grade->timemodified;

        $grade->timecreated = $datesubmitted;

        if ($grade->is_overridden()) {
            // keep original graded date - update_final_grade() sets this for overridden grades

        } else if (is_null($grade->rawgrade) and is_null($grade->feedback)) {
            // no grade and feedback means no grading yet
            $grade->timemodified = null;

        } else if (!empty($dategraded)) {
            // fine - module sends info when graded (yay!)
            $grade->timemodified = $dategraded;

        } else if (grade_floats_different($grade->finalgrade, $oldgrade->finalgrade)
                   or $grade->feedback !== $oldgrade->feedback) {
            // guess - if either grade or feedback changed set new graded date
            $grade->timemodified = time();

        } else {
            //keep original graded date
        }
        // end of hack alert

        if (empty($grade->id)) {
            $result = (boolean)$grade->insert($source);

        } else if (grade_floats_different($grade->finalgrade,  $oldgrade->finalgrade)
                or grade_floats_different($grade->rawgrade,    $oldgrade->rawgrade)
                or grade_floats_different($grade->rawgrademin, $oldgrade->rawgrademin)
                or grade_floats_different($grade->rawgrademax, $oldgrade->rawgrademax)
                or $grade->rawscaleid     != $oldgrade->rawscaleid
                or $grade->feedback       !== $oldgrade->feedback
                or $grade->feedbackformat != $oldgrade->feedbackformat
                or $grade->timecreated    != $oldgrade->timecreated  // part of hack above
                or $grade->timemodified   != $oldgrade->timemodified // part of hack above
                ) {
            $result = $grade->update($source);
        } else {
            return $result;
        }

        if (!$result) {
            // something went wrong - better force final grade recalculation
            $this->force_regrading();

        } else if (!$this->needsupdate) {
            $course_item = grade_item::fetch_course_item($this->courseid);
            if (!$course_item->needsupdate) {
                if (grade_regrade_final_grades($this->courseid, $userid, $this) !== true) {
                    $this->force_regrading();
                }
            }
        }

        return $result;
    }

    /**
     * Calculates final grade values using the formula in calculation property.
     * The parameters are taken from final grades of grade items in current course only.
     * @return boolean false if error
     */
    function compute($userid=null) {
        global $CFG;

        if (!$this->is_calculated()) {
            return false;
        }

        require_once($CFG->libdir.'/mathslib.php');

        if ($this->is_locked()) {
            return true; // no need to recalculate locked items
        }

        // precreate grades - we need them to exist
        $sql = "SELECT DISTINCT go.userid
                  FROM {$CFG->prefix}grade_grades go
                       JOIN {$CFG->prefix}grade_items gi
                       ON (gi.id = go.itemid AND gi.courseid={$this->courseid})
                       LEFT OUTER JOIN {$CFG->prefix}grade_grades g
                       ON (g.userid = go.userid AND g.itemid = $this->id)
                 WHERE gi.id <> $this->id AND g.id IS NULL";
        if ($missing = get_records_sql($sql)) {
            foreach ($missing as $m) {
                $grade = new grade_grade(array('itemid'=>$this->id, 'userid'=>$m->userid), false);
                $grade->grade_item =& $this;
                $grade->insert('system');
            }
        }

        // get used items
        $useditems = $this->depends_on();

        // prepare formula and init maths library
        $formula = preg_replace('/##(gi\d+)##/', '\1', $this->calculation);
        if (strpos($formula, '[[') !== false) {
            // missing item
            return false;
        }
        $this->formula = new calc_formula($formula);

        // where to look for final grades?
        // this itemid is added so that we use only one query for source and final grades
        $gis = implode(',', array_merge($useditems, array($this->id)));

        if ($userid) {
            $usersql = "AND g.userid=$userid";
        } else {
            $usersql = "";
        }

        $grade_inst = new grade_grade();
        $fields = 'g.'.implode(',g.', $grade_inst->required_fields);

        $sql = "SELECT $fields
                  FROM {$CFG->prefix}grade_grades g, {$CFG->prefix}grade_items gi
                 WHERE gi.id = g.itemid AND gi.courseid={$this->courseid} AND gi.id IN ($gis) $usersql
              ORDER BY g.userid";

        $return = true;

        // group the grades by userid and use formula on the group
        if ($rs = get_recordset_sql($sql)) {
            $prevuser = 0;
            $grade_records   = array();
            $oldgrade    = null;
            while ($used = rs_fetch_next_record($rs)) {
                if ($used->userid != $prevuser) {
                    if (!$this->use_formula($prevuser, $grade_records, $useditems, $oldgrade)) {
                        $return = false;
                    }
                    $prevuser = $used->userid;
                    $grade_records   = array();
                    $oldgrade    = null;
                }
                if ($used->itemid == $this->id) {
                    $oldgrade = $used;
                }
                $grade_records['gi'.$used->itemid] = $used->finalgrade;
            }
            if (!$this->use_formula($prevuser, $grade_records, $useditems, $oldgrade)) {
                $return = false;
            }
        }
        rs_close($rs);

        return $return;
    }

    /**
     * internal function - does the final grade calculation
     */
    function use_formula($userid, $params, $useditems, $oldgrade) {
        if (empty($userid)) {
            return true;
        }

        // add missing final grade values
        // not graded (null) is counted as 0 - the spreadsheet way
        foreach($useditems as $gi) {
            if (!array_key_exists('gi'.$gi, $params)) {
                $params['gi'.$gi] = 0;
            } else {
                $params['gi'.$gi] = (float)$params['gi'.$gi];
            }
        }

        // can not use own final grade during calculation
        unset($params['gi'.$this->id]);

        // insert final grade - will be needed later anyway
        if ($oldgrade) {
            $oldfinalgrade = $oldgrade->finalgrade;
            $grade = new grade_grade($oldgrade, false); // fetching from db is not needed
            $grade->grade_item =& $this;

        } else {
            $grade = new grade_grade(array('itemid'=>$this->id, 'userid'=>$userid), false);
            $grade->grade_item =& $this;
            $grade->insert('system');
            $oldfinalgrade = null;
        }

        // no need to recalculate locked or overridden grades
        if ($grade->is_locked() or $grade->is_overridden()) {
            return true;
        }

        // do the calculation
        $this->formula->set_params($params);
        $result = $this->formula->evaluate();

        if ($result === false) {
            $grade->finalgrade = null;

        } else {
            // normalize
            $grade->finalgrade = $this->bounded_grade($result);
        }

        // update in db if changed
        if (grade_floats_different($grade->finalgrade, $oldfinalgrade)) {
            $grade->update('compute');
        }

        if ($result !== false) {
            //lock grade if needed
        }

        if ($result === false) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * Validate the formula.
     * @param string $formula
     * @return boolean true if calculation possible, false otherwise
     */
    function validate_formula($formulastr) {
        global $CFG;
        require_once($CFG->libdir.'/mathslib.php');

        $formulastr = grade_item::normalize_formula($formulastr, $this->courseid);

        if (empty($formulastr)) {
            return true;
        }

        if (strpos($formulastr, '=') !== 0) {
            return get_string('errorcalculationnoequal', 'grades');
        }

        // get used items
        if (preg_match_all('/##gi(\d+)##/', $formulastr, $matches)) {
            $useditems = array_unique($matches[1]); // remove duplicates
        } else {
            $useditems = array();
        }

        // MDL-11902
        // unset the value if formula is trying to reference to itself
        // but array keys does not match itemid
        if (!empty($this->id)) {
            $useditems = array_diff($useditems, array($this->id));
            //unset($useditems[$this->id]);
        }

        // prepare formula and init maths library
        $formula = preg_replace('/##(gi\d+)##/', '\1', $formulastr);
        $formula = new calc_formula($formula);


        if (empty($useditems)) {
            $grade_items = array();

        } else {
            $gis = implode(',', $useditems);

            $sql = "SELECT gi.*
                      FROM {$CFG->prefix}grade_items gi
                     WHERE gi.id IN ($gis) and gi.courseid={$this->courseid}"; // from the same course only!

            if (!$grade_items = get_records_sql($sql)) {
                $grade_items = array();
            }
        }

        $params = array();
        foreach ($useditems as $itemid) {
            // make sure all grade items exist in this course
            if (!array_key_exists($itemid, $grade_items)) {
                return false;
            }
            // use max grade when testing formula, this should be ok in 99.9%
            // division by 0 is one of possible problems
            $params['gi'.$grade_items[$itemid]->id] = $grade_items[$itemid]->grademax;
        }

        // do the calculation
        $formula->set_params($params);
        $result = $formula->evaluate();

        // false as result indicates some problem
        if ($result === false) {
            // TODO: add more error hints
            return get_string('errorcalculationunknown', 'grades');
        } else {
            return true;
        }
    }

    /**
     * Returns the value of the display type. It can be set at 3 levels: grade_item, course setting and site. The lowest level overrides the higher ones.
     * @return int Display type
     */
    function get_displaytype() {
        global $CFG;

        if ($this->display == GRADE_DISPLAY_TYPE_DEFAULT) {
            return grade_get_setting($this->courseid, 'displaytype', $CFG->grade_displaytype);

        } else {
            return $this->display;
        }
    }

    /**
     * Returns the value of the decimals field. It can be set at 3 levels: grade_item, course setting and site. The lowest level overrides the higher ones.
     * @return int Decimals (0 - 5)
     */
    function get_decimals() {
        global $CFG;

        if (is_null($this->decimals)) {
            return grade_get_setting($this->courseid, 'decimalpoints', $CFG->grade_decimalpoints);

        } else {
            return $this->decimals;
        }
    }

    /**
     * Returns a string representing the range of grademin - grademax for this grade item.
     * @param int $rangesdisplaytype
     * @param int $rangesdecimalpoints
     * @return string
     */
    function get_formatted_range($rangesdisplaytype=null, $rangesdecimalpoints=null) {

        global $USER;

        // Determine which display type to use for this average
        if (isset($USER->gradeediting) && $USER->gradeediting[$this->courseid]) {
            $displaytype = GRADE_DISPLAY_TYPE_REAL;

        } else if ($rangesdisplaytype == GRADE_REPORT_PREFERENCE_INHERIT) { // no ==0 here, please resave report and user prefs
            $displaytype = $this->get_displaytype();

        } else {
            $displaytype = $rangesdisplaytype;
        }

        // Override grade_item setting if a display preference (not default) was set for the averages
        if ($rangesdecimalpoints == GRADE_REPORT_PREFERENCE_INHERIT) {
            $decimalpoints = $this->get_decimals();

        } else {
            $decimalpoints = $rangesdecimalpoints;
        }

        if ($displaytype == GRADE_DISPLAY_TYPE_PERCENTAGE) {
            $grademin = "0 %";
            $grademax = "100 %";

        } else {
            $grademin = grade_format_gradevalue($this->grademin, $this, true, $displaytype, $decimalpoints);
            $grademax = grade_format_gradevalue($this->grademax, $this, true, $displaytype, $decimalpoints);
        }

        return $grademin.'&ndash;'. $grademax;
    }

    /**
     * Queries parent categories recursively to find the aggregationcoef type that applies to this
     * grade item.
     */
    function get_coefstring() {
        $parent_category = $this->load_parent_category();
        if ($this->is_category_item()) {
            $parent_category = $parent_category->load_parent_category();
        }

        if ($parent_category->is_aggregationcoef_used()) {
            return $parent_category->get_coefstring();
        } else {
            return false;
        }
    }
}
?>
