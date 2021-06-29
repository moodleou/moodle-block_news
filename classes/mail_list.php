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
 * Manages a list (based on a database recordset, so not all stored in memory)
 * of messages which need to be emailed to users.
 *
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mail_list {
    /** Config flag used to prevent sending mails twice */
    const PENDING_MARK_MAILED = 'pending_mark_mailed';
    const NEWSID_NO_RESTRICTION = 0;
    const NEWSID_NO_MORE_NEWS = -1;
    const MAILSTATE_MAILED = 1;
    const MAILSTATE_NOT_MAILED = 0;
    /** @var int When querying news, request at most this many */
    const MAX_NEWS_PER_QUERY = 1000;
    protected static $nextnewscache = [];

    /** @var int News ID that is being processed, 0 if none */
    protected $newsid;

    private $news, $message;
    private $storedrecord;
    private $time;
    private $rs;
    /** @var float[] Array of times taken for the 3 operations (only valid at end) */
    protected $times = [];

    /**
     * Creates the mail queue and runs query to obtain list of message that should
     * be mailed.
     *
     * @param bool $output If true, outputs a couple of lines using mtrace to indicate progress
     */
    public function __construct($output) {

        global $DB, $CFG;
        $this->time = time();
        $this->news = null;
        $this->message = null;
        $this->storedrecord = null;
        if ($output) {
            mtrace('[News] ', '');
        }

        // Check if an earlier run got aborted. In that case we mark all
        // messages as mailed anyway because it's better to skip some than
        // to send out double-message.
        if ($pending = get_config('block_news', $this->get_pending_flag_name())) {
            list ($time, $newsid) = explode(',', $pending);
            $this->mark_mailed($time, (int) $newsid);
        }
        $this->newsid = $this->get_limited_news_id();
        if ($this->newsid === self::NEWSID_NO_MORE_NEWS) {
            return;
        }

        list ($wheresql, $whereparams) = $this->get_query_where($this->time, $this->newsid);
        $querychunk = $this->get_query_from() . $wheresql;
        if ($output) {
            mtrace('[Messages] ', '');
        }
        $before = microtime(true);

        $userfieldsapi = \core_user\fields::for_identity(\context_block::instance($this->newsid), false)->with_name()
            ->including('id', 'emailstop', 'deleted', 'auth', 'timezone', 'lang');
        $userfieldssql = $userfieldsapi->get_sql('u', false, 'u_', '', false)->selects;
        $this->rs = $DB->get_recordset_sql($sql = "
            SELECT
            " . subscription::select_course_fields('c') . ",
            " . subscription::select_block_news_message_fields('bnm') . ",
            " . $userfieldssql . ",
            bn.*, bi.*, x.*
           $querychunk
           ORDER BY
             bn.id, bnm.id", $whereparams);

        $this->record_time('Getting message list', $before);
    }

    /**
     * Checks if there are any more emails to send.
     *
     * @return bool True if there are no news currently requiring processing
     */
    public function is_finished(): bool {
        return $this->newsid === self::NEWSID_NO_MORE_NEWS;
    }

    /**
     * @return string
     */
    protected function get_pending_flag_name(): string {
        return self::PENDING_MARK_MAILED;
    }

    /**
     * @return array
     */
    protected function get_query_where($time, $newsid): array {
        // In case cron has not run for a while.
        $safetynet = $this->get_safety_net($time);
        $newsbisql = '';
        if ($newsid != 0) {
            $newsbisql = 'AND bn.blockinstanceid = ?';
        }

        $sql = "
            WHERE
            bnm.messagedate < ?
            AND bnm.mailstate = " . self::MAILSTATE_NOT_MAILED . "
            -- Don't mail out really old message
            AND bnm.messagedate > ?
            $newsbisql
            -- Context limitation
            AND x.contextlevel = 50";
        $params = [$time, $safetynet];

        if ($newsid != 0) {
            $params[] = $newsid;
        }

        return [$sql, $params];
    }

    /**
     * Safety net is to prevent the news sending out very old emails if cron
     * is down for a long time, potentially causing a mail flood.
     *
     * @param int $time Current/base time (seconds)
     * @return int Oldest time (seconds) of messages to process
     */
    public static function get_safety_net($time): int {
        global $CFG;
        $hours = isset($CFG->news_donotmailafter)
            ? $CFG->news_donotmailafter : 48;
        return $time - $hours * 3600;
    }

    /**
     * If we should limit this run-through to a single news, then returns that ID. Otherwise
     * returns one of the NEWSID_xx constants.
     *
     * @return int NEWSID_xx constant or news id
     * @throws dml_exception
     */
    protected function get_limited_news_id(): int {
        $newsid = $this->get_next_news_id_for_email_processing();
        if ($newsid) {
            return $newsid;
        } else {
            // No news need processing.
            return self::NEWSID_NO_MORE_NEWS;
        }
    }

    /**
     * Gets the next news that should be processed.
     *
     * @return int news instance id or 0 if none requiring processing
     * @throws dml_exception
     */
    protected function get_next_news_id_for_email_processing(): int {
        global $DB;
        if (!self::$nextnewscache) {
            $before = microtime(true);
            list($wheresql, $whereparams) = $this->get_query_where(
                $this->time, self::NEWSID_NO_RESTRICTION);

            $nextnews = $DB->get_records_sql($sql = "
                        SELECT bn.blockinstanceid
                          FROM {block_news} bn
                          JOIN {block_instances} bi ON bi.id = bn.blockinstanceid
                          JOIN {context} x ON x.id = bi.parentcontextid AND x.contextlevel = ?
                         WHERE EXISTS(
                               SELECT 1
                                 FROM {block_news_messages} bnm
                                 $wheresql
                                      AND bnm.blockinstanceid = bn.blockinstanceid
                       ORDER BY bn.id ASC)",
                $params = array_merge([CONTEXT_COURSE], $whereparams), 0,
                self::MAX_NEWS_PER_QUERY);
            foreach ($nextnews as $rec) {
                self::$nextnewscache[] = $rec->blockinstanceid;
            }

            // If we received less than 1,000 news (so, all of them) then add a marker indicating
            // that there are no news left (this is used so that we don't call the query a second
            // time at the end of the run).
            if (count(self::$nextnewscache) < self::MAX_NEWS_PER_QUERY) {
                self::$nextnewscache[] = self::NEWSID_NO_MORE_NEWS;
            }
            $this->record_time('Finding next news', $before);
        }
        if (self::$nextnewscache) {
            if (self::$nextnewscache[0] === self::NEWSID_NO_MORE_NEWS) {
                // There are no news - don't use up the array and force another query.
                return 0;
            }
            return array_shift(self::$nextnewscache);
        } else {
            // There are still no news!
            return 0;
        }

        // If no news IDs are in the cache, then work out which news to process next.

    }

    /**
     * Records a (cumulative) time.
     *
     * @param string $name Name for time
     * @param float $before Micro-time before this thing was done
     */
    protected function record_time($name, $before) {
        if (!array_key_exists($name, $this->times)) {
            $this->times[$name] = 0;
        }
        $this->times[$name] += microtime(true) - $before;
    }

    /**
     * @return string
     */
    protected function get_query_from(): string {
        return "
            FROM
            {block_news_messages} bnm
            INNER JOIN {user} u ON bnm.userid = u.id
            INNER JOIN {block_news} bn ON bnm.blockinstanceid = bn.blockinstanceid
            INNER JOIN {block_instances} bi ON bi.id = bn.blockinstanceid
            INNER JOIN {context} x ON x.id = bi.parentcontextid
            INNER JOIN {course} c ON c.id = x.instanceid
            ";
    }

    /**
     * Gets times as an associative array.
     *
     * @return float[] List of times
     */
    public function get_times() {
        return $this->times;
    }

    /**
     * Obtains the next news from the list.
     *
     * @param stdClass &$news News (out variable)
     * @param object &$blockcontext Context block
     * @param object &$course Course object (out variable)
     */
    public function next_news(&$news, &$blockcontext, &$course) {
        global $DB;

        if ($this->is_finished()) {
            throw new \coding_exception('Cannot call next_news when finished');
        }

        while ($this->news != null) {
            $this->next_message($message);
        }
        // Check not finished.
        if ($this->storedrecord) {

            $record = $this->storedrecord;
            $this->storedrecord = null;
        } else if (!$this->rs) {
            // Already used entire list and closed recordset.
            return false;
        } else {
            if (!$this->rs->valid()) {
                // End of the line. Mark everything as mailed.
                $this->mark_mailed($this->time, $this->newsid);
                $this->rs->close();
                $this->rs = null;
                return false;
            }

            $record = $this->rs->current();
            $this->rs->next();
        }
        $this->storedrecord = clone($record);
        $course = $DB->get_record('course', ['id' => $record->c_id]);
        $newsfields = $DB->get_record('block_news', ['blockinstanceid' => $record->blockinstanceid]);
        $blockcontext = \context_block::instance($record->blockinstanceid);
        $news = new subscription($course, $record->blockinstanceid, $newsfields);
        $this->news = $news;
        return true;
    }

    /**
     * Obtains the next message in the list.
     * @param \stdClass $message Message
     */
    public function next_message(&$message) {
        if ($this->news == null || $this->is_finished()) {
            throw new \coding_exception("Cannot call next_message");
        }
        if ($this->storedrecord) {
            $record = $this->storedrecord;
            $this->storedrecord = null;
        } else if (!$this->rs) {
            $this->news = null;
            return false;
        } else {
            if (!$this->rs->valid()) {
                $this->mark_mailed($this->time, $this->newsid);
                $this->rs->close();
                $this->news = null;
                $this->rs = null;
                return false;
            }
            $record = $this->rs->current();
            $this->rs->next();
        }
        // If the next database row comes from a different news block.
        if ($record->bnm_blockinstanceid != $this->news->get_blockinstanceid()) {
            $this->storedrecord = $record;
            $this->news = null;
            return false;
        }
        $message = subscription::extract_subobject($record, 'bnm_');
        return true;
    }

    /**
     * Mark email has been mailed.
     */
    private function mark_mailed($time, $bi) {
        list ($wheresql, $whereparams) = $this->get_query_where($time, $bi);
        $querychunk = $this->get_query_from() . $wheresql;
        $before = microtime(true);

        \mod_forumng_utils::update_with_subquery_grrr_mysql("
        UPDATE
            {block_news_messages}
        SET
            mailstate = " . $this->get_target_mail_state() . "
        WHERE
            id %'IN'%", "SELECT bnm.id $querychunk", $whereparams);
        $this->record_time('Marking message processed', $before);

        unset_config($this->get_pending_flag_name(), 'block_news');
    }

    /**
     * @return int
     */
    protected function get_target_mail_state(): int {
        return self::MAILSTATE_MAILED;
    }

    /**
     * Resets the static cache to ensure it will calculate the list of news again.
     */
    public static function reset_static_cache() {
        self::$nextnewscache = [];
    }
}
