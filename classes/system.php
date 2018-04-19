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
 * News block System/Config class
 *
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/blocks/news/atomlib.php');
require_once($CFG->dirroot . '/blocks/news/lib.php');

/**
 * main system class
 *
 * @package blocks
 * @subpackage news
 *
 */
class system {

    /** @var int Maximum std feed messages to show (see generate_block_feed(). */
    const MAXSTDMSGS = 20;

    /** @var int Define grouping support by grouping. */
    const RESTRICTBYGROUPING = 1;

    /** @var int Define grouping support by group. */
    const RESTRICTBYGROUP = 2;

    /** @var int Default display type (news messages only) */
    const DISPLAY_DEFAULT = 0;
    /** @var int Display news and events as separate messages */
    const DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS = 1;

    /** @var int The number of news messages on a page of the View All screen */
    const ALL_NEWS_PAGE_SIZE = 9;
    /** @var int The number of event messages on a page of the View All screen */
    const ALL_EVENTS_PAGE_SIZE = 3;
    /** @var int Max length for feed URLs */
    const MAXURLLEN = 255;


    public static function get_message_sql_start() {
        return "SELECT DISTINCT m.*, u.id AS u_id, " .
                get_all_user_name_fields(true, 'u', null, 'u_') .
                " FROM {block_news_messages} m
             LEFT JOIN {user} u ON m.userid = u.id
             LEFT JOIN {block_news_message_groups} g ON m.id = g.messageid ";
    }

    protected $id;
    protected $blockinstanceid;
    protected $title;
    protected $nummessages;
    protected $summarylength;
    protected $hidetitles;
    protected $hidelinks;
    protected $groupingsupport;
    protected $usergroupingids;
    protected $usergroupids;
    protected $username;
    protected $displaytype = self::DISPLAY_DEFAULT;

    /**
     * Construct object
     *
     * @param \stdClass $bn Block details (typically called internally @see get_block_settings )
     */
    public function __construct($bn) {
        $this->id = $bn->id;
        $this->blockinstanceid = $bn->blockinstanceid;
        $this->title = $bn->title;
        $this->nummessages = $bn->nummessages;
        $this->summarylength = $bn->summarylength;
        $this->hidetitles = $bn->hidetitles;
        $this->hidelinks = $bn->hidelinks;
        $this->groupingsupport = $bn->groupingsupport;
        $this->displaytype = $bn->displaytype;
    }

    /**
     * Creates an instance of itself
     *
     * Sets properties from DB if record exists
     * otherwise sets properties with default values, and inserts a new record in DB
     *
     * @param integer $blockinstanceid
     * @return system
     */
    public static function get_block_settings($blockinstanceid) {
        global $DB;

        $bn = $DB->get_record('block_news', array('blockinstanceid' => $blockinstanceid));

        if (empty($bn)) {
            $bn = new \stdClass;
            $bn->blockinstanceid = $blockinstanceid;
            $bn->title = '';
            $bn->nummessages = 2;
            $bn->summarylength = 100;
            $bn->hidetitles = 0;
            $bn->hidelinks = 0;
            $bn->groupingsupport = 0;
            $bn->username = '';
            $bn->displaytype = self::DISPLAY_DEFAULT;
            $rid = $DB->insert_record('block_news', $bn, true);
            $bn->id = $rid;
        }

        return new system($bn);

    }

    /**
     * Get the database ID of the block_news record.
     *
     * @return integer id
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get the block instance ID.
     *
     * @return integer id
     */
    public function get_blockinstanceid() {
        return $this->blockinstanceid;
    }

    /**
     * Get the configured block title.
     *
     * @return string title
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Get the number of messages to show in the block.
     *
     * @return integer Number of messages to show in block
     */
    public function get_nummessages() {
        return $this->nummessages;
    }

    /**
     * Get the number of characters to limit the message summaries to.
     *
     * @return integer Length of summary in characters (0 if turned off)
     */
    public function get_summarylength() {
        return $this->summarylength;
    }

    /**
     * Return whether to hide message titles in the block.
     *
     * @return boolean Whether to hide all message titles in block
     */
    public function get_hidetitles() {
        return $this->hidetitles;
    }

    /**
     * Gets display type for block (DISPLAY_xx constant).
     *
     * @return int Whether to display default or Separate into events and news items in block
     */
    public function get_displaytype() {
        return $this->displaytype;
    }

    /**
     * Return whether to hide message links in the block.
     *
     * @return boolean Whether to hide message links (from feed messages) in block
     */
    public function get_hidelinks() {
        return $this->hidelinks;
    }

    /**
     * Return whether grouping support is enabled.
     *
     * @return boolean Whether grouping support is enabled in block
     */
    public function get_groupingsupport() {
        return $this->groupingsupport;
    }

    /**
     * Return the username to use when applying group restrictions.
     *
     * @return string Username to get groups when enable group restriction
     */
    public function get_username() {
        return $this->username;
    }

    /**
     * Get a list of groupingids to restrict messages by, if previously set by set_user_groupingids.
     *
     * Retained for compatibility with legacy feed URLs.
     *
     * @return array - array of the groupingids (empty if none).
     */
    public function get_groupingids() {
        return empty($this->usergroupingids) ? [] : $this->usergroupingids;
    }

    /**
     * Get a list of the groups that apply in the current context for use when working
     * out which messages to display.  This will be because the user is a member of particular
     * groups and groups support is enabled or some groups have been specified in a
     * querystring and specified using set_user_groupids().
     *
     * @param int $userid The user id to get the groups.
     * @param int $courseid The course id to get the groups.     *
     * @return array - array of the groupids (empty if none).
     */
    public function get_groupids($userid = 0, $courseid = 0) {
        global $SESSION, $COURSE, $USER;
        if (!empty($this->usergroupids)) {
            return $this->usergroupids;
        }

        if (!empty($SESSION->block_news_user_groups[$COURSE->id])) {
            return $SESSION->block_news_user_groups[$COURSE->id];
        }

        $userid = $userid ? $userid : $USER->id;
        $courseid = $courseid ? $courseid : $COURSE->id;

        $context = \context_course::instance($COURSE->id);
        if (has_capability('moodle/site:accessallgroups', $context)) {
            // If the user has the allgroups capability they can see everything.
            $g = groups_get_all_groups($COURSE->id);
        } else {
            $g = groups_get_all_groups($courseid, $userid);
        }

        $groups = array();
        foreach ($g as $group) {
            $groups[] = $group->id;
        }
        $SESSION->block_news_user_groups[$COURSE->id] = $groups;
        return $groups;
    }

