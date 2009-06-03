<?php  // $Id: lesson.php, v 1.0 25 Jan 2004
/**
 * Handles lesson actions
 * 
 * ACTIONS handled are:
 *    addbranchtable
 *    addendofbranch
 *    addcluster
 *    addendofcluster
 *    addpage
 *    confirmdelete
 *    continue
 *    delete
 *    editpage
 *    insertpage
 *    move
 *    moveit
 *    updatepage
 * @version $Id: lesson.php,v 1.47 2006/09/30 19:49:41 mark-nielsen Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/

    require("../../config.php");
    require("locallib.php");
    
    $id     = required_param('id', PARAM_INT);         // Course Module ID
    $action = required_param('action', PARAM_ALPHA);   // Action
    
    list($cm, $course, $lesson) = lesson_get_basics($id);

    require_login($course->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    
/// Set up some general variables
    $usehtmleditor = can_use_html_editor();
    
/// Process the action
    switch ($action) {
        case 'addbranchtable':
        case 'addpage':
        case 'confirmdelete':
        case 'editpage':
        case 'move':
            lesson_print_header($cm, $course, $lesson);
        case 'addcluster':
        case 'addendofbranch':
        case 'addendofcluster':
        case 'delete':
        case 'insertpage':
        case 'updatepage':
        case 'moveit':
            require_capability('mod/lesson:edit', $context);
        case 'continue':
            include($CFG->dirroot.'/mod/lesson/action/'.$action.'.php');
            break;
        default:
            error("Fatal Error: Unknown action\n");
    }

    print_footer($course);
 
?>
