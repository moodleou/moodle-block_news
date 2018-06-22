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
 * Unit tests for the moodle global search functions.
 *
 * @package    block_news
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/news/classes/search/news_message.php');
require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');

class news_message_testcase extends \advanced_testcase {

    /**
     * Converts recordset to array, indexed numberically (0, 1, 2).
     *
     * @param $rs Record set to convert
     * @return stdClass[] Array of converted records
     */
    protected static function recordset_to_array($rs) {
        $result = array();
        foreach ($rs as $rec) {
            $result[] = $rec;
        }
        $rs->close();
        return $result;
    }

    public function test_get_document_recordset() {

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Enable global search for this test.
        set_config('enableglobalsearch', true);
        $search = \testable_core_search::instance();
        $newsmessage = new \block_news\search\news_message();

        // Create 2 courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // We need a user.
        $user = $this->getDataGenerator()->create_user();

        // Create the generator object.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        $nblock1 = $generator->create_instance(array(), array('courseid' => $course1->id));
        $nblock2 = $generator->create_instance(array(), array('courseid' => $course2->id));
        system::get_block_settings($nblock1->id);
        system::get_block_settings($nblock2->id);

        // Create news messages (one for each course).
        $message1 = new \stdClass();
        $message1->timemodified = 3;
        $message1->title = 'Message No1';
        $message1->message = 'This is message number one';
        $message1->messageformat = 1;
        $message1->messagetype = 1;
        $message1->messageimage = (object) ['image' => '/blocks/news/tests/fixtures/kitten1.jpg'];
        $message1->newsfeedid = null;
        $message1->messagevisible = 1;
        $message1->messagedate = time();
        $message1->hideauthor = 0;
        $message1->messagerepeat = 0;
        $message1->userid = 0;
        $message1id = $generator->create_block_new_message($nblock1, $message1);

        $message2 = new \stdClass();
        $message2->timemodified = 2;
        $message2->title = 'Message No2';
        $message2->message = 'This is message number two';
        $message2->messageformat = 1;
        $message2->messagetype = 1;
        $message2->messageimage = (object) ['image' => '/blocks/news/tests/fixtures/kitten2.jpg'];
        $message2->newsfeedid = null;
        $message2->messagevisible = 1;
        $message2->messagedate = time();
        $message2->hideauthor = 1;
        $message2->messagerepeat = 0;
        $message2->userid = $user->id;
        $message2id = $generator->create_block_new_message($nblock2, $message2);

        // Check without context.
        $rs = $newsmessage->get_document_recordset();
        $recordsetarray = self::recordset_to_array($rs);
        $doc = $newsmessage->get_document($recordsetarray[0]);
        $this->assertCount(2, $recordsetarray);

        // Check with context.
        $coursecontext = \context_course::instance($course1->id);
        $rs2 = $newsmessage->get_document_recordset(0, $coursecontext);
        $recordsetarray2 = self::recordset_to_array($rs2);
        $doc = $newsmessage->get_document($recordsetarray2[0]);
        $this->assertCount(1, $recordsetarray2);
    }

