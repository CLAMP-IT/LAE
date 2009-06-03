<?PHP  // $Id: phpinfo.php,v 1.9 2007/04/30 17:08:36 skodak Exp $
       // phpinfo.php - shows phpinfo for the current server

    require_once("../config.php");
    require_once($CFG->libdir.'/adminlib.php');

    admin_externalpage_setup('phpinfo');

    admin_externalpage_print_header();

    echo '<div class="phpinfo">';

    ob_start();
    phpinfo(INFO_GENERAL + INFO_CONFIGURATION + INFO_MODULES);
    $html = ob_get_contents();
    ob_end_clean();

/// Delete styles from output
    $html = preg_replace('#(\n?<style[^>]*?>.*?</style[^>]*?>)|(\n?<style[^>]*?/>)#is', '', $html);
    $html = preg_replace('#(\n?<head[^>]*?>.*?</head[^>]*?>)|(\n?<head[^>]*?/>)#is', '', $html);
/// Delete DOCTYPE from output
    $html = preg_replace('/<!DOCTYPE html PUBLIC.*?>/is', '', $html);
/// Delete body and html tags
    $html = preg_replace('/<html.*?>.*?<body.*?>/is', '', $html);
    $html = preg_replace('/<\/body><\/html>/is', '', $html);

    echo $html;

    echo '</div>';

    admin_externalpage_print_footer();

?>
