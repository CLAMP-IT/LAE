<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod-forum
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/forum/lib.php');

    $settings->add(new admin_setting_configselect('forum_displaymode', get_string('displaymode', 'forum'),
                       get_string('configdisplaymode', 'forum'), FORUM_MODE_NESTED, forum_get_layout_modes()));

    $settings->add(new admin_setting_configcheckbox('forum_replytouser', get_string('replytouser', 'forum'),
                       get_string('configreplytouser', 'forum'), 1));

    // Less non-HTML characters than this is short
    $settings->add(new admin_setting_configtext('forum_shortpost', get_string('shortpost', 'forum'),
                       get_string('configshortpost', 'forum'), 300, PARAM_INT));

    // More non-HTML characters than this is long
    $settings->add(new admin_setting_configtext('forum_longpost', get_string('longpost', 'forum'),
                       get_string('configlongpost', 'forum'), 600, PARAM_INT));

    // Number of discussions on a page
    $settings->add(new admin_setting_configtext('forum_manydiscussions', get_string('manydiscussions', 'forum'),
                       get_string('configmanydiscussions', 'forum'), 100, PARAM_INT));

    if (isset($CFG->maxbytes)) {
        $settings->add(new admin_setting_configselect('forum_maxbytes', get_string('maxattachmentsize', 'forum'),
                           get_string('configmaxbytes', 'forum'), 512000, get_max_upload_sizes($CFG->maxbytes)));
    }

    // Default number of attachments allowed per post in all forums
    $settings->add(new admin_setting_configtext('forum_maxattachments', get_string('maxattachments', 'forum'),
                       get_string('configmaxattachments', 'forum'), 9, PARAM_INT));

    // Default whether user needs to mark a post as read
    $settings->add(new admin_setting_configcheckbox('forum_trackreadposts', get_string('trackforum', 'forum'),
                       get_string('configtrackreadposts', 'forum'), 1));

    // Default number of days that a post is considered old
    $settings->add(new admin_setting_configtext('forum_oldpostdays', get_string('oldpostdays', 'forum'),
                       get_string('configoldpostdays', 'forum'), 14, PARAM_INT));

    // Default whether user needs to mark a post as read
    $settings->add(new admin_setting_configcheckbox('forum_usermarksread', get_string('usermarksread', 'forum'),
                       get_string('configusermarksread', 'forum'), 0));

    $options = array();
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = sprintf("%02d",$i);
    }
    // Default time (hour) to execute 'clean_read_records' cron
    $settings->add(new admin_setting_configselect('forum_cleanreadtime', get_string('cleanreadtime', 'forum'),
                       get_string('configcleanreadtime', 'forum'), 2, $options));

    // Default time (hour) to send digest email
    $settings->add(new admin_setting_configselect('digestmailtime', get_string('digestmailtime', 'forum'),
                       get_string('configdigestmailtime', 'forum'), 17, $options));

    if (empty($CFG->enablerssfeeds)) {
        $options = array(0 => get_string('rssglobaldisabled', 'admin'));
        $str = get_string('configenablerssfeeds', 'forum').'<br />'.get_string('configenablerssfeedsdisabled2', 'admin');

    } else {
        $options = array(0=>get_string('no'), 1=>get_string('yes'));
        $str = get_string('configenablerssfeeds', 'forum');
    }
    $settings->add(new admin_setting_configselect('forum_enablerssfeeds', get_string('enablerssfeeds', 'admin'),
                       $str, 0, $options));

    $settings->add(new admin_setting_configcheckbox('forum_enabletimedposts', get_string('timedposts', 'forum'),
                       get_string('configenabletimedposts', 'forum'), 0));
    
    $settings->add(new admin_setting_configcheckbox('forum_enableanonymousposts', get_string('anonymouspost', 'forum'),
                       get_string('configenableanonymouspost', 'forum'), 0));
}

