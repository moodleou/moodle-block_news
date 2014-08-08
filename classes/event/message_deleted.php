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

namespace block_news\event;

defined('MOODLE_INTERNAL') || die;

/**
 * Event generated when news message is deleted.
 *
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_deleted extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'block_news_messages';
    }

    public static function get_name() {
        return get_string('eventmessage_deleted', 'block_news');
    }

    public function get_description() {
        return "User {$this->userid} has deleted a news message with id {$this->objectid}.";
    }

    public function get_url() {
        return new \moodle_url('/blocks/news/all.php', array('bi' => $this->context->instanceid));
    }

    public function get_legacy_logdata() {
        return array($this->courseid, 'block_news', 'delete message', '',
                $this->context->instanceid . ' ' . $this->objectid, 0);
    }
}
