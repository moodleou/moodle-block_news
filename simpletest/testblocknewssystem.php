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
 * Unit tests for blocks/news/blocks_news_system.php
 *
 * @package blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Stacey Walker <stacey@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    //  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/blocks/news/block_news_system.php'); // Include the code to test
require_once($CFG->dirroot . '/blocks/news/block_news_message.php');
require_once($CFG->dirroot . '/blocks/news/lib.php');

/** This class contains the test cases for the functions in editlib.php. */
class news_system_test extends UnitTestCaseUsingDatabase {
    public static $includecoverage = array('blocks/news/block_news_system.php',
        'blocks/news/lib.php');
    public $tables = array('lib' => array(
                                      'block_instances',
                                      'course_categories',
                                      'course',
                                      'context',
                                      'user',
                                      'cache_text',
                                      'log',
                                      'config_plugins',
                                      ),
                           'blocks/news' => array(
                                      'block_news',
                                      'block_news_messages',
                                      'block_news_feeds'
                                     )
                            );

    // temp setup/delete objects
    public $defaultnewsitem;

    // longer use objects
    public $user = null;
    public $category = null;
    public $course = null;
    public $instance = null;
    public $news = null;
    public $messages = array();

    /**
     * Backend functions covered:
     *   public function get_id()
     *   public function get_title()
     *   public function get_nummessages()
     *   public function get_summarylengths()
     *   public function get_hidetitles()
     *   public function get_hidelinks()
     *   public function get_groupingsupport()
     *   public static function get_block_settings($blockinstanceid)
     *   public function save()
     *   public function delete()
     *   function generate_block_feed()
     *   function get_feeds_to_update($max=1000)
     *   protected function set_feeds($feeds) // through save()
     *   function update_feed($fbrec) // through update_feed()
     *   function get_simplepie($feedurl) // through update_feed()
     *
     *   public function get_messages_limited($max)
     *   public function get_messages_all($viewhidden)
     *
     *  lib.php function
     *   function block_news_get_new_time($oldtime, $offset);
     *
     * Functionality not covered:
     *   public function get_message($id, $viewhidden)
     *      - where record with $id doesn't exist
     *
     * Functions not covered:
     *   public function get_message_pn($msg, $pn, $viewhidden)
     *   function get_feeds()
     **/

