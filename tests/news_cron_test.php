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

global $CFG;
require_once($CFG->dirroot . '/blocks/news/tests/search_engine_advance_testcase.php');

/**
 * PHPUnit news subscription tests.
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class news_cron_test extends search_engine_advance_testcase {
    /** @var object News block instance */
    private $blockinstance;

    /** @var \block_news_generator Data generator */
    private $generator;

    protected $adminid;
    protected $adminemail;

    /** @var \stdClass|null $group1 */
    protected $group = null;

    /** @var \stdClass|null $user */
    protected $user = null;

    public function setUp(): void {
        global $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->adminid = $USER->id;
        $this->adminemail = $USER->email;
        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create the generator object.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // Create a news block instance on the course.
        $this->blockinstance = $this->generator->create_instance([], ['courseid' => $course->id]);

        // Create a group.
        $this->group = $this->getDataGenerator()->create_group(array('courseid' => $course->id, 'name' => 'group'));

        $this->user = $this->getDataGenerator()->create_user();
        // We need users enrolled on the course.
        $this->getDataGenerator()->enrol_user($this->user->id, $course->id);
        $this->getDataGenerator()->enrol_user($this->adminid, $course->id);

        $this->newsmessageareaid = \core_search\manager::generate_areaid('block_news', 'news_message');
    }

    public function test_news_email_normal(): void {
        $this->generator->create_block_news_record(
            $this->blockinstance,
            (object) ['displaytype' => system::DISPLAY_DEFAULT]);
        $this->generator->create_block_new_message($this->blockinstance,
            (object) ['image' => '/blocks/news/tests/fixtures/kitten1.jpg', 'userid' => $this->adminid]);
        $news = subscription::get_from_bi($this->blockinstance->id);
        $news->subscribe($this->adminid);
        $sink = $this->redirectEmails();
        ob_start();
        \block_news\task\news_email::email_normal();
        ob_end_clean();

        $messages = $sink->get_messages();

        // Check 1 email sent.
        $this->assertEquals(1, count($messages));
    }

    /**
     * Tests, when sending a normal email, that correct headers are included.
     */
    public function test_news_email_normal_headers(): void {
        $this->generator->create_block_news_record(
            $this->blockinstance,
            (object) ['displaytype' => system::DISPLAY_DEFAULT, 'title' => 'Bulletin']);
        $this->generator->create_block_new_message($this->blockinstance,
            (object) ['image' => '/blocks/news/tests/fixtures/kitten1.jpg', 'userid' => $this->adminid]);
        $news = subscription::get_from_bi($this->blockinstance->id);
        $news->subscribe($this->adminid);
        $sink = $this->redirectEmails();
        ob_start();
        \block_news\task\news_email::email_normal();
        ob_end_clean();

        $messages = $sink->get_messages();

        // Check headers.
        $this->assertEquals(1, count($messages));
        $this->assertEquals(\core_user::get_noreply_user()->email, $messages[0]->from);
        $this->assertStringContainsString('List-Id: "Bulletin" <block_news' .
            $this->blockinstance->id . '/moodle@www.example.com>', $messages[0]->header);
        $this->assertMatchesRegularExpression('~List-Unsubscribe: <.*?/blocks/news/subscribe.php\?bi=' .
            $this->blockinstance->id . '&user=' . $this->adminid . '&key=' .
            $news->get_unsubscribe_key($this->adminid) . '>~', $messages[0]->header);
        $this->assertStringContainsString('List-Unsubscribe-Post: List-Unsubscribe=One-Click',
            $messages[0]->header);
    }

    public function test_email_message_with_group_restriction(): void {
        // Add admin user to group.
        groups_add_member($this->group->id, $this->adminid);

        $this->generator->create_block_news_record(
            $this->blockinstance,
            (object) ['displaytype' => system::DISPLAY_DEFAULT]);
        $this->generator->create_block_new_message($this->blockinstance,
            (object) ['userid' => $this->adminid], [$this->group->id]);
        // Enable group support and call get_messages_limited function.
        $bns = system::get_block_settings($this->blockinstance->id);
        $bns->set_groupingsupport(2);
        $news = subscription::get_from_bi($this->blockinstance->id);
        $news->subscribe($this->adminid);
        $news->subscribe($this->user->id);
        $sink = $this->redirectEmails();
        ob_start();
        \block_news\task\news_email::email_normal();
        ob_end_clean();
        $messages = $sink->get_messages();
        // Check 1 email sent because only admin user in group.
        $this->assertEquals(1, count($messages));
        // Double check with email address.
        $this->assertEquals($messages[0]->to, $this->adminemail);
    }

    public function test_email_message_with_future_publication_date(): void {
        $this->generator->create_block_news_record(
            $this->blockinstance,
            (object) ['displaytype' => system::DISPLAY_DEFAULT]);
        $future_time = time() + 3600;
        $this->generator->create_block_new_message($this->blockinstance,
            (object) ['image' => '/blocks/news/tests/fixtures/kitten1.jpg',
                'userid' => $this->adminid, 'messagedate' => $future_time]);

        $news = subscription::get_from_bi($this->blockinstance->id);
        $news->subscribe($this->adminid);
        $sink = $this->redirectEmails();
        ob_start();
        \block_news\task\news_email::email_normal();
        ob_end_clean();
        $messages = $sink->get_messages();

        // Check 0 email sent.
        $this->assertEquals(0, count($messages));
    }

    public function test_block_news_mail_list(): void {
        $this->generator->create_block_news_record(
            $this->blockinstance,
            (object) ['displaytype' => system::DISPLAY_DEFAULT]);
        $mid1 = $this->generator->create_block_new_message($this->blockinstance,
            (object) ['userid' => $this->adminid]);

        $this->resetAfterTest();
        $this->preventResetByRollback();
        mail_list::reset_static_cache();
        $list = new mail_list(false);

        $this->assertFalse($list->is_finished());
        $this->assertTrue($list->next_news($news, $blockcontext, $course));
        $this->assertEquals($this->blockinstance->id, $news->get_blockinstanceid());
        $this->assertTrue($list->next_message($message));
        $this->assertEquals($mid1, $message->id);
        $this->assertFalse($list->next_news($news, $blockcontext, $course));
    }

    /**
     * Tests the scenario when the RSS update then automatically deleted from the database by an adhoc task.
     */
    public function test_delete_index_after_update_feed(): void {
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
        $CFG->block_news_simplepie_feed = __DIR__ . '/fixtures/remote_rss_1.xml';

        // Set the block to use a feed.
        $blocksettings = system::get_block_settings($block->id);
        $blocksettings->save_feed_urls('https://frogs.example.org/');

        // We have 4 messages at first.
        $messages = $blocksettings->get_messages_all(true);
        $this->assertCount(4, $messages);
        $this->assertEquals('Frogs 2', $messages[0]->get_title());
        $this->assertEquals('Frogs 3', $messages[1]->get_title());
        $this->assertEquals('Frogs 1', $messages[2]->get_title());
        $this->assertEquals('Frogs 4', $messages[3]->get_title());
        $this->waitForSecond();
        $this->assertTrue($this->search->index());

        // Check 4 messages are in the index.
        $this->assert_raw_solr_query_result('content:"frogs"', ['Frogs 1', 'Frogs 2', 'Frogs 3', 'Frogs 4']);

        // Update new feed with 2 messages.
        $CFG->block_news_simplepie_feed = __DIR__ . '/fixtures/remote_rss_3.xml';
        $DB->set_field('block_news_feeds', 'feedupdated', 1);
        $task = new \block_news\task\process_feeds();
        ob_start();
        $task->execute();
        ob_end_clean();

        // Now we have 2 messages after update feed.
        $messagesafter = $blocksettings->get_messages_all(true);
        $this->assertCount(2, $messagesafter);
        $this->assertEquals('Frogs 5', $messagesafter[0]->get_title());
        $this->assertEquals('Frogs 6', $messagesafter[1]->get_title());

        $this->waitForSecond();
        $this->search->index();

        // There are 6 items in search index: 4 old items (need to be cleared) and 2 new items.
        $this->assert_raw_solr_query_result('content:"frogs"', ['Frogs 1', 'Frogs 2', 'Frogs 3', 'Frogs 4', 'Frogs 5', 'Frogs 6']);

        // There should be an adhoc task runs.
        $this->expectOutputString("Deleted 4 old news messages search data entries\n");
        // Run search cleanup adhoc task.
        $this->runAdhocTasks();
        // 4 old messages index is cleared.
        $this->assert_raw_solr_query_result('content:"frogs"', ['Frogs 5', 'Frogs 6']);

        // Update feed but disable search clean up.
        $CFG->block_news_simplepie_feed = __DIR__ . '/fixtures/remote_rss_1.xml';
        $DB->set_field('block_news_feeds', 'feedupdated', 2);

        $CFG->block_news_disablesearchcleanup = 1;
        $task = new \block_news\task\process_feeds();
        ob_start();
        $task->execute();
        ob_end_clean();

        $messagesafter = $blocksettings->get_messages_all(true);
        // Now we have 4 messages after update feed.
        $this->assertCount(4, $messagesafter);
        $this->assertEquals('Frogs 2', $messages[0]->get_title());
        $this->assertEquals('Frogs 3', $messages[1]->get_title());
        $this->assertEquals('Frogs 1', $messages[2]->get_title());
        $this->assertEquals('Frogs 4', $messages[3]->get_title());

        $this->waitForSecond();
        $this->search->index();
        $this->runAdhocTasks();
        // There still contain 2 old messages Frogs 5 and Frogs 6.
        $this->assert_raw_solr_query_result('content:"frogs"', ['Frogs 1', 'Frogs 2', 'Frogs 3', 'Frogs 4', 'Frogs 5', 'Frogs 6']);
    }
}
