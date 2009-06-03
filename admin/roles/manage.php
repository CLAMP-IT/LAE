<?php //$Id: manage.php,v 1.52.2.5 2008/12/11 09:21:52 tjhunt Exp $

    require_once('../../config.php');

    require_once($CFG->libdir.'/adminlib.php');

    $roleid      = optional_param('roleid', 0, PARAM_INT);             // if set, we are editing a role
    $name        = optional_param('name', '', PARAM_MULTILANG);        // new role name
    $shortname   = optional_param('shortname', '', PARAM_RAW);         // new role shortname, special cleaning before storage
    $description = optional_param('description', '', PARAM_CLEAN);     // new role desc
    $action      = optional_param('action', '', PARAM_ALPHA);
    $confirm     = optional_param('confirm', 0, PARAM_BOOL);
    $cancel      = optional_param('cancel', 0, PARAM_BOOL);

    admin_externalpage_setup('defineroles', '', array('roleid' => $roleid));

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);

    require_capability('moodle/role:manage', $sitecontext);

    if ($cancel) {
        redirect('manage.php');
    }

    $errors = array();
    $newrole = false;

    $roles = get_all_roles();
    $rolescount = count($roles);

/// fix sort order if needed
    $rolesort = array();
    $i = 0;
    foreach ($roles as $rolex) {
        $rolesort[$i] = $rolex->id;
        if ($rolex->sortorder != $i) {
            $r = new object();
            $r->id = $rolex->id;
            $r->sortorder = $i;
            update_record('role', $r);
            $roles[$rolex->id]->sortorder = $i;
        }
        $i++;
    }

    // do not delete these default system roles
    $defaultroles = array();
    $defaultroles[] = $CFG->notloggedinroleid;
    $defaultroles[] = $CFG->guestroleid;
    $defaultroles[] = $CFG->defaultuserroleid;
    $defaultroles[] = $CFG->defaultcourseroleid;

