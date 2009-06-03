<?php //$Id: index_field_form.php,v 1.4.4.2 2008/04/25 12:20:02 skodak Exp $

require_once($CFG->dirroot.'/lib/formslib.php');

class field_form extends moodleform {

    var $field;

/// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;

        /// Everything else is dependant on the data type
        $datatype = $this->_customdata;
        require_once($CFG->dirroot.'/user/profile/field/'.$datatype.'/define.class.php');
        $newfield = 'profile_define_'.$datatype;
        $this->field = new $newfield();

        $strrequired = get_string('required');

        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'action', 'editfield');
        $mform->addElement('hidden', 'datatype', $datatype);

        $this->field->define_form($mform);

        $this->add_action_buttons(true);
    }


/// alter definition based on existing or submitted data
    function definition_after_data () {
        $mform =& $this->_form;
        $this->field->define_after_data($mform);
    }


/// perform some moodle validation
    function validation($data, $files) {
        return $this->field->define_validate($data, $files);
    }
}

?>
