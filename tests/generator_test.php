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
 * PHPUnit data generator tests.
 *
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * PHPUnit data generator tests.
 *
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_news_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB;

        $this->resetAfterTest(true);

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create the generator object and do standard checks.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_news');
        $this->assertInstanceOf('block_news_generator', $generator);
        $this->assertEquals('news', $generator->get_blockname());

        // Create a new block and check there is a new block_instances record.
        $blocknum = $DB->count_records('block_instances');
        $newsblock = $generator->create_instance(array(), array('courseid' => $course->id));
        $this->assertEquals($blocknum + 1, $DB->count_records('block_instances'));

        // Create a block_positions record.
        $blockpos = $DB->count_records('block_positions');
        $record = new stdClass();
        $record->blockinstanceid = $newsblock->id;
        $generator->create_block_positions_record($record, $course->id);
        // Check the table has one more record.
        $this->assertEquals($blockpos + 1, $DB->count_records('block_positions'));
    }
}
