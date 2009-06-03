<?php // $Id: edit_statement_save.class.php,v 1.4 2007/10/10 05:25:20 nicolasconnault Exp $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas        http://dougiamas.com  //
//           (C) 2001-3001 Eloy Lafuente (stronk7) http://contiento.com  //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/// This class will save the changes performed to the name and comment of
/// one statement

class edit_statement_save extends XMLDBAction {

    /**
     * Init method, every subclass will have its own
     */
    function init() {
        parent::init();

    /// Set own custom attributes

    /// Get needed strings
        $this->loadStrings(array(
            'administration' => ''
        ));
    }

    /**
     * Invoke method, every class will have its own
     * returns true/false on completion, setting both
     * errormsg and output as necessary
     */
    function invoke() {
        parent::invoke();

        $result = true;

    /// Set own core attributes
        $this->does_generate = ACTION_NONE;
        //$this->does_generate = ACTION_GENERATE_HTML;

    /// These are always here
        global $CFG, $XMLDB;

    /// Do the job, setting result as needed

    /// Get parameters
        $dirpath = required_param('dir', PARAM_PATH);
        $dirpath = $CFG->dirroot . stripslashes_safe($dirpath);

        $statementparam = strtolower(required_param('statement', PARAM_CLEAN));
        $name = trim(strtolower(required_param('name', PARAM_CLEAN)));
        $comment = required_param('comment', PARAM_CLEAN);
        $comment = stripslashes_safe($comment);

        $editeddir =& $XMLDB->editeddirs[$dirpath];
        $structure =& $editeddir->xml_file->getStructure();
        $statement =& $structure->getStatement($statementparam);

        $errors = array();    /// To store all the errors found

    /// If there is one name change, do it, changing the prev and next
    /// atributes of the adjacent tables
        if ($statementparam != $name) {
            $statement->setName($name);
            if ($statement->getPrevious()) {
                $prev =& $structure->getStatement($statement->getPrevious());
                $prev->setNext($name);
                $prev->setChanged(true);
            }
            if ($statement->getNext()) {
                $next =& $structure->getStatement($statement->getNext());
                $next->setPrevious($name);
                $next->setChanged(true);
            }
        }

    /// Set comment
        $statement->setComment($comment);

    /// Launch postaction if exists (leave this here!)
        if ($this->getPostAction() && $result) {
            return $this->launch($this->getPostAction());
        }

    /// Return ok if arrived here
        return $result;
    }
}
?>
