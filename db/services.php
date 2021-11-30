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
 * Services.
 *
 * @package block_news
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_news_get_message_page' => [
        'classname' => '\block_news\external',
        'methodname' => 'get_message_page',
        'description' => 'Get a page of messages from the news block.',
        'type' => 'read',
        'capabilities' => '',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile']
    ],
    'block_news_get_courseid_from_messageid' => [
        'classname'   => 'block_news\local\external\get_courseid_from_messageid',
        'methodname'  => 'get_courseid_from_messageid',
        'description' => 'Get courseid from message id',
        'type'        => 'read',
        'capabilities' => '',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile']
    ],
];
