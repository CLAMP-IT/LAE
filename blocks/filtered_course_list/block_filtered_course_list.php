<?PHP 

include_once($CFG->dirroot . '/course/lib.php');

class block_filtered_course_list extends block_list {
    function init() {
        $this->title   = get_string('blockname', 'block_filtered_course_list');
        $this->version = 20080116;
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $THEME, $CFG, $USER;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content         = new stdClass;
        $this->content->items  = array();
        $this->content->icons  = array();
        $this->content->footer = '';
        $icon         = "<img src=\"$CFG->pixpath/i/course.gif\"" .
                        " class=\"icon\" alt=\"" .
                        get_string("course") .
                        "\" />";

        // Obtain values from our config settings
        $filter_type = 'term';
        if (isset($CFG->block_filtered_course_list_filtertype)) {
            $filter_type = $CFG->block_filtered_course_list_filtertype;
        }

        $term_current = ' ';
        if (isset($CFG->block_filtered_course_list_termcurrent)) {
            $term_current = $CFG->block_filtered_course_list_termcurrent;
        }

        $term_future = ' ';
        if (isset($CFG->block_filtered_course_list_termfuture)) {
            $term_future = $CFG->block_filtered_course_list_termfuture;
        }

        $category_ids = ' ';
        if (isset($CFG->block_filtered_course_list_categories)) {
            $category_ids = $CFG->block_filtered_course_list_categories;
        }

        $adminseesall = true;
        if (isset($CFG->block_filtered_course_list_adminview)) {
            if ($CFG->block_filtered_course_list_adminview == 'own'){
                $adminseesall = false;
            }
        }

        if (empty($CFG->disablemycourses) and
            !empty($USER->id) and
            !(has_capability('moodle/course:view', get_context_instance(CONTEXT_SYSTEM))) and 
            !isguest()) {
            // If user can't view all courses, just print My Courses
            $all_courses = get_my_courses($USER->id);

            if ($all_courses) {
                switch ($filter_type) {
                    case 'term':
                        $filtered_courses = $this->_filter_by_term($all_courses,
                                                                   $term_current,
                                                                   $term_future);
                        break;

                    case 'categories':
			$filtered_courses = $this->_filter_by_category($all_courses,
                                                                       $category_ids);
                        break;

                    case 'custom':
                        //$filtered_courses = $this->_filter_by_custom($all_courses,
                        //                                             $category_ids);
                        break;

                    default:
                        // This is unexpected
                        break;
                }

                foreach ($filtered_courses as $section => $course_list) {
                    if (count($course_list) == 0) {
                        continue;
                    }

                    $this->content->items[]= "<center>$section</center>";
                    $this->content->icons[]= '';

                    foreach ($course_list as $course) {
                        $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                        $this->content->items[]= "<a $linkcss title=\"" .
                                                 format_string($course->shortname) .
                                                 "\" " .
                                                 "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" .
                                                 format_string($course->fullname) .
                                                 "</a>";
                        $this->content->icons[]= $icon;
                    }

                    $this->content->items[]="<hr width=\"50%\">";
                    $this->content->icons[]="";

                    // If we can update any course of the view all isn't hidden,
                    // show the view all courses link
                    if (has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) || 
                        empty($CFG->block_filtered_course_list_hideallcourseslink)) {
                        $this->content->footer = "<a href=\"$CFG->wwwroot/course/index.php\">" .
                                                 get_string("fulllistofcourses") .
                                                 "</a> ...";
                    }
                }
            }
        } else {
            // Parent = 0   ie top-level categories only
            $categories = get_categories("0");  

            //Check we have categories
            if ($categories) {
                // Just print top level category links
                if (count($categories) > 1 ||
                   (count($categories) == 1 &&
                    count($course_list) > 100)) {
                    foreach ($categories as $category) {
                        $linkcss = $category->visible ? "" : " class=\"dimmed\" ";
                        $this->content->items[] = "<a $linkcss href=\"$CFG->wwwroot/course/category.php?id=$category->id\">" .
                                                  format_string($category->name) .
                                                  "</a>";
                        $this->content->icons[] = $icon;
                    }

                    $this->content->footer .= "<br><a href=\"$CFG->wwwroot/course/index.php\">" .
                                              get_string('searchcourses') .
                                              '</a> ...<br />';

                    // If we can update any course of the view all isn't hidden,
                    // show the view all courses link
                    if (has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) ||
                        empty($CFG->block_filtered_course_list_hideallcourseslink)) {
                        $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">" .
                                                  get_string('fulllistofcourses') .
                                                  '</a> ...<br>';
                    }

                    $this->title = get_string('blockname', 'block_filtered_course_list');
                } else {
                    // Just print course names of single category
                    $category = array_shift($categories);
                    $courses = get_courses($category->id);

                    if ($courses) {
                        foreach ($courses as $course) {
                            $linkcss = $course->visible ? "" : " class=\"dimmed\" ";

                            $this->content->items[] = "<a $linkcss title=\"" .
                                                      format_string($course->shortname) .
                                                      "\" " .
                                                      "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" .
                                                      format_string($course->fullname) .
                                                      "</a>";
                            $this->content->icons[]=$icon;
                        }

                        // If we can update any course of the view all isn't hidden,
                        // show the view all courses link
                        if (has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) ||
                            empty($CFG->block_filtered_course_list_hideallcourseslink)) {
                            $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">" .
                                                      get_string('fulllistofcourses') .
                                                      '</a> ...';
                        }
                    }
                }
            }
        }
        return $this->content;
    }

  
    function _filter_by_term($courses, $term_current, $term_future) {
        $results = array('Current Courses' => array(),
                         'Future Courses'  => array(),
                         'Old Courses'     => array());

        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }

            if (stristr($course->shortname, $term_current)) {
                $results['Current Courses'] []= $course;
            } else if (stristr($course->shortname, $term_future)) {
                $results['Future Courses']  []= $course;
            } else {
                $results['Old Courses']     []= $course;
            }
        }

        return $results;
    }

    function _filter_by_category($courses, $cat_ids) {
        $filter_categories = explode(',', $cat_ids);
        $results           = array('Course List' => array());

        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }

            foreach ($filter_categories as $filter_category) {
                if ($course->category == $filter_category) {
                    $results['Course List'] []= $course;
                    break;
                }
            }
        }

        return $results;
    }
}

?>
