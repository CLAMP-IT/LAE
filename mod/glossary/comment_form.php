<?php //$Id: comment_form.php,v 1.10 2007/01/05 04:51:48 jamiesensei Exp $

require_once $CFG->libdir.'/formslib.php';

class mod_glossary_comment_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        // visible elements
        $mform->addElement('htmleditor', 'entrycomment',get_string('comment', 'glossary'));
        $mform->addRule('entrycomment', get_string('required'), 'required', null, 'client');
        $mform->setType('entrycomment', PARAM_RAW); // processed by trusttext or cleaned before the display
        $mform->setHelpButton('entrycomment', array('writing', 'richtext'), false, 'editorhelpbutton');

        $mform->addElement('format', 'format', get_string('format'));
        $mform->setHelpButton('format', array('textformat', get_string('helpformatting')));

        // hidden optional params
        $mform->addElement('hidden', 'cid', 0);
        $mform->setType('cid', PARAM_INT);

        $mform->addElement('hidden', 'eid', 0);
        $mform->setType('eid', PARAM_INT);

        $mform->addElement('hidden', 'action', '');
        $mform->setType('action', PARAM_ACTION);

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons(false);
    }
}
?>