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
 * Unit tests for the message class
 *
 * @package    block_news
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news;

defined('MOODLE_INTERNAL') || die();

class message_testcase extends \advanced_testcase {

    /** @var object News block instance */
    private $block;
    /** @var \block_news_generator Data generator */
    private $generator;

    public function setUp() {
        $this->resetAfterTest(true);

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // We need a user.
        $user = $this->getDataGenerator()->create_user();

        // Create the generator object.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        $this->block = $this->generator->create_instance(
                [], ['courseid' => $course->id, 'displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);
    }

    public function test_delete() {
        global $DB;
        $fs = get_file_storage();
        $blockcontext = \context_block::instance($this->block->id);
        $mid1 = $this->generator->create_block_new_message($this->block,
                (object) ['image' => '/blocks/news/tests/fixtures/kitten1.jpg']);
        $mid2 = $this->generator->create_block_new_message($this->block,
                (object) ['image' => '/blocks/news/tests/fixtures/kitten2.jpg']);

        $mrec1 = $DB->get_record('block_news_messages', ['id' => $mid1]);

        $message1 = new message($mrec1);
        $message1->delete();

        // Assert that the message and associated file has been deleted.
        $this->assertFalse($DB->record_exists('block_news_messages', ['id' => $mid1]));
        $this->assertEmpty($fs->get_area_files($blockcontext->id, 'block_news', 'messageimage', $mid1));

        // Assert that the other message and assocaited file are intact.
        $this->assertTrue($DB->record_exists('block_news_messages', ['id' => $mid2]));
        $this->assertNotEmpty($fs->get_area_files($blockcontext->id, 'block_news', 'messageimage', $mid2));
    }
}

