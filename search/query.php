<?php
    /**
    * Global Search Engine for Moodle
    *
    * @package search
    * @category core
    * @subpackage search_engine
    * @author Michael Champanis (mchampan) [cynnical@gmail.com], Valery Fremaux [valery.fremaux@club-internet.fr] > 1.8
    * @date 2008/03/31
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    *
    * The query page - accepts a user-entered query string and returns results.
    *
    * Queries are boolean-aware, e.g.:
    *
    * '+'      term required
    * '-'      term must not be present
    * ''       (no modifier) term's presence increases rank, but isn't required
    * 'field:' search this field
    *
    * Examples:
    *
    * 'earthquake +author:michael'
    *   Searches for documents written by 'michael' that contain 'earthquake'
    *
    * 'earthquake +doctype:wiki'
    *   Search all wiki pages for 'earthquake'
    *
    * '+author:helen +author:foster'
    *   All articles written by Helen Foster
    *
    */
    
    /**
    * includes and requires
    */
    require_once('../config.php');
    require_once("$CFG->dirroot/search/lib.php");
    // include "debugging.php";


    if ($CFG->forcelogin) {
        require_login();
    }
    
    if (empty($CFG->enableglobalsearch)) {
        error(get_string('globalsearchdisabled', 'search'));
    }
    
    $adv = new Object();
    
/// check for php5, but don't die yet (see line 52)

    require_once($CFG->dirroot.'/search/querylib.php');

    $page_number  = optional_param('page', -1, PARAM_INT);
    $pages        = ($page_number == -1) ? false : true;
    $advanced     = (optional_param('a', '0', PARAM_INT) == '1') ? true : false;
    $query_string = stripslashes(optional_param('query_string', '', PARAM_CLEAN));

/// discard harmfull searches  

    if (!isset($CFG->block_search_utf8dir)){
        set_config('block_search_utf8dir', 1);
    }
        
/// discard harmfull searches  

    if (preg_match("/^[\*\?]+$/", $query_string)){
        $query_string = '';
        $error = get_string('fullwildcardquery','search');
    }
    

    if ($pages && isset($_SESSION['search_advanced_query'])) {
        // if both are set, then we are busy browsing through the result pages of an advanced query
        $adv = unserialize($_SESSION['search_advanced_query']);
    } elseif ($advanced) {
        // otherwise we are dealing with a new advanced query
        unset($_SESSION['search_advanced_query']);
        session_unregister('search_advanced_query');
        
        // chars to strip from strings (whitespace)
        $chars = " \t\n\r\0\x0B,-+";
        
        // retrieve advanced query variables
        $adv->mustappear  = trim(optional_param('mustappear', '', PARAM_CLEAN), $chars);
        $adv->notappear   = trim(optional_param('notappear', '', PARAM_CLEAN), $chars);
        $adv->canappear   = trim(optional_param('canappear', '', PARAM_CLEAN), $chars);
        $adv->module      = optional_param('module', '', PARAM_CLEAN);
        $adv->title       = trim(optional_param('title', '', PARAM_CLEAN), $chars);
        $adv->author      = trim(optional_param('author', '', PARAM_CLEAN), $chars);
    } 

    if ($advanced) {
        //parse the advanced variables into a query string
        //TODO: move out to external query class (QueryParse?)
        
        $query_string = '';
        
        // get all available module types adding third party modules
        $module_types = array_merge(array('all'), array_values(search_get_document_types()));
        $module_types = array_merge($module_types, array_values(search_get_document_types('X_SEARCH_TYPE')));
        $adv->module = in_array($adv->module, $module_types) ? $adv->module : 'all';
        
        // convert '1 2' into '+1 +2' for required words field
        if (strlen(trim($adv->mustappear)) > 0) {
            $query_string  = ' +'.implode(' +', preg_split("/[\s,;]+/", $adv->mustappear));
        } 
        
        // convert '1 2' into '-1 -2' for not wanted words field
        if (strlen(trim($adv->notappear)) > 0) {
            $query_string .= ' -'.implode(' -', preg_split("/[\s,;]+/", $adv->notappear));
        } 
        
        // this field is left untouched, apart from whitespace being stripped
        if (strlen(trim($adv->canappear)) > 0) {
            $query_string .= ' '.implode(' ', preg_split("/[\s,;]+/", $adv->canappear));
        } 
        
        // add module restriction
        $doctypestr = 'doctype';
        $titlestr = 'title';
        $authorstr = 'author';
        if ($adv->module != 'all') {
            $query_string .= " +{$doctypestr}:".$adv->module;
        } 
        
        // create title search string
        if (strlen(trim($adv->title)) > 0) {
            $query_string .= " +{$titlestr}:".implode(" +{$titlestr}:", preg_split("/[\s,;]+/", $adv->title));
        } 
        
        // create author search string
        if (strlen(trim($adv->author)) > 0) {
            $query_string .= " +{$authorstr}:".implode(" +{$authorstr}:", preg_split("/[\s,;]+/", $adv->author));
        } 
        
        // save our options if the query is valid
        if (!empty($query_string)) {
            $_SESSION['search_advanced_query'] = serialize($adv);
        } 
    } 

    // normalise page number
    if ($page_number < 1) {
        $page_number = 1;
    } 

    //run the query against the index ensuring internal coding works in UTF-8
    Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8_CaseInsensitive());
    $sq = new SearchQuery($query_string, $page_number, 10, false);
    
    if (!$site = get_site()) {
        redirect("index.php");
    } 
    
    $strsearch = get_string('search', 'search');
    $strquery  = get_string('enteryoursearchquery', 'search');
    
