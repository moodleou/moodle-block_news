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
 * Message edit.
 *
 * @package block_news
 * @copyright 2014 The Open University
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
$mode            = optional_param('mode', '', PARAM_TEXT); // Where from: all|one.

if ($id) {
    $action = EDIT;
    $sql = block_news_system::get_message_sql_start() .
        'WHERE {block_news_messages}.id = ?';
    $mrec = $DB->get_record_sql($sql, array('id' => $id));

    $blockinstanceid = $mrec->blockinstanceid;
    $bnm = new block_news_message($mrec);
    $title = get_string('editmessage', 'block_news') . ': ' . $bnm->get_title();
    $url = new moodle_url('/blocks/news/edit.php', array('m' => $id));
    $publishstate = ($bnm->get_messagedate() > time() ? 'asd' : 'ap');
                                            // At specified date | already published.
} else {
    $action = ADD;
    $title = get_string('addnewmessage', 'block_news');
    $url = new moodle_url('/blocks/news/edit.php', array('bi' => $blockinstanceid));
    $publishstate = '';
}
$PAGE->set_url($url);

if (empty($blockinstanceid)) {
    print_error('errorinvalidblockinstanceid', 'block_news');
}
$bns = block_news_system::get_block_settings($blockinstanceid);

$context = context_system::instance();

// Ensure user can edit/add.
$blockcontext = context_block::instance($blockinstanceid);
require_capability('block/news:add', $blockcontext);

$PAGE->set_context($context);

$csemod = block_news_init_page($blockinstanceid, $bns->get_title());

$courseurl = new moodle_url('/course/view.php?id=' . $csemod->cseid);
$PAGE->set_title($csemod->cseshortname . ': ' . $title);
$PAGE->set_heading($csemod->csefullname);
$PAGE->navbar->add($title);


// Set up redirect urls.
if ($mode == 'all') {
    $returnurl = $CFG->wwwroot . '/blocks/news/all.php?bi=' . $blockinstanceid;
} else if ($mode == 'one') {
    $returnurl = $CFG->wwwroot . '/blocks/news/message.php?&m=' . $id;
} else { // From block.
    if (isset($csemod->modid)) {
        // Under a module.
        $returnurl = $CFG->wwwroot .'/mod/'.$csemod->modtype.'/view.php?id='.$csemod->modid;
    } else {
        // Under a course.
        $returnurl = $CFG->wwwroot . '/course/view.php?id=' . $csemod->cseid;
    }
}

$customdata['publishstate'] = $publishstate;
$customdata['groupingsupportbygrouping'] = 0;
$customdata['groupingsupportbygroup'] = 0;
if ($bns->get_groupingsupport() == $bns::RESTRICTBYGROUPING) {
    $customdata['groupingsupportbygrouping'] = 1;
}
if ($bns->get_groupingsupport() == $bns::RESTRICTBYGROUP) {
    $customdata['groupingsupportbygroup'] = 1;
}
$customdata['displaytype'] = $bns->get_displaytype();


// Edit message form.
$mform = new block_news_edit_message_form($customdata);

// If cancelled redirect to where you come from.
if ($mform->is_cancelled()) {
    redirect($returnurl);
    exit;
}

