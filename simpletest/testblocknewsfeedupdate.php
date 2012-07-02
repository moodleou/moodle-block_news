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
 * @author Jon Sharp <jonathans@catalyst-eu.net>
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
class news_feedupdate_test extends UnitTestCaseUsingDatabase {
    public static $includecoverage = array('blocks/news/block_news_system.php',
        'blocks/news/lib.php');
    public $tables = array('lib' => array(
                                      'block_instances',
                                      'course',
                                      'context',
                                      'user',
                                      'config_plugins',
                                      ),
                           'blocks/news' => array(
                                      'block_news_feeds'
                                     )
                            );

    // temp setup/delete objects
    public $defaultnewsitem;

    // longer use objects
    public $user = null;
    public $course = null;
    public $instance = null;


    /**
     * Backend functions covered:
     *   function get_feeds_to_update()
     *
     * Functionality not covered:
     *
     * Functions not covered:
     */
    public function test_block_news_feedupdate_class() {
        global $CFG;

        // Test just get_feeds_to_update()
        // As we're using test messages generated in a specific order,
        // their id values should be the same every time so we can use them
        // to check results
        $actfeeds = block_news_system::get_feeds_to_update();
        $actids='';
        foreach ($actfeeds as $feed) {
            $actids .= $feed->id.',';
        }

        $this->assertEqual($actids, '6,7,2,4,1,3,');
    }

    public function setUp() {
        parent::setUp();

        // All operations until end of test method will happen in test DB
        $this->switch_to_test_db();

        foreach ($this->tables as $dir => $tables) {
            $this->create_test_tables($tables, $dir); // Create tables
        }

        $this->load_user();
        $this->load_course();
        $this->load_instance();
        // load_config_plugins() is called from load_feeds
        $this->load_feeds();
    }

    // table cleanup done automatically in parent

    public function load_instance($id = 1) {
        // standard block_instance table
        $coursecontext = get_context_instance(CONTEXT_COURSE, $this->course->id);
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

    public function load_config_plugins($val) {
        $config_plugins = new stdClass();
        $config_plugins->plugin = 'block_news';
        $config_plugins->name = 'block_news_updatetime';
        $config_plugins->value = $val;
        $config_plugins->id = $this->testdb->insert_record('config_plugins', $config_plugins);
    }

    /**
     * Create a set of messages with specific feedurl and last updated (feedupdated)
     * values. For ease of inspecton of dates (which is main way to identify the records)
     * a base datestamp rounded to 000 is used, then the test values added to that for individual
     * records.
     * As the rtested function workd on real time, to make this test work at any time
     * the updatetime is adjusted to add an offset from now to the base time
     */
    public function load_feeds() {
        $now=time();
        $now = round($now, -3); //1310729000
        $offset = time() - $now;
        self::load_config_plugins($offset-10); // 10 is updatetime relative to our test values

        $feeds = new stdClass();
        $feeds->blockinstanceid= $this->instance->id;
        $feeds->feedurl='http://a/';
        $feeds->feedupdated=$now+10;
        $feeds->id = $this->testdb->insert_record('block_news_feeds', $feeds);

        $feeds = new stdClass();
        $feeds->blockinstanceid= $this->instance->id;
        $feeds->feedurl='http://b/';
        $feeds->feedupdated=$now+5;
        $feeds->id = $this->testdb->insert_record('block_news_feeds', $feeds);

        $feeds = new stdClass();
        $feeds->blockinstanceid= $this->instance->id;
        $feeds->feedurl='http://a/';
        $feeds->feedupdated=$now+25;
        $feeds->id = $this->testdb->insert_record('block_news_feeds', $feeds);

        $feeds = new stdClass();
        $feeds->blockinstanceid= $this->instance->id;
        $feeds->feedurl='http://b/';
        $feeds->feedupdated=$now+904;
        $feeds->id = $this->testdb->insert_record('block_news_feeds', $feeds);

        $feeds = new stdClass();
        $feeds->blockinstanceid= $this->instance->id;
        $feeds->feedurl='http://e/';
        $feeds->feedupdated=$now+21;
        $feeds->id = $this->testdb->insert_record('block_news_feeds', $feeds);

        $feeds = new stdClass();
        $feeds->blockinstanceid= $this->instance->id;
        $feeds->feedurl='http://t/';
        $feeds->feedupdated=$now+3;
        $feeds->id = $this->testdb->insert_record('block_news_feeds', $feeds);

        $feeds = new stdClass();
        $feeds->blockinstanceid= $this->instance->id;
        $feeds->feedurl='http://t/';
        $feeds->feedupdated=$now+30;
        $feeds->id = $this->testdb->insert_record('block_news_feeds', $feeds);
    }


}
