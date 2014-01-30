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
 * Unit tests for blocks/news/renderer.php
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

require_once($CFG->dirroot . '/blocks/news/renderer.php'); // Include the code to test
require_once($CFG->dirroot . '/blocks/news/block_news_message.php');
require_once($CFG->dirroot . '/blocks/news/block_news_system.php');

/** This class contains the test cases for the functions in editlib.php. */
class news_renderer_test extends UnitTestCaseUsingDatabase {
    public static $includecoverage = array('blocks/news/renderer.php');
    public $tables = array('lib' => array(
                                      'block_instances',
                                      'course_categories',
                                      'course',
                                      'context',
                                      'user',
                                      'cache_text',
                                      'log',
                                      'files',
                                      'filter_active',
                                      'filter_config',
                                      'capabilities',
                                      'role_assignments',
                                      'role_capabilities'
                                      ),
                           'blocks/news' => array(
                                     'block_news',
                                     'block_news_messages',
                                     'block_news_feeds',
                                      )
                            );

    // longer use objects
    public $user = null;
    public $category = null;
    public $course = null;
    public $instance = null;
    public $news = null;
    public $data = null;
    public $message = null;

    /**
     * class block_news_message_full implements renderable
     *
     * Backend functions covered:
     * public function __construct($bnm, $previd, $nextid, $bns, $mode)
     *
     * class block_news_message_short implements renderable
     *
     * Backend functions covered:
     * public function __construct($bnm, $bns, $sumlen, $c)
     *
     * class block_news_renderer extends plugin_renderer_base
     *
     * Backend functions covered:
     * protected function render_block_news_message_full(block_news_message_full $nmsg)
     * protected function render_block_news_message_short(block_news_message_short $nmsg)
     * function block_news_soft_truncate($str, $maxlength)
     *
     * NOTES:
     *       - only testing with a single message object
     *       - all we are able to test is that the result of the render_*
     *       functions return an object of type 'String'
     *
     **/

    public function test_renderer() {
        global $PAGE;

        // create the message and news objects first
        $id = block_news_message::create($this->data);
        $this->message = new block_news_message($this->data);
        $this->assertIsA($this->message, 'block_news_message');
        $this->news = block_news_system::get_block_settings($this->instance->id);
        $this->assertIsA($this->news, 'block_news_system');

        // create the two renderer widgets
        // 1. message_full
        // 2. message_short

        $messagefull = new block_news_message_full($this->message, '-1', '-1', $this->news, 'one');
        $this->assertIsA($messagefull, 'block_news_message_full');

        $messageshort = new block_news_message_short($this->message, $this->news,
                                                $this->news->get_summarylength(), 1);
        $this->assertIsA($messageshort, 'block_news_message_short');

        // test actual rendering
        $newsrenderer = $PAGE->get_renderer('block_news');
        $this->assertIsA($newsrenderer, 'block_news_renderer');

        $fulloutput = $newsrenderer->render($messagefull);
        $isstring = is_string($fulloutput);
        $this->assertTrue($isstring);

        $shortoutput = $newsrenderer->render($messageshort);
        $isstring = is_string($shortoutput);
        $this->assertTrue($isstring);
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
        $this->load_capabilities();
    }

    // table cleanup done automatically in parent

    public function load_instance() {
        // standard block_instance table
        $coursecontext = context_course::instance($this->course->id);
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

        // custom block_news table
        $news = new stdClass;
        $news->blockinstanceid = $this->instance->id;
        $news->title = 'Unit Test Block News';
        $news->nummessages = '5';
        $news->summarylength = 100;
        $news->hidetitles = 0;
        $news->hidelinks = 0;
        $this->testdb->insert_record('block_news', $news);
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

    public function load_capabilities() {
        $cap = new stdClass();
        $cap->name = 'block/news:hide';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'block/news:add';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'block/news:delete';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;
    }
}
