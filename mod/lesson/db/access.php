<?php // $Id: access.php,v 1.7 2007/02/16 08:46:59 vyshane Exp $
/**
 * Capability definitions for the lesson module.
 *
 * For naming conventions, see lib/db/access.php.
 */
$mod_lesson_capabilities = array(

    'mod/lesson:edit' => array(

        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'mod/lesson:manage' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    )
);