    /**
     * Sets the value of the user groupings ids class variable.
     *
     * @param array $groupingids
     */
    public function set_user_groupingids($groupingids) {
        $this->usergroupingids = $groupingids;
    }

    /**
     * Sets the value of the user groups ids class variable.
     *
     * @param array $groupids
     */
    public function set_user_groupids($groupids) {
        $this->usergroupids = $groupids;
    }

    /**
     * Sets the value of the grouping support class variable.
     *
     * @param int $groupingsupport
     */
    public function set_groupingsupport($groupingsupport) {
        $this->groupingsupport = $groupingsupport;
    }

    /**
     * Sets the value of the username class variable.
     *
     * @param string $username
     */
    public function set_username($username) {
        $this->username = $username;
    }

    /**
     * Return SQL WHERE and clause and params to append to queries when group support
     * is enabled.
     *
     * @param int[] $groupingids Optional array of groupingids, to get the groups for groupings instead of from $this->groupids.
     * @return array - 'sql' (empty string if nothing to return)
     * and 'params' (empty array if nothing).
     */
    public function get_group_sql($groupingids = []) {
        global $COURSE, $DB;

        $output = array();
        $output['sql'] = '';
        $output['params'] = array();

        // Return if config_groupingsupport is not group.
        if ($this->get_groupingsupport() != self::RESTRICTBYGROUP) {
            return $output;
        }

        $context = \context_course::instance($COURSE->id);
        if (has_capability('moodle/site:accessallgroups', $context)) {
            return $output;
        }

        if (empty($groupingids)) {
            $groups = $this->get_groupids();
            if (!empty($groups)) {
                list($sql, $params) = $DB->get_in_or_equal($groups);
            }
        } else {
            list($insql, $params) = $DB->get_in_or_equal($groupingids);
            $sql = "IN (
                SELECT grp.id
                  FROM {groups} grp
                  JOIN {groupings_groups} gg ON gg.groupid = grp.id
                 WHERE gg.groupingid " . $insql . ")";
        }

        if (isset($sql) && isset($params)) {
            $output['sql'] = ' AND (g.id IS NULL OR g.groupid ' . $sql . ') ';
            $output['params'] = $params;
        }

