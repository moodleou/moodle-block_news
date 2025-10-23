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

/**
 * Event generated when a news message is viewed.
 *
 * @package block_news
 * @copyright 2025 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_viewed extends \core\event\base {
    #[\Override]
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('eventmessage_viewed', 'block_news');
    }

    #[\Override]
    public function get_description(): string {
        return "The user with id '$this->userid' has viewed the news message with the id '{$this->other['mid']}'" .
            " in the course with id '$this->courseid'.";
    }

    #[\Override]
    public function get_url(): \moodle_url {
        return new \moodle_url('/blocks/news/message.php', ['m' => $this->other['mid']]);
    }
}
