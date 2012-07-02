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
 * Unit tests for blocks/news/blocks_news_message.php
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

require_once($CFG->dirroot . '/blocks/news/block_news_message.php'); // Include the code to test

/** This class contains the test cases for the functions in block_news_messages.php. */
class news_message_test extends UnitTestCaseUsingDatabase {
    public static $includecoverage = array('blocks/news/block_news_message.php');
    // setup test tables - key is parent folder of install.xml - holding the real table defs
    // sub-array values are tables to create from these defs
    public $tables = array('lib' => array(
                                      'block_instances',
                                      'course_categories',
                                      'course',
                                      'context',
                                      'user',
                                      'cache_text',
                                      'log',
                                      'files'
                                      ),
                           'blocks/news' => array(
                                      'block_news_messages',
                                      )
                           );

    // longer use objects
    public $user = null;
    public $category = null;
    public $course = null;
    public $instance = null;
    public $data = null;
    public $message = null;

    /**
     * Backend functions covered:
     * public static function create($data)
     * public function delete()
     * public function get_id()
     * public function get_blockinstanceid()
     * public function get_newsfeedid()
     * public function get_title()
     * public function get_link()
     * public function get_message()
     * public function get_messagerepeat()
     * public function get_messagevisible()
     * public function get_hideauthor()
     * public function get_userid()
     * public function get_user()
     * public function get_groupingid()
     * public function set_visible($visible)
     * public function is_visible_to_students()
     *
     * Functions not covered:
     * public function edit($data) (file/attachment issues)
     **/

    public function test_block_news_system_class() {
        // testing the new message object
        $id = block_news_message::create($this->data);
        $this->assertTrue($id);

        $this->message = new block_news_message($this->data);
        $this->assertIsA($this->message, 'block_news_message');
        $this->assertEqual($this->message->get_blockinstanceid(), 1);
        $this->assertEqual($this->message->get_newsfeedid(), null);
        $this->assertEqual($this->message->get_title(), 'Unit test message');
        $this->assertEqual($this->message->get_link(), null);
        $this->assertEqual($this->message->get_message(), 'Unit test message content');
        $this->assertEqual($this->message->get_messagerepeat(), 0);
        $this->assertEqual($this->message->get_messagevisible(), 1);
        $this->assertEqual($this->message->get_hideauthor(), 0);
        $this->assertEqual($this->message->get_userid(), 1);
        $this->assertEqual($this->message->get_groupingid(), 0);

        // get user
        $getuser = $this->message->get_user();
        $this->assertEqual($getuser->id, $this->user->id);

        // set visible
        $this->message->set_messagevisible(1);
        $visible = $this->message->is_visible_to_students();
        $this->assertTrue($visible);

        // set invisible
        $this->message->set_messagevisible(0);
        $visible = $this->message->is_visible_to_students();
        $this->assertFalse($visible);

        // delete
        $deletedid = $this->message->get_id();
        $this->message->delete();
        $deleted = $this->testdb->record_exists('block_news_messages', array('id' => $deletedid));
        $this->assertFalse($deleted);
    }


    public function setUp() {
        parent::setUp();

        // All operations until end of test method will happen in test DB
        $this->switch_to_test_db();

        foreach ($this->tables as $dir => $tables) {
            $this->create_test_tables($tables, $dir); // Create tables
        }

        // create content
        $this->load_file();
        $this->load_user();
        $this->load_course_category();
        $this->load_course();
        $this->load_instance();
        $this->load_message();
    }

    // table cleanup done automatically in parent

    public function load_instance() {
        // standard block_instance table
        $coursecontext = get_context_instance(CONTEXT_COURSE, $this->course->id);
        $instance = new stdClass;
        $instance->id = 1;
        $instance->parentcontextid = $coursecontext->id;
        $instance->blockname = 'news';
        $instance->showinsubcontexts = 0;
        $instance->pagetypepatter = 'course-view-*';
        $instance->defaultregion = 'side-post';
        $instance->defaultweight = 7;
        $this->testdb->insert_record('block_instances', $instance);
        $this->instance = $instance;

        // load context
        $context = new stdclass;
        $context->contextlevel = 80;
        $context->instanceid = $this->instance->id;
        $context->path = '/1/2/3';
        $context->depth = 4;
        $this->testdb->insert_record('context', $context);
    }


    public function load_file() {
        // load dummy files
        $file = new stdClass;
        $file->contenthash = '1c7fad7e50f98dbaab3d168e51753ee067e719ad';
        $file->pathnamehash = '87a6dd01cdee44c988ec427e2e01ad0e8435914a';
        $file->contextid = 13;
        $file->component = 'user';
        $file->filearea = 'draft';
        $file->itemid = 1;
        $file->filepath = '/';
        $file->filename = 'testfile.png';
        $file->userid = 1;
        $file->filesize = 82304;
        $file->mimetype = 'image/png';
        $file->status = 0;
        $file->source = null;
        $file->author = 'Test User';
        $file->license = 'allrightsreserved';
        $file->timecreated = time();
        $file->timemodified = time();
        $file->sortorder = 0;
        $this->testdb->insert_record('files', $file);
    }


    public function load_message() {
        $data = new stdClass;
        $data->blockinstanceid = $this->instance->id;
        $data->title = 'Unit test message';
        $data->message['text'] = 'Unit test message content';
        $data->message['format'] = 1;
        $data->message['itemid'] = 1;
        $data->newsfeedid=0;
        $data->messagevisible = 1;
        $data->messagedate = time() - 3600;
        $data->hideauthor = 0;
        $data->messagerepeat = 0;
        $data->timemodified = time();
        $data->attachments = null;
        $data->userid = $this->user->id;
        $data->groupingid= 0;
        $data->u_id=$this->user->id;
        $data->u_firstname='Stu';
        $data->u_lastname='Dent';
        $this->data = $data;
    }


    public function load_course_category() {
        $cat = new stdClass;
        $cat->id = 1;
        $cat->name = 'misc';
        $cat->depth = 1;
        $cat->path = '/1';
        $this->testdb->insert_record('course_categories', $cat);
        $this->category = $cat;
    }


    public function load_course() {
        $course = new stdClass;
        $course->id = 1;
        $course->category = 1;
        $course->fullname = 'Anonymous test course';
        $course->shortname = 'ANON';
        $course->summary = '';
        $course->modinfo = null;
        $this->testdb->insert_record('course', $course);
        $this->course = $course;
    }


    public function load_user() {
        $user = new stdClass;
        $user->id = 1;
        $user->username = 'testuser';
        $user->firstname = 'Test';
        $user->lastname = 'User';
        $this->testdb->insert_record('user', $user);
        $this->user = $user;
    }
}
