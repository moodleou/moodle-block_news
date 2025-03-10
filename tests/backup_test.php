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
 * Test backup of news block.
 *
 * @package block_news
 * @copyright 2025 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_test extends \advanced_testcase {

    /**
     * Tests that backup and restore of the block includes all block settings.
     */
    public function test_backup_and_restore_settings(): void {
        $this->resetAfterTest();

        // Create a course.
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        // Create news block.
        /** @var \block_news_generator $blockgenerator */
        $blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_news');
        $block = $blockgenerator->create_instance([], ['courseid' => $course->id]);

        // Set all configuration to non-default values.
        $blockgenerator->create_block_news_record($block, (object)[
            'title' => 'T',
            'nummessages' => 4,
            'summarylength' => 17,
            'hidetitles' => 1,
            'hidelinks' => 1,
            'hideimages' => 1,
            'groupingsupport' => system::RESTRICTBYGROUP,
            'displaytype' => system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS,
        ]);

        // Do backup and restore.
        $newcourseid = $this->backup_and_restore_course($course, get_admin()->id);

        // Get news block id on new course.
        $newblockid = block_news_get_top_news_block($newcourseid);

        // Get block settings for new block and confirm all values.
        $settings = system::get_block_settings($newblockid);
        $this->assertEquals('T', $settings->get_title());
        $this->assertEquals(4, $settings->get_nummessages());
        $this->assertEquals(17, $settings->get_summarylength());
        $this->assertEquals(true, $settings->get_hidetitles());
        $this->assertEquals(true, $settings->get_hidelinks());
        $this->assertEquals(true, $settings->get_hideimages());
        $this->assertEquals(system::RESTRICTBYGROUP, $settings->get_groupingsupport());
        $this->assertEquals(system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS, $settings->get_displaytype());
    }

    /**
     * Carries out a backup and restore of the course.
     *
     * @param \stdClass $course Course object
     * @param int $userid User id
     * @return int New course id
     */
    protected function backup_and_restore_course(\stdClass $course, int $userid): int {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        // Do backup with default settings. MODE_IMPORT means it will just
        // create the directory and not zip it.
        $bc = new \backup_controller(\backup::TYPE_1COURSE, $course->id,
            \backup::FORMAT_MOODLE, \backup::INTERACTIVE_NO, \backup::MODE_IMPORT,
            $userid);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Do restore to new course with default settings.
        $newcourseid = \restore_dbops::create_new_course(
            $course->fullname, $course->shortname . '_2', $course->category);
        $rc = new \restore_controller($backupid, $newcourseid,
            \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $userid,
            \backup::TARGET_NEW_COURSE);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }

}
