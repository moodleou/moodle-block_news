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

use \block_news\system;

/**
 * PHPUnit tests for new news feed functions.
 *
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_news_newsfeed_testcase extends advanced_testcase {

    private $groupings = [];
    private $groups = [];
    private $generator;

    /**
     * Create two groupings containing a group each, and add the user to both groups.
     *
     * @param $course
     * @param $user
     */
    private function create_groupings($course, $user) {
        $this->groupings[1] = $this->generator->create_grouping(['courseid' => $course->id]);
        $this->groupings[2] = $this->generator->create_grouping(['courseid' => $course->id]);
        $this->groups[1] = $this->generator->create_group(['courseid' => $course->id]);
        $this->groups[2] = $this->generator->create_group(['courseid' => $course->id]);
        $this->generator->create_grouping_group(['groupingid' => $this->groupings[1]->id, 'groupid' => $this->groups[1]->id]);
        $this->generator->create_grouping_group(['groupingid' => $this->groupings[2]->id, 'groupid' => $this->groups[2]->id]);
        $this->generator->create_group_member(['userid' => $user->id, 'groupid' => $this->groups[1]->id]);
        $this->generator->create_group_member(['userid' => $user->id, 'groupid' => $this->groups[2]->id]);
    }

    public function test_get_top_news_block() {

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
        global $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // We need a user enrolled on the course.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $groupingids = block_news_get_groupingids($course->id, $USER->id);
        $this->assertEquals('', $groupingids);

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

        // Check admin grouping ids.
        $groupingids = block_news_get_groupingids($course->id, $USER->id);
        $this->assertEquals($grouping1->id . ',' . $grouping2->id, $groupingids);
    }

    public function test_get_feed_by_grouping() {
        $this->resetAfterTest(true);

        $this->generator = $this->getDataGenerator();
        $newsgenerator = $this->getDataGenerator()->get_plugin_generator('block_news');
        // Create a course.
        $course = $this->generator->create_course();

        $block = $newsgenerator->create_instance([], ['courseid' => $course->id]);
        $bns = system::get_block_settings($block->id);
        $bns->save((object) ['groupingsupport' => system::RESTRICTBYGROUP]);
        // We need a user enrolled on the course.
        $user1 = $this->generator->create_user();
        $this->generator->enrol_user($user1->id, $course->id);
        $this->create_groupings($course, $user1);
        $newsgenerator->create_block_new_message($block, ['title' => 'Group1 Message', 'timemodified' => time() - 8],
                [$this->groups[1]->id]);
        $newsgenerator->create_block_new_message($block, ['title' => 'Group2 Message', 'timemodidied' => time() - 6],
                [$this->groups[2]->id]);
        $newsgenerator->create_block_new_message($block, ['title' => 'All groups Message', 'timemodified' => time() - 4],
                [$this->groups[1]->id, $this->groups[2]->id]);
        $newsgenerator->create_block_new_message($block, ['title' => 'No groups Message', 'timemodified' => time() - 2]);

        // Get posts visible to grouping 1.
        $feed1 = system::get_block_feed($block->id, 0, [$this->groupings[1]->id]);
        $this->assertContains('<title>Group1 Message</title>', $feed1);
        $this->assertNotContains('<title>Group2 Message</title>', $feed1);
        $this->assertContains('<title>All groups Message</title>', $feed1);
        $this->assertContains('<title>No groups Message</title>', $feed1);

        // Get posts visible to grouping 2.
        $feed2 = system::get_block_feed($block->id, 0, [$this->groupings[2]->id]);
        $this->assertNotContains('<title>Group1 Message</title>', $feed2);
        $this->assertContains('<title>Group2 Message</title>', $feed2);
        $this->assertContains('<title>All groups Message</title>', $feed2);
        $this->assertContains('<title>No groups Message</title>', $feed2);

        // Get posts visible to grouping 1 and grouping 2.
        $feed3 = system::get_block_feed($block->id, 0, [$this->groupings[1]->id, $this->groupings[2]->id]);
        $this->assertContains('<title>Group1 Message</title>', $feed3);
        $this->assertContains('<title>Group2 Message</title>', $feed3);
        $this->assertContains('<title>All groups Message</title>', $feed3);
        $this->assertContains('<title>No groups Message</title>', $feed3);

        // Get all posts visible to user1 - this should be the same as above as this will get all the groups user1 can view.
        $feed4 = system::get_block_feed($block->id, 0, [], $user1->username);
        $this->assertContains('<title>Group1 Message</title>', $feed4);
        $this->assertContains('<title>Group2 Message</title>', $feed4);
        $this->assertContains('<title>All groups Message</title>', $feed4);
        $this->assertContains('<title>No groups Message</title>', $feed4);
    }

    public function test_process_internal_feed_extras() {
        $this->resetAfterTest(true);

        $message = <<<EOT
<div xmlns="http://www.w3.org/1999/xhtml">
 <div class="block_news-extras">
  <div class="box messageimage"><img class="block_news-main-msg-image" src="img700x330.jpg"/></div>
 </div>
 <p>News message includes an <img src="small.jpg"/> inline image (and an image above).</p>
</div>
EOT;
        list($msg, $imgurl, $imgdesc, $type, $loc, $start, $end) = system::process_internal_feed_extras($message);
        $this->assertNotContains('block_news-extras', $msg);
        $this->assertContains('News message includes', $msg);
        $this->assertEquals('img700x330.jpg', $imgurl);
        $this->assertEquals('', $imgdesc);
        $this->assertEquals('', $type);
        $this->assertEquals('', $loc);
        $this->assertEquals('', $start);
        $this->assertEquals('', $end);

        $message = <<<EOT
<div xmlns="http://www.w3.org/1999/xhtml">
 <div class="block_news-extras">
  <div class="block_news-event-type">2</div>
  <div class="block_news-event-location">Milton Keynes</div>
  <div class="block_news-event-start">1506812411</div>
  <div class="block_news-event-end">1507312411</div>
 </div>
 <p>Event message includes an <img src="small.jpg"/> inline image (but no image above).</p>
</div>
EOT;
        list($msg, $imgurl, $imgdesc, $type, $loc, $start, $end) = system::process_internal_feed_extras($message);
        $this->assertNotContains('block_news-extras', $msg);
        $this->assertContains('Event message includes', $msg);
        $this->assertEquals('', $imgurl);
        $this->assertEquals('', $imgdesc);
        $this->assertEquals(block_news\message::MESSAGETYPE_EVENT, $type);
        $this->assertEquals('Milton Keynes', $loc);
        $this->assertEquals('1506812411', $start);
        $this->assertEquals('1507312411', $end);
    }

    /**
     * Tests that feed caching (and specifically uncaching when a new post is added) works.
     *
     * @throws coding_exception
     */
    public function test_cache_clearing() {
        $this->resetAfterTest(true);

        // Create a course.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        // Create two news blocks, one is group-restricted.
        $newsgenerator = $generator->get_plugin_generator('block_news');
        $block1 = $newsgenerator->create_instance([], ['courseid' => $course->id]);
        $block2 = $newsgenerator->create_instance([], ['courseid' => $course->id]);
        $bns = system::get_block_settings($block2->id);
        $bns->save((object) ['groupingsupport' => system::RESTRICTBYGROUP]);

        // We need users enrolled on the course.
        $user1 = $generator->create_user();
        $generator->enrol_user($user1->id, $course->id);
        $user2 = $generator->create_user();
        $generator->enrol_user($user2->id, $course->id);

        // Get feed for both blocks (the second, for both users) and ensure they are all empty.
        $feed = system::get_block_feed($block1->id, 0);
        $this->assertNotContains('</entry>', $feed);
        $feed = system::get_block_feed($block2->id, 0, null, $user1->username);
        $this->assertNotContains('</entry>', $feed);
        $feed = system::get_block_feed($block2->id, 0, null, $user2->username);
        $this->assertNotContains('</entry>', $feed);

        // Post a message on both blocks.
        $newsgenerator->create_block_new_message($block1, ['title' => 'M1.1']);
        $newsgenerator->create_block_new_message($block2, ['title' => 'M2.1']);

        // Call uncache for the first block.
        system::get_block_settings($block1->id)->uncache_block_feed();

        // First block feed is changed, but second is still cached.
        $feed = system::get_block_feed($block1->id, 0);
        $this->assertContains('</entry>', $feed);
        $feed = system::get_block_feed($block2->id, 0, null, $user1->username);
        $this->assertNotContains('</entry>', $feed);
        $feed = system::get_block_feed($block2->id, 0, null, $user2->username);
        $this->assertNotContains('</entry>', $feed);

        // Call uncache for the second block.
        system::get_block_settings($block2->id)->uncache_block_feed();

        // Second block feed is now changed for both users.
        $feed = system::get_block_feed($block2->id, 0, null, $user1->username);
        $this->assertContains('</entry>', $feed);
        $feed = system::get_block_feed($block2->id, 0, null, $user2->username);
        $this->assertContains('</entry>', $feed);
    }

    /**
     * Tests the update feed function for getting a remote feed, with changes.
     */
    public function test_update_feed() {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        // Create a course.
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

        // Check there are 4 messages, in date order.
        $messages = $blocksettings->get_messages_all(true);
        $this->assertCount(4, $messages);
        $this->assertEquals('Frogs 2', $messages[0]->get_title());
        $this->assertEquals('Frogs 3', $messages[1]->get_title());
        $this->assertEquals('Frogs 1', $messages[2]->get_title());
        $this->assertEquals('Frogs 4', $messages[3]->get_title());

        // Now update the feed (pretend it wasn't updated since 1970).
        $CFG->block_news_simplepie_feed = __DIR__ . '/fixtures/remote_rss_2.xml';
        $DB->set_field('block_news_feeds', 'feedupdated', 1);
        $task = new \block_news\task\process_feeds();
        ob_start();
        $task->execute();
        ob_end_clean();

        // Get messages again - 1 and 2 have gone, there should be a new message 5.
        $messagesafter = $blocksettings->get_messages_all(true);
        $this->assertCount(3, $messagesafter);
        $this->assertEquals('Frogs 3', $messagesafter[0]->get_title());
        $this->assertEquals('Frogs 4', $messagesafter[1]->get_title());
        $this->assertEquals('Frogs 5', $messagesafter[2]->get_title());

        // Confirm that the two existing messages do not have changed ID i.e. they were not
        // recreated.
        $this->assertEquals($messages[1]->get_id(), $messagesafter[0]->get_id());
        $this->assertEquals($messages[3]->get_id(), $messagesafter[1]->get_id());
    }

}
