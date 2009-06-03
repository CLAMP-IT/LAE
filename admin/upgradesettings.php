<?php // $Id: upgradesettings.php,v 1.21.2.3 2008/07/04 23:54:22 skodak Exp $

// detects settings that were added during an upgrade, displays a screen for the admin to
// modify them, and then processes modifications

require_once('../config.php');
require_once($CFG->libdir.'/adminlib.php');

$return = optional_param('return', '', PARAM_ALPHA);

/// no guest autologin
require_login(0, false);

$adminroot =& admin_get_root(); // need all settings
admin_externalpage_setup('upgradesettings'); // now hidden page

// now we'll deal with the case that the admin has submitted the form with new settings
if ($data = data_submitted() and confirm_sesskey()) {
    $count = admin_write_settings($data);
    $adminroot =& admin_get_root(true); //reload tree
}

$newsettings = admin_output_new_settings_by_page($adminroot);
if (isset($newsettings['frontpagesettings'])) {
    $frontpage = $newsettings['frontpagesettings'];
    unset($newsettings['frontpagesettings']);
    array_unshift($newsettings, $frontpage);
}
$newsettingshtml = implode($newsettings);
unset($newsettings);

$focus = '';

if (empty($adminroot->errors) and $newsettingshtml === '') {
    // there must be either redirect without message or continue button or else upgrade would be sometimes broken
    if ($return == 'site') {
        redirect("$CFG->wwwroot/");
    } else {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }
}

if (!empty($adminroot->errors)) {
    $firsterror = reset($adminroot->errors);
    $focus = $firsterror->id;
}

// and finally, if we get here, then there are new settings and we have to print a form
// to modify them
admin_externalpage_print_header($focus);

print_box(get_string('upgradesettingsintro','admin'), 'generalbox');

echo '<form action="upgradesettings.php" method="post" id="adminsettings">';
echo '<div>';
echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
echo '<input type="hidden" name="return" value="'.$return.'" />';
echo '<fieldset>';
echo '<div class="clearer"><!-- --></div>';
echo $newsettingshtml;
echo '</fieldset>';
echo '<div class="form-buttons"><input class="form-submit" type="submit" value="'.get_string('savechanges','admin').'" /></div>';
echo '</div>';
echo '</form>';

if (!empty($CFG->adminusehtmleditor)) {
    use_html_editor();
}

print_footer();

?>
