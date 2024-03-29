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
 * PHPUnit tests for new news group restriction support functions.
 *
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_news\system;

defined('MOODLE_INTERNAL') || die();
global $CFG;

/**
 * PHPUnit tests for new news group restriction support functions.
 *
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_news_grouprestriction_testcase extends advanced_testcase {
    /** @var stdClass $course */
    protected $course = null;

    /** @var stdClass $user */
    protected $user = null;

    /** @var stdClass $group1 */
    protected $group1 = null;

    /** @var stdClass $group2 */
    protected $group2 = null;

    /**
     * Set up.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);

        // Create a course.
        $this->course = $this->getDataGenerator()->create_course();

        // We need a user.
        $this->user = $this->getDataGenerator()->create_user();

        // Create 2 user group.
        $this->group1 = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id, 'name' => 'group1'));
        $this->group2 = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id, 'name' => 'group2'));

        // Add the the user to group1.
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id);
        groups_add_member($this->group1->id, $this->user->id);
    }

    /**
     * Tests the get_messages_limited function when block news do not enable group restriction support.
     */
    public function test_get_messages_limited_with_no_group_restriction_support() {
        // Create the generator object.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // Create news blocks.
        $nblock = $generator->create_instance(array(), array('courseid' => $this->course->id));

        // Create 2 news messages in news blocks.
        $message1 = new stdClass();
        $message1id = $generator->create_block_new_message($nblock, $message1);
        $message2 = new stdClass();
        $message2id = $generator->create_block_new_message($nblock, $message2);

        $bns = system::get_block_settings($nblock->id);
        $msgs = $bns->get_messages_limited(3);

        // When group restriction support is not enabled. Return 2 the messages.
        $this->assertEquals(2, count($msgs));
    }

    /**
     * Tests the get_messages_limited function when block news enable group restriction support.
     */
    public function test_get_messages_limited_with_group_restriction_support() {
        global $COURSE, $SESSION;

        $this->setUser($this->user->id);
        $COURSE->id = $this->course->id;
        // Create the generator object.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // Create news blocks.
        $nblock = $generator->create_instance(array(), array('courseid' => $this->course->id));

        // Create 2 news messages in news blocks. Add message to group.
        $message1 = new stdClass();
        $message1id = $generator->create_block_new_message($nblock, $message1, [$this->group1->id]);
        $message2 = new stdClass();
        $message2id = $generator->create_block_new_message($nblock, $message2, [$this->group2->id]);

        // Enable group support and call get_messages_limited function.
        $bns = system::get_block_settings($nblock->id);
        $bns->set_groupingsupport(2);
        $msgs = $bns->get_messages_limited(3);

        // When group restriction support is enabled. Return 1 the messages with correct groupid.
        $this->assertEquals(1, count($msgs));
        $this->assertTrue(in_array($this->group1->id, $msgs[0]->get_groupids()));

        // When user is removed from groups check restriction works.
        groups_remove_member($this->group1, $this->user);
        groups_remove_member($this->group2, $this->user);
        unset($SESSION->block_news_user_groups);
        $msgs = $bns->get_messages_limited(3);
        $this->assertEmpty($msgs);
    }

    /**
     * Tests the get_messages_all function when block news do not enable group restriction support.
     */
    public function test_get_messages_all_with_no_group_restriction_support() {
        // Create the generator object.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // Create news blocks.
        $nblock = $generator->create_instance(array(), array('courseid' => $this->course->id));

        // Create 2 news messages in news blocks.
        $message1 = new stdClass();
        $message1id = $generator->create_block_new_message($nblock, $message1);
        $message2 = new stdClass();
        $message2id = $generator->create_block_new_message($nblock, $message2);

        // Call get_message_all function.
        $bns = system::get_block_settings($nblock->id);
        $msgs = $bns->get_messages_all(1);

        // When group restriction support is not enabled. Return 2 the messages.
        $this->assertEquals(2, count($msgs));
    }

    /**
     * Tests the get_messages_all function when block news enable group restriction support.
     */
    public function test_get_messages_all_with_group_restriction_support() {
        global $COURSE, $SESSION;

        $this->setUser($this->user->id);
        $COURSE->id = $this->course->id;

        // Create the generator object.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // Create news blocks.
        $nblock = $generator->create_instance(array(), array('courseid' => $this->course->id));

        // Create 2 news messages in news blocks. Add message to group.
        $message1 = new stdClass();
        $message1id = $generator->create_block_new_message($nblock, $message1, [$this->group1->id]);
        $message2 = new stdClass();
        $message2id = $generator->create_block_new_message($nblock, $message2, [$this->group2->id]);

        // Enable group support and call get_messages_all function.
        $bns = system::get_block_settings($nblock->id);
        $bns->set_groupingsupport(2);
        $msgs = $bns->get_messages_all(1);

        // When group restriction support is enabled. Return 1 the messages with correct groupid.
        $this->assertEquals(1, count($msgs));
        $this->assertTrue(in_array($this->group1->id, $msgs[0]->get_groupids()));

        // When user is removed from groups check restriction works.
        groups_remove_member($this->group1, $this->user);
        groups_remove_member($this->group2, $this->user);
        unset($SESSION->block_news_user_groups);
        $msgs = $bns->get_messages_all(1);
        $this->assertEmpty($msgs);
    }

    public function test_remove_deleted_message_groups() {

        global $COURSE, $DB;

        $this->setUser($this->user->id);
        $COURSE->id = $this->course->id;

        // Create the generator object.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // Create news blocks.
        $nblock = $generator->create_instance(array(), array('courseid' => $this->course->id));

        // Create 2 news messages in news blocks. Add message to group.
        $message1 = new stdClass();
        $message1id = $generator->create_block_new_message($nblock, $message1, [$this->group1->id, $this->group2->id]);

        $bns = system::get_block_settings($nblock->id);
        $bns->set_groupingsupport(2);
        $msgs = $bns->get_messages_all(1);
        $groupindication = $bns->get_group_indication($msgs[0]);
        $this->assertTrue($DB->record_exists('block_news_message_groups',
                ['messageid' => $message1id, 'groupid' => $this->group1->id]));
        $this->assertTrue($DB->record_exists('block_news_message_groups',
                ['messageid' => $message1id, 'groupid' => $this->group2->id]));
        $this->assertEquals(get_string('rendermsggroupindication', 'block_news', $this->group1->name . ', ' . $this->group2->name),
                $groupindication);

        // Delete the group and check that the message group is removed when we get the group indication.
        groups_delete_group($this->group1->id);
        $groupindication = $bns->get_group_indication($msgs[0]);
        $this->assertFalse($DB->record_exists('block_news_message_groups',
                ['messageid' => $message1id, 'groupid' => $this->group1->id]));
        $this->assertTrue($DB->record_exists('block_news_message_groups',
                ['messageid' => $message1id, 'groupid' => $this->group2->id]));
        $this->assertEquals(get_string('rendermsggroupindication', 'block_news', $this->group2->name),
                $groupindication);
    }
}
