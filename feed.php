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

$blockinstanceid = required_param('bi', PARAM_INT);
$groupingids = optional_param('groupingsids', 0, PARAM_SEQUENCE);

$murl = new moodle_url($CFG->wwwroot.'/blocks/news/feed.php',
                             array('blockinstanceid'=>$blockinstanceid));
$PAGE->set_url($murl);

// no login required

$context = get_context_instance(CONTEXT_BLOCK, $blockinstanceid);
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