    public function test_block_news_system_class() {
        global $CFG;

        // testing default news block setup
        $this->defaultnewsitem = block_news_system::get_block_settings(10);
        $this->assertIsA($this->defaultnewsitem, 'block_news_system');
        $this->assertEqual($this->defaultnewsitem->get_title(), '');
        $this->assertEqual($this->defaultnewsitem->get_nummessages(), 2);
        $this->assertEqual($this->defaultnewsitem->get_summarylength(), 100);
        $this->assertEqual($this->defaultnewsitem->get_hidetitles(), 0);
        $this->assertEqual($this->defaultnewsitem->get_hidelinks(), 0);
        $this->assertEqual($this->defaultnewsitem->get_groupingsupport(), 0);
        $defaultid = $this->defaultnewsitem->get_id();

        // some vars needed for feed testing
        $feed1 = $CFG->wwwroot . '/blocks/news/simpletest/files/testrss1.xml';
        $feed2 = $CFG->wwwroot . '/blocks/news/simpletest/files/testrss2.xml';
        $feedurls = "$feed1\n$feed2";

        // test edit (update + save)
        $data = new stdClass;
        $data->id = $defaultid;
        $data->blockinstanceid = 10;
        $data->title = 'edited news title';
        $data->feedurls = $feedurls;
        $this->defaultnewsitem->save($data);
        $this->defaultnewsitem->update_from_db();
        $this->assertEqual($this->defaultnewsitem->get_title(), 'edited news title');
                                                    // ensure this value changed
        $this->assertNotEqual($this->defaultnewsitem->get_title(), '');
                                                    // ensure this value changed
        $this->assertEqual($this->defaultnewsitem->get_nummessages(), 2); // not this
        $this->assertEqual($this->defaultnewsitem->get_summarylength(), 100); // not this
        $this->assertEqual($this->defaultnewsitem->get_hidetitles(), 0); // not this
        $this->assertEqual($this->defaultnewsitem->get_hidelinks(), 0); // not this
        $this->assertEqual($this->defaultnewsitem->get_groupingsupport(), 0); // not this

        // test specific feed functions
        $feeds = block_news_system::get_feeds_to_update();
        $this->assertEqual($feeds[1]->feedurl, $feed1);
        $this->assertEqual($feeds[2]->feedurl, $feed2);

        // test deletion of defaultnewsitem
        $deletedid = $this->defaultnewsitem->get_id();
        $this->defaultnewsitem->delete();
        $deleted = $this->testdb->record_exists('block_news', array('id' => $deletedid));
        $this->assertFalse($deleted);

        // testing custom news block setup
        $this->news = block_news_system::get_block_settings($this->instance->id);
        $this->assertIsA($this->news, 'block_news_system');
        $this->assertEqual($this->news->get_title(), 'Unit Test Block News');
        $this->assertEqual($this->news->get_nummessages(), 5);
        $this->assertEqual($this->news->get_summarylength(), 100);
        $this->assertEqual($this->news->get_hidetitles(), 0);
        $this->assertEqual($this->news->get_hidelinks(), 0);
        $this->assertEqual($this->news->get_groupingsupport(), 0);

        // limited number of visibile messages
        $limitedmessages = $this->news->get_messages_limited(3);
        $this->assertEqual(count($limitedmessages), 3);
        foreach ($limitedmessages as $limited) {
            $this->assertEqual($limited->get_messagevisible(), 1);
            $this->assertEqual($limited->get_blockinstanceid(), $this->instance->id);
        }

        // all messages
        $allviewhidden = $this->news->get_messages_all(true);
        $this->assertEqual(count($allviewhidden), 6);
        foreach ($allviewhidden as $viewhidden) {
            $this->assertEqual($viewhidden->get_blockinstanceid(), $this->instance->id);
        }

        // all visible messages
        $allvisible = $this->news->get_messages_all(false);
        $this->assertEqual(count($allvisible), 3);
        foreach ($allvisible as $visible) {
            $this->assertEqual($visible->get_messagevisible(), 1);
            $this->assertEqual($visible->get_blockinstanceid(), $this->instance->id);
        }

        // single message
        $single = $this->news->get_message(2, true);
        $this->assertIsA($single, 'block_news_message');
        $this->assertEqual($single->get_id(), 2);
        $this->assertEqual($single->get_messagevisible(), 1);

        // block_news_get_new_time($oldtime, $offset)

        // hours the same, days different
        $oldstartdate = mktime(0, 0, 0, 7, 7, 2011); // July 7th 2011 12:00AM
        $newstartdate = mktime(0, 0, 0, 7, 8, 2011); // July 8th 2011 12:00AM
        $oldmessagedate = mktime(15, 0, 0, 7, 9, 2011); // July 9th 2011 3:00PM
        $expecteddate = mktime(15, 0, 0, 7, 10, 2011); // July 10th 2011 3:00PM
        $offset = $newstartdate - $oldstartdate;
        $newdate = block_news_get_new_time($oldmessagedate, $offset);
        $this->assertEqual($newdate, $expecteddate);

        // hours differ by 1 hour
        $oldstartdate = mktime(0, 0, 0, 7, 7, 2011); // July 7th 2011 12:00AM
        $newstartdate = mktime(1, 0, 0, 7, 8, 2011); // July 8th 2011 1:00AM
        $oldmessagedate = mktime(15, 0, 0, 7, 9, 2011); // July 9th 2011 3:00PM
        $expecteddate = mktime(15, 0, 0, 7, 11, 2011); // July 11th 2011 3:00PM
        $offset = $newstartdate - $oldstartdate;
        $newdate = block_news_get_new_time($oldmessagedate, $offset);
        $this->assertEqual($newdate, $expecteddate);
    }

