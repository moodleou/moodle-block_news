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
 * Unit tests for mobile output
 *
 * @package    block_news
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news\tests;

defined('MOODLE_INTERNAL') || die();

use block_news\message;
use block_news\system;

class mobile extends \advanced_testcase {

    private $courses = [];
    private $blocks = [];
    private $generator;
    private $upcomingdate;
    private $pastdate;

    /**
     * Generate a start date for an upcoming event.
     *
     * Generates a date in the future. Each time it is called, it will return a date 1 day ahead of the previous date,
     * so events will occur in the order they are generated.
     *
     * @return int Unix timestamp of event start.
     */
    private function get_upcoming_date() {
        if (is_null($this->upcomingdate)) {
            $this->upcomingdate = new \DateTime('now', \core_date::get_server_timezone_object());
        }
        $this->upcomingdate->add(\DateInterval::createFromDateString('1 day'));
        return $this->upcomingdate->getTimestamp();
    }

    /**
     * Generate a start date for a past event.
     *
     * Generates a date in the past. Each time it is called, it will return a date 1 day behind of the previous date,
     * so events will be generated most recent first. It starts 1 day ago so that the end time (which is this + 1 hour)
     * will never by today.
     *
     * @return int Unix timestamp of event start.
     */
    private function get_past_date() {
        if (is_null($this->pastdate)) {
            $this->pastdate = new \DateTime('1 day ago', \core_date::get_server_timezone_object());
        }
        $this->pastdate->sub(\DateInterval::createFromDateString('1 day'));
        return $this->pastdate->getTimestamp();
    }

    /**
     * Create 1 course with a new block instance.
     */
    public function setUp() {
        global $PAGE;
        $this->resetAfterTest();
        $this->courses['course1'] = $this->getDataGenerator()->create_course();
        $PAGE->set_course($this->courses['course1']);
        $this->blocks['block1'] = $this->getDataGenerator()->create_block(
                'news', [], ['courseid' => $this->courses['course1']->id]);
        $this->generator = $this->getDataGenerator()->get_plugin_generator('block_news');
        $position = (object) [
            'blockinstanceid' => $this->blocks['block1']->id
        ];
        $this->generator->create_block_positions_record($position, $this->courses['course1']->id);
    }

    /**
     * Test that the news tab is enabled on courses with a news block.
     */
    public function test_news_init() {
        $this->courses['course2'] = $this->getDataGenerator()->create_course();

        $result = \block_news\output\mobile::news_init();
        $this->assertContains($this->courses['course1']->id, $result['restrict']['courses']);
        $this->assertNotContains($this->courses['course2']->id, $result['restrict']['courses']);
    }

    /**
     * Test that the events tab is enabled on courses with a news block in news and events mode.
     */
    public function test_events_init() {
        $this->courses['course2'] = $this->getDataGenerator()->create_course();
        $this->courses['course3'] = $this->getDataGenerator()->create_course();
        $this->generator->create_block_news_record($this->blocks['block1'], (object) ['displaytype' => system::DISPLAY_DEFAULT]);
        $this->blocks['block2'] = $this->getDataGenerator()->create_block(
                'news', [], ['courseid' => $this->courses['course2']->id]);
        $this->generator->create_block_news_record($this->blocks['block2'], (object) [
            'displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS
        ]);

        $result = \block_news\output\mobile::events_init();
        $this->assertNotContains($this->courses['course1']->id, $result['restrict']['courses']);
        $this->assertContains($this->courses['course2']->id, $result['restrict']['courses']);
        $this->assertNotContains($this->courses['course3']->id, $result['restrict']['courses']);
    }