// Process form submission.
if ($formdata = $mform->get_data()) {
    $formdata->blockinstanceid = $blockinstanceid;
    $formdata->timemodified = time();
    if (isset($formdata->m)) {
        $formdata->id = $formdata->m;
    }
    // We need to set messagerepeat as the
    // database expects it.
    if (!isset($formdata->messagerepeat)) {
        $formdata->messagerepeat = 0;
    }
    // Set internal newsfeedid.
    if (!isset($formdata->newsfeedid)) {
        $formdata->newsfeedid = 0;
    }
    // Set userid to current user.
    $formdata->userid = $USER->id;

    // From editor.
    $formdata->messageformat = $formdata->message['format'];

    $bns = block_news_system::get_block_settings($blockinstanceid);
    $bns->uncache_block_feed();

    // Add or edit - set current date and set publish to 'Already published'
    // as its about to be and correct when redisplayed in future
    // If publish is 'At specified time' or 'Already published' leave as set in form.
    if ($formdata->publish == 0) { // Immediately.
        $formdata->messagedate = time();
    }

    if ($action == EDIT) {
        $bnm->edit($formdata);
    } else {
        $id = block_news_message::create($formdata);
    }

    // Save thumbnail version of the image file.
    $fs = get_file_storage();
    $dirfiles = $fs->get_directory_files($blockcontext->id, 'block_news', 'messageimage', $id, '/');
    if ($dirfiles) {
        $file = reset($dirfiles);
        $filename = $file->get_filename();
        $fext = '.' . substr($filename, strripos($filename, '.') + 1);
        $dir = make_temp_directory('block_news');
        $tempfile = tempnam($dir, $id . '_thumbnail_');
        $fullpath = $tempfile . $fext;
        rename($tempfile, $fullpath);
        $file->copy_content_to($fullpath);
        $jpeg = preg_match('~\.jpe?g$~i', $fullpath);
        $png = preg_match('~\.png$~i', $fullpath);
        if (($jpeg || $png) && file_exists($fullpath)) {
            // Create thumbnail, overwriting existing temp file.
            $ok = \theme_osep\util::create_thumbnail($fullpath, $fullpath, block_news_edit_message_form::THUMBNAIL_MAX_EDGE);
            if ($ok) {
                $thumbnailexist = $fs->get_file($blockcontext->id, 'block_news', 'thumbnail', $id,
                        '/', block_news_message::THUMBNAIL_JPG);
                if ($thumbnailexist) {
                    $thumbnailexist->delete();
                }
                $info = array(
                        'contextid' => $blockcontext->id,
                        'component' => 'block_news',
                        'filearea' => 'thumbnail',
                        'itemid' => $id,
                        'filepath' => '/',
                        'filename' => block_news_message::THUMBNAIL_JPG);
                $fs->create_file_from_pathname($info, $fullpath);
            }
            unlink($fullpath);
        }
    }

    redirect($returnurl);
}

// Else create and display the form.
$toform = array();
$toform['bi'] = $blockinstanceid;
$toform['m'] = empty($id) ? null : $id;
$toform['mode'] = $mode;

if ($action == EDIT) {
    $toform['title'] = $bnm->get_title();
    $toform['messagevisible'] = $bnm->get_messagevisible();
    // Publish values: 0 => 'Immediately', 1 => 'At specified date', 2 => 'Already published'.
    $toform['publish'] = ($bnm->get_messagedate() > time() ? 1 : 2);
    $toform['messagetype'] = $bnm->get_messagetype();
    $toform['messagedate'] = $bnm->get_messagedate();
    $toform['messagerepeat'] = $bnm->get_messagerepeat();
    $toform['hideauthor'] = $bnm->get_hideauthor();
    $toform['groupingid'] = $bnm->get_groupingid();
    $toform['groupid'] = $bnm->get_groupid();
    $toform['eventstart'] = $bnm->get_eventstart();
    $toform['alldayevent'] = $bnm->get_alldayevent();
    $toform['eventend'] = $bnm->get_eventend();
    $toform['eventlocation'] = $bnm->get_eventlocation();
    $timemodified = $bnm->get_timemodified();
    $usr = $bnm->get_user();
    if ($timemodified != 0 && $usr != null) {
        $toform['lastupdated'] = userdate($bnm->get_timemodified(),
                    get_string('dateformatlong', 'block_news')) . ' by ' . fullname($usr);
    } else {
        $toform['lastupdated'] = '-';
    }
    // For attachments.
    $messagetext = $bnm->get_message();
    $messageformat = $bnm->get_messageformat();
} else {
    $messagetext = null;
    $messageformat = null;
    $messagetype = block_news_message::MESSAGETYPE_NEWS;
    $toform['publish'] = 0;
}

// Files.
$imagefileoptions = block_news_edit_message_form::IMAGE_FILE_OPTIONS;
$imagedraftitemid = file_get_submitted_draft_itemid('messageimage');
file_prepare_draft_area($imagedraftitemid, $blockcontext->id, 'block_news', 'messageimage',
        empty($id) ? null : $id, $imagefileoptions);
$toform['messageimage'] = $imagedraftitemid;

$draftitemid = file_get_submitted_draft_itemid('attachments');
file_prepare_draft_area($draftitemid, $blockcontext->id, 'block_news', 'attachment',
        empty($id) ? null : $id);
$toform['attachments'] = $draftitemid;

$draftideditor = file_get_submitted_draft_itemid('message');
$currenttext = file_prepare_draft_area($draftideditor, $blockcontext->id, 'block_news',
    'message', empty($id) ? null : $id, array('subdirs' => 0),
    empty($messagetext) ? '' : $messagetext);
$toform['message'] = array('text' => $currenttext,
    'format' => empty($messageformat) ? editors_get_preferred_format() : $messageformat,
    'itemid' => $draftideditor);

// Set data.
$mform->set_data($toform);

// Display form.
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$mform->display();

echo $OUTPUT->footer();
