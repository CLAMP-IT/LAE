<?php
global $CFG;
require_once "$CFG->libdir/form/select.php";

/**
 * HTML class for a drop down element to select the grade for an activity,
 * used in mod update form
 *
 * @author       Jamie Pratt
 * @access       public
 */
class MoodleQuickForm_modgrade extends MoodleQuickForm_select{


    var $_includenograde=true;
    /**
     * Class constructor
     *
     * @param     string    Select name attribute
     * @param     mixed     Label(s) for the select
     * @param     mixed     Either a typical HTML attribute string or an associative array
     * @param     mixed     $options ignored
     * @access    public
     * @return    void
     */
    function MoodleQuickForm_modgrade($elementName=null, $elementLabel=null, $attributes=null, $includenograde=true)
    {
        HTML_QuickForm_element::HTML_QuickForm_element($elementName, $elementLabel, $attributes, null);
        $this->_type = 'modgrade';
        $this->_includenograde=$includenograde;

    } //end constructor

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param     string    $event  Name of event
     * @param     mixed     $arg    event arguments
     * @param     object    $caller calling object
     * @since     1.0
     * @access    public
     * @return    mixed
     */
    function onQuickFormEvent($event, $arg, &$caller)
    {
        global $COURSE, $CFG;
        switch ($event) {
            case 'createElement':
                $strscale = get_string('scale');
                $strscales = get_string('scales');
                $scales = get_scales_menu($COURSE->id);
                foreach ($scales as $i => $scalename) {
                    $grades[-$i] = $strscale .': '. $scalename;
                }
                if ($this->_includenograde) {
                    $grades[0] = get_string('nograde');
                }
                for ($i=100; $i>=1; $i--) {
                    $grades[$i] = $i;
                }
                $this->load($grades);
                $linkobject = '<span class="helplink"><img height="17" width="17" alt="'.$strscales.'" src="'.$CFG->pixpath .'/help.gif" /></span>';
                $this->setHelpButton(array('/course/scales.php?id='. $COURSE->id .'&amp;list=true', 'ratingscales',
                                     $linkobject, 400, 500, $strscales, 'none', true), 'link_to_popup_window');
                break;
        }
        return parent::onQuickFormEvent($event, $arg, $caller);
    }

}
?>