//    if ($CFG->version < 2007032200){ NOT RELIABLE
    if (!function_exists('build_navigation')){
        print_header("$site->shortname: $strsearch: $strquery", "$site->fullname",
               "<a href=\"index.php\">$strsearch</a> -> $strquery");
    } else {
        $navlinks[] = array('name' => $strsearch, 'link' => "index.php", 'type' => 'misc');
        $navlinks[] = array('name' => $strquery, 'link' => null, 'type' => 'misc');
        $navigation = build_navigation($navlinks);
        $site = get_site();
        print_header("$strsearch", "$site->fullname" , $navigation, "", "", true, "&nbsp;", navmenu($site));
    }
    
    if (!empty($error)){
        notice ($error);
    }
    
    print_box_start();
    print_heading($strquery);
    
    print_box_start();
    
    $vars = get_object_vars($adv);
    
    if (isset($vars)) {
        foreach ($vars as $key => $value) {
            // htmlentities breaks non-ascii chars
            $adv->key = stripslashes($value);
            //$adv->$key = stripslashes(htmlentities($value));
        } 
    }
    ?>
    <form id="query" method="get" action="query.php">
    <?php 
    if (!$advanced) { 
    ?>
        <input type="text" name="query_string" length="50" value="<?php p($query_string) ?>" />
        &nbsp;<input type="submit" value="<?php print_string('search', 'search') ?>" /> &nbsp;
        <a href="query.php?a=1"><?php print_string('advancedsearch', 'search') ?></a> |
        <a href="stats.php"><?php print_string('statistics', 'search') ?></a>
    <?php 
    } 
    else {
        print_box_start();
      ?>
        <input type="hidden" name="a" value="<?php p($advanced); ?>"/>
    
        <table border="0" cellpadding="3" cellspacing="3">
    
        <tr>
          <td width="240"><?php print_string('thesewordsmustappear', 'search') ?>:</td>
          <td><input type="text" name="mustappear" length="50" value="<?php p($adv->mustappear); ?>" /></td>
        </tr>
    
        <tr>
          <td><?php print_string('thesewordsmustnotappear', 'search') ?>:</td>
          <td><input type="text" name="notappear" length="50" value="<?php p($adv->notappear); ?>" /></td>
        </tr>
    
        <tr>
          <td><?php print_string('thesewordshelpimproverank', 'search') ?>:</td>
          <td><input type="text" name="canappear" length="50" value="<?php p($adv->canappear); ?>" /></td>
        </tr>
    
        <tr>
          <td><?php print_string('whichmodulestosearch?', 'search') ?>:</td>
          <td>
            <select name="module">
    <?php 
        foreach($module_types as $mod) {
            if ($mod == $adv->module) {
                if ($mod != 'all'){
                    print "<option value='$mod' selected=\"selected\">".get_string('modulenameplural', $mod)."</option>\n";
                }
                else{
                    print "<option value='$mod' selected=\"selected\">".get_string('all', 'search')."</option>\n";
                }
            } 
            else {
                if ($mod != 'all'){
                    print "<option value='$mod'>".get_string('modulenameplural', $mod)."</option>\n";
                }
                else{
                    print "<option value='$mod'>".get_string('all', 'search')."</option>\n";
                }
            } 
        } 
    ?>
            </select>
          </td>
        </tr>
    
        <tr>
          <td><?php print_string('wordsintitle', 'search') ?>:</td>
          <td><input type="text" name="title" length="50" value="<?php p($adv->title); ?>" /></td>
        </tr>
    
        <tr>
          <td><?php print_string('authorname', 'search') ?>:</td>
          <td><input type="text" name="author" length="50" value="<?php p($adv->author); ?>" /></td>
        </tr>
    
        <tr>
          <td colspan="3" align="center"><br /><input type="submit" value="<?php p(get_string('search', 'search')) ?>" /></td>
        </tr>
    
        <tr>
          <td colspan="3" align="center">
            <table border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td><a href="query.php"><?php print_string('normalsearch', 'search') ?></a> |</td>
                <td>&nbsp;<a href="stats.php"><?php print_string('statistics', 'search') ?></a></td>
              </tr>
            </table>
          </td>
        </tr>
        </table>
    <?php
        print_box_end();
        } 
    ?>
    </form>
    <br/>
    
    <div align="center">
    <?php
    print_string('searching', 'search') . ': ';
    
    if ($sq->is_valid_index()) {
        //use cached variable to show up-to-date index size (takes deletions into account)
        print $CFG->search_index_size;
    } 
    else {
        print "0";
    } 
    
    print ' ';
    print_string('documents', 'search');
    print '.';
    
    if (!$sq->is_valid_index() and has_capability('moodle/site:doanything', get_context_instance(CONTEXT_SYSTEM))) {
        print '<p>' . get_string('noindexmessage', 'search') . '<a href="indexersplash.php">' . get_string('createanindex', 'search')."</a></p>\n";
    } 
    
    ?>
    </div>
    <?php
    print_box_end();
    