    public function setUp() {
        parent::setUp();

        // All operations until end of test method will happen in test DB
        $this->switch_to_test_db();

        foreach ($this->tables as $dir => $tables) {
            $this->create_test_tables($tables, $dir); // Create tables
        }

        $this->load_user();
        $this->load_course_category();
        $this->load_course();
        $this->load_instance();
        $this->load_block_news();
        $this->load_messages();
        $this->load_config_plugins();
    }

    // table cleanup done automatically in parent

    public function load_instance($id = 1) {
        // standard block_instance table
        $coursecontext = context_course::instance($this->course->id);
        $instance = new stdClass;
        $instance->id = $id;
        $instance->parentcontextid = $coursecontext->id;
        $instance->blockname = 'news';
        $instance->showinsubcontexts = 0;
        $instance->pagetypepatter = 'course-view-*';
        $instance->defaultregion = 'side-post';
        $instance->defaultweight = 7;
        $this->testdb->insert_record('block_instances', $instance);
        $this->instance = $instance;
    }

    public function load_block_news() {
        // custom block_news table
        $news = new stdClass;
        $news->blockinstanceid = $this->instance->id;
        $news->title = 'Unit Test Block News';
        $news->nummessages = '5';
        $news->summarylength = 100;
        $news->hidetitles = 0;
        $news->hidelinks = 0;
        $news->groupingsupport = 0;
        $news->feedurls = '';
        $this->testdb->insert_record('block_news', $news);
    }

    public function load_messages() {
        // visible messages
        for ($i = 1; $i <= 3; $i++) {
            $message = new stdClass();
            $message->blockinstanceid = $this->instance->id;
            $message->title = 'message #' . $i;
            $message->link = '';
            $message->message = 'message content for #' . $i;
            $message->newsfeedid=0;
            $message->messageformat = 1;
            $message->messagevisible = 1;
            $message->messagerepeat = 0;
            $message->messagedate = time();
            $message->hideauthor = 0;
            $message->timemodified = time();
            $message->userid = $this->user->id;
            $message->id = $i;
            $message->u_id = $i;
            $message->u_firstname = 'Stu'.$i;
            $message->u_lastname = 'Dent';
            $this->testdb->insert_record('block_news_messages', $message);
            $this->messages[$i] = $message;
        }

        // hidden messages
        for ($j = 4; $j <= 6; $j++) {
            $message = new stdClass();
            $message->blockinstanceid = $this->instance->id;
            $message->title = 'message #' . $j;
            $message->link = '';
            $message->message = 'message content for #' . $j;
            $message->newsfeedid=0;
            $message->messageformat = 1;
            $message->messagevisible = 0;
            $message->messagerepeat = 0;
            $message->messagedate = time();
            $message->hideauthor = 0;
            $message->timemodified = time();
            $message->userid = $this->user->id;
            $message->id = $j;
            $message->u_id = $j;
            $message->u_firstname = 'Stu'.$j;
            $message->u_lastname = 'Dent';

            $this->testdb->insert_record('block_news_messages', $message);
            $this->messages[$j] = $message;
        }
    }

    public function load_course_category() {
        $cat = new stdClass();
        $cat->name = 'misc';
        $cat->depth = 1;
        $cat->path = '/1';
        $cat->id = $this->testdb->insert_record('course_categories', $cat);
        $this->category = $cat;
    }

    public function load_course() {
        $course = new stdClass();
        $course->category = 1;
        $course->fullname = 'Anonymous test course';
        $course->shortname = 'ANON';
        $course->summary = '';
        $course->modinfo = null;
        $course->id = $this->testdb->insert_record('course', $course);
        $this->course = $course;
    }

    public function load_user() {
        $user = new stdClass();
        $user->id = 1;
        $user->username = 'testuser';
        $user->firstname = 'Test';
        $user->lastname = 'User';
        $user->id = $this->testdb->insert_record('user', $user);
        $this->user = $user;
    }

    public function load_config_plugins() {
        $config_plugins = new stdClass();
        $config_plugins->plugin = 'block_news';
        $config_plugins->name = 'block_news_updatetime';
        $config_plugins->value = -10; // -10 seconds - to avoid race conditions
        $config_plugins->id = $this->testdb->insert_record('config_plugins', $config_plugins);
    }
}
