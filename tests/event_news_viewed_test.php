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

namespace block_news;

use block_news\event\message_viewed;
use block_news\event\page_viewed;

/**
 * Unit tests for news viewed events.
 *
 * @package    block_news
 * @category   test
 * @copyright  2025 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_news\event
 */
class event_news_viewed_test extends \advanced_testcase {
    /** @var object News block instance */
    private $blockinstance;
    /** @var object Course */
    private $course;
    /** @var \block_news_generator Data generator */
    private $generator;

    /**
     * Reset after each test.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        // Create a course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create the generator object.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // Create a news block instance on the course.
        $this->blockinstance = $this->generator->create_instance([], ['courseid' => $this->course->id]);
    }

    /**
     * Test message viewed event.
     */
    public function test_message_viewed_event(): void {
        $blockcontext = \context_block::instance($this->blockinstance->id);
        $mid = $this->generator->create_block_new_message($this->blockinstance,
                (object) ['image' => '/blocks/news/tests/fixtures/kitten1.jpg']);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $sink = $this->redirectEvents();
        $event = message_viewed::create([
            'context' => $blockcontext,
            'other' => ['mid' => $mid],
        ]);
        $event->trigger();
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('block_news\event\message_viewed', $event);
        $this->assertEquals($mid, $event->other['mid']);
        $this->assertEquals($blockcontext->id, $event->contextid);
    }

    /**
     * Test message viewed event description.
     */
    public function test_message_viewed_event_description(): void {
        $blockcontext = \context_block::instance($this->blockinstance->id);
        $mid = $this->generator->create_block_new_message($this->blockinstance,
                (object) ['image' => '/blocks/news/tests/fixtures/kitten1.jpg']);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $event = message_viewed::create([
            'context' => $blockcontext,
            'other' => ['mid' => $mid],
        ]);
        $description = $event->get_description();
        $expected = "The user with id '{$user->id}' has viewed the news message " .
                "with the id '{$mid}' in the course with id '{$this->course->id}'.";
        $this->assertEquals($expected, $description);
    }

    /**
     * Test page viewed event.
     */
    public function test_page_viewed_event(): void {
        $blockcontext = \context_block::instance($this->blockinstance->id);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $sink = $this->redirectEvents();
        $event = page_viewed::create([
            'context' => $blockcontext,
            'other' => ['bid' => $this->blockinstance->id],
        ]);
        $event->trigger();
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('block_news\event\page_viewed', $event);
        $this->assertEquals($this->blockinstance->id, $event->other['bid']);
        $this->assertEquals($blockcontext->id, $event->contextid);
    }

    /**
     * Test page viewed event description.
     */
    public function test_page_viewed_event_description(): void {
        $blockcontext = \context_block::instance($this->blockinstance->id);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $event = page_viewed::create([
            'context' => $blockcontext,
            'other' => ['bid' => $this->blockinstance->id],
        ]);
        $description = $event->get_description();
        $expected = "The user with id '{$user->id}' has viewed the news page " .
                "with the id '{$this->blockinstance->id}' in the course with id '{$this->course->id}'.";
        $this->assertEquals($expected, $description);
    }
}
