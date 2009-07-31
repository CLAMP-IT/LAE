<?php // $Id$

/**
 * Library of functions and constants for notes
 */

/**
 * Constants for states.
 */
define('NOTES_STATE_DRAFT', 'draft');
define('NOTES_STATE_PUBLIC', 'public');
define('NOTES_STATE_SITE', 'site');

/**
 * Constants for note parts (flags used by note_print and note_print_list).
 */
define('NOTES_SHOW_FULL', 0x07);
define('NOTES_SHOW_HEAD', 0x02);
define('NOTES_SHOW_BODY', 0x01);
define('NOTES_SHOW_FOOT', 0x04);

/**
 * Retrieves a list of note objects with specific atributes.
 *
 * @param int    $courseid id of the course in which the notes were posted (0 means any)
 * @param int    $userid id of the user to which the notes refer (0 means any)
 * @param string $state state of the notes (i.e. draft, public, site) ('' means any)
 * @param int    $author id of the user who modified the note last time (0 means any)
 * @param string $order an order to sort the results in
 * @param int    $limitfrom number of records to skip (offset)
 * @param int    $limitnum number of records to fetch
 * @return array of note objects
 */
function note_list($courseid=0, $userid=0, $state = '', $author = 0, $order='lastmodified DESC', $limitfrom=0, $limitnum=0) {
    // setup filters
    $selects = array();
    if($courseid) {
        $selects[] = 'courseid=' . $courseid;
    }
    if($userid) {
        $selects[] = 'userid=' . $userid;
    }
    if($author) {
        $selects[] = 'usermodified=' . $author;
    }
    if($state) {
        $selects[] = "publishstate='$state'";
    }
    $selects[] = "module='notes'";
    $select = implode(' AND ', $selects);
    $fields = 'id,courseid,userid,content,format,created,lastmodified,usermodified,publishstate';
    // retrieve data
    $rs =& get_recordset_select('post', $select, $order, $fields, $limitfrom, $limitnum);
    return recordset_to_array($rs);
}

/**
 * Retrieves a note object based on its id.
 *
 * @param int    $note_id id of the note to retrieve
 * @return note object
 */
function note_load($note_id) {
    $fields = 'id,courseid,userid,content,format,created,lastmodified,usermodified,publishstate';
    return get_record_select('post', "id=$note_id AND module='notes'", $fields);
}

/**
 * Saves a note object. The note object is passed by reference and its fields (i.e. id)
 * might change during the save.
 *
 * @param note   $note object to save
 * @return boolean true if the object was saved; false otherwise
 */
function note_save(&$note) {
    global $USER;

    // setup & clean fields
    $note->module       = 'notes';
    $note->lastmodified = time();
    $note->usermodified = $USER->id;
    if (empty($note->format)) {
        $note->format = FORMAT_PLAIN;
    }
    if (empty($note->publishstate)) {
        $note->publishstate = NOTES_STATE_PUBLIC;
    }
    // save data
    if (empty($note->id)) {
        // insert new note
        $note->created = $note->lastmodified;
        if ($id = insert_record('post', $note)) {
            $note = addslashes_recursive(get_record('post', 'id', $id));
            $result = true;
        } else {
            $result = false;
        }
    } else {
        // update old note
        $result = update_record('post', $note);
    }
    unset($note->module);
    return $result;
}

/**
 * Deletes a note object based on its id.
 *
 * @param int    $note_id id of the note to delete
 * @return boolean true if the object was deleted; false otherwise
 */
function note_delete($noteid) {
    return delete_records_select('post', "id=$noteid AND module='notes'");
}

/**
 * Converts a state value to its corespondent name
 *
 * @param string  $state state value to convert
 * @return string corespondent state name
 */
function note_get_state_name($state) {
    // cache state names
    static $states;
    if (empty($states)) {
        $states = note_get_state_names();
    }
    if (isset($states[$state])) {
        return $states[$state];
    } else {
        return null;
    }
}

/**
 * Returns an array of mappings from state values to state names
 *
 * @return array of mappings
 */
function note_get_state_names() {
    return array(
        NOTES_STATE_DRAFT => get_string('personal', 'notes'),
        NOTES_STATE_PUBLIC => get_string('course', 'notes'),
        NOTES_STATE_SITE => get_string('site', 'notes'),
    );
}

/**
 * Prints a note object
 *
 * @param note  $note the note object to print
 * @param int   $detail OR-ed NOTES_SHOW_xyz flags that specify which note parts to print
 */