/// form processing, editing a role, adding a role, deleting a role etc.
    switch ($action) {
        case 'add':
            if ($data = data_submitted() and confirm_sesskey()) {

                $shortname = moodle_strtolower(clean_param(clean_filename($shortname), PARAM_SAFEDIR)); // only lowercase safe ASCII characters
                $legacytype = required_param('legacytype', PARAM_RAW);

                $legacyroles = get_legacy_roles();
                if (!array_key_exists($legacytype, $legacyroles)) {
                    $legacytype = '';
                }

                if (empty($name)) {
                    $errors['name'] = get_string('errorbadrolename', 'role');
                } else if (count_records('role', 'name', $name)) {
                    $errors['name'] = get_string('errorexistsrolename', 'role');
                }

                if (empty($shortname)) {
                    $errors['shortname'] = get_string('errorbadroleshortname', 'role');
                } else if (count_records('role', 'shortname', $shortname)) {
                    $errors['shortname'] = get_string('errorexistsroleshortname', 'role');
                }

                if (empty($errors)) {
                    $newroleid = create_role($name, $shortname, $description);

                    // set proper legacy type
                    if (!empty($legacytype)) {
                        assign_capability($legacyroles[$legacytype], CAP_ALLOW, $newroleid, $sitecontext->id);
                    }

                } else {
                    $newrole = new object();
                    $newrole->name        = $name;
                    $newrole->shortname   = $shortname;
                    $newrole->description = $description;
                    $newrole->legacytype  = $legacytype;
                }

                $allowed_values = array(CAP_INHERIT, CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT);
                $capabilities = fetch_context_capabilities($sitecontext); // capabilities applicable in this context

                foreach ($capabilities as $cap) {
                    if (!isset($data->{$cap->name})) {
                        continue;
                    }

                    // legacy caps have their own selector
                    if (islegacy($data->{$cap->name})) {
                        continue;
                    }

                    $capname = $cap->name;
                    $value = clean_param($data->{$cap->name}, PARAM_INT);
                    if (!in_array($value, $allowed_values)) {
                        continue;
                    }

                    if (empty($errors)) {
                        assign_capability($capname, $value, $newroleid, $sitecontext->id);
                    } else {
                        $newrole->$capname = $value;
                    }
                }

                // added a role sitewide...
                mark_context_dirty($sitecontext->path);

                if (empty($errors)) {
                    $rolename = get_field('role', 'name', 'id', $newroleid);
                    add_to_log(SITEID, 'role', 'add', 'admin/roles/manage.php?action=add', $rolename, '', $USER->id);
                    redirect('manage.php');
                }
            }
            
            break;

        case 'edit':
            if ($data = data_submitted() and confirm_sesskey()) {

                $shortname = moodle_strtolower(clean_param(clean_filename($shortname), PARAM_SAFEDIR)); // only lowercase safe ASCII characters
                $legacytype = required_param('legacytype', PARAM_RAW);

                $legacyroles = get_legacy_roles();
                if (!array_key_exists($legacytype, $legacyroles)) {
                    $legacytype = '';
                }

                if (empty($name)) {
                    $errors['name'] = get_string('errorbadrolename', 'role');
                } else if ($rs = get_records('role', 'name', $name)) {
                    unset($rs[$roleid]);
                    if (!empty($rs)) {
                        $errors['name'] = get_string('errorexistsrolename', 'role');
                    }
                }

                if (empty($shortname)) {
                    $errors['shortname'] = get_string('errorbadroleshortname', 'role');
                } else if ($rs = get_records('role', 'shortname', $shortname)) {
                    unset($rs[$roleid]);
                    if (!empty($rs)) {
                        $errors['shortname'] = get_string('errorexistsroleshortname', 'role');
                    }
                }
                if (!empty($errors)) {
                    $newrole = new object();
                    $newrole->name        = $name;
                    $newrole->shortname   = $shortname;
                    $newrole->description = $description;
                    $newrole->legacytype  = $legacytype;
                }

                $allowed_values = array(CAP_INHERIT, CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT);
                $capabilities = fetch_context_capabilities($sitecontext); // capabilities applicable in this context

                foreach ($capabilities as $cap) {
                    if (!isset($data->{$cap->name})) {
                        continue;
                    }

                    // legacy caps have their own selector
                    if (islegacy($data->{$cap->name}) === 0 ) {
                        continue;
                    }

                    $capname = $cap->name;
                    $value = clean_param($data->{$cap->name}, PARAM_INT);
                    if (!in_array($value, $allowed_values)) {
                        continue;
                    }

                    if (!empty($errors)) {
                        $newrole->$capname = $value;
                        continue;
                    }

                    // edit default caps
                    $SQL = "SELECT * FROM {$CFG->prefix}role_capabilities
                            WHERE roleid = $roleid AND capability = '$capname'
                              AND contextid = $sitecontext->id";

                    $localoverride = get_record_sql($SQL);

                    if ($localoverride) { // update current overrides
                        if ($value == CAP_INHERIT) { // inherit = delete
                            unassign_capability($capname, $roleid, $sitecontext->id);

                        } else {
                            $localoverride->permission = $value;
                            $localoverride->timemodified = time();
                            $localoverride->modifierid = $USER->id;
                            update_record('role_capabilities', $localoverride);
                        }
                    } else { // insert a record
                        if ($value != CAP_INHERIT) {
                            assign_capability($capname, $value, $roleid, $sitecontext->id);
                        }
                    }

                }

                if (empty($errors)) {
                    // update normal role settings
                    $role->id = $roleid;
                    $role->name = $name;
                    $role->shortname = $shortname;
                    $role->description = $description;

                    if (!update_record('role', $role)) {
                        error('Could not update role!');
                    }

                    // set proper legacy type
                    foreach($legacyroles as $ltype=>$lcap) {
                        if ($ltype == $legacytype) {
                            assign_capability($lcap, CAP_ALLOW, $roleid, $sitecontext->id);
                        } else {
                            unassign_capability($lcap, $roleid);
                        } 
                    }                    

                    // edited a role sitewide...
                    mark_context_dirty($sitecontext->path);
                    add_to_log(SITEID, 'role', 'edit', 'admin/roles/manage.php?action=edit&roleid='.$role->id, $role->name, '', $USER->id);

                    redirect('manage.php');
                }

                // edited a role sitewide - with errors, but still...
                mark_context_dirty($sitecontext->path);
            }

            break;

        case 'delete':
            if (in_array($roleid, $defaultroles)) {
                error('This role is used as one of the default system roles, it can not be deleted');
            }
            if ($confirm and data_submitted() and confirm_sesskey()) {
                if (!delete_role($roleid)) {

                    // partially deleted a role sitewide...?
                    mark_context_dirty($sitecontext->path);

                    error('Could not delete role with ID '.$roleid);
                }
                // deleted a role sitewide...
                mark_context_dirty($sitecontext->path);

            } else if (confirm_sesskey()){
                // show confirmation
                admin_externalpage_print_header();
                $optionsyes = array('action'=>'delete', 'roleid'=>$roleid, 'sesskey'=>sesskey(), 'confirm'=>1);
                $a = new object();
                $a->id = $roleid;
                $a->name = $roles[$roleid]->name;
                $a->shortname = $roles[$roleid]->shortname;
                $a->count = (int)count_records('role_assignments', 'roleid', $roleid);
                notice_yesno(get_string('deleterolesure', 'role', $a), 'manage.php', 'manage.php', $optionsyes, NULL, 'post', 'get');
                admin_externalpage_print_footer();
                die;
            }

            redirect('manage.php');
            break;

        case 'moveup':
            if (array_key_exists($roleid, $roles) and confirm_sesskey()) {
                $role = $roles[$roleid];
                if ($role->sortorder > 0) {
                    $above = $roles[$rolesort[$role->sortorder - 1]];

                    if (!switch_roles($role, $above)) {
                        error("Cannot move role with ID $roleid");
                    }
                }
            }

            redirect('manage.php');
            break;

        case 'movedown':
            if (array_key_exists($roleid, $roles) and confirm_sesskey()) {
                $role = $roles[$roleid];
                if ($role->sortorder + 1 < $rolescount) {
                    $below = $roles[$rolesort[$role->sortorder + 1]];

                    if (!switch_roles($role, $below)) {
                        error("Cannot move role with ID $roleid");
                    }
                }
            }

            redirect('manage.php');
            break;

        case 'duplicate':
            if (!array_key_exists($roleid, $roles)) {
                redirect('manage.php');
            }

            if ($confirm and data_submitted() and confirm_sesskey()) {
                //ok - lets duplicate!
            } else {
                // show confirmation
                admin_externalpage_print_header();
                $optionsyes = array('action'=>'duplicate', 'roleid'=>$roleid, 'sesskey'=>sesskey(), 'confirm'=>1);
                $optionsno  = array('action'=>'view', 'roleid'=>$roleid);
                $a = new object();
                $a->id = $roleid;
                $a->name = $roles[$roleid]->name;
                $a->shortname = $roles[$roleid]->shortname;
                notice_yesno(get_string('duplicaterolesure', 'role', $a), 'manage.php', 'manage.php', $optionsyes, $optionsno, 'post', 'get');
                admin_externalpage_print_footer();
                die;
            }

            // duplicate current role
            $sourcerole = get_record('role','id',$roleid);

            $fullname = $sourcerole->name;
            $shortname = $sourcerole->shortname;
            $currentfullname = "";
            $currentshortname = "";
            $counter = 0;

            // find a name for the duplicated role
            do {
                if ($counter) {
                    $suffixfull = " ".get_string("copyasnoun")." ".$counter;
                    $suffixshort = "_".$counter;
                } else {
                    $suffixfull = "";
                    $suffixshort = "";
                }
                $currentfullname = $fullname.$suffixfull;
                // Limit the size of shortname - database column accepts <= 100 chars
                $currentshortname = substr($shortname, 0, 100 - strlen($suffixshort)).$suffixshort;
                $coursefull  = get_record("role","name",addslashes($currentfullname));
                $courseshort = get_record("role","shortname",addslashes($currentshortname));
                $counter++;
            } while ($coursefull || $courseshort);

            $description = 'duplicate of '.$fullname;
            if ($newrole = create_role($currentfullname, $currentshortname, $description)) {
                // dupilcate all the capabilities
                role_cap_duplicate($sourcerole, $newrole);

                // dup'ed a role sitewide...
                mark_context_dirty($sitecontext->path);

            }
            $rolename = get_field('role', 'name', 'id', $newrole);
            add_to_log(SITEID, 'role', 'duplicate', 'admin/roles/manage.php?roleid='.$newrole.'&action=duplicate', $rolename, '', $USER->id);
            redirect('manage.php');
            break;

        case 'reset':
            if (!array_key_exists($roleid, $roles)) {
                redirect('manage.php');
            }

            if ($confirm and data_submitted() and confirm_sesskey()) {
                reset_role_capabilities($roleid);

                // reset a role sitewide...
                mark_context_dirty($sitecontext->path);

                $rolename = get_field('role', 'name', 'id', $roleid);
                add_to_log(SITEID, 'role', 'reset', 'admin/roles/manage.php?roleid='.$roleid.'&action=reset', $rolename, '', $USER->id);

                redirect('manage.php?action=view&amp;roleid='.$roleid);

            } else {
                // show confirmation
                admin_externalpage_print_header();
                $optionsyes = array('action'=>'reset', 'roleid'=>$roleid, 'sesskey'=>sesskey(), 'confirm'=>1);
                $optionsno  = array('action'=>'view', 'roleid'=>$roleid);
                $a = new object();
                $a->id = $roleid;
                $a->name = $roles[$roleid]->name;
                $a->shortname = $roles[$roleid]->shortname;
                $a->legacytype = get_legacy_type($roleid);
                if (empty($a->legacytype)) {
                    $warning = get_string('resetrolesurenolegacy', 'role', $a);
                } else {
                    $warning = get_string('resetrolesure', 'role', $a);
                }
                notice_yesno($warning, 'manage.php', 'manage.php', $optionsyes, $optionsno, 'post', 'get');
                admin_externalpage_print_footer();
                die;
            }

            break;

        default:
            break;
    }

