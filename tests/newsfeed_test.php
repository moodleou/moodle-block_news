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
 * PHPUnit tests for new news feed functions.
 *
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib.php');

/**
 * PHPUnit tests for new news feed functions.
 *
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_news_newsfeed_testcase extends advanced_testcase {
    public function test_newsfeed_by_course_shortname() {
        global $DB;

        $this->resetAfterTest(true);

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // We need a user.
        $user = $this->getDataGenerator()->create_user();

        // Create the generator object.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // There no news blocks yet, test the function returns null.
        $topblockid = block_news_get_top_news_block($course->id, $user->id);
        $this->assertEquals(null, $topblockid);

        // Create 2 news blocks.
        $nblock1 = $generator->create_instance(array(), array('courseid' => $course->id));
        $nblock2 = $generator->create_instance(array(), array('courseid' => $course->id));

        // Create the block_positions record for news block nblock1.
        $record = new stdClass();
        $record->blockinstanceid = $nblock1->id;
        // This block is displayed below nblock2.
        $record->weight = 1;
        $generator->create_block_positions_record($record, $course->id);

        // Create the block_positions record for news block nblock2.
        $record = new stdClass();
        $record->blockinstanceid = $nblock2->id;
        // This block is displayed above nblock1.
        $record->weight = 0;
        $generator->create_block_positions_record($record, $course->id);

        // The top news block should be equal to the id of $nblock2.
        $topblockid = block_news_get_top_news_block($course->id, $user->id);
        $this->assertEquals($nblock2->id, $topblockid);
    }

    public function test_groupingids_function() {

        $this->resetAfterTest(true);

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // We need a user enrolled on the course.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // The user is not yet in any groups, there should be no grouping ids.
        $groupingids = block_news_get_groupingids($course->id, $user->id);
        $this->assertEquals('', $groupingids);

        // Make two grouping and three groups.
        $generator = $this->getDataGenerator();
        $grouping1 = $generator->create_grouping(array('courseid' => $course->id));
        $grouping2 = $generator->create_grouping(array('courseid' => $course->id));
        $group1 = $generator->create_group(array('courseid' => $course->id));
        $group2 = $generator->create_group(array('courseid' => $course->id));
        groups_assign_grouping($grouping1->id, $group1->id);
        groups_assign_grouping($grouping2->id, $group2->id);

        // Add the the user to group1.
        groups_add_member($group1->id, $user->id);

        // There should be one grouping id, the id of grouping 1.
        $groupingids = block_news_get_groupingids($course->id, $user->id);
        $this->assertEquals($grouping1->id, $groupingids);

        // Add the the user to the other group, group2.
        groups_add_member($group2->id, $user->id);

        // There should be two grouping ids, that of grouping 1 and 2, separated by a comma.
        $groupingids = block_news_get_groupingids($course->id, $user->id);
        $this->assertEquals($grouping1->id . ',' . $grouping2->id, $groupingids);
    }
}
