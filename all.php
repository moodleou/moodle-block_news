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

use block_news\output\view_all_page;

$blockinstanceid = required_param('bi', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

$bns = block_news_system::get_block_settings($blockinstanceid);

// Codechecker complains about missing require_login.  It's part of the following function.
$csemod = block_news_init_page($blockinstanceid, $bns->get_title(), $bns->get_displaytype());

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
$viewhidden = has_capability('block/news:viewhidden', $blockcontext);
if ($bns->get_displaytype() == block_news_system::DISPLAY_DEFAULT) {
    $bnms = $bns->get_messages_all($viewhidden);
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

} else {
    $pageinfo = [
            (object) [
                'messagecount' => $bns->get_message_count($viewhidden, block_news_message::MESSAGETYPE_NEWS),
                'pagesize' => block_news_system::ALL_NEWS_PAGE_SIZE,
            ],
            (object) [
                'messagecount' => $bns->get_message_count($viewhidden, block_news_message::MESSAGETYPE_EVENT),
                'pagesize' => block_news_system::ALL_EVENTS_PAGE_SIZE,
            ],
            (object) [
                'messagecount' => $bns->get_message_count($viewhidden, block_news_message::MESSAGETYPE_EVENT, true),
                'pagesize' => block_news_system::ALL_EVENTS_PAGE_SIZE,
            ],
    ];
    $mostpages = $bns->find_most_pages($pageinfo);
    $pager = new paging_bar($mostpages->messagecount, $page, $mostpages->pagesize, '/blocks/news/all.php?bi=' . $blockinstanceid);
    echo $output->render($pager);

    $news = $bns->get_messages_all($viewhidden, block_news_system::ALL_NEWS_PAGE_SIZE, $page, block_news_message::MESSAGETYPE_NEWS);
    $upcomingevents = $bns->get_messages_all(
            $viewhidden, block_news_system::ALL_EVENTS_PAGE_SIZE, $page, block_news_message::MESSAGETYPE_EVENT);
    $pastevents = $bns->get_messages_all(
            $viewhidden, block_news_system::ALL_EVENTS_PAGE_SIZE, $page, block_news_message::MESSAGETYPE_EVENT,
            'eventstart DESC, messagedate DESC', true);

    $viewallpage = new view_all_page($bns, $news, $upcomingevents, $pastevents);
    echo $output->render($viewallpage);

    echo $output->render($pager);
}

echo $OUTPUT->footer();
