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
 * News block message.
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

/**
 * main message class
 *
 * @package blocks
 * @subpackage news
 *
 */
class message {

    const MESSAGETYPE_NEWS = 1;
    const MESSAGETYPE_EVENT = 2;
    const THUMBNAIL_JPG = 'thumbnail.jpg';

    protected $id;
    protected $blockinstanceid;
    protected $newsfeedid;
    protected $title;
    protected $link;
    protected $message;
    protected $messageformat;
    protected $messagetype;
    protected $messagedate;
    protected $messagerepeat;
    protected $messagevisible;
    protected $imagedesc;
    protected $imagedescnotnecessary;
    protected $hideauthor;
    protected $publish;
    protected $timemodified;
    protected $userid;
    protected $groupids;
    protected $eventstart;
    protected $eventend;
    protected $eventlocation;

    protected $user;

    /**
     * Constructor
     *
     * @param \stdClass $mrec Database record for the message.
     * @param int[] $groupids IDs of groups to restrict this message to.
     */
    public function __construct($mrec, $groupids = []) {
        // Assign the properties.
        $this->user = new \stdClass;
        foreach ((array) $mrec as $field => $value) {
            if (property_exists($this, $field)) {
                $this->{$field} = $value;
            } else {
                if (strpos($field, 'u_') === 0) {
                    $subfield = substr($field, 2);
                    $this->user->{$subfield} = $value;
                }
            }
        }
        $this->groupids = $groupids;
    }

    /**
     * Get the ID of the message.
     *
     * @return int Message id
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get the block instance ID for the block the message appears in.
     *
     * @return int Block instance id
     */
    public function get_blockinstanceid() {
        return $this->blockinstanceid;
    }

    /**
     * Get the ID of the block's news feed.
     *
     * @return int News feed id
     */
    public function get_newsfeedid() {
        return $this->newsfeedid;
    }

    /**
     * Get the title of the message.
     *
     * @return string Message title
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Get the link to the message, if from a feed.
     *
     * @return string Link to message on remote feed (if feed message)
     */
    public function get_link() {
        return $this->link;
    }

    /**
     * Get the main text of the message.
     *
     * @return string Message text
     */
    public function get_message() {
        $this->message = str_replace("<div class=author></div>", "", $this->message);
        $this->message = str_replace("<div class=author>-</div>", "", $this->message);
        return $this->message;
    }

    /**
     * Get the format of the message text.
     *
     * @return int Message format
     */
    public function get_messageformat() {
        return $this->messageformat;
    }

    /**
     * Get the type of message, news or event.
     *
     * @return int Message type (self::MESSAGETYPE_NEWS for news items and self::MESSAGETYPE_EVENT for events)
     */
    public function get_messagetype() {
        return $this->messagetype;
    }

    /**
     * Get the publication date of the message.
     *
     * @return int Message published date (seconds since start of epoch)
     */
    public function get_messagedate() {
        return $this->messagedate;
    }

    /**
     * Should the message repeat when restored to a new course?
     *
     * @return boolean Whether message is repeated on course restore
     */
    public function get_messagerepeat() {
        return $this->messagerepeat;
    }

    /**
     * Is this message visible?
     *
     * @return boolean Whether message is visible
     * @see is_visible_to_students
     */
    public function get_messagevisible() {
        return $this->messagevisible;
    }

    /**
     * Should the author's name be hidden?
     *
     * @return boolean Whether author name is hidden
     */
    public function get_hideauthor() {
        return $this->hideauthor;
    }

    /**
     * Get the publication status of the message.
     *
     * @return int Publish status (0=Immediately, 1=At specified date, 2=Already published)
     */
    public function get_publish() {
        return $this->publish;
    }

    /**
     * Get the author's User ID.
     *
     * @return int Internal id of message author
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * Get the group ID of the message.
     *
     * @return int[] group ids of the message
     */
    public function get_groupids() {
        return $this->groupids;
    }

    /**
     * Get the last modified time for the message.
     *
     * @return int Time message record was last updated (seconds since epoch)
     */
    public function get_timemodified() {
        return $this->timemodified;
    }

