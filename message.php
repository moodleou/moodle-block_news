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
 * @package    blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once('block_news_message.php');
require_once('block_news_system.php');
require_once('lib.php');

$id              = required_param('m', PARAM_INT);
$mode            = optional_param('mode', '', PARAM_TEXT);
$action          = optional_param('action', '', PARAM_TEXT);
$confirm         = optional_param('confirm', '', PARAM_TEXT);

$sql=block_news_system::MSGSQLHDR .
    'WHERE {block_news_messages}.id = ?';
$mrec = $DB->get_record_sql($sql, array('id' => $id));

$blockinstanceid = $mrec->blockinstanceid;
$bnm = new block_news_message($mrec);

if (empty($blockinstanceid)) {
    print_error('errorinvalidblockinstanceid', 'block_news');
}

$bns = block_news_system::get_block_settings($blockinstanceid);
$newstitle = $bns->get_title();
$csemod = block_news_init_page($blockinstanceid, $newstitle);

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

// stop display to students of hidden/future msgs
// shouldnt need following check unless hand crafted URL
$blockcontext = get_context_instance(CONTEXT_BLOCK, $blockinstanceid);
if (!$bnm->is_visible_to_students()) {
    require_capability('block/news:viewhidden', $blockcontext); // if-not: exit with error
}

if ($action == 'hide') {
    require_capability('block/news:hide', $blockcontext);

    // toggle
    if ($bnm->get_messagevisible()) {
        $bnm->set_messagevisible(false);
    } else {
        $bnm->set_messagevisible(true);
    }

    // reset the feed cache as something has changed
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

    // set up cancel/delete redirects depending if from  all/single message display
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

    // confirm delete
    block_news_output_hdr(get_string('confirmdeletion', 'block_news', $messagetitle));

    echo $OUTPUT->confirm(get_string('msgclassconfdel', 'block_news',
        $bnm->get_title()), $urld, $urlc);

} else if ($action == 'delete' && $confirm) {
    require_capability('block/news:delete', $blockcontext);

    // irrespective of mode - cant go back to page - so always go to list
    $urlh = $CFG->wwwroot.'/blocks/news/all.php?bi='.$blockinstanceid;

    $bnm->delete();
    $bns->uncache_block_feed();

    redirect($urlh);

} else {

    // normal display of a message
    block_news_output_hdr($title);

    $SESSION->news_block_views[$id] = true;

    if (has_capability('block/news:viewhidden', $blockcontext)) {
        $viewhidden = true;
    } else {
        $viewhidden = false;
    }

    // get next and prev message ids
    $pn = $bns->get_message_pn($bnm, $viewhidden);

    $msgwidget = new block_news_message_full($bnm, $pn->previd, $pn->nextid, $bns, 'one');
    echo $output->render($msgwidget);
}

echo $OUTPUT->footer();

////  end main ////

function block_news_output_hdr($title) {
    global $OUTPUT;
    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);
}

