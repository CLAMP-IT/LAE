<?php  // $Id: user.php,v 1.1.2.2 2008/03/18 08:29:20 scyrma Exp $

require_once('../config.php');
require_once('lib.php');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$tag = optional_param('tag', '', PARAM_TAG);
        
require_login();

if (empty($CFG->usetags)) {
    error('Tags are disabled!');
}

if (isguestuser()) {
    print_error('noguest');
}

if (!confirm_sesskey()) {
    print_error('sesskey');
}


switch ($action) {
    case 'addinterest':
        if (empty($tag) && $id) { // for backward-compatibility (people saving bookmarks, mostly..)
            $tag = tag_get_name($id);
        } 
        
        tag_set_add('user', $USER->id, $tag);

        redirect($CFG->wwwroot.'/tag/index.php?tag='. rawurlencode($tag));
        break;

    case 'removeinterest':
        if (empty($tag) && $id) { // for backward-compatibility (people saving bookmarks, mostly..)
            $tag = tag_get_name($id);
        } 

        tag_set_delete('user', $USER->id, $tag);

        redirect($CFG->wwwroot.'/tag/index.php?tag='. rawurlencode($tag));
        break;

    case 'flaginappropriate':
        
        tag_set_flag(tag_get_id($tag));
        
        redirect($CFG->wwwroot.'/tag/index.php?tag='. rawurlencode($tag), get_string('responsiblewillbenotified', 'tag'));
        break;

    default:
        error('No action was specified');
        break;
}

?>
