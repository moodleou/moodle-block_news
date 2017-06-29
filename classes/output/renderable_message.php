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
 * Abstract renderable class for news messages.
 *
 * @package block_news
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news\output;

defined('MOODLE_INTERNAL') || die();

use block_news\message;

/**
 * Base class for rendering news messages.
 *
 * @package block_news
 */
abstract class renderable_message implements \renderable {
    /** @var string Title of message */
    public $title;
    /** @var string Additional CSS classes for message wrapper */
    public $classes;
    /** @var string Source URL for the message (if from a feed) */
    public $link;
    /** @var string URL to view the whole message */
    public $viewlink;
    /** @var string Message text */
    public $message;
    /** @var string Message publication date */
    public $messagedate;
    /** @var bool Is the message visibile? */
    public $messagevisible;
    /** @var int Message text format */
    public $messageformat;
    /** @var int Message type - news or event */
    public $messagetype;
    /** @var string Name of author (if shown) */
    public $author;
    /** @var string Group visibility indication (if shown) */
    public $groupindication;
    /** @var array imageinfo for thumbnail, if there is one */
    public $thumbinfo;
    /** @var \moodle_url The URL of the thumbnail image */
    public $thumburl;
    /** @var string The message text with formatting and filtering applied */
    public $formattedmessage;
    /** @var int The width of the thumbnail image in pixels */
    public $thumbwidth;
    /** @var int The height of the thumbnail image in pixels */
    public $thumbheight;
    /** @var array Info for message image */
    public $imageinfo;
    /** @var \moodle_url URL for message image */
    public $imageurl;
    /** @var int Image width, for templates. */
    public $imagewidth;
    /** @var int Image height, for templates. */
    public $imageheight;
    /** @var int The timestamp for the start of the event */
    public $eventstart;
    /** @var int The timestamp for the end of the event */
    public $eventend;
    /** @var string The ISO8601 date for the start of the event (and time if it's not an all-day event) */
    public $eventdatetime;
    /** @var string The format string to format eventstart into eventdatetime */
    public $eventdatetimeformat;
    /** @var string The 2-digit day of the event's start date */
    public $eventday;
    /** @var string The shorthand month of the event's start date */
    public $eventmonth;
    /** @var string The location of the event */
    public $eventlocation;
    /** @var string The full formatted description of the event's start (and end) date (and time). */
    public $fulleventdate;
    /** @var array Additional notes if the message has restricted display */
    public $notes;
    /** @var string Show/Hide action to display the correct icon. */
    public $showhideact;
    /** @var \pix_icon Icon for "hide" link */
    public $hideicon;
    /** @var \moodle_url URL for "hide link */
    public $hideurl;
    /** @var \pix_icon Icon for "edit" link */
    public $editicon;
    /** @var \moodle_url URL for "edit" link */
    public $editurl;
    /** @var \pix_icon Icon for "delete" link */
    public $deleteicon;
    /** @var \moodle_url URL for "delete" link */
    public $deleteurl;
    /** @var array Action icons and URLs */
    public $actions;
    /** @var bool True if $actions isn't empty */
    public $hasactions;


    /**
     * Set the edit icons and URLs based on the current user's permissions.
     *
     * @param message $bnm
     * @param \context $blockcontext
     * @param string $mode
     */
    protected function set_edit_links(message $bnm, \context $blockcontext, $mode) {
        // If a feed message (newsfeedid != 0) dont show edit etc icons.
        if (empty($bnm->get_newsfeedid())) {
            if (has_capability('block/news:hide', $blockcontext)) {
                $this->hideicon = new \pix_icon('t/' . $this->showhideact, $this->showhideact);
                // Eg 't/hide', 'hide'.
                $this->hideurl = new \moodle_url('/blocks/news/message.php',
                        ['m' => $bnm->get_id(), 'action' => 'hide', 'mode' => $mode]);
            }

            if (has_capability('block/news:add', $blockcontext)) {
                $this->editicon = new \pix_icon('t/edit',
                        get_string('edit', 'block_news', $bnm->get_title()));
                $this->editurl = new \moodle_url('/blocks/news/edit.php', ['m' => $bnm->get_id(), 'mode' => $mode]);
            }

            if (has_capability('block/news:delete', $blockcontext)) {
                $this->deleteicon = new \pix_icon('t/delete',
                        get_string('delete', 'block_news', $bnm->get_title()));
                $this->deleteurl = new \moodle_url('/blocks/news/message.php',
                        ['m' => $bnm->get_id(), 'action' => 'delete', 'mode' => $mode]);
            }
        }
    }

    /**
     * Set various attributes to display the message correctly depending on whether its visible to students.
     *
     * @param message $bnm
     */
    protected function set_visibility_attributes(message $bnm) {
        if ($bnm->is_visible_to_students()) {
            $this->classes .= ' msgvis';
            $this->showhideact = 'hide'; // Wrong way round.
        } else {
            $this->classes .= ' msghide';
            $this->showhideact = 'hide';

            if (!$bnm->get_messagevisible()) {
                $this->notes[] = get_string('rendermsghidden', 'block_news');
                $this->showhideact = 'show';
            }
            if ($bnm->get_messagedate() > time()) {
                $this->notes[] = get_string('rendermsgfuture', 'block_news',
                        userdate($bnm->get_messagedate(), get_string('dateformatlong', 'block_news')));
            }
        }
    }

    /**
     * Set fields specific to an event message.
     *
     * @param message $bnm
     */
    protected function set_event_data(message $bnm) {
        $alldayevent = $bnm->get_alldayevent();
        if ($alldayevent) {
            $this->eventstart = $bnm->get_eventstart_local();
            $this->eventdatetimeformat = '%F';
            $this->eventend = null;
        } else {
            $this->eventstart = $bnm->get_eventstart();
            $this->eventend = $bnm->get_eventend();
            $this->eventdatetimeformat = '%FT%T';
        }
        $this->eventlocation = $bnm->get_eventlocation();

        if ($alldayevent) {
            $this->fulleventdate = userdate($this->eventstart, get_string('strftimedaydate', 'langconfig'));
        } else {
            $eventtime = (object) ['start' => '', 'end' => ''];
            $eventtime->start = userdate($this->eventstart, get_string('strftimedaydatetime', 'langconfig'));
            if (userdate($this->eventstart, '%Y%m%d') === userdate($this->eventend, '%Y%m%d')) {
                $eventtime->end = userdate($this->eventend, get_string('strftimetime', 'langconfig'));
            } else {
                $eventtime->end = userdate($this->eventend, get_string('strftimedaydatetime', 'langconfig'));
            }
            $this->fulleventdate = get_string('fulleventdate', 'block_news', $eventtime);
        }
    }

    /**
     * Fill $this->actions with data for action icons.
     *
     * @param \renderer_base $output
     */
    protected function export_actions_for_template(\renderer_base $output) {
        $this->actions = [];
        if (isset($this->hideurl) && isset($this->hideicon)) {
            $this->actions[] = (object) ['icon' => $this->hideicon->export_for_template($output),
                    'url' => $this->hideurl->out(false)];
        }
        if (isset($this->editurl) && isset($this->editicon)) {
            $this->actions[] = (object) ['icon' => $this->editicon->export_for_template($output),
                    'url' => $this->editurl->out(false)];
        }
        if (isset($this->deleteurl) && isset($this->deleteicon)) {
            $this->actions[] = (object) ['icon' => $this->deleteicon->export_for_template($output),
                    'url' => $this->deleteurl->out(false)];
        }
        $this->hasactions = !empty($this->actions);
    }
}
