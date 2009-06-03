<?php

// Don't let lib/setup.php set any cookies
// as we will be executing under the OS security
// context of the user we are trying to login, rather than
// of the webserver.
$nomoodlecookie=true;

require_once(dirname(dirname(dirname(__FILE__)))."/config.php");

//HTTPS is potentially required in this page
httpsrequired();

$authsequence = get_enabled_auth_plugins(true); // auths, in sequence
if (!in_array('ldap',$authsequence,true)) {
    print_error('ldap_isdisabled','auth');
}

$authplugin = get_auth_plugin('ldap');
if (empty($authplugin->config->ntlmsso_enabled)) {
    print_error('ntlmsso_isdisabled','auth');
}

$sesskey = required_param('sesskey', PARAM_RAW);
$file = $CFG->dirroot . '/pix/spacer.gif';

if ($authplugin->ntlmsso_magic($sesskey) 
    && file_exists($file)) {

    if (!empty($authplugin->config->ntlmsso_ie_fastpath)) {
        if (check_browser_version('MSIE')) {
            redirect($CFG->wwwroot . '/auth/ldap/ntlmsso_finish.php');
        }
    } 

    // Serve GIF
    // Type
    header('Content-Type: image/gif');
    header('Content-Length: '.filesize($file));

    // Output file
    $handle=fopen($file,'r');
    fpassthru($handle);
    fclose($handle);
    exit;
} else {
    print_error('ntlmsso_iwamagicnotenabled','auth');
}

?>
