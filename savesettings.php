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
 * Save news block data and redirect back.
 *
 * @package    block_news
 * @copyright  2017 the Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use block_news\system;

$blockinstanceid = required_param('bid', PARAM_INT);
$feedurls = required_param('feedurls', PARAM_RAW_TRIMMED);

$courseid = context_block::instance($blockinstanceid)->get_parent_context()->instanceid;

require_login($courseid);

$context = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $context, null, true);

require_sesskey();

$instance = $DB->get_record('block_instances', ['id' => $blockinstanceid], '*', MUST_EXIST);
$block = block_instance('news', $instance);

$errors = system::validate_form(['config_feedurls' => $feedurls]);

if ($errors) {
    foreach ($errors as $error) {
        core\notification::error($error);
    }
} else {
    $bns = system::get_block_settings($blockinstanceid);
    $bns->save_feed_urls($feedurls);
}

$url = new moodle_url('/course/view.php', ['id' => $courseid]);
redirect($url);
