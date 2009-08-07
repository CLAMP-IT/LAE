<?php // $Id$

require('../config.php');
require('lib.php');

require_login();

if (isguest()) {
    redirect($CFG->wwwroot);
}

if (empty($CFG->messaging)) {
    error("Messaging is disabled on this site");
}

if (has_capability('moodle/site:sendmessage', get_context_instance(CONTEXT_SYSTEM))) {

/// Don't use print_header, for more speed
    $stylesheetshtml = '';
    foreach ($CFG->stylesheets as $stylesheet) {
        $stylesheetshtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />';
    }

/// Select direction
    if ( get_string('thisdirection') == 'rtl' ) {
        $direction = ' dir="rtl"';
    } else {
        $direction = ' dir="ltr"';
    }

    @header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
    echo "<html $direction xmlns=\"http://www.w3.org/1999/xhtml\">\n<head>\n";
    echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
    echo $stylesheetshtml;
    include($CFG->javascript);

    require_once($CFG->libdir .'/editor/htmlEditor.class.php');
    $htmlEditorObject = new htmlEditor();
    echo $htmlEditorObject->configure();

    echo '<title> </title></head>';

/// Script parameters
    $userid   = required_param('id', PARAM_INT);
    $message  = optional_param('message', '', PARAM_CLEANHTML);
    $format   = optional_param('format', FORMAT_MOODLE, PARAM_INT);

/// Check the user we are talking to is valid
    if (! $user = get_record('user', 'id', $userid)) {
        error("User ID was incorrect");
    }

/// Check that the user is not blocking us!!
    if ($contact = get_record('message_contacts', 'userid', $user->id, 'contactid', $USER->id)) {
        if ($contact->blocked and !has_capability('moodle/site:readallmessages', get_context_instance(CONTEXT_SYSTEM))) {
            print_heading(get_string('userisblockingyou', 'message'));
            exit;
        }
    }
    $userpreferences = get_user_preferences(NULL, NULL, $user->id);

    if (!empty($userpreferences['message_blocknoncontacts'])) {  // User is blocking non-contacts
        if (empty($contact)) {   // We are not a contact!
            print_heading(get_string('userisblockingyounoncontact', 'message'));
            exit;
        }
    }

    echo '<body class="message course-1" id="message-send">';

    if ($message!='' and confirm_sesskey()) {   /// Current user has just sent a message

    /// Save it to the database...
        $messageid = message_post_message($USER, $user, addslashes($message), $format, 'direct');

    /// Format the message as HTML
        $options = NULL;
        $options->para = false;
        $options->newlines = true;
        $message = format_text($message, $format, $options);

        $time = userdate(time(), get_string('strftimedatetimeshort'));
        $message = '<div class="message me"><span class="author">'.fullname($USER).'</span> '.
                   '<span class="time">['.$time.']</span>: '.
                   '<span class="content">'.$message.'</span></div>';
        $message = addslashes_js($message);  // So Javascript can write it

    /// Then write it to our own message screen immediately
        echo "\n<script type=\"text/javascript\">\n<!--\n";
        echo 'parent.messages.document.write(\''.$message."\\n');\n";
        echo 'parent.messages.scroll(1,5000000);';
        echo "\n-->\n</script>\n\n";

        add_to_log(SITEID, 'message', 'write', 'history.php?user1='.$user->id.'&amp;user2='.$USER->id.'#m'.$messageid, $user->id);
    }

    echo '<form id="editing" method="post" action="send.php">';
    echo '<div class="message-form">';
    echo '<input type="hidden" name="id" value="'.$user->id.'" />';
    echo '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'" />';

    $usehtmleditor = (can_use_html_editor() && get_user_preferences('message_usehtmleditor', 0));
    if ($usehtmleditor) {
        echo '<div class="message-send-box">';
        print_textarea($usehtmleditor, 5, 34, 0, 0, 'message', '', 0, false, '', 'form-textarea-simple');
        echo '</div>';
        echo '<input class="message-send-button" type="submit" value="'.get_string('sendmessage', 'message').'" />';
        //use_html_editor('message', 'formatblock subscript superscript copy cut paste clean undo redo justifyleft justifycenter justifyright justifyfull lefttoright righttoleft insertorderedlist insertunorderedlist outdent indent inserthorizontalrule createanchor nolink inserttable');
        echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
    } else {
        print_textarea(false, 5, 34, 0, 0, 'message', '');
        echo '<input type="hidden" name="format" value="'.FORMAT_MOODLE.'" />';
        echo '<br /><input class="message-send-button" type="submit" value="'.get_string('sendmessage', 'message').'" />';
    }
    echo '</div>';
    echo '</form>';
    if (!empty($CFG->messagewasjustemailed)) {
        notify(get_string('mailsent', 'message'), 'notifysuccess');
    }
    echo '<div class="noframesjslink"><a target="_parent" href="discussion.php?id='.$userid.'&amp;noframesjs=1">'.get_string('noframesjs', 'message').'</a></div>';

    echo <<<EOF
<script type="text/javascript">
//<![CDATA[
document.getElementById("edit-message").focus();
//]]>
</script>

EOF;

    echo '</body></html>';
}
?>
