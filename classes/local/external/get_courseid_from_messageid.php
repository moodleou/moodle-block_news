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

namespace block_news\local\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use block_news\system;

require_once($CFG->libdir . '/externallib.php');

/**
 * Block news services implementation to get courseid from messageid.
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_courseid_from_messageid extends external_api {
    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function get_courseid_from_messageid_parameters() {
        return new external_function_parameters(array(
                'messageid' => new external_value(PARAM_INT, 'Message ID')
        ));
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function get_courseid_from_messageid_returns() : external_single_structure {
        return new external_single_structure([
                'title' => new external_value(PARAM_RAW, 'Block news title'),
                'messageid' => new external_value(PARAM_INT, 'Message id'),
                'courseid' => new external_value(PARAM_INT, 'Course id'),
        ]);
    }

    /**
     * Get course id from message id.
     *
     * @param int $messageid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function get_courseid_from_messageid($messageid) {
        global $DB;
        // There are no security restrictions because the information is not secret.
        $params = self::validate_parameters(self::get_courseid_from_messageid_parameters(), ['messageid' => $messageid]);
        $sql = system::get_message_sql_start() .
                'WHERE m.id = ?';
        $mrec = $DB->get_record_sql($sql, [$params['messageid']], MUST_EXIST);

        $blockinstanceid = $mrec->blockinstanceid;

        $csemod = block_news_get_course_mod_info($blockinstanceid);

        $courseid = $csemod->course->id;
        return [
            'title' => get_string('newsheading', 'block_news'),
            'messageid' => $params['messageid'],
            'courseid' => $courseid
        ];
    }
}
