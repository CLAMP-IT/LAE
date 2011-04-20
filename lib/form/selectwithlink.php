<?php
require_once('HTML/QuickForm/select.php');

/**
 * HTML class for a select type element
 *
 * @author       Jamie Pratt
 * @access       public
 */
class MoodleQuickForm_selectwithlink extends HTML_QuickForm_select{
    /**
     * html for help button, if empty then no help
     *
     * @var string
     */
    var $_helpbutton='';
    var $_hiddenLabel=false;
    var $_link=null;
    var $_linklabel=null;
    var $_linkreturn=null;

    function MoodleQuickForm_selectwithlink($elementName=null, $elementLabel=null, $options=null, $attributes=null, $linkdata=null)
    {
        if (!empty($linkdata['link']) && !empty($linkdata['label'])) {
            $this->_link = $linkdata['link'];
            $this->_linklabel = $linkdata['label'];
        }

        if (!empty($linkdata['return'])) {
            $this->_linkreturn = $linkdata['return'];
        }

        parent::HTML_QuickForm_select($elementName, $elementLabel, $options, $attributes);
    } //end constructor

    function setHiddenLabel($hiddenLabel){
        $this->_hiddenLabel = $hiddenLabel;
    }
    function toHtml(){
        $retval = '';
        if ($this->_hiddenLabel){
            $this->_generateId();
            $retval = '<label class="accesshide" for="'.$this->getAttribute('id').'" >'.
                        $this->getLabel().'</label>'.parent::toHtml();
        } else {
             $retval = parent::toHtml();
        }

        if (!empty($this->_link)) {
            if (!empty($this->_linkreturn) && is_array($this->_linkreturn)) {
                $appendchar = '?';
                if (strstr($this->_link, '?')) {
                    $appendchar = '&amp;';
                }

                foreach ($this->_linkreturn as $key => $val) {
                    $this->_link .= $appendchar."$key=$val";
                    $appendchar = '&amp;';
                }
            }

            $retval .= '<a style="margin-left: 5px" href="'.$this->_link.'">'.$this->_linklabel.'</a>';
        }

        return $retval;
    }
   /**
    * Automatically generates and assigns an 'id' attribute for the element.
    *
    * Currently used to ensure that labels work on radio buttons and
    * checkboxes. Per idea of Alexander Radivanovich.
    * Overriden in moodleforms to remove qf_ prefix.
    *
    * @access private
    * @return void
    */
    function _generateId()
    {
        static $idx = 1;

        if (!$this->getAttribute('id')) {
            $this->updateAttributes(array('id' => 'id_'. substr(md5(microtime() . $idx++), 0, 6)));
        }
    } // end func _generateId
    /**
     * set html for help button
     *
     * @access   public
     * @param array $help array of arguments to make a help button
     * @param string $function function name to call to get html
     */
    function setHelpButton($helpbuttonargs, $function='helpbutton'){
        if (!is_array($helpbuttonargs)){
            $helpbuttonargs=array($helpbuttonargs);
        }else{
            $helpbuttonargs=$helpbuttonargs;
        }
        //we do this to to return html instead of printing it
        //without having to specify it in every call to make a button.
        if ('helpbutton' == $function){
            $defaultargs=array('', '', 'moodle', true, false, '', true);
            $helpbuttonargs=$helpbuttonargs + $defaultargs ;
        }
        $this->_helpbutton=call_user_func_array($function, $helpbuttonargs);
    }
    /**
     * get html for help button
     *
     * @access   public
     * @return  string html for help button
     */
    function getHelpButton(){
        return $this->_helpbutton;
    }
    /**
     * Removes an OPTION from the SELECT
     *
     * @param     string    $value      Value for the OPTION to remove
     * @since     1.0
     * @access    public
     * @return    void
     */
    function removeOption($value)
    {
        $key=array_search($value, $this->_values);
        if ($key!==FALSE and $key!==null) {
            unset($this->_values[$key]);
        }
        foreach ($this->_options as $key=>$option){
            if ($option['attr']['value']==$value){
                unset($this->_options[$key]);
                return;
            }
        }
    } // end func removeOption
    /**
     * Removes all OPTIONs from the SELECT
     *
     * @param     string    $value      Value for the OPTION to remove
     * @since     1.0
     * @access    public
     * @return    void
     */
    function removeOptions()
    {
        $this->_options = array();
    } // end func removeOption
    /**
     * Slightly different container template when frozen. Don't want to use a label tag
     * with a for attribute in that case for the element label but instead use a div.
     * Templates are defined in renderer constructor.
     *
     * @return string
     */
    function getElementTemplateType(){
        if ($this->_flagFrozen){
            return 'static';
        } else {
            return 'default';
        }
    }
}
?>
