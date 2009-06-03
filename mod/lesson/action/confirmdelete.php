<?php // $Id: confirmdelete.php,v 1.6 2007/10/09 21:43:30 iarenaza Exp $
/**
 * Action for confirming the deletion of a page
 *
 * @version $Id: confirmdelete.php,v 1.6 2007/10/09 21:43:30 iarenaza Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/
    confirm_sesskey();

    $pageid = required_param('pageid', PARAM_INT);
    if (!$thispage = get_record("lesson_pages", "id", $pageid)) {
        error("Confirm delete: the page record not found");
    }
    print_heading(get_string("deletingpage", "lesson", format_string($thispage->title)));
    // print the jumps to this page
    if ($answers = get_records_select("lesson_answers", "lessonid = $lesson->id AND jumpto = $pageid + 1")) {
        print_heading(get_string("thefollowingpagesjumptothispage", "lesson"));
        echo "<p align=\"center\">\n";
        foreach ($answers as $answer) {
            if (!$title = get_field("lesson_pages", "title", "id", $answer->pageid)) {
                error("Confirm delete: page title not found");
            }
            echo $title."<br />\n";
        }
    }
    notice_yesno(get_string("confirmdeletionofthispage","lesson"), 
         "lesson.php?action=delete&amp;id=$cm->id&amp;pageid=$pageid&amp;sesskey=".$USER->sesskey, 
         "view.php?id=$cm->id");
?>
