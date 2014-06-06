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
 * Return feed xml
 *
 * @package    blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once('block_news_system.php');

$blockinstanceid = optional_param('bi', 0, PARAM_INT);
$shortname = optional_param('shortname', '', PARAM_ALPHANUMEXT);
$groupingids = optional_param('groupingsids', 0, PARAM_SEQUENCE);

// Decide which block instance to use. The bi parameter takes precedence if both
// are provided.
if ($blockinstanceid == 0 && $shortname === '') {
    // One of the params is required.
    throw new moodle_exception(get_string('missingparam', 'error', 'bi/shortname'));
} else if ($shortname && $blockinstanceid == 0) {
    global $DB;
    // Get the required username param and the userid.
    $username = required_param('username', PARAM_ALPHANUM);
    $userid = $DB->get_field('user', 'id', array('username' => $username), MUST_EXIST);

    // Get the course id from the course short name.
    $courseid = $DB->get_field('course', 'id', array('shortname' => $shortname), MUST_EXIST);

    // Get the top news block instance id.
    $blockinstanceid = block_news_get_top_news_block($courseid);

    // Get the grouping ids.
    $groupingids = block_news_get_groupingids($courseid, $userid);
}

$murl = new moodle_url($CFG->wwwroot.'/blocks/news/feed.php',
                             array('blockinstanceid'=>$blockinstanceid));
$PAGE->set_url($murl);

// no login required

$context = context_block::instance($blockinstanceid);
$PAGE->set_context($context);

if (!isset($CFG->enablerssfeeds) || !$CFG->enablerssfeeds) {
    exit;
}

// get ifmodified header if present
$ifmodifiedsince=0;
if (array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER)) {
    $ifmodifiedsince=strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
}
$groupingids = explode(',', $groupingids);
// Remove any empty elements from clean param if non ints sent.
$groupingids = array_filter($groupingids);
$atomxml=block_news_system::get_block_feed($blockinstanceid, $ifmodifiedsince, $groupingids);

if ($atomxml == false) {
    header("HTTP/1.0 304 Not Modified");
    exit;
}


header('Content-type: application/atom+xml');
print($atomxml);


