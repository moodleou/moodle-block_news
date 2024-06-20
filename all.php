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

use block_news\system;
use block_news\message;
use block_news\output\full_message;
use block_news\output\view_all_page;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/classes/subscription.php');
require_once('lib.php');

$blockinstanceid = required_param('bi', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$get_news = \block_news\subscription::get_from_bi($blockinstanceid);;
$bns = system::get_block_settings($blockinstanceid);
// Check prison theme to make breadcrumb consistent with title.
$title = $bns->get_title();
$isprison = class_exists('\auth_prison\util') && \auth_prison\util::is_prison_vle();
$title = $isprison && $bns->get_displaytype() == system::DISPLAY_DEFAULT || empty($title) ?
        get_string('pluginname', 'block_news') : $title;

// Codechecker complains about missing require_login.  It's part of the following function.
$csemod = block_news_init_page($blockinstanceid, $title, $bns->get_displaytype());

$output = $PAGE->get_renderer('block_news');
$blockcontext = context_block::instance($blockinstanceid);

$urlparams = array('bi' => $blockinstanceid);
$PAGE->set_url('/blocks/news/all.php', $urlparams);

// Breadcrumb.
$title .= ': ' . get_string('allmessages', 'block_news');
$PAGE->set_title($csemod->cseshortname . ': ' . $title);
$PAGE->set_heading($csemod->csefullname);

$output->pre_header($bns);

echo $OUTPUT->header();

echo $output->render_message_page_header($bns, $title, (isset($CFG->enablerssfeeds) && $CFG->enablerssfeeds),
        has_capability('block/news:add', $blockcontext), $get_news);

// Get the messages.
$viewhidden = has_capability('block/news:viewhidden', $blockcontext);
if ($bns->get_displaytype() == system::DISPLAY_DEFAULT) {
    $bnms = $bns->get_messages_all($viewhidden);
    // Display the messages.
    if ($bnms == null) {
        echo $OUTPUT->container(get_string('msgblocknonews', 'block_news'), 'block_news_nonews');
    } else {
        $images = $bns->get_images();
        $files = $bns->get_files();
        foreach ($bnms as $bnm) {
            $SESSION->news_block_views[$bnm->get_id()] = true;
            $msgwidget = new full_message($bnm, null, null, $bns, 'all', $images, '', $files);
            echo $output->render($msgwidget);
        }
    }

} else {
    $pageinfo = [
            (object) [
                'messagecount' => $bns->get_message_count($viewhidden, message::MESSAGETYPE_NEWS),
                'pagesize' => system::ALL_NEWS_PAGE_SIZE,
            ],
            (object) [
                'messagecount' => $bns->get_message_count($viewhidden, message::MESSAGETYPE_EVENT),
                'pagesize' => system::ALL_EVENTS_PAGE_SIZE,
            ],
            (object) [
                'messagecount' => $bns->get_message_count($viewhidden, message::MESSAGETYPE_EVENT, true),
                'pagesize' => system::ALL_EVENTS_PAGE_SIZE,
            ],
    ];
    $mostpages = $bns->find_most_pages($pageinfo);
    $pager = new paging_bar($mostpages->messagecount, $page, $mostpages->pagesize, '/blocks/news/all.php?bi=' . $blockinstanceid);
    echo $output->render($pager);

    $news = $bns->get_messages_all($viewhidden, system::ALL_NEWS_PAGE_SIZE, $page, message::MESSAGETYPE_NEWS);
    $upcomingevents = $bns->get_messages_all(
            $viewhidden, system::ALL_EVENTS_PAGE_SIZE, $page, message::MESSAGETYPE_EVENT);
    $pastevents = $bns->get_messages_all(
            $viewhidden, system::ALL_EVENTS_PAGE_SIZE, $page, message::MESSAGETYPE_EVENT,
            'eventstart DESC, messagedate DESC', true);

    $viewallpage = new view_all_page($bns, $news, $upcomingevents, $pastevents);
    echo $output->render($viewallpage);

    echo $output->render($pager);
}
if($get_news->can_view_subscribers()) {
    echo $output->render_view_subscriber($get_news);
}

echo $output->render_news_subscribe_bottom($get_news);

echo $OUTPUT->footer();
