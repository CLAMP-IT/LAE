<?php // $Id: uploadpicture_form.php,v 1.1.2.1 2008/02/21 05:23:25 moodler Exp $
    require_once $CFG->libdir.'/formslib.php';

    class admin_uploadpicture_form extends moodleform {
        function definition (){
            global $CFG, $USER;
            global $userfields;
    
            $mform =& $this->_form;
    
            $this->set_upload_manager(new upload_manager('userpicturesfile', false, false, null, false, 0, true, true, false));
    
            $mform->addElement('header', 'settingsheader', get_string('upload'));
    
            $mform->addElement('file', 'userpicturesfile', get_string('file'), 'size="40"');
            $mform->addRule('userpicturesfile', null, 'required');
    
            $choices = $userfields;
            $mform->addElement('select', 'userfield', get_string('uploadpicture_userfield', 'admin'), $choices);
            $mform->setType('userfield', PARAM_INT);
    
            $choices = array( 0 => get_string('no'), 1 => get_string('yes') );
            $mform->addElement('select', 'overwritepicture', get_string('uploadpicture_overwrite', 'admin'), $choices);
            $mform->setType('overwritepicture', PARAM_INT);
    
            $this->add_action_buttons(false, get_string('uploadpictures', 'admin'));
        }
    }
?>
