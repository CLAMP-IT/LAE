<?php  // $Id$
/// This page prints a particular instance of glossary
    require_once("../../config.php");
    require_once("lib.php");
    require_once("$CFG->libdir/rsslib.php");

    $id = optional_param('id', 0, PARAM_INT);           // Course Module ID
    $g  = optional_param('g', 0, PARAM_INT);            // Glossary ID

    $tab  = optional_param('tab', GLOSSARY_NO_VIEW, PARAM_ALPHA);    // browsing entries by categories?
    $displayformat = optional_param('displayformat',-1, PARAM_INT);  // override of the glossary display format

    $mode       = optional_param('mode', '', PARAM_ALPHA);           // term entry cat date letter search author approval
    $hook       = optional_param('hook', '', PARAM_CLEAN);           // the term, entry, cat, etc... to look for based on mode
    $fullsearch = optional_param('fullsearch', 0,PARAM_INT);         // full search (concept and definition) when searching?
    $sortkey    = optional_param('sortkey', '', PARAM_ALPHA);// Sorted view: CREATION | UPDATE | FIRSTNAME | LASTNAME...
    $sortorder  = optional_param('sortorder', 'ASC', PARAM_ALPHA);   // it defines the order of the sorting (ASC or DESC)
    $offset     = optional_param('offset', 0,PARAM_INT);             // entries to bypass (for paging purposes)
    $page       = optional_param('page', 0,PARAM_INT);               // Page to show (for paging purposes)
    $show       = optional_param('show', '', PARAM_ALPHA);           // [ concept | alias ] => mode=term hook=$show

    if (!empty($id)) {
        if (! $cm = get_coursemodule_from_id('glossary', $id)) {
            error("Course Module ID was incorrect");
        }
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
        if (! $glossary = get_record("glossary", "id", $cm->instance)) {
            error("Course module is incorrect");
        }
    } else if (!empty($g)) {
        if (! $glossary = get_record("glossary", "id", $g)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $glossary->course)) {
            error("Could not determine which course this belonged to!");
        }
        if (!$cm = get_coursemodule_from_instance("glossary", $glossary->id, $course->id)) {
            error("Could not determine which course module this belonged to!");
        }
        $id = $cm->id;
    } else {
        error("Must specify glossary ID or course module ID");
    }

    require_course_login($course->id, true, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

/// Loading the textlib singleton instance. We are going to need it.
    $textlib = textlib_get_instance();

/// redirecting if adding a new entry
    if ($tab == GLOSSARY_ADDENTRY_VIEW ) {
        redirect("edit.php?id=$cm->id&amp;mode=$mode");
    }

/// setting the defaut number of entries per page if not set

    if ( !$entriesbypage = $glossary->entbypage ) {
        $entriesbypage = $CFG->glossary_entbypage;
    }

/// If we have received a page, recalculate offset
    if ($page != 0 && $offset == 0) {
        $offset = $page * $entriesbypage;
    }

/// setting the default values for the display mode of the current glossary
/// only if the glossary is viewed by the first time
    if ( $dp = get_record('glossary_formats','name', addslashes($glossary->displayformat)) ) {
    /// Based on format->defaultmode, we build the defaulttab to be showed sometimes
        switch ($dp->defaultmode) {
            case 'cat':
                $defaulttab = GLOSSARY_CATEGORY_VIEW;
                break;
            case 'date':
                $defaulttab = GLOSSARY_DATE_VIEW;
                break;
            case 'author':
                $defaulttab = GLOSSARY_AUTHOR_VIEW;
                break;
            default:
                $defaulttab = GLOSSARY_STANDARD_VIEW;
        }
    /// Fetch the rest of variables
        $printpivot = $dp->showgroup;
        if ( $mode == '' and $hook == '' and $show == '') {
            $mode      = $dp->defaultmode;
            $hook      = $dp->defaulthook;
            $sortkey   = $dp->sortkey;
            $sortorder = $dp->sortorder;
        }
    } else {
        $defaulttab = GLOSSARY_STANDARD_VIEW;
        $printpivot = 1;
        if ( $mode == '' and $hook == '' and $show == '') {
            $mode = 'letter';
            $hook = 'ALL';
        }
    }

    if ( $displayformat == -1 ) {
         $displayformat = $glossary->displayformat;
    }

    if ( $show ) {
        $mode = 'term';
        $hook = $show;
        $show = '';
    }
/// Processing standard security processes
    if ($course->id != SITEID) {
        require_login($course->id);
    }
    if (!$cm->visible and !has_capability('moodle/course:viewhiddenactivities', $context)) {
        print_header();
        notice(get_string("activityiscurrentlyhidden"));
    }
    add_to_log($course->id, "glossary", "view", "view.php?id=$cm->id&amp;tab=$tab", $glossary->id, $cm->id);

/// stablishing flag variables
    if ( $sortorder = strtolower($sortorder) ) {
        if ($sortorder != 'asc' and $sortorder != 'desc') {
            $sortorder = '';
        }
    }
    if ( $sortkey = strtoupper($sortkey) ) {
        if ($sortkey != 'CREATION' and
            $sortkey != 'UPDATE' and
            $sortkey != 'FIRSTNAME' and
            $sortkey != 'LASTNAME'
            ) {
            $sortkey = '';
        }
    }

    switch ( $mode = strtolower($mode) ) {
    case 'search': /// looking for terms containing certain word(s)
        $tab = GLOSSARY_STANDARD_VIEW;

        //Clean a bit the search string
        $hook = trim(strip_tags($hook));

    break;

    case 'entry':  /// Looking for a certain entry id
        $tab = GLOSSARY_STANDARD_VIEW;
        if ( $dp = get_record("glossary_formats","name", $glossary->displayformat) ) {
            $displayformat = $dp->popupformatname;
        }
    break;

    case 'cat':    /// Looking for a certain cat
        $tab = GLOSSARY_CATEGORY_VIEW;
        if ( $hook > 0 ) {
            $category = get_record("glossary_categories","id",$hook);
        }
    break;

    case 'approval':    /// Looking for entries waiting for approval
        $tab = GLOSSARY_APPROVAL_VIEW;
        if ( !$hook and !$sortkey and !$sortorder) {
            $hook = 'ALL';
        }
    break;

    case 'term':   /// Looking for entries that include certain term in its concept, definition or aliases
        $tab = GLOSSARY_STANDARD_VIEW;
    break;

    case 'date':
        $tab = GLOSSARY_DATE_VIEW;
        if ( !$sortkey ) {
            $sortkey = 'UPDATE';
        }
        if ( !$sortorder ) {
            $sortorder = 'desc';
        }
    break;

    case 'author':  /// Looking for entries, browsed by author
        $tab = GLOSSARY_AUTHOR_VIEW;
        if ( !$hook ) {
            $hook = 'ALL';
        }
        if ( !$sortkey ) {
            $sortkey = 'FIRSTNAME';
        }
        if ( !$sortorder ) {
            $sortorder = 'asc';
        }
    break;

    case 'letter':  /// Looking for entries that begin with a certain letter, ALL or SPECIAL characters
    default:
        $tab = GLOSSARY_STANDARD_VIEW;
        if ( !$hook ) {
            $hook = 'ALL';
        }
    break;
    }

    switch ( $tab ) {
    case GLOSSARY_IMPORT_VIEW:
    case GLOSSARY_EXPORT_VIEW:
    case GLOSSARY_APPROVAL_VIEW:
        $showcommonelements = 0;
    break;

    default:
        $showcommonelements = 1;
    break;
    }

/// Printing the heading
    $strglossaries = get_string("modulenameplural", "glossary");
    $strglossary = get_string("modulename", "glossary");
    $strallcategories = get_string("allcategories", "glossary");
    $straddentry = get_string("addentry", "glossary");
    $strnoentries = get_string("noentries", "glossary");
    $strsearchconcept = get_string("searchconcept", "glossary");
    $strsearchindefinition = get_string("searchindefinition", "glossary");
    $strsearch = get_string("search");
    $strwaitingapproval = get_string('waitingapproval', 'glossary');

/// If we are in approval mode, prit special header
    if ($tab == GLOSSARY_APPROVAL_VIEW) {
        require_capability('mod/glossary:approve', $context);

        $navigation = build_navigation($strwaitingapproval, $cm);
        print_header_simple(format_string($glossary->name), "", $navigation, "", "", true,
            update_module_button($cm->id, $course->id, $strglossary), navmenu($course, $cm));

        print_heading($strwaitingapproval);
    } else { /// Print standard header
        $navigation = build_navigation('', $cm);
        print_header_simple(format_string($glossary->name), "", $navigation, "", "", true,
            update_module_button($cm->id, $course->id, $strglossary), navmenu($course, $cm));
    }

/// All this depends if whe have $showcommonelements
    if ($showcommonelements) {
    /// To calculate available options
        $availableoptions = '';

    /// Decide about to print the import link
        if (has_capability('mod/glossary:import', $context)) {
            $availableoptions = '<span class="helplink">' .
                                '<a href="' . $CFG->wwwroot . '/mod/glossary/import.php?id=' . $cm->id . '"' .
                                '  title="' . s(get_string('importentries', 'glossary')) . '">' .
                                get_string('importentries', 'glossary') . '</a>' .
                                '</span>';
        }
    /// Decide about to print the export link
        if (has_capability('mod/glossary:export', $context)) {
            if ($availableoptions) {
                $availableoptions .= '&nbsp;/&nbsp;';
            }
            $availableoptions .='<span class="helplink">' .
                                '<a href="' . $CFG->wwwroot . '/mod/glossary/export.php?id=' . $cm->id .
                                '&amp;mode='.$mode . '&amp;hook=' . urlencode($hook) . '"' .
                                '  title="' . s(get_string('exportentries', 'glossary')) . '">' .
                                get_string('exportentries', 'glossary') . '</a>' .
                                '</span>';
        }

    /// Decide about to print the approval link
        if (has_capability('mod/glossary:approve', $context)) {
        /// Check we have pending entries
            if ($hiddenentries = count_records_select('glossary_entries',"glossaryid  = $glossary->id and approved = 0")) {
                if ($availableoptions) {
                    $availableoptions .= '<br />';
                }
                $availableoptions .='<span class="helplink">' .
                                    '<a href="' . $CFG->wwwroot . '/mod/glossary/view.php?id=' . $cm->id .
                                    '&amp;mode=approval' . '"' .
                                    '  title="' . s(get_string('waitingapproval', 'glossary')) . '">' .
                                    get_string('waitingapproval', 'glossary') . ' ('.$hiddenentries.')</a>' .
                                    '</span>';
            }
        }

    /// Start to print glossary controls
//        print_box_start('glossarycontrol clearfix');
        echo '<div class="glossarycontrol" style="text-align: right">';
        echo $availableoptions;

    /// If rss are activated at site and glossary level and this glossary has rss defined, show link
        if (isset($CFG->enablerssfeeds) && isset($CFG->glossary_enablerssfeeds) &&
            $CFG->enablerssfeeds && $CFG->glossary_enablerssfeeds && $glossary->rsstype && $glossary->rssarticles) {

            $tooltiptext = get_string("rsssubscriberss","glossary",format_string($glossary->name,true));
            if (empty($USER->id)) {
                $userid = 0;
            } else {
                $userid = $USER->id;
            }
//            print_box_start('rsslink');
            echo '<span class="wrap rsslink">';
            rss_print_link($course->id, $userid, "glossary", $glossary->id, $tooltiptext);
            echo '</span>';
//            print_box_end();
        }

    /// The print icon
        if ( $showcommonelements and $mode != 'search') {
            if (has_capability('mod/glossary:manageentries', $context) or $glossary->allowprintview) {
//                print_box_start('printicon');
                echo '<span class="wrap printicon">';
                echo " <a title =\"". get_string("printerfriendly","glossary") ."\" href=\"print.php?id=$cm->id&amp;mode=$mode&amp;hook=".urlencode($hook)."&amp;sortkey=$sortkey&amp;sortorder=$sortorder&amp;offset=$offset\"><img class=\"icon\" src=\"print.gif\" alt=\"". get_string("printerfriendly","glossary") . "\" /></a>";
                echo '</span>';
//                print_box_end();
            }
        }
    /// End glossary controls
//        print_box_end(); /// glossarycontrol
        echo '</div>';
        
//        print_box('&nbsp;', 'clearer');
    }

/// Info box
    if ( $glossary->intro && $showcommonelements ) {
        $options = new stdclass;
        $options->para = false;
        print_box(format_text($glossary->intro, FORMAT_MOODLE, $options), 'generalbox', 'intro');
    }

/// Search box
    if ($showcommonelements ) {
        echo '<form method="post" action="view.php">';

        echo '<table class="boxaligncenter" width="70%" border="0">';
        echo '<tr><td align="center" class="glossarysearchbox">';

        echo '<input type="submit" value="'.$strsearch.'" name="searchbutton" /> ';
        if ($mode == 'search') {
            echo '<input type="text" name="hook" size="20" value="'.s($hook).'" alt="'.$strsearch.'" /> ';
        } else {
            echo '<input type="text" name="hook" size="20" value="" alt="'.$strsearch.'" /> ';
        }
        if ($fullsearch || $mode != 'search') {
            $fullsearchchecked = 'checked="checked"';
        } else {
            $fullsearchchecked = '';
        }
        echo '<input type="checkbox" name="fullsearch" id="fullsearch" value="1" '.$fullsearchchecked.' />';
        echo '<input type="hidden" name="mode" value="search" />';
        echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
        echo '<label for="fullsearch">'.$strsearchindefinition.'</label>';
        echo '</td></tr></table>';

        echo '</form>';

        echo '<br />';
    }

/// Show the add entry button if allowed
    if (has_capability('mod/glossary:write', $context) && $showcommonelements ) {
        echo '<div class="singlebutton glossaryaddentry">';
        echo "<form id=\"newentryform\" method=\"get\" action=\"$CFG->wwwroot/mod/glossary/edit.php\">";
        echo '<div>';
        echo "<input type=\"hidden\" name=\"id\" value=\"$cm->id\" />";
        echo '<input type="submit" value="';
        print_string('addentry', 'glossary');
        echo '" />';
        echo '</div>';
        echo '</form>';
        echo "</div>\n";
    }

    echo '<br />';

    include("tabs.php");

    include_once("sql.php");

/// printing the entries
    $entriesshown = 0;
    $currentpivot = '';
    $ratingsmenuused = NULL;
    $paging = NULL;

    if ($allentries) {

        //Decide if we must show the ALL link in the pagebar
        $specialtext = '';
        if ($glossary->showall) {
            $specialtext = get_string("allentries","glossary");
        }

        //Build paging bar
        $paging = glossary_get_paging_bar($count, $page, $entriesbypage, "view.php?id=$id&amp;mode=$mode&amp;hook=$hook&amp;sortkey=$sortkey&amp;sortorder=$sortorder&amp;fullsearch=$fullsearch&amp;",9999,10,'&nbsp;&nbsp;', $specialtext, -1);

        echo '<div class="paging">';
        echo $paging;
        echo '</div>';

        $ratings = NULL;
        $ratingsmenuused = false;
        if ($glossary->assessed and isloggedin() and !isguestuser()) {
            $ratings = new object();
            if ($ratings->scale = make_grades_menu($glossary->scale)) {
                $ratings->assesstimestart = $glossary->assesstimestart;
                $ratings->assesstimefinish = $glossary->assesstimefinish;
            }
            if ($glossary->assessed == 2 and !has_capability('mod/glossary:rate', $context)) {
                $ratings->allow = false;
            } else {
                $ratings->allow = true;
            }
            $formsent = 1;

            echo "<form method=\"post\" action=\"rate.php\">";
            echo "<div>";
            echo "<input type=\"hidden\" name=\"glossaryid\" value=\"$glossary->id\" />";
        }

        foreach ($allentries as $entry) {

            // Setting the pivot for the current entry
            $pivot = $entry->glossarypivot;
            $upperpivot = $textlib->strtoupper($pivot);
            // Reduce pivot to 1cc if necessary
            if ( !$fullpivot ) {
                $upperpivot = $textlib->substr($upperpivot, 0, 1);
            }

            // if there's a group break
            if ( $currentpivot != $upperpivot ) {

                // print the group break if apply
                if ( $printpivot )  {
                    $currentpivot = $upperpivot;

                    echo '<div>';
                    echo '<table cellspacing="0" class="glossarycategoryheader">';

                    echo '<tr>';
                    $pivottoshow = $currentpivot;
                    if ( isset($entry->userispivot) ) {
                    // printing the user icon if defined (only when browsing authors)
                        echo '<th align="left">';

                        $user = get_record("user","id",$entry->userid);
                        print_user_picture($user, $course->id, $user->picture);
                        $pivottoshow = fullname($user, has_capability('moodle/site:viewfullnames', get_context_instance(CONTEXT_COURSE, $course->id)));
                    } else {
                        echo '<th >';
                    }

                    print_heading($pivottoshow);
                    echo "</th></tr></table></div>\n";

                }
            }

            $concept = $entry->concept;
            $definition = $entry->definition;

            /// highlight the term if necessary
            if ($mode == 'search') {
                //We have to strip any word starting by + and take out words starting by -
                //to make highlight works properly
                $searchterms = explode(' ', $hook);    // Search for words independently
                foreach ($searchterms as $key => $searchterm) {
                    if (preg_match('/^\-/',$searchterm)) {
                        unset($searchterms[$key]);
                    } else {
                        $searchterms[$key] = preg_replace('/^\+/','',$searchterm);
                    }
                    //Avoid highlight of <2 len strings. It's a well known hilight limitation.
                    if (strlen($searchterm) < 2) {
                        unset($searchterms[$key]);
                    }
                }
                $strippedsearch = implode(' ', $searchterms);    // Rebuild the string
                $entry->highlight = $strippedsearch;
            }

            /// and finally print the entry.

            if ( glossary_print_entry($course, $cm, $glossary, $entry, $mode, $hook,1,$displayformat,$ratings) ) {
                $ratingsmenuused = true;
            }

            $entriesshown++;
        }
    }
    if ( !$entriesshown ) {
        print_simple_box('<div style="text-align:center">' . get_string("noentries","glossary") . '</div>',"center","95%");
    }


    if ($ratingsmenuused) {

        echo "<div class=\"boxaligncenter\"><input type=\"submit\" value=\"".get_string("sendinratings", "glossary")."\" />";
        if ($glossary->scale < 0) {
            if ($scale = get_record("scale", "id", abs($glossary->scale))) {
                print_scale_menu_helpbutton($course->id, $scale );
            }
        }
        echo "</div>";
    }

    if (!empty($formsent)) {
        // close the form properly if used
        echo "</div>";
        echo "</form>";
    }

    if ( $paging ) {
        echo '<hr />';
        echo '<div class="paging">';
        echo $paging;
        echo '</div>';
    }
    echo '<br />';
    glossary_print_tabbed_table_end();

/// Finish the page

    print_footer($course);

?>
