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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // It must be included from a Moodle page
}


/**
 * main message class
 * @package blocks
 * @subpackage news
 *
 */
class block_news_message {

    protected $id;
    protected $blockinstanceid;
    protected $newsfeedid;
    protected $title;
    protected $link;
    protected $message;
    protected $messageformat;
    protected $messagedate;
    protected $messagerepeat;
    protected $messagevisible;
    protected $hideauthor;
    protected $publish;
    protected $timemodified;
    protected $userid;
    protected $groupingid;

    protected $user;

    /**
     * Constructor
     *
     * @param int $id Message is
     * @return object block_news_message
     */
    public function __construct($mrec) {
        // assign the properties
        $this->user = new stdClass;
        foreach ((array)$mrec as $field => $value) {
            if (property_exists($this, $field)) {
                $this->{$field} = $value;
            } else if (strpos($field, 'u_') === 0) {
                $subfield = substr($field, 2);
                $this->user->{$subfield} = $value;
            }
        }
    }

    /**
     * @return integer Message id
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @return integer Block instance id
     */
    public function get_blockinstanceid() {
        return $this->blockinstanceid;
    }

    /**
     * @return integer News feed id
     */
    public function get_newsfeedid() {
        return $this->newsfeedid;
    }

    /**
     * @return string Message title
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * @return string Link to message on remote feed (if feed message)
     */
    public function get_link() {
        return $this->link;
    }

    /**
     * @return string Message text
     */
    public function get_message() {
        return $this->message;
    }

    /**
     * @return integer Message format
     */
    public function get_messageformat() {
        return $this->messageformat;
    }

    /**
     * @return integer Message published date (seconds since start of epoch)
     */
    public function get_messagedate() {
        return $this->messagedate;
    }

    /**
     * @return boolean Whether message is repeated on course restore
     */
    public function get_messagerepeat() {
        return $this->messagerepeat;
    }

    /**
     * @return boolean Whether message is visible
     * @see is_visible_to_students
     */
    public function get_messagevisible() {
        return $this->messagevisible;
    }

    /**
     * @return boolean Whether author name is hidden
     */
    public function get_hideauthor() {
        return $this->hideauthor;
    }

    /**
     * @return integer Publish status (0=Immediately, 1=At specified date, 2=Already published)
     */
    public function get_publish() {
        return $this->publish;
    }

    /**
     * @return integer Internal id of message author
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * @return integer grouping id of the message
     */
    public function get_groupingid() {
        return $this->groupingid;
    }

    /**
     * @return integer Time message record was last updated (seconds since epoch)
     */
    public function get_timemodified() {
        return $this->timemodified;
    }

    /**
     * Build mock-up of minimal user object from object-local data
     *
     * @return StdClass Author user details
     */
    public function get_user() {
        if (empty($this->user->id)) {
            return null;
        } else {
            return clone($this->user);
        }
    }

    /**
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
     * @return integer $id or false
     */
    public static function create($data) {
        global $DB, $USER, $COURSE;

        /* the message property, from the form editor is:
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
        later (if rewritten to accommodate embedded images)
        */

        // no extra handling/conversion for feed messages (->message is not an array)
        if ($data->newsfeedid != 0 ) {
            $id = $DB->insert_record('block_news_messages', $data, true);
            // No logging for feed messages.
            return $id;
        }

        $temp_message=$data->message;
        $data->message = $data->message['text'];

        $id = $DB->insert_record('block_news_messages', $data, true);

        // save files
        $context = context_block::instance($data->blockinstanceid);
        if ($data->attachments) {
            file_save_draft_area_files($data->attachments, $context->id,
                'block_news', 'attachment', $id, array('subdirs' => 0));
        }

        // embedded images (let function check if imgs etc are present or not)
        $rw_message_text = file_save_draft_area_files($temp_message['itemid'],
            $context->id, 'block_news', 'message', $id, array('subdirs' => 0),
            $temp_message['text']);

        if ($rw_message_text != $temp_message['text']) {
            $DB->set_field('block_news_messages', 'message', $rw_message_text, array('id' => $id));
        }

        $event = \block_news\event\message_created::create(array(
            'objectid' => $id,
            'context' => context_block::instance($data->blockinstanceid)
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
        global $DB, $USER, $COURSE;

        $DB->update_record('block_news_messages', $data);

        // save files.
        $context = context_block::instance($data->blockinstanceid);
        if ($data->attachments) {
            file_save_draft_area_files($data->attachments, $context->id, 'block_news',
             'attachment', $data->id, array('subdirs' => 0));
        }
        $data->message = file_save_draft_area_files($data->message['itemid'], $context->id,
             'block_news', 'message', $data->id, array('subdirs' => 0), $data->message['text']);
        $DB->set_field('block_news_messages', 'message', $data->message, array('id' => $data->id));

        $event = \block_news\event\message_updated::create(array(
            'objectid' => $data->id,
            'context' => context_block::instance($data->blockinstanceid)
        ));
        $event->trigger();

        return true;
    }

    /*
     * Delete message
     */
    public function delete() {
        global $DB, $USER, $COURSE;
        $context = context_block::instance($this->blockinstanceid);

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
        $fs->delete_area_files($context->id, 'block_news');
    }
}
