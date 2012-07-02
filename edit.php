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
 * Message edit
 *
 * @package    blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once('edit_message_form.php');
require_once('block_news_message.php');
require_once('block_news_system.php');
require_once('lib.php');

define('ADD', 1);
define('EDIT', 2);

$blockinstanceid = optional_param('bi', 0, PARAM_INTEGER);
$id              = optional_param('m', 0, PARAM_INTEGER);
$mode            = optional_param('mode', '', PARAM_TEXT); // where from: all|one

if ($id) {
    $action = EDIT;
    $sql=block_news_system::MSGSQLHDR .
        'WHERE {block_news_messages}.id = ?';
    $mrec = $DB->get_record_sql($sql, array('id' => $id));

    $blockinstanceid = $mrec->blockinstanceid;
    $bnm = new block_news_message($mrec);
    $title = get_string('editmessage', 'block_news') . ': ' . $bnm->get_title();
    $url = new moodle_url('/blocks/news/edit.php', array('m' => $id));
    $publishstate=($bnm->get_messagedate() > time() ? 'asd' : 'ap');
                                            // at specified date | already published
} else {
    $action = ADD;
    $title = get_string('addnewmessage', 'block_news');
    $url = new moodle_url('/blocks/news/edit.php', array('bi' => $blockinstanceid));
    $publishstate='';
}
$PAGE->set_url($url);

if (empty($blockinstanceid)) {
    print_error('errorinvalidblockinstanceid', 'block_news');
}
$bns = block_news_system::get_block_settings($blockinstanceid);

$context = get_context_instance(CONTEXT_SYSTEM);

// Ensure user can edit/add
$blockcontext = get_context_instance(CONTEXT_BLOCK, $blockinstanceid);
require_capability('block/news:add', $blockcontext);

$PAGE->set_context($context);

$csemod = block_news_init_page($blockinstanceid, $bns->get_title());

$courseurl = new moodle_url('/course/view.php?id=' . $csemod->cseid);
$PAGE->set_title($csemod->cseshortname . ': ' . $title);
$PAGE->set_heading($csemod->csefullname);
$PAGE->navbar->add($title);


// set up redirect urls
if ($mode == 'all') {
    $returnurl = $CFG->wwwroot . '/blocks/news/all.php?bi=' . $blockinstanceid;
} else if ($mode == 'one') {
    $returnurl = $CFG->wwwroot . '/blocks/news/message.php?&m=' . $id;
} else { // from block
    if (isset($csemod->modid)) {
        // under a module
        $returnurl = $CFG->wwwroot .'/mod/'.$csemod->modtype.'/view.php?id='.$csemod->modid;
    } else {
        // under a course
        $returnurl = $CFG->wwwroot . '/course/view.php?id=' . $csemod->cseid;
    }
}

$customdata['publishstate'] = $publishstate;
$customdata['groupingsupport'] = $bns->get_groupingsupport();

// edit message form
$mform = new block_news_edit_message_form($customdata);

// if cancelled redirect to where you come from
if ($mform->is_cancelled()) {
    redirect($returnurl);
    exit;
}

// process form submission
if ($formdata = $mform->get_data()) {
    $formdata->blockinstanceid = $blockinstanceid;
    $formdata->timemodified = time();
    if (isset($formdata->m)) {
        $formdata->id = $formdata->m;
    }

    // we need to set messagerepeat as the
    // database expects it
    if (!isset($formdata->messagerepeat)) {
        $formdata->messagerepeat = 0;
    }
    // set internal newsfeedid
    if (!isset($formdata->newsfeedid)) {
        $formdata->newsfeedid = 0;
    }
    // set userid to current user
    $formdata->userid = $USER->id;

    // from editor
    $formdata->messageformat = $formdata->message['format'];

    $bns = block_news_system::get_block_settings($blockinstanceid);
    $bns->uncache_block_feed();

    // add or edit - set current date and set publish to 'Already published'
    // as its about to be and correct when redisplayed in future
    // If publish is 'At specified time' or 'Already published' leave as set in form
    if ($formdata->publish == 0) { // Immediately
        $formdata->messagedate = time();
    }

    if ($action == EDIT) {
        $bnm->edit($formdata);
    } else {
        $id = block_news_message::create($formdata);
    }

    redirect($returnurl);
}

// else create and display the form
$toform = array();
$toform['bi'] = $blockinstanceid;
$toform['m'] = empty($id) ? null : $id;
$toform['mode'] = $mode;

if ($action == EDIT) {
    $toform['title'] = $bnm->get_title();
    $toform['messagevisible'] = $bnm->get_messagevisible();
    // 0 => 'Immediately', 1 => 'At specified date', 2 => 'Already published'
    $toform['publish'] = ($bnm->get_messagedate() > time() ? 1 : 2);
    $toform['messagedate'] = $bnm->get_messagedate();
    $toform['messagerepeat'] = $bnm->get_messagerepeat();
    $toform['hideauthor'] = $bnm->get_hideauthor();
    $toform['groupingid'] = $bnm->get_groupingid();
    $timemodified=$bnm->get_timemodified();
    $usr=$bnm->get_user();
    if ($timemodified != 0 && $usr != null) {
        $toform['lastupdated'] = userdate($bnm->get_timemodified(),
                    get_string('dateformatlong', 'block_news')) . ' by ' . fullname($usr);
    } else {
        $toform['lastupdated'] = '-';
    }
    // for attachments
    $messagetext = $bnm->get_message();
    $messageformat = $bnm->get_messageformat();
} else {
    $messagetext = null;
    $messageformat = null;
    $toform['publish'] = 0;
}

// files
$draftitemid = file_get_submitted_draft_itemid('attachments');
file_prepare_draft_area($draftitemid, $blockcontext->id, 'block_news', 'attachment',
    empty($id) ? null : $id);
$toform['attachments'] = $draftitemid;

$draftid_editor = file_get_submitted_draft_itemid('message');
$currenttext = file_prepare_draft_area($draftid_editor, $blockcontext->id, 'block_news',
    'message', empty($id) ? null : $id, array('subdirs' => 0),
    empty($messagetext) ? '' : $messagetext);
$toform['message'] = array('text' => $currenttext,
    'format' => empty($messageformat) ? editors_get_preferred_format() : $messageformat,
    'itemid' => $draftid_editor);

// set data
$mform->set_data($toform);

// display form
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$mform->display();

echo $OUTPUT->footer();
