<?php
require_once('HTML/QuickForm/select.php');

/**
 * HTML class for a select type element
 *
 * @author       Jamie Pratt
 * @access       public
 */
class MoodleQuickForm_select extends HTML_QuickForm_select{
    /**
     * html for help button, if empty then no help
     *
     * @var string
     */
    var $_helpbutton='';
    var $_hiddenLabel=false;

    function MoodleQuickForm_select($elementName=null, $elementLabel=null, $options=null, $attributes=null) {
        parent::HTML_QuickForm_select($elementName, $elementLabel, $options, $attributes);
    }
    function setHiddenLabel($hiddenLabel){
        $this->_hiddenLabel = $hiddenLabel;
    }
    function toHtml(){
        if ($this->_hiddenLabel){
            $this->_generateId();
            return '<label class="accesshide" for="'.$this->getAttribute('id').'" >'.
                        $this->getLabel().'</label>'.parent::toHtml();
        } else {
             return parent::toHtml();
        }
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
                // we must reindex the options because the ugly code in quickforms' select.php expects that keys are 0,1,2,3... !?!?
                $this->_options = array_merge($this->_options);
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

   /**
    * We check the options and return only the values that _could_ have been
    * selected. We also return a scalar value if select is not "multiple"
    */
    function exportValue(&$submitValues, $assoc = false)
    {
        if (empty($this->_options)) {
            return $this->_prepareValue(null, $assoc);
        }

        $value = $this->_findValue($submitValues);
        if (is_null($value)) {
            $value = $this->getValue();
        }
        $value = (array)$value;

        $cleaned = array();
        foreach ($value as $v) {
            foreach ($this->_options as $option) {
                if ((string)$option['attr']['value'] === (string)$v) {
                    $cleaned[] = (string)$option['attr']['value'];
                    break;
                }
            }
        }

        if (empty($cleaned)) {
            return $this->_prepareValue(null, $assoc);
        }
        if ($this->getMultiple()) {
            return $this->_prepareValue($cleaned, $assoc);
        } else {
            return $this->_prepareValue($cleaned[0], $assoc);
        }
    }
}

?>