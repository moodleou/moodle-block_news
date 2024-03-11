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
global $PAGE;

/**
 * This script handles requests to subscribe/unsubscribe from a news.
 * It operates in two modes: 'go back' mode, where after subscribing it
 * redirects, and 'full' mode (normally used only for links in email) where
 * it displays information about the action.
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use block_news\system;
require_once('../../config.php');
require_once('lib.php');
$courseid = optional_param('course', 0, PARAM_INT);
$bi = optional_param('bi', 0, PARAM_INT);
$back = optional_param('back', '', PARAM_ALPHA);

$userid = optional_param('user', 0, PARAM_INT);
$key = optional_param('key', '', PARAM_RAW);

$pageparams = array();
if ($bi) {
    $pageparams['bi'] = $bi;
}
$requestingsubscribe = optional_param('submitsubscribe', '', PARAM_RAW);
$requestingunsubscribe = optional_param('submitunsubscribe', '', PARAM_RAW);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get request always does unsubscribe.
    $requestingunsubscribe = 'y';
    $requestingsubscribe = '';
}
$subscribeoptions = ($requestingsubscribe ? 1 : 0) + ($requestingunsubscribe ? 1 : 0);
if ($subscribeoptions != 1 && !$key) {
    throw new moodle_exception('error_subscribeparams', 'block_news');
}

$confirmtext = get_string(
    $requestingsubscribe ? 'subscribe_already' : 'unsubscribe_already', 'block_news');
if ($bi) {
    $news = \block_news\subscription::get_from_bi($bi);

    $subscriptioninfo = $news->get_subscription_info($userid);
    if ($subscriptioninfo->subscribed) {
        $subscribed = \block_news\subscription::FULLY_SUBSCRIBED;
    } else {
        $subscribed = \block_news\subscription::NOT_SUBSCRIBED;
    }
    if ($requestingsubscribe && $subscribed != \block_news\subscription::FULLY_SUBSCRIBED) {
        $news->subscribe();
        $confirmtext = get_string('subscribe_confirm', 'block_news');
    } else if ($requestingunsubscribe && $subscribed != \block_news\subscription::NOT_SUBSCRIBED) {
        $news->unsubscribe();
        $confirmtext = get_string('unsubscribe_confirm', 'block_news');
    }
}

if ($back == 'view') {
    redirect($news->get_url(\block_news\subscription::PARAM_PLAIN));
}

if ($bi) {
    $backurl = $news->get_url(\block_news\subscription::PARAM_HTML);

    // Handle one-click subscribe specifically.
    if ($key) {
        // Check key is valid.
        if (!$userid || $key !== $news->get_unsubscribe_key($userid)) {
            throw new \moodle_exception('error_subscribeparams', 'block_news');
        }
        if ($subscriptioninfo->subscribed) {
            $news->unsubscribe($userid);
        }
        $output = block_news_init_page($bi, null, system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS, false);
    } else {
        $output = block_news_init_page($bi, null);
    }

    $urlparams = ['bi' => $bi];
    $PAGE->set_url('/blocks/news/subscribers.php', $urlparams);
    $output = $PAGE->get_renderer('block_news');
    print $output->header();
    print $output->notification($confirmtext, 'success');
    print $output->continue_button($backurl);
    print $output->footer();
}
