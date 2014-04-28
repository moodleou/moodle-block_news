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
 * News block data generator.
 *
 * @package   block_news
 * @copyright 2014 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_news_generator extends testing_block_generator {

    /**
     * Create new block instance
     *
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $DB;

        $this->instancecount++;

        $record = (object)(array)$record;
        $options = (array)$options;

        $context = context_course::instance($options['courseid']);
        $record->parentcontextid = $context->id;
        $record = $this->prepare_record($record);
        $record->blockname = 'news';

        $id = $DB->insert_record('block_instances', $record);
        context_block::instance($id);

        return $DB->get_record('block_instances', array('id' => $id), '*', MUST_EXIST);
    }

    /**
     * Create block_positions record.
     *
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass activity record with extra cmid field
     */
    public function create_block_positions_record($record, $courseid) {
        global $DB;

        $context = context_course::instance($courseid);

        if (!isset($record->contextid)) {
            $record->contextid = $context->id;
        }
        if (!isset($record->pagetype)) {
            $record->pagetype = 'course-view-' . $DB->get_field('course', 'format', array('id' => $courseid));
        }
        if (!isset($record->subpage)) {
            $record->subpage = '';
        }
        if (!isset($record->visible)) {
            $record->visible = 1;
        }
        if (!isset($record->region)) {
            $record->region = 'side-post';
        }
        if (!isset($record->weight)) {
            $record->weight = 0;
        }

        return $DB->insert_record('block_positions', $record);
    }
}