    /**
     * Get the timestamp of the event.
     *
     * @return int The timestamp the event will occur
     */
    public function get_eventstart() {
        return $this->eventstart;
    }

    /**
     * Get the timestamp of 00:00 on the day the event will occur, adjusted for the user's timezone.
     *
     * Because "All day events" are stored as midnight on the day, users in a timezone behind the server's would see the start
     * date as the previous day.  This adjusts the timestamp to the same date within their timezone.
     *
     * @return int The timestamp for the event's start day, adjusted to the user's timezone.
     */
    public function get_eventstart_local() {
        global $USER;
        $eventdate = new \DateTime('now', \core_date::get_server_timezone_object());
        $eventdate->setTimestamp($this->eventstart);
        $localdate = new \DateTime('now', \core_date::get_user_timezone_object($USER));
        $localdate->setDate($eventdate->format('Y'), $eventdate->format('m'), $eventdate->format('d'));
        $localdate->setTime(0, 0);
        return $localdate->getTimestamp();
    }

    /**
     * Is this an all day event?
     *
     * An event happening at 00:00 server time with no end date is considered an all day event.
     *
     * @return bool Whether this message is an all-day event.
     */
    public function get_alldayevent() {
        $eventstart = new \DateTime('now', \core_date::get_server_timezone_object());
        $eventstart->setTimestamp((int) $this->eventstart);
        if (empty($this->eventend) && $eventstart->format('Hi') == '0000') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the timestamp of the event's end.
     *
     * @return int The timestamp the event will end
     */
    public function get_eventend() {
        return $this->eventend;
    }

    /**
     * Get the location of the event.
     *
     * @return string The event location
     */
    public function get_eventlocation() {
        return $this->eventlocation;
    }

    /**
     * Build mock-up of minimal user object from object-local data
     *
     * @return \stdClass Author user details
     */
    public function get_user() {
        if (empty($this->user->id)) {
            return null;
        } else {
            return clone($this->user);
        }
    }

    /**
     * Get the message description.
     *
     * @return string message description
     */
    public function get_imagedesc() {
        return $this->imagedesc;
    }

    /**
     * Is the message description not necessary?
     *
     * @return boolean Whether message is not necessary
     */
    public function get_imagedescnotnecessary() {
        return $this->imagedescnotnecessary;
    }

    /**
     * Set the message's visibility.
     *
     * @param boolean $visible Message visible
     */
    public function set_messagevisible($visible) {
        global $DB;
        $id = $this->get_id();
        $DB->set_field('block_news_messages', 'messagevisible', $visible, array('id' => $id));
        $this->messagevisible = $visible;
    }

    /**
     * Assess if message is visible to students (typically if get_messagevisible()
     * AND get_messagedate() is not in the future)
     *
     * @return boolean
     */
    public function is_visible_to_students() {
        if ($this->messagevisible && $this->messagedate <= time()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create new message
     *
     * @param object $data
     * @return int $id or false
     */
    public static function create($data) {
        global $DB, $USER, $COURSE;

        /* The message property, from the form editor is:
        Array (
            [text] => <p>mmmmmm</p>
            [format] => 1
            [itemid] => 12345
        )
        if an embedded image is present:
        Array (
            [text] => <p>mmmmm<img src="http://domain/moodle
                    /draftfile.php/13/user/draft/273782079/GL-280.jpg"
                    alt="" width="125" height="94" />nnnn</p>
            [format] => 1
            [itemid] => 12345
        )
         (after  file_save_draft_area_files() this is:
                   <p>mmmmm<img src="@@PLUGINFILE@@/GL-280.jpg"
                     alt="" width="125" height="94" />nnnn</p>"  )

        insert_record() handles the message being an array by putting the word 'Array'
        in the message column, which is then overwritten by the following set_field() of
        the (rewritten) data->message['text'].

        MySQL generates a warning on an Array being passed in as a column string.
        So set the column as its text here, and overwrite with (rewritten) text
        later (if rewritten to accommodate embedded images).
        */
        // No extra handling/conversion for feed messages (->message is not an array).
        if (!empty($data->newsfeedid)) {
            $id = $DB->insert_record('block_news_messages', $data, true);
            // No logging for feed messages.
            return $id;
        }

        $tempmessage = $data->message;
        $data->message = $data->message['text'];

        $data = self::set_alldayevent($data);

        $id = $DB->insert_record('block_news_messages', $data, true);

        self::create_messagegroups($data, $id);

        // Save files.
        $context = \context_block::instance($data->blockinstanceid);
        if (!empty($data->messageimage)) {
            file_save_draft_area_files($data->messageimage, $context->id,
                    'block_news', 'messageimage', $id, array('subdirs' => 0));
        }
        if ($data->attachments) {
            file_save_draft_area_files($data->attachments, $context->id,
                    'block_news', 'attachment', $id, array('subdirs' => 0));
        }

        // Embedded images (let function check if imgs etc are present or not).
        $rwmessagetext = file_save_draft_area_files($tempmessage['itemid'],
                $context->id, 'block_news', 'message', $id, array('subdirs' => 0),
                $tempmessage['text']);

        if ($rwmessagetext != $tempmessage['text']) {
            $DB->set_field('block_news_messages', 'message', $rwmessagetext, array('id' => $id));
        }

        $event = \block_news\event\message_created::create(array(
                'objectid' => $id,
                'context' => \context_block::instance($data->blockinstanceid)
        ));
        $event->trigger();

        return $id;
    }

    /**
     * Save edited message
     *
     * @param object $data
     * @return boolean
     */
    public function edit($data) {
        global $DB;
        $data = self::set_alldayevent($data);
        $DB->update_record('block_news_messages', $data);

        $DB->delete_records('block_news_message_groups', ['messageid' => $data->id]);
        self::create_messagegroups($data, $data->id);

        // Save files.
        $context = \context_block::instance($data->blockinstanceid);
        if (!empty($data->messageimage)) {
            file_save_draft_area_files($data->messageimage, $context->id, 'block_news',
                    'messageimage', $data->id, array('subdirs' => 0));
        }
        if ($data->attachments) {
            file_save_draft_area_files($data->attachments, $context->id, 'block_news',
                    'attachment', $data->id, array('subdirs' => 0));
        }
        $data->message = file_save_draft_area_files($data->message['itemid'], $context->id,
                'block_news', 'message', $data->id, array('subdirs' => 0), $data->message['text']);
        $DB->set_field('block_news_messages', 'message', $data->message, array('id' => $data->id));

        $event = \block_news\event\message_updated::create(array(
                'objectid' => $data->id,
                'context' => \context_block::instance($data->blockinstanceid)
        ));
        $event->trigger();

        return true;
    }

    /**
     * Delete message
     */
    public function delete() {
        global $DB;
        $context = \context_block::instance($this->blockinstanceid);

        $event = \block_news\event\message_deleted::create(array(
                'objectid' => $this->id,
                'context' => $context
        ));
        $event->add_record_snapshot('block_news_messages',
                $DB->get_record('block_news_messages', array('id' => $this->id)));
        $event->trigger();

        $DB->delete_records('block_news_messages', array('id' => $this->id));

        // Delete files.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'block_news', false, $this->id);
    }

    /**
     * Return the submitted data with adjusted eventstart and eventend time if alldayevent was selected.
     *
     * @param \stdClass $data The submitted form data
     * @return \stdClass
     */
    private static function set_alldayevent($data) {
        if (!empty($data->messagetype) && $data->messagetype == self::MESSAGETYPE_EVENT && $data->alldayevent) {
            $eventstart = new \DateTime('now', \core_date::get_server_timezone_object());
            $eventstart->setTimestamp($data->eventstart);
            $eventstart->setTime(0, 0);
            $data->eventstart = $eventstart->getTimestamp();
            $data->eventend = null;
        }
        return $data;
    }

    /**
     * Create message group records
     *
     * @param \stdClass $data Submitted form data, contaning groupids array.
     * @param int $id message record ID.
     */
    private static function create_messagegroups($data, $id) {
        global $DB;
        if (!empty($data->groupids)) {
            foreach ($data->groupids as $groupid) {
                if (!empty($groupid)) {
                    $DB->insert_record('block_news_message_groups', (object) ['messageid' => $id, 'groupid' => $groupid]);
                }
            }
        }
    }
}
