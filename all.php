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
 * Displays all messages from news block.
 *
 * @package block_news
 * @copyright 2013 The Open University
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once('block_news_message.php');
require_once('block_news_system.php');
require_once('lib.php');

$blockinstanceid = required_param('bi', PARAM_INT);

$bns = block_news_system::get_block_settings($blockinstanceid);

$csemod = block_news_init_page($blockinstanceid, $bns->get_title());

$output = $PAGE->get_renderer('block_news');
$blockcontext = context_block::instance($blockinstanceid);

$urlparams = array('bi' => $blockinstanceid);
$PAGE->set_url('/blocks/news/all.php', $urlparams);

// Breadcrumb.
$title = $bns->get_title();
$title = empty($title) ? get_string('pluginname', 'block_news') : $title;
$title .= ': ' . get_string('allmessages', 'block_news');
$PAGE->set_title($csemod->cseshortname . ': ' . $title);
$PAGE->set_heading($csemod->csefullname);

$output->pre_header($bns);

echo $OUTPUT->header();

echo $output->render_message_page_header($bns, $title, (isset($CFG->enablerssfeeds) && $CFG->enablerssfeeds),
        has_capability('block/news:add', $blockcontext));

// Get the messages.
if (has_capability('block/news:viewhidden', $blockcontext)) {
    $bnms = $bns->get_messages_all(true); // See all dates, all visibilty.
} else {
    $bnms = $bns->get_messages_all(false); // See past/present only and visible.
}

// Display the messages.
if ($bnms == null) {
    echo $OUTPUT->container(get_string('msgblocknonews', 'block_news'), 'block_news_nonews');
} else {
    foreach ($bnms as $bnm) {
        $SESSION->news_block_views[$bnm->get_id()] = true;
        $msgwidget = new block_news_message_full($bnm, null, null, $bns, 'all', $bns->get_images());
        echo $output->render($msgwidget);
    }
}

echo $OUTPUT->footer();
