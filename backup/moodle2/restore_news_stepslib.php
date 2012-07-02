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
 * News block restore steps
 *
 * @package blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Stacey Walker <stacey@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/blocks/news/lib.php');
/**
 * Define the complete news structure for restore
 * @package blocks
 * @subpackage news
 */
class restore_news_block_structure_step extends restore_structure_step {

    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element('block', '/block', true);
        $paths[] = new restore_path_element('news', '/block/news/instance');
        $paths[] = new restore_path_element('news_message', '/block/news/messages/message');
        $paths[] = new restore_path_element('news_feed', '/block/news/feeds/feed');

        return $paths;
    }

    public function process_block($data) {
        global $DB;

        $data = (object)$data;
        // For any reason (non multiple, dupe detected...) block not restored, return
        if (!$this->task->get_blockid()) {
            return;
        }

        // new course startdate in case we need it
        $newstartdate = (int)$DB->get_field('course', 'startdate',
            array('id' => $this->get_courseid()));
        $original = (int)$this->task->get_info()->original_course_startdate;
        $rollforward = $original != $newstartdate;

        // restore thew news block instance custom data
        if (isset($data->news['instance'])) {
            foreach ($data->news['instance'] as $instance) {
                $instance = (object)$instance;
                $oldid = $instance->id;

                // we may need to reset message publish dates
                // but it isn't necessary to the news block instance

                $instance->blockinstanceid = $this->task->get_blockid();
                $newid = $DB->insert_record('block_news', $instance);
                $this->set_mapping('block_news', $oldid, $newid);
            }
        }

        // restore news block feeds
        if (isset($data->news['feeds']['feed'])) {
            foreach ($data->news['feeds']['feed'] as $feed) {
                $feed = (object)$feed;
                $oldid = $feed->id;

                $feed->blockinstanceid = $this->task->get_blockid();
                $newid = $DB->insert_record('block_news_feeds', $feed);
                $this->set_mapping('block_news_feed', $oldid, $newid, true);
            }
        }

        // restore news block messages
        if (isset($data->news['messages']['message'])) {
            foreach ($data->news['messages']['message'] as $message) {
                $message = (object)$message;
                $oldid = $message->id;

                // If not rollforward (full backup), restore all messages
                // If rollforward, do not include messages which aren't set
                // to repeat unless they are from feeds
                if (!$rollforward || ($message->messagerepeat || $message->newsfeedid)) {
                    // reset the repeated date if need be
                    if ($rollforward && !$message->newsfeedid) {
                        $offset = $newstartdate - $original;
                        $new = block_news_get_new_time((int)$message->messagedate, $offset);
                        $message->messagedate = $new;
                    }

                    $message->userid = $this->get_mappingid('user', $message->userid);
                    $message->groupingid = $this->get_mappingid('grouping', $message->groupingid);
                    if ($message->newsfeedid) {
                        $message->newsfeedid = $this->get_mappingid('block_news_feed', $message->newsfeedid);
                    }
                    $message->blockinstanceid = $this->task->get_blockid();
                    $newid = $DB->insert_record('block_news_messages', $message);

                    $this->add_related_files('block_news', 'attachment', 'news_message',
                            null, $oldid);
                    $this->add_related_files('block_news', 'message', 'news_message', null, $oldid);
                }
            }
        }

    }
}
