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

global $CFG;
require_once($CFG->dirroot . '/blocks/news/tests/search_engine_advance_testcase.php');

class message_test extends search_engine_advance_testcase {

    /** @var object News block instance */
    private $blockinstance;
    /** @var \block_news_generator Data generator */
    private $generator;

    public function setUp(): void {
        $this->resetAfterTest(true);

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // We need a user.
        $user = $this->getDataGenerator()->create_user();

        // Create the generator object.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // Create a news block instance on the course.
        $this->blockinstance = $this->generator->create_instance([], ['courseid' => $course->id]);
    }

    public function test_delete() {
        global $DB;
        $fs = get_file_storage();
        $blockcontext = \context_block::instance($this->blockinstance->id);
        $mid1 = $this->generator->create_block_new_message($this->blockinstance,
                (object) ['image' => '/blocks/news/tests/fixtures/kitten1.jpg']);
        $mid2 = $this->generator->create_block_new_message($this->blockinstance,
                (object) ['image' => '/blocks/news/tests/fixtures/kitten2.jpg']);

        $mrec1 = $DB->get_record('block_news_messages', ['id' => $mid1]);

        $groupids = $DB->get_fieldset_select('block_news_message_groups', 'groupid', 'messageid = ?', [$mrec1->id]);
        $message1 = new message($mrec1, $groupids);
        $message1->delete();

        // Assert that the message and associated file has been deleted.
        $this->assertFalse($DB->record_exists('block_news_messages', ['id' => $mid1]));
        $this->assertEmpty($fs->get_area_files($blockcontext->id, 'block_news', 'messageimage', $mid1));

        // Assert that the other message and assocaited file are intact.
        $this->assertTrue($DB->record_exists('block_news_messages', ['id' => $mid2]));
        $this->assertNotEmpty($fs->get_area_files($blockcontext->id, 'block_news', 'messageimage', $mid2));
    }

    public function test_get_messages_all() {
        // Create a news block that displays separate news and events.
        $this->generator->create_block_news_record(
                $this->blockinstance,
                (object)['displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);
        // Set up a series of events with various start dates but no end date (all day events).
        $record = (object)['messagetype' => 'event',
                'eventstart' => time(),
                'eventend' => null,
                'eventlocation' => 'Milton Keynes'];
        $today = $this->generator->create_block_new_message($this->blockinstance, $record);
        $record->eventstart = time() - 86410; // Starts yesterday (plus 10 seconds)
        $yesterday = $this->generator->create_block_new_message($this->blockinstance, $record);
        $record->eventstart = time() + 86410;
        $tomorrow = $this->generator->create_block_new_message($this->blockinstance, $record);
        $bns = system::get_block_settings($this->blockinstance->id);
        $order = 'eventstart ASC, messagedate DESC';
        // Check events for display in upcoming events.
        $results1 = $bns->get_messages_all(false,null, null, 2, $order, false);
        $this->assertTrue(count($results1) == 2);
        $this->assertEquals($today, $results1[0]->get_id());
        $this->assertEquals($tomorrow, $results1[1]->get_id());
        // Check events for display in past events.
        $results2 = $bns->get_messages_all(false,null, null, 2, $order, true);
        $this->assertTrue(count($results2) == 1);
        $this->assertEquals($yesterday, $results2[0]->get_id());
        // Now check events with end dates.
        $record->eventstart = time() + 10; // Different from $today's start time.
        $record->eventend = time() + 3600; // One hour.
        $todayon = $this->generator->create_block_new_message($this->blockinstance, $record);
        $record->eventstart = time() - 86420; //Starts yesterday, ends today.
        $yesterdayon = $this->generator->create_block_new_message($this->blockinstance, $record);
        $record->eventend = time() - 86400; //Starts yesterday, ended yesterday.
        $yesterdayended = $this->generator->create_block_new_message($this->blockinstance, $record);
        // Check events for display in upcoming events.
        $results1 = $bns->get_messages_all(false,null, null, 2, $order, false);
        $this->assertTrue(count($results1) == 4);
        $this->assertEquals($yesterdayon, $results1[0]->get_id());
        $this->assertEquals($today, $results1[1]->get_id());
        $this->assertEquals($todayon, $results1[2]->get_id());
        $this->assertEquals($tomorrow, $results1[3]->get_id());
        // Check events for display in past events.
        $results2 = $bns->get_messages_all(false,null, null, 2, $order, true);
        $this->assertTrue(count($results2) == 2);
        $this->assertEquals($yesterdayended, $results2[0]->get_id());
        $this->assertEquals($yesterday, $results2[1]->get_id());
    }

    /**
     * Tests the scenario when delete a message then automatically deleted from the database by an adhoc task.
     */
    public function test_delete_index_after_delete_message(): void {
        global $DB, $CFG;

        $this->resetAfterTest();
        if (!$this->solr_setup()) {
            $this->markTestSkipped();
        }
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        // Create a news block.
        $newsgenerator = $generator->get_plugin_generator('block_news');
        $block = $newsgenerator->create_instance([], ['courseid' => $course->id]);

        // Unit testing hack to request feed from a file.
        $CFG->block_news_simplepie_feed = __DIR__ . '/fixtures/remote_rss_3.xml';

        // Set the block to use a feed.
        $blocksettings = system::get_block_settings($block->id);
        $blocksettings->save_feed_urls('https://frogs.example.org/');
        $messages = $blocksettings->get_messages_all(true);
        $this->assertCount(2, $messages);
        $this->assertEquals('Frogs 5', $messages[0]->get_title());
        $this->assertEquals('Frogs 6', $messages[1]->get_title());

        $this->waitForSecond();
        $this->assertTrue($this->search->index());

        // Check the message is in the index.
        $this->assert_raw_solr_query_result('content:"frogs"', ['Frogs 5', 'Frogs 6']);

        // Delete a message.
        $messages[0]->delete();

        $messagesafter = $blocksettings->get_messages_all(true);
        // There is only 1 message left.
        $this->assertCount(1, $messagesafter);
        $this->assertEquals('Frogs 6', $messagesafter[0]->get_title());

        // But the message Frogs 5 is still in the index.
        $this->assert_raw_solr_query_result('content:"frogs"', ['Frogs 5', 'Frogs 6']);
        // There should be an adhoc task runs.
        $this->expectOutputString("Deleted 1 old news messages search data entries\n");
        // Run search cleanup adhoc task.
        $this->runAdhocTasks();
        // The old index is deleted.
        $this->assert_raw_solr_query_result('content:"frogs"', ['Frogs 6']);
    }
}

