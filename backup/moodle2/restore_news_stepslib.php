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

defined('MOODLE_INTERNAL') || die();

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
        $paths[] = new restore_path_element('news_message_group', '/block/news/messages/message/messagegroups/messagegroup');
        $paths[] = new restore_path_element('news_feed', '/block/news/feeds/feed');
        $paths[] = new restore_path_element('news_subscription', '/block/news/subscriptions/subscription');

        return $paths;
    }

    public function process_block($data) {
        global $DB;

        $data = (object)$data;
        // For any reason (non multiple, dupe detected...) block not restored, return.
        if (!$this->task->get_blockid()) {
            return;
        }

        // New course startdate in case we need it.
        $newstartdate = (int)$DB->get_field('course', 'startdate',
            array('id' => $this->get_courseid()));
        $original = (int)$this->task->get_info()->original_course_startdate;
        $rollforward = $original != $newstartdate;

        // Restore thew news block instance custom data.
        if (isset($data->news['instance'])) {
            foreach ($data->news['instance'] as $instance) {
                $instance = (object)$instance;
                $oldid = $instance->id;

                // We may need to reset message publish dates
                // but it isn't necessary to the news block instance.

                $instance->blockinstanceid = $this->task->get_blockid();
                if (isset($instance->groupingsupport) && $instance->groupingsupport == 1) {
                    // Convert old grouping mode to groups mode.
                    $instance->groupingsupport = 2;
                }
                $newid = $DB->insert_record('block_news', $instance);
                $this->set_mapping('block_news', $oldid, $newid);
            }
        }

        // Restore news block feeds.
        if (isset($data->news['feeds']['feed'])) {
            foreach ($data->news['feeds']['feed'] as $feed) {
                $feed = (object)$feed;
                $oldid = $feed->id;

                $feed->blockinstanceid = $this->task->get_blockid();
                $newid = $DB->insert_record('block_news_feeds', $feed);
                $this->set_mapping('block_news_feed', $oldid, $newid, true);
            }
        }

        // Restore news block messages.
        if (isset($data->news['messages']['message'])) {
            foreach ($data->news['messages']['message'] as $message) {
                $message = (object)$message;
                $oldid = $message->id;

                // If not rollforward (full backup), restore all messages
                // If rollforward, do not include messages which aren't set
                // to repeat unless they are from feeds.
                if (!$rollforward || ($message->messagerepeat || $message->newsfeedid)) {
                    // Reset the repeated date if need be.
                    if ($rollforward && !$message->newsfeedid) {
                        $offset = $newstartdate - $original;
                        $new = block_news_get_new_time((int)$message->messagedate, $offset);
                        $message->messagedate = $new;
                        if (isset($message->messagetype) && $message->messagetype == \block_news\message::MESSAGETYPE_EVENT) {
                            $newstart = block_news_get_new_time((int)$message->eventstart, $offset);
                            $message->eventstart = $newstart;
                            $newend = block_news_get_new_time((int)$message->eventend, $offset);
                            $message->eventend = $newend;
                        }
                    }

                    $message->userid = $this->get_mappingid('user', $message->userid);
                    if (isset($message->groupingid)) {
                        $message->groupingid = $this->get_mappingid('grouping', $message->groupingid);
                    }
                    if (isset($message->groupid)) {
                        $message->groupid = $this->get_mappingid('group', $message->groupid);
                    }
                    if ($message->newsfeedid) {
                        $message->newsfeedid = $this->get_mappingid('block_news_feed', $message->newsfeedid);
                    }
                    $message->blockinstanceid = $this->task->get_blockid();
                    $newid = $DB->insert_record('block_news_messages', $message);
                    $this->set_mapping('block_news\message', $oldid, $newid);

                    $this->add_related_files('block_news', 'attachment', 'block_news\message',
                            null, $oldid);
                    $this->add_related_files('block_news', 'message', 'block_news\message', null, $oldid);

                    $messagegroups = array();
                    if (!empty($message->messagegroups)) {
                        foreach ($message->messagegroups['messagegroup'] as $messagegroup) {
                            $messagegroups[] = (object) [
                                'messageid' => $newid,
                                'groupid' => $this->get_mappingid('group', $messagegroup['groupid'])
                            ];
                        }
                    }
                    // Covert legacy group and grouping IDs to new message_groups records.
                    if (isset($message->groupid)) {
                        $messagegroups[] = (object) ['messageid' => $newid, 'groupid' => $message->groupid];
                    }
                    if (isset($message->groupingid)) {
                        $groups = groups_get_all_groups($this->task->get_courseid(), 0, $message->groupingid);
                        foreach ($groups as $group) {
                            $messagegroups[] = (object) ['messageid' => $newid, 'groupid' => $group->id];
                        }
                    }
                    foreach ($messagegroups as $messagegroup) {
                        $DB->insert_record('block_news_message_groups', $messagegroup, true, true);
                    }
                }
            }
        }

    }

    /**
     * Process news subscription data.
     *
     * @param array $data parsed element data
     */
    protected function process_news_subscription($data): void {
        global $DB;

        $data = (object)$data;

        $data->blockinstanceid = $this->task->get_blockid();
        $data->userid = $this->get_mappingid_or_null('user', $data->userid);

        $DB->insert_record('block_news_subscriptions', $data);
    }

    /**
     * Get mapping id or null.
     *
     * @param string $type field identifier
     * @param int $oldid the old id of that field
     * @return mixed
     */
    private function get_mappingid_or_null(string $type, int $oldid) {
        if ($oldid === null) {
            return null;
        }
        return $this->get_mappingid($type, $oldid);
    }
}
