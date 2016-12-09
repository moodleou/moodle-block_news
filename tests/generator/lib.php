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

    protected function preprocess_record(stdClass $record, array $options) {
        $context = context_course::instance($options['courseid']);
        $record->parentcontextid = $context->id;
        $record->blockname = 'news';
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

    /**
     * Create New Block News
     *
     * @param stdclass $block
     * @param stdclass $block
     * @return bool|int
     */
    public function create_block_news_record($block, $record) {
        global $DB;

        $record->blockinstanceid = $block->id;

        if (!isset($record->title)) {
            $record->title = 'Block News Title';
        }

        if (!isset($record->nummessages)) {
            $record->nummessages = 2;
        }

        if (!isset($record->summarylength)) {
            $record->summarylength = 50;
        }

        return $DB->insert_record('block_news', $record, true);
    }

    /**
     * Create New Block Message
     *
     * @param stdclass $block
     * @param stdclass $block
     * @return bool|int
     */
    public function create_block_new_message($block, $record) {
        global $DB;

        $record->blockinstanceid = $block->id;

        if (!isset($record->title)) {
            $record->title = 'Unit Test message';
        }
        if (!isset($record->message)) {
            $record->message = 'Message Text';
        }
        if (!isset($record->messageformat)) {
            $record->messageformat = 1;
        }
        if (!isset($record->messagetype)) {
            $record->messagetype = block_news_message::MESSAGETYPE_NEWS;
        }
        if (!isset($record->messageimage)) {
            $record->messageimage = null;
        }
        if (!isset($record->newsfeedid)) {
            $record->newsfeedid = 0;
        }
        if (!isset($record->messagevisable)) {
            $record->messagevisible = 1;
        }
        if (!isset($record->massagedate)) {
            $record->messagedate = time() - 3600;
        }
        if (!isset($record->hideauthor)) {
            $record->hideauthor = 0;
        }
        if (!isset($record->messagerepeat)) {
            $record->messagerepeat = 0;
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        if (!isset($record->attachments)) {
            $record->attachments = null;
        }
        return $DB->insert_record('block_news_messages', $record, true);
    }
}
