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
 * Output component for View All page.
 *
 * @package    block_news
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news\output;

defined('MOODLE_INTERNAL') || die();

use block_news\message;

class view_page implements \renderable, \templatable {

    /** @var string Configured title for the block, used for the page heading. */
    public $title;
    /** @var full_message */
    public $message;

    /**
     * Construct the view_page widget with the title ana message.
     *
     * @param full_message $message The message to display.
     */
    public function __construct(full_message $message) {
        if ($message->messagetype == message::MESSAGETYPE_NEWS) {
            $this->title = get_string('news', 'block_news');
        } else {
            $this->title = get_string('events', 'block_news');
        }
        $this->message = $message;
    }

    /**
     * Return the context data for the template.
     *
     * @param \renderer_base $output
     * @return object
     */
    public function export_for_template(\renderer_base $output) {
        return (object) [
            'title' => $this->title,
            'message' => $this->message->export_for_template($output)
        ];
    }
}