    public function test_get_messages_news_none() {
        $this->generator->create_block_news_record($this->blocks['block1'], (object) ['displaytype' => system::DISPLAY_DEFAULT]);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id);
        $this->assertEmpty($result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);

    }

    public function test_get_messages_news_one_page() {
        $this->generator->create_block_news_record($this->blocks['block1'], (object) ['displaytype' => system::DISPLAY_DEFAULT]);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id);
        $this->assertCount(4, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_news_one_full_page() {
        $this->generator->create_block_news_record($this->blocks['block1'], (object) ['displaytype' => system::DISPLAY_DEFAULT]);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id);
        $this->assertCount(5, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_news_two_pages() {
        $this->generator->create_block_news_record($this->blocks['block1'], (object) ['displaytype' => system::DISPLAY_DEFAULT]);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id);
        $this->assertCount(5, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_YES, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_NEWS, 1);
        $this->assertCount(1, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_events_none() {
        $this->generator->create_block_news_record($this->blocks['block1'],
                (object) ['displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT);
        $this->assertEmpty($result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 0, true);
        $this->assertEmpty($result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_events_news_only() {
        $this->generator->create_block_news_record($this->blocks['block1'],
                (object) ['displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);
        $this->generator->create_block_new_message($this->blocks['block1'], []);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT);
        $this->assertEmpty($result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 0, true);
        $this->assertEmpty($result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_events_one_page() {
        $this->generator->create_block_news_record($this->blocks['block1'],
                (object) ['displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT);
        $this->assertCount(4, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 0, true);
        $this->assertEmpty($result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_events_one_full_page() {
        $this->generator->create_block_news_record($this->blocks['block1'],
                (object) ['displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT);
        $this->assertCount(5, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 0, true);
        $this->assertEmpty($result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_events_two_pages() {
        $this->generator->create_block_news_record($this->blocks['block1'],
                (object) ['displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT);
        $this->assertCount(5, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_YES, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 1);
        $this->assertCount(1, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 0, true);
        $this->assertEmpty($result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_events_switch_to_past() {
        $this->generator->create_block_news_record($this->blocks['block1'],
                (object) ['displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT);
        $this->assertCount(5, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_SWITCHMODE, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 0, true);
        $this->assertCount(1, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_events_past_only() {
        $this->generator->create_block_news_record($this->blocks['block1'],
                (object) ['displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT);
        $this->assertEmpty($result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_SWITCHMODE, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 0, true);
        $this->assertCount(4, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_events_past_full_page() {
        $this->generator->create_block_news_record($this->blocks['block1'],
                (object) ['displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT);
        $this->assertEmpty($result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_SWITCHMODE, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 0, true);
        $this->assertCount(5, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }


    public function test_get_messages_events_past_two_pages() {
        $this->generator->create_block_news_record($this->blocks['block1'],
                (object) ['displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT);
        $this->assertEmpty($result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_SWITCHMODE, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 0, true);
        $this->assertCount(5, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_YES, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 1, true);
        $this->assertCount(1, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_events_both_two_pages() {
        $this->generator->create_block_news_record($this->blocks['block1'],
                (object) ['displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_upcoming_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);
        $eventstart = $this->get_past_date();
        $this->generator->create_block_new_message($this->blocks['block1'],
                ['eventstart' => $eventstart, 'eventend' => $eventstart + 3600, 'messagetype' => message::MESSAGETYPE_EVENT]);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT);
        $this->assertCount(5, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_YES, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 1);
        $this->assertCount(1, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_SWITCHMODE, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 0, true);
        $this->assertCount(5, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_YES, $result['moremessages']);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id, message::MESSAGETYPE_EVENT, 1, true);
        $this->assertCount(1, $result['messages']);
        $this->assertEquals(\block_news\output\mobile::MOREMESSAGES_NO, $result['moremessages']);
    }

    public function test_get_messages_group_visibility() {
        global $COURSE, $SESSION;

        $user = $this->getDataGenerator()->create_user();

        // Create 2 user group.
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $this->courses['course1']->id, 'name' => 'group1'));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $this->courses['course1']->id, 'name' => 'group2'));

        // Add the the user to group1.
        $this->getDataGenerator()->enrol_user($user->id, $this->courses['course1']->id);
        groups_add_member($group1->id, $user->id);
        $this->setUser($user->id);

        $COURSE->id = $this->courses['course1']->id;

        // Create 2 news messages in news blocks. Add message to group.
        $message1 = (object) ['title' => 'message1'];
        $message1id = $this->generator->create_block_new_message($this->blocks['block1'], $message1, [$group1->id]);
        $message2 = (object) ['title' => 'message2'];
        $message2id = $this->generator->create_block_new_message($this->blocks['block1'], $message2, [$group2->id]);

        // Enable group support and call get_messages function.
        $bns = system::get_block_settings($this->blocks['block1']->id);
        $bns->save((object) ['groupingsupport' => system::RESTRICTBYGROUP]);
        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id);

        // When group restriction support is enabled. Return 1 the messages with correct groupid.
        $this->assertEquals(1, count($result['messages']));
        $this->assertEquals('message1', $result['messages'][0]->title);

        // When user is removed from groups check restriction works.
        groups_remove_member($group1, $user);
        groups_remove_member($group2, $user);
        unset($SESSION->block_news_user_groups);

        // Create news blocks.
        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id);
        $this->assertEmpty($result['messages']);
    }

    /**
     * When there are multiple news blocks, we should only get messages from the first one.
     */
    public function test_get_messages_multiple_blocks() {
        $this->generator->create_block_news_record($this->blocks['block1'], (object) [
            'displaytype' => system::DISPLAY_DEFAULT
        ]);
        $this->blocks['block2'] = $this->getDataGenerator()->create_block(
            'news', [], ['courseid' => $this->courses['course1']->id]);
        $this->generator->create_block_news_record($this->blocks['block2'], (object) [
            'displaytype' => system::DISPLAY_DEFAULT
        ]);
        $position = (object) [
            'blockinstanceid' => $this->blocks['block2']->id,
            'weight' => 1
        ];
        $this->generator->create_block_positions_record($position, $this->courses['course1']->id);
        $message1 = (object) ['title' => 'message1'];
        $message1id = $this->generator->create_block_new_message($this->blocks['block1'], $message1);
        $message2 = (object) ['title' => 'message2'];
        $message2id = $this->generator->create_block_new_message($this->blocks['block2'], $message2);

        $result = \block_news\output\mobile::get_messages($this->courses['course1']->id);
        $this->assertCount(1, $result['messages']);
        $this->assertEquals($message1id, $result['messages'][0]->id);

    }
}
