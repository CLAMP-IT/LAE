<?php // $Id: mssql_n.class.php,v 1.3 2007/10/10 05:25:22 nicolasconnault Exp $

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

/// This class generate SQL code to be used against MSSQL
/// with extended support to automatic handling of the "N"
/// char for Unicode strings. As DB is the same, this inherits
/// everything from XMLDBmssql

require_once($CFG->libdir . '/xmldb/classes/generators/mssql/mssql.class.php');

class XMLDBmssql_n extends XMLDBmssql {

    /**
     * Creates one new XMLDBmssql
     */
    function XMLDBmssql_n() {
        XMLDBmssql::XMLDBmssql();
    }
}

?>