    public function test_news_message() {

        $this->resetAfterTest(true);
        $this->setAdminUser();
        // Enable global search for this test.
        set_config('enableglobalsearch', true);
        $search = \testable_core_search::instance();

        $newsmessage = new \block_news\search\news_message();
        $rs = $newsmessage->get_document_recordset();
        $this->assertCount(0, self::recordset_to_array($rs));

        // Create 2 courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // We need a user.
        $user = $this->getDataGenerator()->create_user();

        // Create the generator object.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // Create 2 news blocks for one course and one for the other.
        $nblock1 = $generator->create_instance(array(), array('courseid' => $course1->id));
        $nblock2 = $generator->create_instance(array(), array('courseid' => $course1->id));

        // Initialise settings for each block.
        system::get_block_settings($nblock1->id);
        system::get_block_settings($nblock2->id);

        // Create 3 news messages in news blocks.
        $message1 = new \stdClass();
        $message1->timemodified = 3;
        $message1->title = 'Message No1';
        $message1->message = 'This is message number one';
        $message1->messageformat = 1;
        $message1->messagetype = 1;
        $message1->messageimage = (object) ['image' => '/blocks/news/tests/fixtures/kitten1.jpg'];
        $message1->newsfeedid = null;
        $message1->messagevisible = 1;
        $message1->messagedate = time();
        $message1->hideauthor = 0;
        $message1->messagerepeat = 0;
        $message1->userid = 0;
        $message1id = $generator->create_block_new_message($nblock1, $message1);

        $message2 = new \stdClass();
        $message2->timemodified = 2;
        $message2->title = 'Message No2';
        $message2->message = 'This is message number two';
        $message2->messageformat = 1;
        $message2->messagetype = 1;
        $message2->messageimage = (object) ['image' => '/blocks/news/tests/fixtures/kitten2.jpg'];
        $message2->newsfeedid = null;
        $message2->messagevisible = 1;
        $message2->messagedate = time();
        $message2->hideauthor = 1;
        $message2->messagerepeat = 0;
        $message2->userid = $user->id;
        $message2id = $generator->create_block_new_message($nblock2, $message2);

        $message3 = new \stdClass();
        $message3->timemodified = 1;
        $message3->title = 'Message No3';
        $message3->message = 'This is message number three';
        $message3->messageformat = 1;
        $message3->messagetype = 1;
        $message3->newsfeedid = null;
        $message3->messagevisible = 0;
        $message3->messagedate = time();
        $message3->hideauthor = 0;
        $message3->messagerepeat = 0;
        $message3->userid = $user->id;
        $message3id = $generator->create_block_new_message($nblock1, $message3);

        $rs2 = $newsmessage->get_document_recordset();
        $myrecordsetarray = self::recordset_to_array($rs2);
        $this->assertCount(3, $myrecordsetarray);

        // Check results.
        $doc = $newsmessage->get_document($myrecordsetarray[0]);
        $this->assertEquals(1, $doc->get('modified'));
        $this->assertEquals('Message No3', $doc->get('title'));
        $this->assertEquals('This is message number three', $doc->get('content'));
        $this->assertEquals(\core_search\manager::TYPE_TEXT, $doc->get('type'));
        $this->assertEquals(\context_block::instance($nblock1->id)->id, $doc->get('contextid'));
        $this->assertEquals($course1->id, $doc->get('courseid'));
        $this->assertEquals(\core_search\manager::NO_OWNER_ID, $doc->get('owneruserid'));
        $this->assertEquals($user->id, $doc->get('userid'));

        $doc = $newsmessage->get_document($myrecordsetarray[1]);
        $this->assertEquals(2, $doc->get('modified'));
        $this->assertFalse($doc->is_set('userid'));

        $doc = $newsmessage->get_document($myrecordsetarray[2]);
        $this->assertEquals(3, $doc->get('modified'));
        $this->assertFalse($doc->is_set('userid'));
    }

