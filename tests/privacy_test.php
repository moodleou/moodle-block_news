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

use core_privacy\tests\provider_testcase;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use block_news\privacy\provider;

/**
 * Data provider testcase class.
 *
 * @package block_news
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_test extends provider_testcase {

    /** @var array */
    protected $users = [];
    /** @var array */
    protected $bctxs = [];
    /** @var array */
    protected $messages = [];
    /** @var array */
    protected $blocks = [];
    /** @var \stdClass */
    protected $generator;

    /**
     * Set up for each test.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $this->generator = $dg->get_plugin_generator('block_news');
        $course1 = $dg->create_course();
        $course2 = $dg->create_course();
        $this->users[1] = $dg->create_user();
        $this->users[2] = $dg->create_user();
        $this->users[3] = $dg->create_user();
        // Create 2 news blocks for one course and 1 for the other.
        $this->blocks[1] = $this->generator->create_instance([], ['courseid' => $course1->id]);
        $this->blocks[2] = $this->generator->create_instance([], ['courseid' => $course1->id]);
        $this->blocks[3] = $this->generator->create_instance([], ['courseid' => $course2->id]);

        $this->bctxs = [
                1 => \context_block::instance($this->blocks[1]->id),
                2 => \context_block::instance($this->blocks[2]->id),
                3 => \context_block::instance($this->blocks[3]->id)
        ];

        // Create 2 news messages in news blocks by User 1.
        $message1 = new \stdClass();
        $message1->title = 'Message No1';
        $message1->message = 'This is message number one';
        $message1->image = '/blocks/news/tests/fixtures/kitten1.jpg';
        $message1->userid = $this->users[1]->id;
        $this->messages[1] = $this->generator->create_block_new_message($this->blocks[1], $message1);

        $message2 = new \stdClass();
        $message2->title = 'Message No2';
        $message2->message = 'This is message number two';
        $message2->image = '/blocks/news/tests/fixtures/kitten2.jpg';
        $message2->userid = $this->users[1]->id;
        $this->messages[2] = $this->generator->create_block_new_message($this->blocks[2], $message2);

        // User 1 subscribe a block message.
        $DB->insert_record('block_news_subscriptions', ['blockinstanceid' => $this->blocks[3]->id,
                'userid' => $this->users[1]->id, 'subscribed' => '1']);

        // Create 2 news messages in news blocks by User 2.
        $message3 = new \stdClass();
        $message3->title = 'Message No3';
        $message3->message = 'This is message number three';
        $message3->image = '/blocks/news/tests/fixtures/kitten2.jpg';
        $message3->userid = $this->users[2]->id;
        $this->messages[3] = $this->generator->create_block_new_message($this->blocks[1], $message3);

        $message4 = new \stdClass();
        $message4->title = 'Message No4';
        $message4->message = 'This is message number four';
        $message4->userid = $this->users[2]->id;
        $this->messages[4] = $this->generator->create_block_new_message($this->blocks[3], $message4);
        $DB->insert_record('block_news_message_groups', ['groupid' => 2, 'messageid' => $this->messages[4]]);

        // User 3 subscribe a block message.
        $DB->insert_record('block_news_subscriptions', ['blockinstanceid' => $this->blocks[3]->id,
                'userid' => $this->users[3]->id, 'subscribed' => '1']);
    }
    /**
     * Test get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $contextids = provider::get_contexts_for_userid($this->users[1]->id)->get_contextids();
        $this->assertCount(3, $contextids);
        $this->assertTrue(in_array($this->bctxs[1]->id, $contextids));
        $this->assertTrue(in_array($this->bctxs[2]->id, $contextids));
        $this->assertTrue(in_array($this->bctxs[3]->id, $contextids));

        $contextids = provider::get_contexts_for_userid($this->users[3]->id)->get_contextids();
        $this->assertCount(1, $contextids);
        $this->assertTrue(in_array($this->bctxs[3]->id, $contextids));
    }

    /**
     * Test for export_user_data().
     */
    public function test_export_data_for_user() {
        global $DB;
        $appctx = new approved_contextlist($this->users[1], 'block_news',
                [$this->bctxs[1]->id, $this->bctxs[2]->id, $this->bctxs[3]->id]);
        provider::export_user_data($appctx);
        // Export data.

        $data1 = writer::with_context($this->bctxs[1])->get_data([]);
        $this->assertEquals('Message No1', $data1->news['Message No1']->title);
        $this->assertEquals('This is message number one', $data1->news['Message No1']->message);

        $data2 = writer::with_context($this->bctxs[2])->get_data([]);
        $this->assertEquals('Message No2', $data2->news['Message No2']->title);
        $this->assertEquals('This is message number two', $data2->news['Message No2']->message);

        // Check messages image.
        $files = writer::with_context($this->bctxs[2])->get_files([]);
        $this->assertEquals(['kitten2.jpg'], array_keys($files));
        // Second page has a attachment upload by this user.
        $files = writer::with_context($this->bctxs[1])->get_files([]);
        $this->assertEquals(['kitten1.jpg'], array_keys($files));

        // Test export for user 1 with subscription data.
        $sub = $DB->get_record('block_news_subscriptions', ['blockinstanceid' => $this->blocks[3]->id,
                'userid' => $this->users[1]->id]);
        $this->assertEquals((object)[
                'userid' => get_string('privacy_you', 'block_news'),
                'subscribed' => \core_privacy\local\request\transform::yesno(1),
        ], writer::with_context($this->bctxs[3])->get_data([get_string('blocknewssubscriptions', 'block_news')
                . '-' . $sub->id]));

        // Test export for user 3 with subscription data.
        $appctx1 = new approved_contextlist($this->users[3], 'block_news',
                [$this->bctxs[3]->id]);
        provider::export_user_data($appctx1);
        $sub = $DB->get_record('block_news_subscriptions', ['blockinstanceid' => $this->blocks[3]->id,
                'userid' => $this->users[3]->id]);
        $this->assertEquals((object)[
                'userid' => get_string('privacy_you', 'block_news'),
                'subscribed' => \core_privacy\local\request\transform::yesno(1),
        ], writer::with_context($this->bctxs[3])->get_data([get_string('blocknewssubscriptions', 'block_news')
                . '-' . $sub->id]));
    }

    /**
     * Test for delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        provider::delete_data_for_all_users_in_context($this->bctxs[1]);
        // Userid field in all records in given context were updated to admin id.
        $records = $DB->get_records('block_news_messages', ['blockinstanceid' => $this->bctxs[1]->instanceid]);
        foreach ($records as $record) {
            $this->assertEquals(get_admin()->id, $record->userid);
        }

        // Userid field in records not in given context were not changed.
        $record = $DB->get_record('block_news_messages', ['blockinstanceid' => $this->bctxs[3]->instanceid]);
        $this->assertEquals($this->users[2]->id, $record->userid);

        // Test delete all subscription.
        $records = $DB->get_records('block_news_subscriptions', ['blockinstanceid' => $this->bctxs[3]->instanceid]);
        $this->assertCount(2, $records);
        provider::delete_data_for_all_users_in_context($this->bctxs[3]);
        $records = $DB->get_records('block_news_subscriptions', ['blockinstanceid' => $this->bctxs[3]->instanceid]);
        $this->assertCount(0, $records);
    }

    /**
     * Test for delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB;
        $appctx = new approved_contextlist($this->users[1], 'block_news',
                [$this->bctxs[1]->id, $this->bctxs[2]->id]);
        provider::delete_data_for_user($appctx);
        $records = $DB->get_records('block_news_messages',
                ['blockinstanceid' => $this->bctxs[1]->instanceid, 'userid' => $this->users[1]->id]);
        $this->assertCount(0, $records);
        $records = $DB->get_records('block_news_messages',
                ['blockinstanceid' => $this->bctxs[1]->instanceid, 'userid' => $this->users[2]->id]);
        $this->assertCount(1, $records);
        $records = $DB->get_records('block_news_messages',
                ['blockinstanceid' => $this->bctxs[1]->instanceid, 'userid' => get_admin()->id]);
        $this->assertCount(1, $records);

        // Test delete data for user 3.
        $appctx1 = new approved_contextlist($this->users[3], 'block_news',
                [$this->bctxs[3]->id]);
        provider::delete_data_for_user($appctx1);
        $records = $DB->get_records('block_news_subscriptions', ['blockinstanceid' => $this->bctxs[3]->instanceid]);
        $this->assertCount(1, $records);
    }

    /**
     * Test for get_users_in_context()
     */
    public function test_get_users_in_context() {
        $component = 'block_news';

        $userlist1 = new \core_privacy\local\request\userlist($this->bctxs[1], $component);
        $userlist2 = new \core_privacy\local\request\userlist($this->bctxs[2], $component);
        $userlist3 = new \core_privacy\local\request\userlist($this->bctxs[3], $component);

        // Check first block news.
        provider::get_users_in_context($userlist1);
        $userids = $userlist1->get_userids();
        $this->assertCount(2, $userids);
        $this->assertTrue(in_array($this->users[1]->id, $userids));
        $this->assertTrue(in_array($this->users[2]->id, $userids));

        // Check second block news.
        provider::get_users_in_context($userlist2);
        $userids = $userlist2->get_userids();
        $this->assertCount(1, $userids);
        $this->assertTrue(in_array($this->users[1]->id, $userids));

        // Check third block news.
        provider::get_users_in_context($userlist3);
        $userids = $userlist3->get_userids();
        $this->assertCount(3, $userids);
        $this->assertTrue(in_array($this->users[1]->id, $userids));
        $this->assertTrue(in_array($this->users[2]->id, $userids));
        $this->assertTrue(in_array($this->users[3]->id, $userids));
    }

    /**
     * Test update data for multiple users in context.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_delete_data_for_users() {
        global $DB;
        $component = 'block_news';

        $approveduserids = [$this->users[1]->id];
        $approvedlist = new \core_privacy\local\request\approved_userlist($this->bctxs[1], $component, $approveduserids);
        provider::delete_data_for_users($approvedlist);
        $bnm = $DB->get_records('block_news_messages', ['blockinstanceid' => $this->bctxs[1]->instanceid]);
        // Check first message belongs to admin now.
        $this->assertEquals($bnm[$this->messages[1]]->userid, get_admin()->id);

        // Test data belongs to second user still there, other are deleted for first context.
        $this->assertCount(2, $bnm);
        $this->assertArrayHasKey($this->messages[3], $bnm);

        // Check data belongs to second context is not updated.
        $bnm = $DB->get_records('block_news_messages', ['blockinstanceid' => $this->bctxs[2]->instanceid]);
        $this->assertCount(1, $bnm);
        $this->assertArrayHasKey($this->messages[2], $bnm);

        $approvedlist = new \core_privacy\local\request\approved_userlist($this->bctxs[3], $component, $approveduserids);
        provider::delete_data_for_users($approvedlist);
        $records = $DB->get_records('block_news_subscriptions', ['blockinstanceid' => $this->bctxs[3]->instanceid]);
        $this->assertCount(1, $records);
    }
}
