<?php  // $Id$

function glossary_show_entry_encyclopedia($course, $cm, $glossary, $entry, $mode='',$hook='',$printicons=1,$ratings=NULL, $aliases=true) {
    global $CFG, $USER;


    $user = get_record('user', 'id', $entry->userid);
    $strby = get_string('writtenby', 'glossary');

    $return = false;
    if ($entry) {
        echo '<table class="glossarypost encyclopedia" cellspacing="0">';
        echo '<tr valign="top">';
        echo '<td class="left picture">';
        
        print_user_picture($user, $course->id, $user->picture);
    
        echo '</td>';
        echo '<th class="entryheader">';
        echo '<div class="concept">';
        glossary_print_entry_concept($entry);
        echo '</div>';

        $fullname = fullname($user);
        $by->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.$fullname.'</a>';
        $by->date = userdate($entry->timemodified);
        echo '<span class="author">'.get_string('bynameondate', 'forum', $by).'</span>';

        echo '</th>';
        
        echo '<td class="entryapproval">';
        glossary_print_entry_approval($cm, $entry, $mode);
        echo '</td>';
        
        echo '</tr>';

        echo '<tr valign="top">';
        echo '<td class="left side" rowspan="2">&nbsp;</td>';
        echo '<td colspan="2" class="entry">';

        if ($entry->attachment) {
            $entry->course = $course->id;
            if (strlen($entry->definition)%2) {
                $align = 'right';
            } else {
                $align = 'left';
            }
            glossary_print_entry_attachment($entry,'',$align,false);
        }
        glossary_print_entry_definition($entry);

        if ($printicons or $ratings or $aliases) {
            echo '</td></tr>';
            echo '<tr>';
            echo '<td colspan="2" class="entrylowersection">';
            $return = glossary_print_entry_lower_section($course, $cm, $glossary, $entry,$mode,$hook,$printicons,$ratings, $aliases);
            echo ' ';
        }
        
        echo '</td></tr>';
        echo "</table>\n";
        
    } else {
        echo '<div style="text-align:center">';
        print_string('noentry', 'glossary');
        echo '</div>';
    }
    
    return $return;
}

function glossary_print_entry_encyclopedia($course, $cm, $glossary, $entry, $mode='', $hook='', $printicons=1, $ratings=NULL) {

    //The print view for this format is exactly the normal view, so we use it

    //Take out autolinking in definitions un print view
    $entry->definition = '<span class="nolink">'.$entry->definition.'</span>';

    //Call to view function (without icons, ratings and aliases) and return its result
    
    return glossary_show_entry_encyclopedia($course, $cm, $glossary, $entry, $mode, $hook, false, false, false);

}

?>