    public function test_check_access() {

        $this->resetAfterTest(true);
        $this->setAdminUser();
        // Enable global search for this test.
        set_config('enableglobalsearch', true);
        $search = \testable_core_search::instance();

        $newsmessage = new \block_news\search\news_message();

        // Create 2 courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // We need a user.
        $user = $this->getDataGenerator()->create_user();

        // Add groups.
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course1->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course1->id));

        // Create the generator object.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // Create 2 news blocks for one course and one for the other.
        $nblock1 = $generator->create_instance(array(), array('courseid' => $course1->id));
        $nblock2 = $generator->create_instance(array(), array('courseid' => $course1->id));
        $nblock3 = $generator->create_instance(array(), array('courseid' => $course2->id));

        // Create news messages in news blocks.
        $message1 = new \stdClass();
        $message1->timemodified = 4;
        $message1id = $generator->create_block_new_message($nblock1, $message1, array(1));
        $message2 = new \stdClass();
        $message2->timemodified = 3;
        $message2id = $generator->create_block_new_message($nblock2, $message2);
        $message3 = new \stdClass();
        $message3->timemodified = 2;
        $message3id = $generator->create_block_new_message($nblock1, $message3);
        $message4 = new \stdClass();
        $message4->timemodified = 1;
        $message4->messagedate = time() + 3600;
        $message4id = $generator->create_block_new_message($nblock1, $message4);
        $bns1 = system::get_block_settings($nblock1->id);
        $bns2 = system::get_block_settings($nblock2->id);

        $bns1->save((object)['title' => 'News', 'groupingsupport' => 2]);
        $bns2->save((object)['title' => 'News', 'groupingsupport' => 2]);
        $rs2 = $newsmessage->get_document_recordset();

        // Admin user.
        // Confirm access to message1.
        $this->assertEquals(1, $newsmessage->check_access($message1id));
        // Confirm deleted status for attempted access to non-existent message.
        $this->assertEquals(2, $newsmessage->check_access($message4id + 1));
        // Confirm access to future message.
        $this->assertEquals(1, $newsmessage->check_access($message4id));

        // Non-admin user.
        $this->setUser($user);
        // Confirm no access to message1 as not a member of the required group.
        $this->assertEquals(0, $newsmessage->check_access($message1id));
        // Confirm deleted status for attempted access to non-existent message.
        $this->assertEquals(2, $newsmessage->check_access($message4id + 1));
        // Confirm access as a group member.
        groups_add_member($group2, $user);
        $this->assertEquals(1, $newsmessage->check_access($message2id));
        // Confirm no access to future message.
        $this->assertEquals(0, $newsmessage->check_access($message4id));
    }

    public function test_get_doc_and_context_url() {

        $this->resetAfterTest(true);
        $this->setAdminUser();
        // Enable global search for this test.
        set_config('enableglobalsearch', true);
        $search = \testable_core_search::instance();

        $newsmessage = new \block_news\search\news_message();
        $rs = $newsmessage->get_recordset_by_timestamp();

        // Create a course.
        $course1 = $this->getDataGenerator()->create_course();

        // We need a user.
        $user = $this->getDataGenerator()->create_user();

        // Create the generator object.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_news');

        // Create a news block.
        $nblock1 = $generator->create_instance(array(), array('courseid' => $course1->id));

        // Create a news message.
        $message1 = new \stdClass();
        $message1->timemodified = 3;
        $message1->title = 'Message No1';
        $message1->message = 'This is message number one';
        $message1->messageformat = 1;
        $message1->messagetype = 1;
        $message1->messageimage = (object) ['image' => '/blocks/news/tests/fixtures/kitten1.jpg'];
        $message1->newsfeedid = null;
        $message1->messagevisible = 1;
        $message1->messagedate = time();
        $message1->hideauthor = 0;
        $message1->messagerepeat = 0;
        $message1->userid = 0;
        $message1id = $generator->create_block_new_message($nblock1, $message1);

        $bns1 = system::get_block_settings($nblock1->id);

        $rs2 = $newsmessage->get_document_recordset();
        $myrecordsetarray = self::recordset_to_array($rs2);

        // Check get_doc_url.
        $doc = $newsmessage->get_document($myrecordsetarray[0]);
        $docurl = $newsmessage->get_doc_url($doc);
        $urlstring = $docurl->__toString();
        $this->assertEquals('https://www.example.com/moodle/blocks/news/message.php?m=' . $message1id, $urlstring);

        // Check get_context_url.
        $docurl = $newsmessage->get_context_url($doc);
        $urlstring = $docurl->__toString();
        $this->assertEquals('https://www.example.com/moodle/blocks/news/message.php?m=' . $message1id, $urlstring);
    }
}
