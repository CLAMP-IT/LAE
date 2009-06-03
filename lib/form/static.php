<?php
require_once("HTML/QuickForm/static.php");

/**
 * HTML class for a text type element
 *
 * @author       Jamie Pratt
 * @access       public
 */
class MoodleQuickForm_static extends HTML_QuickForm_static{
    var $_elementTemplateType='static';
    /**
     * html for help button, if empty then no help
     *
     * @var string
     */
    var $_helpbutton='';
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

    function getElementTemplateType(){
        return $this->_elementTemplateType;
    }
}
?>