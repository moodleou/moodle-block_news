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
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // It must be included from a Moodle page
}
require_once('block_news_message.php');
require_once('atomlib.php');
require_once('lib.php');


/**
 * main system class
 * @package blocks
 * @subpackage news
 *
 */
class block_news_system {

    // DAYSECS, used below, is defined in lib/moodlelib.php
    const MAXSTDMSGS = 20; // maximum std feed messages to show (see generate_block_feed()

    public static function get_message_sql_start() {
        return "SELECT {block_news_messages}.*, u.id AS u_id, " .
            get_all_user_name_fields(true, 'u', null, 'u_') .
            " FROM {block_news_messages}
             LEFT JOIN {user} u ON {block_news_messages}.userid = u.id ";
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

    /**
     * Construct object
     *
     * @param StdClass $bn Block details (typically called internally @see get_block_settings )
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
    }

    /**
     * Creates an instance of itself
     *
     * Sets properties from DB if record exists
     * otherwise sets properties with default values, and inserts a new record in DB
     *
     * @param integer $blockinstanceid
     * @return block_news_system
     */
    public static function get_block_settings($blockinstanceid) {
        global $DB, $USER, $COURSE;

        $bn = $DB->get_record('block_news', array('blockinstanceid'=>$blockinstanceid));

        if (empty($bn)) {
            $bn = new StdClass;
            $bn->blockinstanceid = $blockinstanceid;
            $bn->title = '';
            $bn->nummessages = 2;
            $bn->summarylength = 100;
            $bn->hidetitles = 0;
            $bn->hidelinks = 0;
            $bn->groupingsupport = 0;
            $rid = $DB->insert_record('block_news', $bn, true);
            $bn->id = $rid;
        }

        return new block_news_system($bn);

    }

    /**
     * @return integer id
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @return string title
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * @return integer Number of messages to show in block
     */
    public function get_nummessages() {
        return $this->nummessages;
    }

    /**
     * @return integer Length of summary in characters (0 if turned off)
     */
    public function get_summarylength() {
        return $this->summarylength;
    }

    /**
     * @return boolean Whether to hide all message titles in block
     */
    public function get_hidetitles() {
        return $this->hidetitles;
    }

    /**
     * @return boolean Whether to hide message links (from feed messages) in block
     */
    public function get_hidelinks() {
        return $this->hidelinks;
    }

    /**
     * @return boolean Whether grouping support is enabled in block
     */
    public function get_groupingsupport() {
        return $this->groupingsupport;
    }

    /**
     * Get a list of the groupsings that apply in the current context for use when working
     * out which messages to display.  This will be because the user is a member of particular
     * groupings and groupings support is enabled or some groupings have been specified in a
     * querystring and specified using set_user_groupingids().
     * @return array - array of the groupingids (empty if none).
     */
    public function get_groupingids() {
        global $SESSION, $COURSE, $USER, $DB;

        if (!empty($this->usergroupingids)) {
            return $this->usergroupingids;
        }

        if (!empty($SESSION->block_news_user_groupings[$COURSE->id])) {
            return $SESSION->block_news_user_groupings[$COURSE->id];
        }

        $context = context_course::instance($COURSE->id);
        if (has_capability('moodle/site:accessallgroups', $context)) {
            //if the user has the allgroups capability they can see everything.
            $g = groups_get_all_groupings($COURSE->id);
            $groupings = array();
            foreach ($g as $grouping) {
                $groupings[] = $grouping->id;
            }
            $SESSION->block_news_user_groupings[$COURSE->id] = $groupings;
            return $groupings;
        }

        $sql = 'SELECT DISTINCT({groupings}.id)
                FROM {user}
                INNER JOIN {groups_members}
                ON {user}.id = {groups_members}.userid
                INNER JOIN {groupings_groups}
                ON {groups_members}.groupid = {groupings_groups}.groupid
                INNER JOIN {groupings}
                ON {groupings_groups}.groupingid = {groupings}.id
                WHERE {user}.id = ?
                AND {groupings}.courseid = ?';
        $results = $DB->get_records_sql($sql, array($USER->id, $COURSE->id));

        $groupings = array();
        foreach ($results as $result) {
            $groupings[] = $result->id;
        }
        $SESSION->block_news_user_groupings[$COURSE->id] = $groupings;
        return $groupings;
    }

    /**
     * Sets the value of the user groupings ids class variable.
     * @param array $groupingsids
     */
    public function set_user_groupingids($groupingids) {
        $this->usergroupingids = $groupingids;
    }

    /**
     * Return SQL WHERE and clause and params to append to queries when grouping support
     * is enabled.
     * @return array - 'sql' (empty string if nothing to return)
     * and 'params' (empty array if nothing).
     */
    public function get_grouping_sql() {
        global $COURSE, $DB;

        $output = array();
        $output['sql'] = '';
        $output['params'] = array();

        if (!$this->get_groupingsupport()) {
            return $output;
        }

        $context = context_course::instance($COURSE->id);
        if (has_capability('moodle/site:accessallgroups', $context)) {
            return $output;
        }

        $groupings = $this->get_groupingids();

        $groupings[] = 0;

        list($sql, $groupings) = $DB->get_in_or_equal($groupings);

        $output['sql'] = ' AND groupingid ' . $sql .' ';
        $output['params'] = $groupings;

        return $output;
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
     * @param StdClass $data Form data
     */
    public function save($data) {
        global $DB;

        $data->id = $this->id;
        $data->timemodified = time();

        $DB->update_record('block_news', $data);

        // now do feeds
        // convert from textarea to array
        $feeds = preg_split('/\R/', $data->feedurls); // splits on any of \n \r\n \r

        $feeds = array_values(array_unique($feeds)); // remove any duplicate lines, reindex

        // check each feed url - throw away any empty ones (length check done in edit_form.php)
        $number_of_feeds = count($feeds);
        for ($i = 0; $i < $number_of_feeds; $i++) {
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
     * Get block_news_message objects limited by visibility and date and sorted by messagedate
     *
     * Read DB and pass each row to constructor
     *
     * @param integer $max Maximum number of messages to return
     * @return array block_news_message
     */
    public function get_messages_limited($max) {
        global $DB;
        $bnms = array();

        $groupings = $this->get_grouping_sql();
        $sql = self::get_message_sql_start() .
                'WHERE blockinstanceid=?
                 AND messagevisible=1
                 AND messagedate <= ?'
                .$groupings['sql'].
                 'ORDER BY messagedate DESC
                 LIMIT '.$max;

        $params = array($this->blockinstanceid, time());
        $params = array_merge($params, $groupings['params']);
        $mrecs = $DB->get_records_sql($sql, $params);
        foreach ($mrecs as $mrec) {
            $bnms[] = new block_news_message($mrec);
        }

        return $bnms;
    }


    /**
     * Get block_news_message objects controlled by visibility and sorted by message date
     * Read DB and pass each row to constructor
     *
     * @param boolean $viewhidden
     * @return array block_news_message
     */
    public function get_messages_all($viewhidden) {
        global $DB;
        $bnms = array();

        $groupings = $this->get_grouping_sql();
        if ($viewhidden) { // see all dates, all visibilty
            $sql = self::get_message_sql_start() .
                    'WHERE blockinstanceid = ?'
                    .$groupings['sql'].
                    'ORDER BY messagedate DESC';
            $params = array($this->blockinstanceid);
            $params = array_merge($params, $groupings['params']);
            $mrecs = $DB->get_records_sql($sql, $params);
        } else {  // see past/present only and visible
            $sql =  self::get_message_sql_start() .
                    'WHERE blockinstanceid = ?
                     AND messagevisible = 1
                     AND messagedate <= ?'
                    .$groupings['sql'].
                    'ORDER BY messagedate DESC';
            $params = array($this->blockinstanceid, time());
            $params = array_merge($params, $groupings['params']);
            $mrecs = $DB->get_records_sql($sql, $params);
        }

        foreach ($mrecs as $mrec) {
            $bnms[] = new block_news_message($mrec);
        }

        return $bnms;
    }


    /**
     * Get block_news_message object controlled by visibility
     * Read DB and pass to constructor
     *
     * @param integer $id Message id
     * @param boolean $viewhidden
     * @return block_news_message
     */
    public function get_message($id, $viewhidden) {
        global $DB;

        $groupings = $this->get_grouping_sql();
        if ($viewhidden) {  // see any date, any visibilty
            $sql = self::get_message_sql_start() .
                  'WHERE blockinstanceid = ?
                   AND {block_news_messages}.id = ?'
                  .$groupings['sql'];

            $params = array($this->blockinstanceid, $id);
            $params = array_merge($params, $groupings['params']);
            $mrec = $DB->get_record_sql($sql, $params);
        } else {  // see past & present only and visible
            $sql = self::get_message_sql_start() .
                  'WHERE blockinstanceid = ?
                   AND {block_news_messages}.id = ?
                   AND messagevisible = 1
                   AND messagedate <= ?'
                  .$groupings['sql'];

            $params = array($this->blockinstanceid, $id , time());
            $params = array_merge($params, $groupings['params']);
            $mrec = $DB->get_record_sql($sql, $params);
        }

        if (!empty($mrec)) {
            return new block_news_message($mrec);
        } else {
            print_error('errornomsgfound', 'block_news', $id);
        }

    }


    /**
     * Get next and previous block_news_message ids
     * as determined by message date order and visibility
     *
     * @param block_news_message $bnm Current message
     * @param boolean $viewhidden Whether allowed to see hidden messages
     * @return StdClass Message prev and next id  (if id is -1 then at end of list)
     */
    public function get_message_pn($bnm, $viewhidden) {
        global $DB;

        /* get all relevant messages into an array of ids (as sorted by messagedate asc)
         * indexed by a subscript. The offset of the current message id is found and
         * the ids of the messages either side returned, or -1 if at end of list
         */
        if ($viewhidden) {  // no date limit, all visibilty
            $sql_vh = '';
        } else {
            $sql_vh = '  AND messagevisible = 1
                         AND messagedate <= '.time().' ';
        }

        $sql = 'SELECT id, messagedate
                FROM {block_news_messages}
                WHERE blockinstanceid = ? '
                . $sql_vh
                .'ORDER BY messagedate ASC';

        $mrecs = $DB->get_records_sql($sql, array($this->blockinstanceid, $bnm->get_messagedate()));
        $pn_id = -1;
        $i = 0;
        if (!empty($mrecs)) {
            foreach ($mrecs as $mrec) {
                $mida[$i] = $mrec->id;
                $i++;
            }
        } else {
            print_error('errornomsgfound', 'block_news', $id);
        }

        $off = array_search($bnm->get_id(), $mida);

        // next
        $pn = new StdClass();
        if ($off == count($mida)-1) {
            $pn->nextid = -1;
        } else {
            $pn->nextid = $mida[$off+1];
        }

        //prev
        if ($off == 0) {
            $pn->previd = -1;
        } else {
            $pn->previd = $mida[$off-1];
        }

        return $pn;

    }


    /**
     * @return string - the url of the current news feed including grouping support
     */
    public function get_feed_url() {
        global $CFG, $OUTPUT;

        $feedurl = $CFG->wwwroot . '/blocks/news/feed.php?bi='.$this->blockinstanceid;

        if (!$this->get_groupingsupport()) {
            return $feedurl;
        }

        $groupings = $this->get_groupingids();
        if (empty($groupings)) {
            return $feedurl;
        }

        $feedurl .= '&groupingsids=';

        $firstgrouping = true;
        foreach ($groupings as $grouping) {
            if (!$firstgrouping) {
                $feedurl .= ',';
            }
            $feedurl .= $grouping;
            $firstgrouping = false;
        }

        return $feedurl;
    }

    /**
     * Create feed record for this instance
     * @param array $feeds Feed URL strings
     */
    protected function set_feeds($feeds) {
        global $DB;

        $frecs=array();

        // get current feed records
        $frecs=$DB->get_records('block_news_feeds',
                                    array('blockinstanceid'=>$this->blockinstanceid));

        foreach ($frecs as $frec) {
            $idx = array_search($frec->feedurl, $feeds); // needle, haystack
            if ($idx !== false) {
                // feed already present
                // remove from requested list so we can add any left over, below
                unset($feeds[$idx]);
            } else {
                // an existing feed is not in requested list - remove
                $DB->delete_records('block_news_feeds', array('id'=>$frec->id));
                $DB->delete_records('block_news_messages', array('newsfeedid'=>$frec->id));

                // also clear cache
                $this->uncache_block_feed();
            }
        }

        // handle the new ones
        foreach ($feeds as $feed) {
            $frec=new StdClass();
            $frec->blockinstanceid=$this->blockinstanceid;
            $frec->feedurl=$feed;
            $frec->currenthash='0';
            $frec->feedupdated=0;
            $frec->feederror='';
            $fid=$DB->insert_record('block_news_feeds', $frec, true);
            $frec->id=$fid;

            // now get the messages
            $this->update_feed($frec);
        }

    }


    /**
     * Get all feed setup details for this instance
     * @return array feed-record
     */
    public function get_feeds() {
        global $DB;

        $frecs = array();
        $bnfrecs = $DB->get_records('block_news_feeds',
            array('blockinstanceid'=>$this->blockinstanceid));

        return $bnfrecs;
    }


    /**
     * Updates a feed for a block
     * @param StdClass $fbrec block_news_feeds
     */
    public function update_feed($fbrec) {
        global $DB;

        // do whole process in a transaction
        $transaction = $DB->start_delegated_transaction();

        // re-get record
        $bnf = $DB->get_record('block_news_feeds', array('id' => $fbrec->id));

        $bnf->feederror = '';
        $bnf->feedupdated = time();
        $DB->update_record('block_news_feeds', $bnf);

        // get the feed items
        $fia = @$this->get_simplepie($fbrec->feedurl);

        if (isset($fia[0]->errortext)) {
            $bnf->feederror = core_text::substr($fia[0]->errortext, 0, 255);
            $DB->update_record('block_news_feeds', $bnf);
            $transaction->allow_commit();
            return;
        }
        // else OK

        // see if feed is different from the last time we did an update
        $hash = sha1(serialize($fia));

        if ($hash != $bnf->currenthash) {
            // delete existing
            $DB->delete_records('block_news_messages', array('newsfeedid' => $bnf->id));

            // also clear cache
            $this->uncache_block_feed();

            // write new message
            foreach ($fia as $fi) {
                // add missing cols
                $fi->blockinstanceid = $fbrec->blockinstanceid;
                $fi->newsfeedid = $bnf->id;
                // title, message, link already set
                // constrict title
                $fi->title = core_text::substr($fi->title, 0, 255);
                // put author at at start of message text, allow an empty element if no author
                $fi->message = '<div class=author>'.$fi->author.' </div>'.$fi->message;
                unset($fi->author);
                $fi->messageformat = FORMAT_HTML;
                // convert date-time string into unixdate (false/0 if error)
                $fi->messagedate = strtotime($fi->date);
                unset($fi->date);
                $fi->messagerepeat = 0;
                $fi->messagevisible = 1;
                $fi->hideauthor = 0;
                $fi->userid = null; // set to null for feed msgs
                $fi->timemodified = time();

                block_news_message::create($fi);
            }

            // write new hash
            $bnf->currenthash = $hash;
            $DB->update_record('block_news_feeds', $bnf);
        }
        // else do nothing more if hashes match

        $transaction->allow_commit(); // seal up
        // Transactions are automatically rolled back if there is an error

        return;
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

        require_once($CFG->libdir.'/simplepie/moodle_simplepie.php');
        $feed = new moodle_simplepie($feedurl);

        $feed->set_timeout = 10;//secs
        if (isset($CFG->block_rss_client_timeout)) {
            $feed->set_cache_duration($CFG->block_rss_client_timeout*60);
        }

        if ($feed->error()) {
            $err = new StdClass();
            $err->errortext = $feed->error();
            $fia[] = $err;
            return $fia;
        }

        if (isset($CFG->maxitemsperfeed) && is_numeric($CFG->maxitemsperfeed)) {
            $maxitems = $CFG->maxitemsperfeed;
        } else {
            $maxitems = 0; // all
        }
        $feeditems = $feed->get_items(0, $maxitems); // offset, length (0=all)

        $fia = array();
        foreach ($feeditems as $item) {
            $fi = new StdClass();
            $fi->link = $item->get_link();
            $fi->title  = $item->get_title();
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
    public static function get_feeds_to_update($max=1000) {
        global $DB;

        // get value from system config_plugins table
        if (!$updatetime = get_config('block_news', 'block_news_updatetime') ) {
            print_error('errornoupdatetime', 'block_news');
        }

        // now get the update control data
        $fbrecs = array();
        // The update_feed process then handles each feed in the list independently (ie does
        // a get on the url for each duplicate url) but http caching on server will avoid
        // fresh gets each time (could optimise by having update_feed do one get per url
        // and then update all feed recs with same url)
        $sql = 'SELECT {block_news_feeds}.* FROM
                    (SELECT feedurl,min(feedupdated) AS lowestdate
                    FROM {block_news_feeds}
                    WHERE feedupdated <= ?
                    GROUP BY feedurl) tfeeds
                JOIN {block_news_feeds} ON tfeeds.feedurl =  {block_news_feeds}.feedurl
                ORDER BY tfeeds.lowestdate
                LIMIT '.$max;

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
     * @return mixed boolean false or string feed xml
     */
    public static function get_block_feed($blockinstanceid,
                                          $ifmodifiedsince=0,
                                          $groupingids = null) {

        $fn =  self::get_feed_filename($blockinstanceid, $groupingids);

        // if a cached file is present and its internal cache expire marker
        // is within ifmodified time from client - return false (nothing)
        $cacheexpires = 0;
        $cachefeedxml = '';
        if (file_exists($fn)) {
            $cachefeedxml = file_get_contents($fn);
        }

        if ($cachefeedxml != '') {
            // get custom 'expires': xxxxx news:expires="1234"
            $matches = null;
            if (preg_match('/news:expires="(.*)"/', $cachefeedxml, $matches) != 0) {
                $cacheexpires = $matches[1];
            }

            // if feed not expired, and file mod time < ifmodifiedsince, return false,
            // else return cachedfeed
            if ($cacheexpires == 0 || $cacheexpires > time()) {
                $cachefmodified = filemtime($fn);
                if ($cachefmodified < $ifmodifiedsince) {
                    return false;
                } else {
                    return $cachefeedxml;
                }
            }
        }

        // else generate feed
        if (!$bns = self::get_block_settings($blockinstanceid)) {
            return false;
        }

        $bns->set_user_groupingids($groupingids);

        $feedxml = $bns->generate_block_feed();

        if ($feedxml == false) {
            return false;
        }

        // and save it
        check_dir_exists(dirname($fn), true, true); // creates all required paths if absent
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
        global $DB, $PAGE, $CFG, $COURSE;

        if (!$csemod = block_news_get_course_mod_info($this->blockinstanceid)) {
            print_error('errornocsemodinfo', 'block_news');
        }

        $feedtitle = $csemod->cseshortname.' - ';
        $feedtitle .= (isset($csemod->modname) ? $csemod->modname.' - ' : '');
        $feedtitle .= ($this->get_title() == '' ?
                                get_string('pluginname', 'block_news') : $this->get_title());

        // work out if we're in a module or course and derive the feed hdr alternate link value
        if (isset($csemod->modname)) {
            $altpath = '/mod/'.$csemod->modtype.'/view.php?id='.$csemod->modid;
        } else {
            $altpath = '/course/view.php?id='.$csemod->cseid;
        }

        // all msgs for this block instance in desc date order, newest (highest) first
        $bnms = $this->get_messages_all(true);
        $earliestdate = 0;
        $now = time();
        // to work out date when an update is definitely needed, get date of next future
        // message (if any)
        // for visible msgs, get oldest (lower) future date which is more than now
        // overwrite date until now is reached
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
            // 'expires' as integer not rfc3332
            $nsi = "\nxmlns:news=\"http://ns.open.ac.uk/vle/news\" news:expires=\""
            .$earliestdate."\"";
        } else {
            $nsi = '';
        }

        // do header
        $uniqueid = $this->get_feed_url();
        $linkself = $this->get_feed_url();
        $linkalt = $CFG->wwwroot.$altpath;
        $header = atom_standard_header($nsi, $uniqueid, $linkself, $linkalt, time(),
        $feedtitle, null);

        // generate Atom items
        // in desc order of date from now (recent first)
        // include all posted in last day plus any others to make up MAXSTDMSGS
        // (if posted-in-last-day < MAXSTDMSGS)
        $items = '';
        $c = 0;
        $onedayago = $now-DAYSECS;
        $items = array();
        foreach ($bnms as $bnm) {
            // messagevisible && messagedate <= now
            if ($bnm->is_visible_to_students()) {
                // ie just last day, not future
                if ($bnm->get_messagedate() > $onedayago) {
                    $items[] = $this->generate_atom_item($bnm);
                } else if ($c < self::MAXSTDMSGS) {
                    $items[] = $this->generate_atom_item($bnm);
                }
                $c++;
            }
        }

        $body=atom_add_items($items);

        $footer=atom_standard_footer();

        $feedxml=$header.$body.$footer;
        return $feedxml;
    }


    /**
     * Create an Atom 'entry'
     *
     * @param block_news_message $bnm Message
     * @return string Atom 'entry' xml
     */
    protected function generate_atom_item($bnm) {
        global $CFG;

        $it = new StdClass();

        $it->title = $bnm->get_title();

        $it->id = $CFG->wwwroot."/blocks/news/message.php?m=" . $bnm->get_id();

        $it->link = $it->id; // use url for both

        $it->pubdate = $bnm->get_messagedate();
        // must contain an author so set to '-' if hiding or not set
        $user = $bnm->get_user();
        if (empty($user) || $bnm->get_hideauthor()) {
            $author = '-';
        } else {
            $author = fullname($user); // applies Moodle system settings on hiding lastname etc
        }

        $it->author = $author;

        // convert any @@PLUGINFILE@@ links to real URLs
        $context = context_block::instance($bnm->get_blockinstanceid());
        $it->content = file_rewrite_pluginfile_urls($bnm->get_message(), 'pluginfile.php',
                             $context->id, 'block_news', 'message', $bnm->get_id(), null);

        return $it;
    }


    /**
     *  Custom file names
     *
     * @param integer $blockinstanceid
     * @return string filename
     */
    private static function get_feed_filename($blockinstanceid, $groupingids = null) {
        global $CFG;

        if (empty($groupingids)) {
            $filename = $blockinstanceid.'.atom';
        } else {
            $filename = $blockinstanceid;
            foreach ($groupingids as $grouping) {
                $filename .= '-'.$grouping;
            }
            $filename .= '.atom';
        }
        $ttnum = floor($blockinstanceid/10000);
        $fn = $CFG->dataroot.'/cache/block_news/'.$ttnum.'/'.$filename;

        return $fn;
    }


    /**
     * Delete the feed file associated with this blockinstance
     *
     * @throws moodle_exception if error
     */
    public function uncache_block_feed() {
        $fn = self::get_feed_filename($this->blockinstanceid);
        // check if exists as its possible it was never created
        if (file_exists($fn)) {
            if (unlink($fn) == false) {
                throw new moodle_exception('cannotdeletefile');
            }
        }
    }

} // end class
