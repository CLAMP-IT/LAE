<?php  // $Id: dictionary_format.php,v 1.9.22.1 2007/11/09 14:35:06 nfreear Exp $

function glossary_show_entry_dictionary($course, $cm, $glossary, $entry, $mode='', $hook='', $printicons=1, $ratings=NULL, $aliases=true) {

    global $CFG, $USER;

    echo '<table class="glossarypost dictionary" cellspacing="0">';
    echo '<tr valign="top">';
    echo '<td class="entry">';
    glossary_print_entry_approval($cm, $entry, $mode);
    glossary_print_entry_attachment($entry,'html','right');
    echo '<div class="concept">';
    glossary_print_entry_concept($entry);
    echo ':</div> ';
    glossary_print_entry_definition($entry);
    echo '</td></tr>';
    echo '<tr valign="top"><td class="entrylowersection">';
    $return = glossary_print_entry_lower_section($course, $cm, $glossary, $entry, $mode, $hook, $printicons, $ratings, $aliases);
    echo '</td>';
    echo '</tr>';
    echo "</table>\n";

    return $return;
}

function glossary_print_entry_dictionary($course, $cm, $glossary, $entry, $mode='', $hook='', $printicons=1, $ratings=NULL) {

    //The print view for this format is exactly the normal view, so we use it

    //Take out autolinking in definitions in print view
    $entry->definition = '<span class="nolink">'.$entry->definition.'</span>';

    //Call to view function (without icons, ratings and aliases) and return its result
    return glossary_show_entry_dictionary($course, $cm, $glossary, $entry, $mode, $hook, false, false, false);
}

?>
