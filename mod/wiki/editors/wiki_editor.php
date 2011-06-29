<?php
/**
 * This file defines a simple editor
 *
 * @author Jordi Piguillem
 * @author Kenneth Riba
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package wiki
 *
 */

/**
 * Printing wiki editor.
 * Depending on where it is called , action will go to different destinations.
 * If it is called from comments section, the return will be in comments section
 *  in any other case it will be in edit view section.
 * @param $pageid. Current pageid
 * @param $content. Content to be edited.
 * @param $section. Current section, default null
 * @param $comesfrom. Information about where the function call is made
 * @param commentid. id comment of comment that will be edited.
 */

function wiki_print_editor_wiki($pageid, $content, $editor, $version = -1, $section = null, $upload = false, $deleteuploads = array(), $comesfrom = 'editorview', $commentid = 0) {
    global $CFG, $OUTPUT, $PAGE;

    if ($comesfrom == 'editcomments') {
        $action = $CFG->wwwroot . '/mod/wiki/instancecomments.php?pageid=' . $pageid . '&id=' . $commentid . '&action=edit';
    } else if ($comesfrom == 'addcomments') {
        $action = $CFG->wwwroot . '/mod/wiki/instancecomments.php?pageid=' . $pageid . '&id=' . $commentid . '&action=add';
    } else {
        $action = $CFG->wwwroot . '/mod/wiki/edit.php?pageid=' . $pageid;
    }

    if (!empty($section)) {
        $action .= "&amp;section=" . urlencode($section);
    }

    ///Get tags for every element we are displaying
    $tag = wiki_parser_get_token($editor, 'bold');
    $wiki_editor['bold'] = array('ed_bold.gif', get_string('wikiboldtext', 'wiki'), $tag[0], $tag[1], get_string('wikiboldtext', 'wiki'));
    $tag = wiki_parser_get_token($editor, 'italic');
    $wiki_editor['italic'] = array('ed_italic.gif', get_string('wikiitalictext', 'wiki'), $tag[0], $tag[1], get_string('wikiitalictext', 'wiki'));
    $tag = wiki_parser_get_token($editor, 'link');
    $wiki_editor['internal'] = array('ed_internal.gif', get_string('wikiinternalurl', 'wiki'), $tag[0], $tag[1], get_string('wikiinternalurl', 'wiki'));
    $tag = wiki_parser_get_token($editor, 'url');
    $wiki_editor['external'] = array('ed_external.gif', get_string('wikiexternalurl', 'wiki'), $tag[0], $tag[1], get_string('wikiexternalurl', 'wiki'));
    $tag = wiki_parser_get_token($editor, 'list');
    $wiki_editor['u_list'] = array('ed_ul.gif', get_string('wikiunorderedlist', 'wiki'), '\\n' . $tag[0], '', '');
    $wiki_editor['o_list'] = array('ed_ol.gif', get_string('wikiorderedlist', 'wiki'), '\\n' . $tag[1], '', '');
    $tag = wiki_parser_get_token($editor, 'image');
    $wiki_editor['image'] = array('ed_img.gif', get_string('wikiimage', 'wiki'), $tag[0], $tag[1], get_string('wikiimage', 'wiki'));
    $tag = wiki_parser_get_token($editor, 'header');
    $wiki_editor['h1'] = array('ed_h1.gif', get_string('wikiheader', 'wiki', 1), '\\n' . $tag . ' ', ' ' . $tag . '\\n', get_string('wikiheader', 'wiki', 1));
    $wiki_editor['h2'] = array('ed_h2.gif', get_string('wikiheader', 'wiki', 2), '\\n' . $tag . $tag . ' ', ' ' . $tag . $tag . '\\n', get_string('wikiheader', 'wiki', 2));
    $wiki_editor['h3'] = array('ed_h3.gif', get_string('wikiheader', 'wiki', 3), '\\n' . $tag . $tag . $tag . ' ', ' ' . $tag . $tag . $tag . '\\n', get_string('wikiheader', 'wiki', 3));
    $tag = wiki_parser_get_token($editor, 'line_break');
    $wiki_editor['hr'] = array('ed_hr.gif', get_string('wikihr', 'wiki'), '\\n' . $tag . '\\n', '', '');
    $tag = wiki_parser_get_token($editor, 'nowiki');
    $wiki_editor['nowiki'] = array('ed_nowiki.gif', get_string('wikinowikitext', 'wiki'), $tag[0], $tag[1], get_string('wikinowikitext', 'wiki'));

    $OUTPUT->heading(strtoupper(get_string('format' . $editor, 'wiki')));

    $PAGE->requires->js('/mod/wiki/editors/wiki/buttons.js');

    echo $OUTPUT->container_start('mdl-align');
    foreach ($wiki_editor as $button) {
        echo "<a href=\"javascript:insertTags";
        echo "('" . $button[2] . "','" . $button[3] . "','" . $button[4] . "');\">";
        echo "<img width=\"23\" height=\"22\" src=\"$CFG->wwwroot/mod/wiki/editors/wiki/images/$button[0]\" alt=\"" . $button[1] . "\" title=\"" . $button[1] . "\" />";
        echo "</a>";
    }
    echo $OUTPUT->container_end();

    echo $OUTPUT->container_start('mdl-align');
    echo '<form method="post" id="mform1" action="' . $action . '">';
    echo $OUTPUT->container(print_textarea(false, 20, 60, 0, 0, "newcontent", $content, 0, true), false, 'wiki_editor');
    echo $OUTPUT->container_start();
    wiki_print_edit_form_default_fields($editor, $pageid, $version, $upload, $deleteuploads);
    echo $OUTPUT->container_end();
    echo '</form>';
    echo $OUTPUT->container_end();
}
