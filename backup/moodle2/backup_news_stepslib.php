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
 * News block backup steps
 *
 * @package blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Stacey Walker <stacey@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that wll be used by the backup_news_block_task
 */

/**
 * Define the complete forum structure for backup, with file and id annotations
 * @package blocks
 * @subpackage news
 */
class backup_news_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        global $DB;

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('users');

        // Get the block.
        $block = $DB->get_record('block_instances', ['id' => $this->task->get_blockid()]);

        // Define each element.
        $news = new backup_nested_element('news', ['id'], null);

        $instance = new backup_nested_element('instance', ['id'], [
            'blockinstanceid', 'title', 'nummessages', 'summarylength',
            'hidetitles', 'hidelinks', 'hideimages', 'groupingsupport', 'displaytype', 'cstartdate']);

        $messages = new backup_nested_element('messages');

        $message = new backup_nested_element('message', ['id'], [
            'blockinstanceid', 'newsfeedid', 'title', 'link', 'message',
            'messageformat', 'messagedate', 'messagevisible', 'messagerepeat',
            'messagetype', 'hideauthor', 'userid', 'timemodified', 'eventstart',
            'eventend', 'eventlocation', 'imagedesc', 'imagedescnotnecessary', 'currenthash']);

        $messagegroups = new backup_nested_element('messagegroups');

        $messagegroup = new backup_nested_element('messagegroup', ['id'], [
            'messageid',
            'groupid',
        ]);

        $feeds = new backup_nested_element('feeds');

        $feed = new backup_nested_element('feed', ['id'], [
            'blockinstanceid', 'feedurl', 'currenthash', 'feedupdated', 'feederror']);

        $subscriptions = new backup_nested_element('subscriptions');

        $subscription = new backup_nested_element('subscription', ['id'], ['userid', 'subscribed',
            'blockinstanceid']);

        // Define tree.
        $news->add_child($instance);

        $news->add_child($messages);
        $messages->add_child($message);

        $message->add_child($messagegroups);
        $messagegroups->add_child($messagegroup);

        $news->add_child($feeds);
        $feeds->add_child($feed);

        $news->add_child($subscriptions);
        $subscriptions->add_child($subscription);

        // Define sources.
        $news->set_source_array([(object)['id' => $this->task->get_blockid()]]);

        $instance->set_source_sql("
            SELECT *
                FROM {block_news}
            WHERE blockinstanceid = ?", [backup::VAR_PARENTID]);

        $message->set_source_sql("
            SELECT *
                FROM {block_news_messages}
            WHERE blockinstanceid = ?", [backup::VAR_PARENTID]);

        $messagegroup->set_source_sql("
            SELECT *
              FROM {block_news_message_groups}
             WHERE messageid = ?", [backup::VAR_PARENTID]);

        $feed->set_source_sql("
            SELECT *
                FROM {block_news_feeds}
            WHERE blockinstanceid = ?", [backup::VAR_PARENTID]);

        if ($userinfo) {
            $subscription->set_source_sql("
            SELECT *
              FROM {block_news_subscriptions}
             WHERE blockinstanceid = ?", [backup::VAR_PARENTID]);
        }

        // Annotations.
        $message->annotate_ids('user', 'userid');
        $messagegroup->annotate_ids('group', 'groupid');

        // Define file annotations.
        $message->annotate_files('block_news', 'attachment', 'id');
        $message->annotate_files('block_news', 'message', 'id');

        // Subscriptions.
        $subscription->annotate_ids('user', 'userid');

        // Return the root element (rss_client), wrapped into standard block structure.
        return $this->prepare_block_structure($news);
    }
}