function note_print($note, $detail = NOTES_SHOW_FULL) {
    global $CFG, $USER;

    if (!$user = get_record('user','id',$note->userid)) {
        debugging("User $note->userid not found");
        return;
    }
    if (!$author = get_record('user','id',$note->usermodified)) {
        debugging("User $note->usermodified not found");
        return;
    }
    $context = get_context_instance(CONTEXT_COURSE, $note->courseid);
    $systemcontext = get_context_instance(CONTEXT_SYSTEM);

    $authoring = new object();
    $authoring->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$author->id.'&amp;course='.$note->courseid.'">'.fullname($author).'</a>';
    $authoring->date = userdate($note->lastmodified);

    echo '<div class="notepost '. $note->publishstate . 'notepost' .
        ($note->usermodified == $USER->id ? ' ownnotepost' : '')  .
        '" id="note-'. $note->id .'">';

    // print note head (e.g. author, user refering to, etc)
    if ($detail & NOTES_SHOW_HEAD) {
        echo '<div class="header">';
        echo '<div class="user">';
        print_user_picture($user, $note->courseid, $user->picture);
        echo fullname($user) . '</div>';
        echo '<div class="info">' .
            get_string('bynameondate', 'notes', $authoring) .
            ' (' . get_string('created', 'notes') . ': ' . userdate($note->created) . ')</div>';
        echo '</div>';
    }

    // print note content
    if ($detail & NOTES_SHOW_BODY) {
        echo '<div class="content">';
        echo format_text($note->content, $note->format);
        echo '</div>';
    }

    // print note options (e.g. delete, edit)
    if ($detail & NOTES_SHOW_FOOT) {
        if (has_capability('moodle/notes:manage', $systemcontext) && $note->publishstate == NOTES_STATE_SITE ||
            has_capability('moodle/notes:manage', $context) && ($note->publishstate == NOTES_STATE_PUBLIC || $note->usermodified == $USER->id)) {
            echo '<div class="footer"><p>';
            echo '<a href="'.$CFG->wwwroot.'/notes/edit.php?id='.$note->id. '">'. get_string('edit') .'</a> | ';
            echo '<a href="'.$CFG->wwwroot.'/notes/delete.php?id='.$note->id. '">'. get_string('delete') .'</a>';
            echo '</p></div>';
        }
    }
    echo '</div>';
}

/**
 * Prints a list of note objects
 *
 * @param array  $notes array of note objects to print
 * @param int   $detail OR-ed NOTES_SHOW_xyz flags that specify which note parts to print
 */
function note_print_list($notes, $detail = NOTES_SHOW_FULL) {

    /// Start printing of the note
    echo '<div class="notelist">';
    foreach ($notes as $note) {
        note_print($note, $detail);
    }
    echo '</div>';
}

/**
 * Retrieves and prints a list of note objects with specific atributes.
 *
 * @param string  $header HTML to print above the list
 * @param int     $addcourseid id of the course for the add notes link (0 hide link)
 * @param boolean $viewnotes true if the notes should be printed; false otherwise (print notesnotvisible string)
 * @param int     $courseid id of the course in which the notes were posted (0 means any)
 * @param int     $userid id of the user to which the notes refer (0 means any)
 * @param string  $state state of the notes (i.e. draft, public, site) ('' means any)
 * @param int     $author id of the user who modified the note last time (0 means any)
 */
function note_print_notes($header, $addcourseid = 0, $viewnotes = true, $courseid = 0, $userid = 0, $state = '', $author = 0) {
    global $CFG;

    if ($header) {
        echo '<h3 class="notestitle">' . $header . '</h3>';
        echo '<div class="notesgroup">';
    }
    if ($addcourseid) {
        if ($userid) {
           echo '<p><a href="'. $CFG->wwwroot .'/notes/edit.php?courseid=' . $addcourseid . '&amp;userid=' . $userid . '&amp;publishstate=' . $state . '">' . get_string('addnewnote', 'notes') . '</a></p>';
        } else {
           echo '<p><a href="'. $CFG->wwwroot .'/user/index.php?id=' . $addcourseid. '">' . get_string('addnewnoteselect', 'notes') . '</a></p>';
        }
    }
    if ($viewnotes) {
        $notes = note_list($courseid, $userid, $state, $author);
        if ($notes) {
            note_print_list($notes);
        }
    } else {
        echo '<p>' . get_string('notesnotvisible', 'notes') . '</p>';
    }
    if ($header) {
        echo '</div>';  // notesgroup
    }
}

/**
 * Delete all notes about users in course-
 * @param int $courseid
 * @return bool success
 */
function note_delete_all($courseid) {
    return delete_records('post', 'module', 'notes', 'courseid', $courseid);
}
?>