/// print UI now

    admin_externalpage_print_header('');

    $currenttab = 'manage';
    include_once('managetabs.php');

    if (($roleid and ($action == 'view' or $action == 'edit')) or $action == 'add') { // view or edit role details

        if ($action == 'add') {
            $roleid = 0;
            if (empty($errors) or empty($newrole)) {
                $role = new object();
                $role->name        = '';
                $role->shortname   = '';
                $role->description = '';
                $role->legacytype  = '';
            } else {
                $role = stripslashes_safe($newrole);
            }
        } else if ($action == 'edit' and !empty($errors) and !empty($newrole)) {
                $role = stripslashes_safe($newrole);
        } else {
            if(!$role = get_record('role', 'id', $roleid)) {
                error('Incorrect role ID!');
            }
            $role->legacytype = get_legacy_type($role->id);
        }

        foreach ($roles as $rolex) {
            $roleoptions[$rolex->id] = strip_tags(format_string($rolex->name));
        }

        // this is the array holding capabilities of this role sorted till this context
        $r_caps = role_context_capabilities($roleid, $sitecontext);

        // this is the available capabilities assignable in this context
        $capabilities = fetch_context_capabilities($sitecontext);

        $usehtmleditor = can_use_html_editor();

        switch ($action) {
            case 'add':
                print_heading_with_help(get_string('addrole', 'role'), 'roles');
                break;
            case 'view':
                print_heading_with_help(get_string('viewrole', 'role'), 'roles');
                break;
            case 'edit':
                print_heading_with_help(get_string('editrole', 'role'), 'roles');
                break;
        }

        echo '<div class="selector">';
        if ($action == 'view') {
            popup_form('manage.php?action=view&amp;roleid=', $roleoptions, 'switchrole', $roleid, '', '', '',
                       false, 'self', get_string('selectrole', 'role'));

            echo '<div class="buttons">';

            $legacytype = get_legacy_type($roleid); 
            $options = array();
            $options['roleid'] = $roleid;
            $options['action'] = 'edit';
            print_single_button('manage.php', $options, get_string('edit'));
            $options['action'] = 'reset';
            if (empty($legacytype)) {
                print_single_button('manage.php', $options, get_string('resetrolenolegacy', 'role'));
            } else {
                print_single_button('manage.php', $options, get_string('resetrole', 'role'));
            }
            $options['action'] = 'duplicate';
            print_single_button('manage.php', $options, get_string('duplicaterole', 'role'));
            print_single_button('manage.php', null, get_string('listallroles', 'role'));
            echo '</div>';
        }
        echo '</div>';

        $lang = str_replace('_utf8', '', current_language());

        print_simple_box_start('center');
        include_once('manage.html');
        print_simple_box_end();

        if ($usehtmleditor) {
            use_html_editor('description');
        }

    } else {

        print_heading_with_help(get_string('roles', 'role'), 'roles');

        $table = new object;

        $table->tablealign = 'center';
        $table->align = array('right', 'left', 'left', 'left');
        $table->wrap = array('nowrap', '', 'nowrap','nowrap');
        $table->cellpadding = 5;
        $table->cellspacing = 0;
        $table->width = '90%';
        $table->data = array();

        $table->head = array(get_string('name'),
                             get_string('description'),
                             get_string('shortname'),
                             get_string('edit'));

        /*************************
         * List all current roles *
         **************************/

        foreach ($roles as $role) {

            $stredit     = get_string('edit');
            $strdelete   = get_string('delete');
            $strmoveup   = get_string('moveup');
            $strmovedown = get_string('movedown');

            $row = array();
            $row[0] = '<a href="manage.php?roleid='.$role->id.'&amp;action=view">'.format_string($role->name).'</a>';
            $row[1] = format_text($role->description, FORMAT_HTML);
            $row[2] = s($role->shortname);
            $row[3] = '<a title="'.$stredit.'" href="manage.php?action=edit&amp;roleid='.$role->id.'">'.
                         '<img src="'.$CFG->pixpath.'/t/edit.gif" class="iconsmall" alt="'.$stredit.'" /></a> ';
            if (in_array($role->id, $defaultroles)) {
                $row[3] .= '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" class="iconsmall" alt="" /> ';
            } else {
                $row[3] .= '<a title="'.$strdelete.'" href="manage.php?action=delete&amp;roleid='.$role->id.'&amp;sesskey='.sesskey().'">'.
                             '<img src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall" alt="'.$strdelete.'" /></a> ';
            }
            if ($role->sortorder != 0) {
                $row[3] .= '<a title="'.$strmoveup.'" href="manage.php?action=moveup&amp;roleid='.$role->id.'&amp;sesskey='.sesskey().'">'.
                     '<img src="'.$CFG->pixpath.'/t/up.gif" class="iconsmall" alt="'.$strmoveup.'" /></a> ';
            } else {
                $row[3] .= '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" class="iconsmall" alt="" /> ';
            }
            if ($role->sortorder+1 < $rolescount) {
                $row[3] .= '<a title="'.$strmovedown.'" href="manage.php?action=movedown&amp;roleid='.$role->id.'&amp;sesskey='.sesskey().'">'.
                     '<img src="'.$CFG->pixpath.'/t/down.gif" class="iconsmall" alt="'.$strmovedown.'" /></a> ';
            } else {
                $row[3] .= '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" class="iconsmall" alt="" /> ';
            }

            $table->data[] = $row;

        }
        print_table($table);

        $options = new object();
        $options->action = 'add';
        echo '<div class="buttons">';
        print_single_button('manage.php', $options, get_string('addrole', 'role'), 'get');
        echo '</div>';
    }

    admin_externalpage_print_footer();
    die;



?>
