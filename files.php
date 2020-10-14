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
 * News block files service.
 *
 * This is the equivalent of pluginfile.php but without the require logins.
 *
 * This code is used when users view messages in a feed. When they view files in the VLE,
 * standard pluginfile, and the callback in lib.php is used instead.
 *
 * @package block_news
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output.
define('NO_DEBUG_DISPLAY', true);

require_once('../../config.php');
require_once($CFG->libdir . '/filelib.php');

$relativepath = get_file_argument();
$forcedownload = optional_param('forcedownload', 0, PARAM_BOOL);

// Relative path must start with '/'.
if (!$relativepath) {
    print_error('invalidargorconf');
} else if ($relativepath[0] != '/') {
    print_error('pathdoesnotstartslash');
}

// Extract relative path components.
$args = explode('/', ltrim($relativepath, '/'));
if (count($args) < 4) {
    print_error('invalidarguments');
}
$contextid = (int)array_shift($args);
$component = clean_param(array_shift($args), PARAM_COMPONENT);
$filearea  = clean_param(array_shift($args), PARAM_AREA);
$filename = array_pop($args);
$newsid = (int)array_shift($args);
$other = implode('/', $args);
$blockname = substr($component, 6);

list($context, $course, $cm) = get_context_info_array($contextid);

if ($context->contextlevel == CONTEXT_BLOCK) {
    $birecord = $DB->get_record('block_instances', ['id' => $context->instanceid], '*', MUST_EXIST);
    if ($birecord->blockname !== $blockname) {
        // Somebody tries to gain illegal access!
        send_file_not_found();
    }
} else {
    send_file_not_found();
}

$fs = get_file_storage();
$fullpath = "/$context->id/block_news/$filearea/$newsid/$other$filename";
if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
    send_file_not_found();
}

if ($parentcontext = context::instance_by_id($birecord->parentcontextid)) {
    if ($parentcontext->contextlevel == CONTEXT_USER) {
        // Force download on all personal pages including /my/
        // because we do not have reliable way to find out from where this is used.
        $forcedownload = true;
    }
} else {
    // Weird, there should be parent context, better force download then.
    $forcedownload = true;
}

\core\session\manager::write_close();
send_stored_file($file, 60 * 60, 0, $forcedownload);
