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
 * External API for mobile app / AJAX service calls.
 *
 * @package block_news
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news;

use block_news\output\mobile;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class external extends \external_api {

    /**
     * Define paramters for get_message_page function
     *
     * @return \external_function_parameters
     */
    public static function get_message_page_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
            'type' => new \external_value(PARAM_INT, 'Message type', VALUE_REQUIRED,
                    message::MESSAGETYPE_NEWS),
            'pagenum' => new \external_value(PARAM_INT, 'Page number', VALUE_REQUIRED),
            'pastevents' => new \external_value(PARAM_BOOL, 'Display past events', VALUE_REQUIRED,
                    false)
        ]);
    }

    /**
     * Get a page of news or event messages.
     *
     * @param int $courseid
     * @param int $type
     * @param int $pagenum
     * @param bool $pastevents
     * @return array
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function get_message_page($courseid, $type, $pagenum, $pastevents) {
        global $PAGE;
        $params = self::validate_parameters(self::get_message_page_parameters(),
            ['courseid' => $courseid, 'type' => $type, 'pagenum' => $pagenum, 'pastevents' => $pastevents]);
        self::validate_context(\context_course::instance($params['courseid']));
        $PAGE->set_course(get_course($params['courseid']));
        $messagedata = mobile::get_messages($params['courseid'], $params['type'],
            $params['pagenum'], $params['pastevents']);
        $messages = array_map(function($message) {
            // Strip out null optional attributes to make the return structure validation easier.
            return array_filter((array) $message);
        }, $messagedata['messages']);
        return ['messages' => $messages, 'moremessages' => $messagedata['moremessages']];
    }

    /**
     * Return definition for get_message_page
     *
     * @return \external_single_structure
     */
    public static function get_message_page_returns() {
        return new \external_single_structure([
            'messages' => new \external_multiple_structure(new \external_single_structure([
                'isnews' => new \external_value(PARAM_BOOL, 'Is this message news, rather than an event?', VALUE_OPTIONAL, false),
                'messagedate' => new \external_value(PARAM_TEXT, 'Message date'),
                'title' => new \external_value(PARAM_TEXT, 'Message title'),
                'author' => new \external_value(PARAM_TEXT, 'Author\'s name', VALUE_OPTIONAL),
                'imageurl' => new \external_value(PARAM_URL, 'URL of message image',
                    VALUE_OPTIONAL),
                'imagewidth' => new \external_value(PARAM_INT, 'Width of message image',
                    VALUE_OPTIONAL, 700),
                'imageheight' => new \external_value(PARAM_INT, 'Height of message image',
                    VALUE_OPTIONAL, 330),
                'imagedesc' => new \external_value(PARAM_TEXT, 'Image description', VALUE_OPTIONAL, ''),
                'eventdate' => new \external_value(PARAM_TEXT, 'Short-form start date of event', VALUE_OPTIONAL),
                'eventmonth' => new \external_value(PARAM_ALPHA, 'Month of the event start date', VALUE_OPTIONAL),
                'eventday' => new \external_value(PARAM_ALPHANUM, 'Day of the event start date', VALUE_OPTIONAL),
                'fulleventdate' => new \external_value(PARAM_TEXT, 'Long-form start date of event', VALUE_OPTIONAL),
                'eventlocation' => new \external_value(PARAM_TEXT, 'Event location', VALUE_OPTIONAL),
                'formattedmessage' => new \external_value(PARAM_RAW, 'HTML formatted message text'),
                'viewlink' => new \external_value(PARAM_URL, 'Internal link to full message', VALUE_OPTIONAL),
                'link' => new \external_value(PARAM_URL, 'External link to story from a feed', VALUE_OPTIONAL),
                'classes' => new \external_value(PARAM_TEXT, 'Extra CSS classes', VALUE_OPTIONAL, ''),
                'groupindication' => new \external_value(PARAM_RAW, 'Details of group-specific visibility', VALUE_OPTIONAL),
                'hasattachments' => new \external_value(PARAM_BOOL, 'Does the message include attachments?', VALUE_OPTIONAL, false),
                'attachments' => new \external_multiple_structure(
                    new \external_single_structure([
                        'filename' => new \external_value(PARAM_TEXT, 'Attachement filename'),
                        'iconsrc' => new \external_value(PARAM_URL, 'Attachment icon URL'),
                        'iconalt' => new \external_value(PARAM_TEXT, 'Attachement icon alt text'),
                        'url' => new \external_value(PARAM_URL, 'Attachement download URL')
                    ], 'Attachment link', VALUE_OPTIONAL
                ), 'Attachment links', VALUE_OPTIONAL)
            ]), VALUE_OPTIONAL),
            'moremessages' => new \external_value(PARAM_INT,
                    'Are there more messages to load? 0 for no, 1 for yes, 2 for yes but switch to past events.')
        ]);
    }
}
