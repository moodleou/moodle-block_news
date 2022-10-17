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

/**
 * PHPUnit news subscription tests.
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class news_cron_test extends \advanced_testcase {
    /** @var object News block instance */
    private $blockinstance;
    /** @var \block_news_generator Data generator */
    private $generator;

    protected $adminid;
    protected $adminemail;

    /** @var stdClass $group1 */
    protected $group = null;

    /** @var stdClass $user */
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
}