/// prints all the results in a box

    if ($sq->is_valid()) {
        print_box_start();
        
        search_stopwatch();
        $hit_count = $sq->count();
        
        print "<br />";
        
        print $hit_count.' '.get_string('resultsreturnedfor', 'search') . " '".s($query_string)."'.";
        print "<br />";
        
        if ($hit_count > 0) {
            $page_links = $sq->page_numbers();
            $hits = $sq->results();
            
            if ($advanced) {
                // if in advanced mode, search options are saved in the session, so
                // we can remove the query string var from the page links, and replace
                // it with a=1 (Advanced = on) instead
                $page_links = preg_replace("/query_string=[^&]+/", 'a=1', $page_links);
            } 
            
            print "<ol>";
            
            $typestr = get_string('type', 'search');
            $scorestr = get_string('score', 'search');
            $authorstr = get_string('author', 'search');

            $searchables = search_collect_searchables(false, false);

            foreach ($hits as $listing) {  
                
                if ($listing->doctype == 'user'){ // A special handle for users                    
                    $icon = print_user_picture ($listing->userid, 0, true, 0, true, false) ;
                } else {
                    $iconpath = $CFG->modpixpath.'/'.$listing->doctype.'/icon.gif';
                    $icon = "<img align=\"top\" src=\"".$iconpath."\" class=\"activityicon\" alt=\"\"/>";
                }
              	$coursename = get_field('course', 'fullname', 'id', $listing->courseid);
                $courseword = mb_convert_case(get_string('course', 'moodle'), MB_CASE_LOWER, 'UTF-8');
                $course = ($listing->doctype != 'user') ? '<strong> ('.$courseword.': \''.$coursename.'\')</strong>' : '' ;

                $title_post_processing_function = $listing->doctype.'_link_post_processing';
                $searchable_instance = $searchables[$listing->doctype];
                if ($searchable_instance->location == 'internal'){
                    require_once "{$CFG->dirroot}/search/documents/{$listing->doctype}_document.php";
                } else {
                    require_once "{$CFG->dirroot}/{$searchable_instance->location}/{$listing->doctype}/search_document.php";
                }
                if (function_exists($title_post_processing_function)) {
                    $listing->title = $title_post_processing_function($listing->title);
                }
    
                echo "<li value='".($listing->number + 1)."'><a href='"
                    .str_replace('DEFAULT_POPUP_SETTINGS', DEFAULT_POPUP_SETTINGS ,$listing->url)
                    ."'>$icon $listing->title</a> $course<br />\n";
                // print "<li value='".($listing->number+1)."'><a href='".str_replace('DEFAULT_POPUP_SETTINGS', DEFAULT_POPUP_SETTINGS ,$listing->url)."'>$listing->title</a><br />\n"
                // ."<em>".search_shorten_url($listing->url, 70)."</em><br />\n"
                echo "{$typestr}: " . $listing->doctype . ", {$scorestr}: " . round($listing->score, 3);
                if (!empty($listing->author) && !is_numeric($listing->author)){
                    echo ", {$authorstr}: ".$listing->author."\n"
                        ."</li>\n";
                }
            }            
            echo "</ol>";
            echo $page_links;
        }     
        print_box_end();
    ?>
    <div align="center">
    <?php 
        print_string('ittook', 'search');
        search_stopwatch(); 
        print_string('tofetchtheseresults', 'search');
    ?>.
    </div>
    
    <?php
    }
    print_box_end();
    print_footer();
?>