        return $output;
    }

    /**
     * Return SQL WHERE clause and params to restrict results by a given message type when separate display is enabled.
     *
     * @param int $type Message type, one of the block_news\message::MESSAGETYPE_ constants.
     * @param bool $pastevents If showing events, show past events instead of upcoming ones?
     * @return array
     */
    public function get_type_sql($type, $pastevents = false) {
        $sql = '';
        $params = [];
        if ($this->displaytype == self::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS && !is_null($type)) {
            $sql = ' AND messagetype = ? ';
            $params = [$type];
            if ($type == message::MESSAGETYPE_EVENT) {
                if ($pastevents) {
                    // Show events that have already happened.
                    $sql .= 'AND COALESCE(eventend, eventstart) < ? ';
                } else {
                    // Automatically exclude events that happened before midnight this morning (according to server time).
                    // But include events that have an end date that have not yet passed.
                    $sql .= 'AND COALESCE(eventend, eventstart) >= ? ';
                }
                $date = new \DateTime(null, \core_date::get_server_timezone_object());
                $date->setTime(0, 0);
                $params[] = $date->getTimestamp();
            }
        }
        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Return SQL WHERE clause and params to restrict results visibility if the user cannot view hidden.
     *
     * @param $viewhidden
     * @return array
     */
    public function get_hidden_sql($viewhidden) {
        $hidden = ['sql' => '', 'params' => []];
        if (!$viewhidden) { // Only show visible and past/present messages.
            $hidden['sql'] = ' AND messagevisible = 1 AND messagedate <= ? ';
            $hidden['params'] = [time()];
        }
        return $hidden;
    }

    /**
     *  Helper function to update the object params from database record
     *  Used by simpletests
     *
     */
    public function update_from_db() {
        global $DB;
        if ($data = $DB->get_record('block_news', array('id' => $this->id))) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     *  Save object - convenience method to save object returned from form processing
     *
     * @param \stdClass $data Form data
     */
    public function save($data) {
        global $DB;

        $data->id = $this->id;

        if (isset($data->displaytype) && ($data->displaytype == self::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS)) {
            // Force default (new News block) to 'News and events'.
            if ($data->title == get_string('defaultblocktitle', 'block_news')) {
                $data->title = get_string('newsandeventsblocktitle', 'block_news');
            }
        }

        $DB->update_record('block_news', $data);

        if (isset($data->feedurls)) {
            $this->save_feed_urls($data->feedurls);
        }
    }

    /**
     * Save multiline string of feed URLs to separate feed URL records.
     *
     * @param string $feedurls
     */
    public function save_feed_urls($feedurls) {
        // Now do feeds.
        // Convert from textarea to array.
        $feeds = preg_split('/\R/', $feedurls); // Splits on any of \n \r\n \r.

        $feeds = array_values(array_unique($feeds)); // Remove any duplicate lines, reindex.

        // Check each feed url - throw away any empty ones (length check done in edit_form.php).
        $numberoffeeds = count($feeds);
        for ($i = 0; $i < $numberoffeeds; $i++) {
            if (isset($feeds[$i])) {
                $feeds[$i] = trim($feeds[$i]);
                if (strlen($feeds[$i]) == 0) {
                    unset($feeds[$i]);
                }
            }
        }

        $this->set_feeds(array_values($feeds));
    }

    /**
     * Delete this object's record from DB & related message records
     */
    public function delete() {
        global $DB;
        $DB->delete_records('block_news', array('id' => $this->id));
        $DB->delete_records('block_news_messages',
                array('blockinstanceid' => $this->blockinstanceid));
        $DB->delete_records('block_news_feeds',
                array('blockinstanceid' => $this->blockinstanceid));
    }

    /**
     * Get block_news\message objects limited by visibility and date and sorted by messagedate
     *
     * Read DB and pass each row to constructor
     *
     * @param integer $max Maximum number of messages to return
     * @param integer $type Restrict returned messages by messagetype.
     * @return array block_news\message
     */
    public function get_messages_limited($max, $type = null) {
        global $DB;
        $bnms = array();

        $groups = $this->get_group_sql();
        $restricttype = $this->get_type_sql($type);
        $sql = self::get_message_sql_start() .
                'WHERE blockinstanceid=?
                 AND messagevisible=1
                 AND messagedate <= ?'
                . $groups['sql']
                . $restricttype['sql'] .
                'ORDER BY eventstart ASC, messagedate DESC
                 LIMIT ' . $max;

        $params = array($this->blockinstanceid, time());
        $params = array_merge($params, $groups['params'], $restricttype['params']);
        $mrecs = $DB->get_records_sql($sql, $params);
        $groupids = $this->get_groupids_by_message(array_keys($mrecs));
        foreach ($mrecs as $mrec) {
            $bnms[] = new message($mrec, $groupids[$mrec->id]);
        }

        return $bnms;
    }

    /**
     * Get block_news\message objects controlled by visibility and sorted by message date
     * Read DB and pass each row to constructor
     *
     * @param boolean $viewhidden
     * @param int|null $pagesize The size of page to use, if paging
     * @param int|null $pagenumber The page to get results for
     * @param int|null $type Restrict returned messages by messagetype.
     * @param string $order ORDER BY statement for sorting results (default: eventstart ASC, messagedate DESC)
     * @param bool $pastevents If showing events, show past events instead of upcoming ones?
     * @return message[] Messages
     */
    public function get_messages_all($viewhidden, $pagesize = null, $pagenumber = null, $type = null,
            $order = 'eventstart ASC, messagedate DESC', $pastevents = false) {
        global $DB;
        $bnms = array();
        $orderby = 'ORDER BY ' . $order;
        $limitfrom = 0;
        $limitnum = 0;
        if (!is_null($pagenumber) && !is_null($pagesize)) {
            $limitfrom = $pagesize * $pagenumber;
            $limitnum = $pagesize;
        }

        $groups = $this->get_group_sql($this->get_groupingids());
        $restricttype = $this->get_type_sql($type, $pastevents);
        $hidden = $this->get_hidden_sql($viewhidden);

        $sql = self::get_message_sql_start() .
                'WHERE blockinstanceid = ?'
                . $hidden['sql']
                . $groups['sql']
                . $restricttype['sql']
                . $orderby;
        $params = array($this->blockinstanceid);
        $params = array_merge($params, $hidden['params'], $groups['params'], $restricttype['params']);
        $mrecs = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $groupids = $this->get_groupids_by_message(array_keys($mrecs));
        foreach ($mrecs as $mrec) {
            $bnms[] = new message($mrec, $groupids[$mrec->id]);
        }

        return $bnms;
    }

    /**
     * Get the total number of events of the given type.
     *
     * @param bool $viewhidden Include hidden or future messages?
     * @param int $type Message type to count - block_news\message::MESSAGETYPE_* constant
     * @param bool $pastevents If counting events, count past events instead of upcoming ones?
     * @return int
     */
    public function get_message_count($viewhidden, $type, $pastevents = false) {
        global $DB;
        $groups = $this->get_group_sql();
        $restricttype = $this->get_type_sql($type, $pastevents);
        $hidden = $this->get_hidden_sql($viewhidden);

        $sql = "SELECT COUNT(DISTINCT m.id)
                  FROM {block_news_messages} m
             LEFT JOIN {block_news_message_groups} g ON m.id = g.messageid
                 WHERE blockinstanceid = ? " .
                $hidden['sql'] .
                $groups['sql'] .
                $restricttype['sql'];
        $params = array($this->blockinstanceid);
        $params = array_merge($params, $hidden['params'], $groups['params'], $restricttype['params']);
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Get block_news\message object controlled by visibility
     * Read DB and pass to constructor
     *
     * @param integer $id Message id
     * @param boolean $viewhidden
     * @return message
     */
    public function get_message($id, $viewhidden) {
        global $DB;

        if ($viewhidden) {  // See any date, any visibilty.
            $sql = self::get_message_sql_start() .
                    'WHERE blockinstanceid = ?
                   AND {block_news_messages}.id = ?';

            $params = array($this->blockinstanceid, $id);
            $mrec = $DB->get_record_sql($sql, $params);
        } else {  // See past & present only and visible.
            $sql = self::get_message_sql_start() .
                    'WHERE blockinstanceid = ?
                   AND {block_news_messages}.id = ?
                   AND messagevisible = 1
                   AND messagedate <= ?';

            $params = array($this->blockinstanceid, $id, time());
            $mrec = $DB->get_record_sql($sql, $params);
        }

        if (!empty($mrec)) {
            $groupids = $DB->get_fieldset_select('block_news_message_groups', 'groupid', 'messageid = ?', [$mrec->id]);
            return new message($mrec, $groupids);
        } else {
            print_error('errornomsgfound', 'block_news', $id);
        }

    }

    /**
     * Get next and previous block_news\message ids
     * as determined by message date order and visibility
     *
     * @param message $bnm Current message
     * @param boolean $viewhidden Whether allowed to see hidden messages
     * @return \stdClass Message prev and next id  (if id is -1 then at end of list)
     */
    public function get_message_pn($bnm, $viewhidden) {
        global $DB;

        // Get all relevant messages into an array of ids (as sorted by messagedate asc)
        // indexed by a subscript. The offset of the current message id is found and
        // the ids of the messages either side returned, or -1 if at end of list.
        if ($viewhidden) {  // No date limit, all visibilty.
            $sqlvh = '';
            $paramsvh = array();
        } else {
            $sqlvh = '  AND messagevisible = 1
                         AND messagedate <= ' . time() . ' ';
            $paramsvh = array($bnm->get_messagedate());
        }

        $groups = $this->get_group_sql();

        $sql = 'SELECT DISTINCT m.id, messagedate
                  FROM {block_news_messages} m
             LEFT JOIN {block_news_message_groups} g ON m.id = g.messageid
                 WHERE blockinstanceid = ?
                   AND messagetype = ?'
                . $groups['sql']
                . $sqlvh
                . 'ORDER BY messagedate ASC';

        $params = array($this->blockinstanceid, $bnm->get_messagetype());
        $params = array_merge($params, $groups['params'], $paramsvh);
        $mrecs = $DB->get_records_sql($sql, $params);
        $pnid = -1;
        $i = 0;
        if (!empty($mrecs)) {
            foreach ($mrecs as $mrec) {
                $mida[$i] = $mrec->id;
                $i++;
            }
        } else {
            print_error('errornomsgfound', 'block_news', '', $bnm->get_id());
        }

        $off = array_search($bnm->get_id(), $mida);

        // Next.
        $pn = new \stdClass();
        if ($off == count($mida) - 1) {
            $pn->nextid = -1;
        } else {
            $pn->nextid = $mida[$off + 1];
        }

        // Prev.
        if ($off == 0) {
            $pn->previd = -1;
        } else {
            $pn->previd = $mida[$off - 1];
        }

        return $pn;

    }

    /**
     * Get the URL of the feed for the block.
     *
     * @return string - the url of the current news feed including grouping support
     */
    public function get_feed_url() {
        global $CFG, $USER;

        $feedurl = $CFG->wwwroot . '/blocks/news/feed.php?bi=' . $this->blockinstanceid;

        if (!$this->get_groupingsupport() == self::RESTRICTBYGROUP) {
            return $feedurl;
        }

        $groupings = $this->get_groupingids();
        if (empty($groupings)) {
            // Pass username to URL.
            $username = $this->username ? $this->username : $USER->username;
            $feedurl .= '&username=' . $username;
        } else {
            // Pass groupingsids to URL.
            $feedurl .= '&groupingsids=' . implode(',',  $groupings);
        }

        return $feedurl;
    }

    /**
     * Create feed record for this instance
     *
     * @param array $feeds Feed URL strings
     */
    protected function set_feeds($feeds) {
        global $DB;

        $frecs = array();

        // Get current feed records.
        $frecs = $DB->get_records('block_news_feeds',
                array('blockinstanceid' => $this->blockinstanceid));

        foreach ($frecs as $frec) {
            $idx = array_search($frec->feedurl, $feeds); // Needle, haystack.
            if ($idx !== false) {
                // Feed already present.
                // remove from requested list so we can add any left over, below.
                unset($feeds[$idx]);
            } else {
                // An existing feed is not in requested list - remove.
                $DB->delete_records('block_news_feeds', array('id' => $frec->id));
                $DB->delete_records('block_news_messages', array('newsfeedid' => $frec->id));

                // Also clear cache.
                $this->uncache_block_feed();
            }
        }

        // Handle the new ones.
        foreach ($feeds as $feed) {
            $frec = new \stdClass();
            $frec->blockinstanceid = $this->blockinstanceid;
            $frec->feedurl = $feed;
            $frec->currenthash = '0';
            $frec->feedupdated = 0;
            $frec->feederror = '';
            $fid = $DB->insert_record('block_news_feeds', $frec, true);
            $frec->id = $fid;

            // Now get the messages.
            $this->update_feed($frec);
        }

    }

    /**
     * Get all feed setup details for this instance
     *
     * @return array feed-record
     */
    public function get_feeds() {
        global $DB;

        $frecs = array();
        $bnfrecs = $DB->get_records('block_news_feeds',
                array('blockinstanceid' => $this->blockinstanceid));

        return $bnfrecs;
    }

    /**
     * Updates a feed for a block
     *
     * @param \stdClass $fbrec block_news_feeds
     */
    public function update_feed($fbrec) {
        global $DB, $CFG;

        // Do whole process in a transaction.
        $transaction = $DB->start_delegated_transaction();

        // Re-get record.
        $bnf = $DB->get_record('block_news_feeds', array('id' => $fbrec->id));

        $bnf->feederror = '';
        $bnf->feedupdated = time();
        $DB->update_record('block_news_feeds', $bnf);

        // Get the feed items.
        $fia = @$this->get_simplepie($fbrec->feedurl);

        if (isset($fia[0]->errortext)) {
            $bnf->feederror = \core_text::substr($fia[0]->errortext, 0, 255);
            $DB->update_record('block_news_feeds', $bnf);
            $transaction->allow_commit();
            return;
        }
        // Else OK.

        // See if feed is different from the last time we did an update.
        $hash = sha1(serialize($fia));

        if ($hash != $bnf->currenthash) {
            // Get existing records.
            $existing = $DB->get_records('block_news_messages', ['newsfeedid' => $bnf->id]);

            // Get hash of existing messages (key fields).
            $existinghashes = [];
            foreach ($existing as $messagedata) {
                // Use all fields except the ones which are fixed to a given value below.
                $hash = sha1($messagedata->title . $messagedata->link . $messagedata->message .
                        $messagedata->messagedate . $messagedata->messagetype .
                        $messagedata->eventstart . $messagedata->eventend .
                        $messagedata->eventlocation);
                $existinghashes[$hash] = $messagedata->id;
            }

            // Clear cache.
            $this->uncache_block_feed();

            $context = \context_block::instance($bnf->blockinstanceid);
            $bns = self::get_block_settings($bnf->blockinstanceid);

            // Write new message.
            foreach ($fia as $fi) {
                // Set default null values.
                $fi->messagetype = message::MESSAGETYPE_NEWS;
                $fi->eventlocation = null;
                $fi->eventstart = null;
                $fi->eventend = null;

                // Ensure message is non-null.
                if ($fi->message === null) {
                    $fi->message = '';
                }

                // Add missing cols.
                $fi->blockinstanceid = $fbrec->blockinstanceid;
                $fi->newsfeedid = $bnf->id;
                // Title, message, link already set.
                // constrict title.
                $fi->title = \core_text::substr(html_entity_decode($fi->title), 0, 255);
                $extraimageurl = false;
                if (strpos($fi->message, '<div class="block_news-extras">') !== false) {
                    // For internal feeds gather and strip out the extra internal information.
                    list($msg, $extraimageurl, $type, $loc, $start, $end) = self::process_internal_feed_extras($fi->message);
                    // Skip importing any messages of type event if the block does not allow events.
                    if ($type && $bns->get_displaytype() == self::DISPLAY_DEFAULT) {
                        continue;
                    }
                    $fi->message = $msg;
                    if ($type) {
                        $fi->messagetype = message::MESSAGETYPE_EVENT;
                    }
                    if ($loc) {
                        $fi->eventlocation = $loc;
                    }
                    if ($start) {
                        $fi->eventstart = strtotime($start);
                    }
                    if ($end) {
                        $fi->eventend = strtotime($end);
                    }
                }
                // Put author in at start of message text, if author isnt empty.
                if (!empty($fi->author) && $fi->author !== '-') {
                    $fi->message = '<div class="author">' . $fi->author . '</div>' . $fi->message;
                }
                unset($fi->author);
                $fi->messageformat = FORMAT_HTML;
                // Convert date-time string into unixdate (false/0 if error).
                $fi->messagedate = strtotime($fi->date);
                unset($fi->date);
                $fi->messagerepeat = 0;
                $fi->messagevisible = 1;
                $fi->hideauthor = 0;
                $fi->userid = null; // Set to null for feed msgs.
                $fi->timemodified = time();

                $hash = sha1($fi->title . $fi->link . $fi->message . $fi->messagedate .
                        $fi->messagetype . $fi->eventstart . $fi->eventend . $fi->eventlocation);

                if (array_key_exists($hash, $existinghashes)) {
                    // Reuse existing message.
                    unset($existing[$existinghashes[$hash]]);
                } else {
                    // Create new message.
                    $id = $DB->insert_record('block_news_messages', $fi);

                    if ($extraimageurl) {
                        $this->store_message_images($extraimageurl, $context, $id);
                    }
                }
            }

            // Delete all the existing messages we didn't reuse.
            $DB->delete_records_list('block_news_messages', 'id', array_keys($existing));

            // Write new hash.
            $bnf->currenthash = $hash;
            $DB->update_record('block_news_feeds', $bnf);
        }
        // Else do nothing more if hashes match.

        $transaction->allow_commit(); // Seal up.
        // Transactions are automatically rolled back if there is an error.
    }

    /**
     * For internal feeds only gather any extras information and strip this data
     * from the message content.
     * @param $message
     * @return array
     */
    public static function process_internal_feed_extras($message) {
        $imgurl = $type = $location = $start = $end = '';
        // Unfortunately because we are just parsing snippets of a feed here the
        // message nearly always throws a warning during load.
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<html><head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"></head>' .
                $message . '</html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new \DOMXpath($doc);
        if (strpos($message, 'block_news-main-msg-image')) {
            $imgurl = $xpath->evaluate("string(//img[@class='block_news-main-msg-image']/@src)");
        }
        if (strpos($message, 'block_news-event-type')) {
            $node = $xpath->query("//div[@class='block_news-event-type']")[0];
            $type = $node->nodeValue;
        }
        if (strpos($message, 'block_news-event-location')) {
            $node = $xpath->query("//div[@class='block_news-event-location']")[0];
            $location = $node->nodeValue;
        }
        if (strpos($message, 'block_news-event-start')) {
            $node = $xpath->query("//div[@class='block_news-event-start']")[0];
            $start = $node->nodeValue;
        }
        if (strpos($message, 'block_news-event-end')) {
            $node = $xpath->query("//div[@class='block_news-event-end']")[0];
            $end = $node->nodeValue;
        }
        $node = $xpath->query("//div[@class='block_news-extras']")[0];
        $node->parentNode->removeChild($node);
        $message = str_replace(array('<html>', '</html>') , '' , $doc->saveHTML());
        return [$message, $imgurl, $type, $location, $start, $end];
    }

    /**
     * Stores message images from a url.
     *
     * @param string $url Extra message image url
     * @param $context
     * @param int $id Message id
     */
    private function store_message_images($url, $context, $id) {
        global $CFG;
        if (substr_count($url, $CFG->wwwroot . '/blocks/news/images.php/')) {
            // The image exists on this server so just copy it to this blocks images.
            list($contextid, $component, $filearea, $itemid, $filename) = explode(
                    '/', str_replace($CFG->wwwroot . '/blocks/news/images.php/', '', $url));
            $fs = get_file_storage();
            $imgfile = $fs->get_file($contextid, $component, $filearea, $itemid, '/', $filename);
            if ($imgfile) {
                // Create the main image.
                $newimgfile = array('contextid' => $context->id, 'itemid' => $id);
                $fs->create_file_from_storedfile($newimgfile, $imgfile);
                // Create a thumbnail as well.
                $thumbnail = [
                        'contextid' => $context->id,
                        'component' => $component,
                        'filearea'  => 'thumbnail',
                        'itemid'    => $id,
                        'filepath'  => '/',
                        'filename'  => message::THUMBNAIL_JPG
                ];
                $fs->convert_image($thumbnail, $imgfile, '340', null, true, null);
            }
        } else if (substr_count($url, '/blocks/news/images.php/')) {
            // The image is on another server.
            $tmpfile = tempnam($CFG->tempdir, 'blocknewstempimage');
            $ok = download_file_content($url, null, null, false, 5, 5, false, $tmpfile);
            if ($ok) {
                $filename = substr($url, strrpos($url, '/') + 1);
                $fs = get_file_storage();
                $newimginfo = [
                        'contextid' => $context->id,
                        'component' => 'block_news',
                        'filearea' => 'messageimage',
                        'itemid' => $id,
                        'filepath' => '/',
                        'filename' => $filename
                ];
                $imgfile = $fs->create_file_from_pathname($newimginfo, $tmpfile);
                unlink($tmpfile);
                $thumbnail = [
                        'contextid' => $context->id,
                        'component' => 'block_news',
                        'filearea' => 'thumbnail',
                        'itemid' => $id,
                        'filepath' => '/',
                        'filename' => message::THUMBNAIL_JPG
                ];
                $fs->convert_image($thumbnail, $imgfile, '340', null, true, null);
            }
        }
    }

    /**
     * Get a whole feed using simplepie library
     *
     * @param string $feedurl
     * @return array StdClass Feed content items
     */
    private function get_simplepie($feedurl) {
        global $CFG;

        $fia = array();

        require_once($CFG->libdir . '/simplepie/moodle_simplepie.php');

        if (PHPUNIT_TEST && !empty($CFG->block_news_simplepie_feed)) {
            $feed = new \moodle_simplepie();
            $file = new \SimplePie_File($CFG->block_news_simplepie_feed);
            $feed->set_file($file);
            $feed->init();
        } else {
            $feed = new \moodle_simplepie($feedurl);
        }

        $feed->set_timeout = 10; // Secs.
        if (isset($CFG->block_rss_client_timeout)) {
            $feed->set_cache_duration($CFG->block_rss_client_timeout * 60);
        }

        if ($feed->error()) {
            $err = new \stdClass();
            $err->errortext = $feed->error();
            $fia[] = $err;
            return $fia;
        }

        if (isset($CFG->maxitemsperfeed) && is_numeric($CFG->maxitemsperfeed)) {
            $maxitems = $CFG->maxitemsperfeed;
        } else {
            $maxitems = 0; // All.
        }
        $feeditems = $feed->get_items(0, $maxitems); // Offset, length (0=all).

        $fia = array();
        foreach ($feeditems as $item) {
            $fi = new \stdClass();
            $fi->link = $item->get_link();
            $fi->title = $item->get_title();
            $fi->author = ($item->get_author() == null ? '' : $item->get_author()->name);
            $fi->message = $item->get_description();
            $fi->date = $item->get_date();

            $fia[] = $fi;
        }

        return $fia;
    }

    /**
     * Get a list of feeds that need updating
     *
     * Gets the feedurls of the feeds which are older than the update time limit, then
     * for each, get all the feeds matching the url (ie those that might be younger)
     * and orders each feedurl group to put group with oldest feed first etc
     *
     * @param integer $max - limits number of feeds returned
     * @return array StdClass block_news_feeds
     */
    public static function get_feeds_to_update($max = 1000) {
        global $DB;

        // Get value from system config_plugins table.
        if (!$updatetime = get_config('block_news', 'block_news_updatetime')) {
            print_error('errornoupdatetime', 'block_news');
        }

        // Now get the update control data.
        $fbrecs = array();
        // The update_feed process then handles each feed in the list independently (ie does
        // a get on the url for each duplicate url) but http caching on server will avoid
        // fresh gets each time (could optimise by having update_feed do one get per url
        // and then update all feed recs with same url).
        $sql = 'SELECT {block_news_feeds}.* FROM
                    (SELECT feedurl,MIN(feedupdated) AS lowestdate
                    FROM {block_news_feeds}
                    WHERE feedupdated <= ?
                    GROUP BY feedurl) tfeeds
                JOIN {block_news_feeds} ON tfeeds.feedurl =  {block_news_feeds}.feedurl
                ORDER BY tfeeds.lowestdate
                LIMIT ' . $max;

        $utime = time() - $updatetime;
        $fbrecs = $DB->get_records_sql($sql, array($utime));

        return $fbrecs;
    }

    /**
     * Get Atom feed of recent visible messages for this block instance
     * ref @link http://tools.ietf.org/html/rfc4287
     * Typically called from feed.php
     *
     * @param integer $blockinstanceid
     * @param integer $ifmodifiedsince If feed file generated less than n secs ago, no processing
     * @param array $groupingids Ids of groupings to be included in output.  null = all groupings.
     * @param int $username The username to get the groups.
     * @return mixed boolean false or string feed xml
     */
    public static function get_block_feed($blockinstanceid,
            $ifmodifiedsince = 0,
            $groupingids = null,
            $username = '') {
        global $DB;

        $fn = self::get_feed_filename($blockinstanceid, $groupingids, $username);

        // If a cached file is present and its internal cache expire marker
        // is within ifmodified time from client - return false (nothing).
        $cacheexpires = 0;
        $cachefeedxml = '';
        if (file_exists($fn)) {
            $cachefeedxml = file_get_contents($fn);
        }

        if ($cachefeedxml != '') {
            // Get custom 'expires': xxxxx news:expires="1234".
            $matches = null;
            if (preg_match('/news:expires="(.*)"/', $cachefeedxml, $matches) != 0) {
                $cacheexpires = $matches[1];
            }

            // If feed not expired, and file mod time < ifmodifiedsince, return false,
            // else return cachedfeed.
            if ($cacheexpires == 0 || $cacheexpires > time()) {
                $cachefmodified = filemtime($fn);
                if ($cachefmodified < $ifmodifiedsince) {
                    return false;
                } else {
                    return $cachefeedxml;
                }
            }
        }

        // Else generate feed.
        if (!$bns = self::get_block_settings($blockinstanceid)) {
            return false;
        }

        $bns->set_user_groupingids($groupingids);

        // Block news use group restriction, return false if not pass userid, courseid.
        if (($bns->get_groupingsupport() == $bns::RESTRICTBYGROUP && empty($groupingids))) {
            if ($username) {
                $userid = $DB->get_field('user', 'id', array('username' => $username), MUST_EXIST);
                $bni = $DB->get_record('block_instances', array('id' => $blockinstanceid));
                $context = \context::instance_by_id($bni->parentcontextid);
                $courseid = $context->instanceid;
                $bns->set_user_groupids($bns->get_groupids($userid, $courseid));
                $bns->set_username($username);
            } else {
                return false;
            }
        }

        $feedxml = $bns->generate_block_feed();

        if ($feedxml == false) {
            return false;
        }

        // And save it.
        check_dir_exists(dirname($fn), true, true); // Creates all required paths if absent.
        if (($ret = @file_put_contents($fn, $feedxml)) === false) {
            print_error('errorwritefile', 'block_news');
        }
        return $feedxml;
    }

    /**
     * Create an Atom feed of recent visible messages from this feed
     * validator: @link http://validator.w3.org/feed/check.cgi
     *
     * @return mixed boolean False or string feed xml
     */
    protected function generate_block_feed() {
        global $CFG;

        if (!$csemod = block_news_get_course_mod_info($this->blockinstanceid)) {
            print_error('errornocsemodinfo', 'block_news');
        }

        $feedtitle = $csemod->cseshortname . ' - ';
        $feedtitle .= (isset($csemod->modname) ? $csemod->modname . ' - ' : '');
        $feedtitle .= ($this->get_title() == '' ? get_string('pluginname', 'block_news') : $this->get_title());

        // Work out if we're in a module or course and derive the feed hdr alternate link value.
        if (isset($csemod->modname)) {
            $altpath = '/mod/' . $csemod->modtype . '/view.php?id=' . $csemod->modid;
        } else {
            $altpath = '/course/view.php?id=' . $csemod->cseid;
        }

        // All msgs for this block instance in desc date order, newest (highest) first.
        $bnms = $this->get_messages_all(true);
        $earliestdate = 0;
        $now = time();
        // To work out date when an update is definitely needed, get date of next future
        // message (if any).
        // For visible msgs, get oldest (lower) future date which is more than now
        // overwrite date until now is reached.
        foreach ($bnms as $bnm) {
            if ($bnm->get_messagevisible()) {
                if ($bnm->get_messagedate() <= $now) {
                    break;
                } else {
                    $earliestdate = $bnm->get_messagedate();
                }
            }
        }

        if ($earliestdate != 0) {
            // Use 'expires' as integer not rfc3332.
            $nsi = "\nxmlns:news=\"http://ns.open.ac.uk/vle/news\" news:expires=\""
                    . $earliestdate . "\"";
        } else {
            $nsi = '';
        }

        // Do header.
        $uniqueid = $this->get_feed_url();
        $linkself = $this->get_feed_url();
        $linkalt = $CFG->wwwroot . $altpath;
        $header = atom_standard_header($nsi, $uniqueid, $linkself, $linkalt, time(),
                $feedtitle, null);

        // Generate Atom items
        // in desc order of date from now (recent first)
        // include all posted in last day plus any others to make up MAXSTDMSGS
        // (if posted-in-last-day < MAXSTDMSGS).
        $items = '';
        $c = 0;
        $onedayago = $now - DAYSECS;
        $items = array();
        foreach ($bnms as $bnm) {
            // Messagevisible && messagedate <= now.
            if ($bnm->is_visible_to_students()) {
                // Ie just last day, not future.
                if ($bnm->get_messagedate() > $onedayago) {
                    $items[] = $this->generate_atom_item($bnm);
                } else {
                    if ($c < self::MAXSTDMSGS) {
                        $items[] = $this->generate_atom_item($bnm);
                    }
                }
                $c++;
            }
        }

        $body = atom_add_items($items);

        $footer = atom_standard_footer();

        $feedxml = $header . $body . $footer;
        return $feedxml;
    }

    /**
     * Create an Atom 'entry'
     *
     * @param message $bnm Message
     * @return string Atom 'entry' xml
     */
    protected function generate_atom_item($bnm) {
        global $CFG;

        $it = new \stdClass();

        $it->title = $bnm->get_title();

        $it->id = $CFG->wwwroot . "/blocks/news/message.php?m=" . $bnm->get_id();

        $it->link = $it->id; // Use url for both.

        $it->pubdate = $bnm->get_messagedate();
        // Must contain an author so set to '-' if hiding or not set.
        $user = $bnm->get_user();
        if (empty($user) || $bnm->get_hideauthor()) {
            $author = '-';
        } else {
            $author = fullname($user); // Applies Moodle system settings on hiding lastname etc.
        }

        $it->author = $author;

        $context = \context_block::instance($bnm->get_blockinstanceid());
        $it->content = '';

        // Add the message image, if exists, into the content.
        $images = $this->get_images();
        $started = false;
        if (array_key_exists($bnm->get_id(), $images)) {
            $it->content .= \html_writer::start_div('block_news-extras');
            $started = true;
            $it->content .= \html_writer::start_div('box messageimage');
            $image = $images[$bnm->get_id()];
            $pathparts = array('/blocks/news/images.php', $context->id, 'block_news',
                    'messageimage', $bnm->get_id(), $image->get_filename());
            $imageurl = new \moodle_url(implode('/', $pathparts));
            $it->content .= \html_writer::img($imageurl->out(), '', ['class' => 'block_news-main-msg-image']);
            $it->content .= \html_writer::end_div();
        }
        // Add event dates.
        if ($bnm->get_messagetype() == $bnm::MESSAGETYPE_EVENT) {
            if (!$started) {
                $it->content .= \html_writer::start_div('block_news-extras');
                $started = true;
            }
            $it->content .= \html_writer::div(get_string('event', 'block_news'), 'block_news-event-type');
            $it->content .= \html_writer::div($bnm->get_eventlocation(), 'block_news-event-location');
            $it->content .= \html_writer::div(
                    strftime(get_string('dateformatlong', 'block_news'), $bnm->get_eventstart()),
                    'block_news-event-start');
            if ($bnm->get_eventend()) {
                $it->content .= \html_writer::div(
                        strftime(get_string('dateformatlong', 'block_news'), $bnm->get_eventend()),
                        'block_news-event-end');
            }
        }
        if ($started) {
            $it->content .= \html_writer::end_div();
        }

        // Convert any @@PLUGINFILE@@ links to real URLs.
        $it->content .= file_rewrite_pluginfile_urls($bnm->get_message(), 'blocks/news/images.php',
                $context->id, 'block_news', 'message', $bnm->get_id(), null);

        return $it;
    }

    /**
     *  Custom file names
     *
     * @param integer $blockinstanceid
     * @param string $groupingids
     * @param string $username
     * @return string filename
     */
    private static function get_feed_filename($blockinstanceid, $groupingids = null, $username = '') {
        global $CFG;

        $filename = $blockinstanceid;
        if (!empty($groupingids)) {
            foreach ($groupingids as $grouping) {
                $filename .= '-' . $grouping;
            }
        }
        if ($username) {
            $filename .= '-' . $username;
        }
        $filename .= '.atom';
        $ttnum = floor($blockinstanceid / 10000);
        $fn = $CFG->dataroot . '/cache/block_news/' . $ttnum . '/' . $filename;

        return $fn;
    }

    /**
     * Delete all the cached feed files associated with this block instance.
     *
     * @throws \moodle_exception if error
     */
    public function uncache_block_feed() {
        // Get base filename., but insert a * metacharacter.
        $filepattern = preg_replace('~\.atom$~', '*.atom',
                self::get_feed_filename($this->blockinstanceid));

        // Glob for the files. Treat error (false) same as no results.
        $result = glob($filepattern);
        if ($result) {
            foreach ($result as $filename) {
                if (unlink($filename) == false) {
                    throw new \moodle_exception('cannotdeletefile');
                }
            }
        }
    }

    /**
     * Return an array of group names for the provided group IDs where the group still exists.
     *
     * When the group does not still exist, references to it will be removed from block_news_message_groups.
     *
     * @param int[] $groupids
     * @return string[] Group names
     */
    private function get_active_group_names($groupids) {
        global $DB;
        list($insql, $params) = $DB->get_in_or_equal($groupids);
        $groups = $DB->get_records_select('groups', 'id ' . $insql, $params, 'name ASC');
        $missinggroups = array_diff($groupids, array_keys($groups));
        if (!empty($missinggroups)) {
            list($deleteinsql, $deleteparams) = $DB->get_in_or_equal($missinggroups);
            $DB->delete_records_select('block_news_message_groups', 'groupid ' . $deleteinsql, $deleteparams);
        }
        $groupnames = array_map(function($group) {
            return $group->name;
        }, $groups);
        return $groupnames;
    }

    /**
     * Get group indication text
     *
     * @param message $bnm Message
     * @return string groupindication
     */
    public function get_group_indication($bnm) {
        $groupindication = '';
        // Set groupingsupport indication message.
        if ($this->get_groupingsupport() == self::RESTRICTBYGROUP) {
            $messagegroups = $bnm->get_groupids();
            if (!empty($messagegroups)) {
                $groupnames = implode(', ', $this->get_active_group_names($messagegroups));
                $groupindication = get_string('rendermsggroupindication', 'block_news', $groupnames);
            }
        }

        return $groupindication;
    }

    /**
     * Get images for the current block instance
     *
     * This returns an array of all the image files for the current block instance, keyed by message ID.  This allows us to avoid
     * having to call get_area_files() for each message when displaying several on a page.
     *
     * @param string $filearea The area to get images from, e.g. 'messageimage' or 'thumbnail'
     * @param int|bool $itemid Optional, Restrict results to this message (if we're only displaying one)
     * @return array stored_file objects, keyed by message ID.
     */
    public function get_images($filearea = 'messageimage', $itemid = false) {
        $fs = get_file_storage();
        $context = \context_block::instance($this->blockinstanceid);
        $imagefiles = $fs->get_area_files($context->id, 'block_news', $filearea, $itemid, 'itemid', false);
        $imagesbyitemid = [];
        if (!empty($imagefiles)) {
            foreach ($imagefiles as $imagefile) {
                $imagesbyitemid[$imagefile->get_itemid()] = $imagefile;
            }
        }
        return $imagesbyitemid;
    }

    /**
     * Return the total number of messages posted to the block that the user can view, optionally restricted by type.
     *
     * @param bool $viewhidden
     * @param null|int $type
     * @return int
     */
    public function count_messages($viewhidden, $type = null) {
        global $DB;
        $groups = $this->get_group_sql();
        $restricttype = $this->get_type_sql($type);
        $hidden = $this->get_hidden_sql($viewhidden);
        $sql = "SELECT COUNT(DISTINCT m.id)
                  FROM {block_news_messages} m
             LEFT JOIN {block_news_message_groups} g ON m.id = g.messageid
                 WHERE blockinstanceid = ? "
                . $hidden['sql']
                . $groups['sql']
                . $restricttype['sql'];
        $params = [$this->blockinstanceid];
        $params = array_merge($params, $hidden['params'], $groups['params'], $restricttype['params']);
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Find the largest number of pages given counts and page sizes of different message types.
     *
     * @param \stdClass[] $pageinfo Array of objects with 'messagecount' and 'pagesize' fields.
     * @return \stdClass The member of $pageinfo with the largest result of messagecount / pagesize.
     */
    public function find_most_pages(array $pageinfo) {
        return array_reduce($pageinfo, function($carry, $item) {
            if (empty($carry)) {
                return $item;
            }
            if (($carry->messagecount / $carry->pagesize) > ($item->messagecount / $item->pagesize)) {
                return $carry;
            } else {
                return $item;
            }
        });
    }

    /**
     * Return the correct text for the block's "View all" link, depending on the display mode.
     *
     * @return string
     */
    public function get_viewall_label() {
        if ($this->displaytype == self::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS) {
            return get_string('msgblockviewallnewsandevents', 'block_news');
        } else {
            return get_string('msgblockviewall', 'block_news');
        }
    }

    /**
     * Validate setting form data.
     *
     * @param array $data
     * @return array Error messages, keyed by field.
     */
    public static function validate_form($data) {
        $errors = array();

        // Now do feeds.
        // Convert from textarea to array.
        $feeds = preg_split('/\R/', $data['config_feedurls']); // Splits on any of \n \r\n \r.

        // Just check length of each feed url (other cleanup done in block_news\system::save().
        foreach ($feeds as $feed) {
            if (strlen(trim($feed)) > self::MAXURLLEN) {
                $errors['config_feedurls'] = get_string('errorurltoolong', 'block_news', self::MAXURLLEN);
                break;
            }
        }

        return $errors;
    }

    /**
     * Find all messagegroups for the specified messages, and return them as a list of groupids keyed by messageid.
     *
     * @param int[] $messageids
     * @return array Array of groupids, keyed by message ID.
     */
    public function get_groupids_by_message(array $messageids) {
        global $DB;
        $groupids = array_fill_keys($messageids, []);
        if (!empty($messageids)) {
            list($sql, $params) = $DB->get_in_or_equal($messageids);
            $messagegroups = $DB->get_records_select('block_news_message_groups', 'messageid ' . $sql, $params);
            foreach ($messagegroups as $messagegroup) {
                $groupids[$messagegroup->messageid][] = $messagegroup->groupid;
            }
        }
        return $groupids;
    }

} // End class.
