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

$urlparams = array('bi' => $blockinstanceid);
$PAGE->set_url('/blocks/news/all.php', $urlparams);

// breadcrumb
$title = $bns->get_title();
$title = empty($title) ? get_string('pluginname', 'block_news') : $title;
$title .= ': ' . get_string('allmessages', 'block_news');
$PAGE->set_title($csemod->cseshortname . ': ' . $title);
$PAGE->set_heading($csemod->csefullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

echo $OUTPUT->container_start('block_news_top', null);
// show Add if permittted
$blockcontext = get_context_instance(CONTEXT_BLOCK, $blockinstanceid);
if (has_capability('block/news:add', $blockcontext)) {
    echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/news/edit.php?bi=' .
        $blockinstanceid . '&mode=all', 'Add a new message', 'Add news', null,
        array('id' => 'block_news_add'));
}

// if feeds allowed on site, display icon
if (isset($CFG->enablerssfeeds) && $CFG->enablerssfeeds) {
    $pi = new pix_icon('i/rss', 'RSS');
    echo $OUTPUT->container_start('', 'block_news_rss_all');
    echo $OUTPUT->action_icon($bns->get_feed_url(), $pi);
    echo $OUTPUT->container_end();
}
echo $OUTPUT->container_end(); // \block_news_top

// get the messages
if (has_capability('block/news:viewhidden', $blockcontext)) {
    $bnms = $bns->get_messages_all(true); // see all dates, all visibilty
} else {
    $bnms = $bns->get_messages_all(false); // see past/present only and visible
}

// display the messages
$output = $PAGE->get_renderer('block_news'); // looks for class xxx_renderer
if ($bnms == null) {
    echo $OUTPUT->container(get_string('msgblocknonews', 'block_news'), 'block_news_nonews');
} else {
    foreach ($bnms as $bnm) {
        $SESSION->news_block_views[$bnm->get_id()] = true;
        $msgwidget = new block_news_message_full($bnm, null, null, $bns, 'all');
        echo $output->render($msgwidget);
    }
}

echo $OUTPUT->footer();
