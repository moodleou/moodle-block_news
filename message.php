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
 * Display single message
 *
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use block_news\system;
use block_news\message;
use block_news\output\full_message;
use block_news\output\view_page;

require_once(dirname(__FILE__) . '/../../config.php');
require_once('lib.php');

$id              = required_param('m', PARAM_INT);
$mode            = optional_param('mode', '', PARAM_TEXT);
$action          = optional_param('action', '', PARAM_TEXT);
$confirm         = optional_param('confirm', '', PARAM_TEXT);

$sql = system::get_message_sql_start() .
    'WHERE m.id = ?';
$mrec = $DB->get_record_sql($sql, array('id' => $id), MUST_EXIST);

$blockinstanceid = $mrec->blockinstanceid;
$groupids = $DB->get_fieldset_select('block_news_message_groups', 'groupid', 'messageid = ?', [$mrec->id]);
$bnm = new message($mrec, $groupids);

if (empty($blockinstanceid)) {
    throw new moodle_exception('errorinvalidblockinstanceid', 'block_news');
}

$bns = system::get_block_settings($blockinstanceid);
$newstitle = $bns->get_title();
// Check prison theme to make breadcrumb consistent with title.
$isprison = class_exists('\auth_prison\util') && \auth_prison\util::is_prison_vle();
$newstitle = $isprison && $bns->get_displaytype() == system::DISPLAY_DEFAULT ?
        get_string('pluginname', 'block_news') : $newstitle;
$csemod = block_news_init_page($blockinstanceid, $newstitle);

if ($bns->get_groupingsupport() == $bns::RESTRICTBYGROUP) {
    $messagegroups = $bnm->get_groupids();
    if (!empty($messagegroups)) {
        // Get the course id from the group id.
        list($sql, $params) = $DB->get_in_or_equal($messagegroups);
        $groups = $DB->get_records_select('groups', 'id ' . $sql, $params);

        $allowedgroups = [];
        foreach ($groups as $group) {
            // Get the groups the user has access to.
            $allowedgroups = array_merge($allowedgroups, $bns->get_groupids($USER->id, $group->courseid));
        }

        // Check that at least one group the message is visible to is accessible to the user.
        if (empty(array_intersect($messagegroups, $allowedgroups))) {
            throw new moodle_exception('errormessageaccessrestricted', 'block_news');
        }
    }
}

$urlparams = array('m' => $id);
$PAGE->set_url('/blocks/news/message.php', $urlparams);

$strtitle = get_string('msgeditpgtitle', 'block_news');

$messagetitle = '';

$title = (empty($newstitle)) ? get_string('pluginname', 'block_news') : $newstitle;
$hidetitle = $bns->get_hidetitles();
if (!$hidetitle) {
    $messagetitle = $bnm->get_title();
    $title .= ': ' . $messagetitle;
}


$PAGE->set_title($csemod->cseshortname . ': ' . $title);
$PAGE->set_heading($csemod->csefullname);
if (!$hidetitle) {
    $PAGE->navbar->add($messagetitle);
}

$output = $PAGE->get_renderer('block_news');
$output->pre_header($bns);

// Stop display to students of hidden/future msgs
// shouldnt need following check unless hand crafted URL.
$blockcontext = context_block::instance($blockinstanceid);
if (!$bnm->is_visible_to_students()) {
    require_capability('block/news:viewhidden', $blockcontext); // If-not: exit with error.
}

if ($action == 'hide') {
    require_capability('block/news:hide', $blockcontext);

    // Toggle.
    if ($bnm->get_messagevisible()) {
        $bnm->set_messagevisible(false);
    } else {
        $bnm->set_messagevisible(true);
    }

    // Reset the feed cache as something has changed.
    $bns->uncache_block_feed();

    if ($mode == 'all') {
        $urlh = $CFG->wwwroot . '/blocks/news/all.php?bi=' . $blockinstanceid;
    } else {
        $urlh = $CFG->wwwroot . '/blocks/news/message.php?m=' . $id;
    }

    redirect($urlh);
}

if ($action == 'delete' && !$confirm) {
     require_capability('block/news:delete', $blockcontext);

    // Set up cancel/delete redirects depending if from  all/single message display.
    if ($mode == 'all') {
        $urlc = $CFG->wwwroot . '/blocks/news/all.php?&bi=' . $blockinstanceid .
            '&mode=' . $mode;
        $urld = $CFG->wwwroot . '/blocks/news/message.php?m=' . $id .'&mode=' . $mode .
                                                                '&action=delete&confirm=1';
    } else {
        $urlc = $CFG->wwwroot . '/blocks/news/message.php?m=' . $id . '&mode=' . $mode;
        $urld = $CFG->wwwroot . '/blocks/news/message.php?m=' . $id . '&mode=' . $mode .
                                                                '&action=delete&confirm=1';
    }

    // Confirm delete.
    block_news_output_hdr(get_string('confirmdeletion', 'block_news', $messagetitle));

    echo $OUTPUT->confirm(get_string('msgclassconfdel', 'block_news',
        $bnm->get_title()), $urld, $urlc);

} else if ($action == 'delete' && $confirm) {
    require_capability('block/news:delete', $blockcontext);

    // Irrespective of mode - cant go back to page - so always go to list.
    $urlh = $CFG->wwwroot.'/blocks/news/all.php?bi='.$blockinstanceid;

    $bnm->delete();
    $bns->uncache_block_feed();

    redirect($urlh);

} else {

    $SESSION->news_block_views[$id] = true;

    if (has_capability('block/news:viewhidden', $blockcontext)) {
        $viewhidden = true;
    } else {
        $viewhidden = false;
    }

    // Get next and prev message ids.
    $pn = $bns->get_message_pn($bnm, $viewhidden);

    $image = $bns->get_images('messageimage', $bnm->get_id());
    $file = $bns->get_files('attachment', $bnm->get_id());
    $msgwidget = new full_message($bnm, $pn->previd, $pn->nextid, $bns, 'one', $image, '', $file);

    $page = new view_page($msgwidget);
    echo $OUTPUT->header();
    echo $output->render($page);
}

echo $OUTPUT->footer();

// End main.

function block_news_output_hdr($title, $bns = null) {
    global $OUTPUT, $PAGE;
    $r = $PAGE->get_renderer('block_news');
    echo $OUTPUT->header();
    echo $r->render_message_page_header($bns, $title, false, false);
}

