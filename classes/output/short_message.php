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
 * Short message class.
 *
 * @package block_news
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news\output;

defined('MOODLE_INTERNAL') || die();

use block_news\message;

/**
 * Short message class (for display in the block).
 *
 * @package block_news
 */
class short_message extends renderable_message implements \templatable {

    /** @var string Tags allowed for block news short message */
    const ALLOW_TAGS = '<a><br><p><div><span><ol><ul><li><strong><b><i><em>';

    /** @var array Action icons and URLs */
    public $actions;

    /** @var bool True if $actions isn't empty */
    public $hasactions;

    /**
     * Build short message data
     *
     * @param message $bnm
     * @param \block_news\system $bns
     * @param int $summarylength Length of text displayed (0 = none)
     * @param int $count Sequence, eg 1 is first message in the block
     * @param array $thumbnails Thumbnail images for all messages, keyed by message ID.
     * @param null|string $mode Render mode, 'all' if rendering for the "View all" page.
     */
    public function __construct(message $bnm, $bns, $summarylength, $count, array $thumbnails = [], $mode = null) {
        global $CFG;

        $this->classes = '';
        if ($bns->get_hidetitles()) {
            $this->title = '';
        } else {
            $this->title = $bnm->get_title();
        }

        if ($bns->get_hidelinks()) {
            $this->link = '';
        } else {
            $this->link = $bnm->get_link();
        }

        $this->viewlink = $CFG->wwwroot . '/blocks/news/message.php?m=' . $bnm->get_id();

        $this->messagedate = userdate($bnm->get_messagedate(),
                get_string('dateformat', 'block_news'));
        $this->messagevisible = $bnm->get_messagevisible();
        $this->messageformat = $bnm->get_messageformat();

        $usr = $bnm->get_user();
        if ($bnm->get_hideauthor() || $usr == null) {
            $this->author = '';
        } else {
            $this->author = fullname($usr);
        }

        // Context for access checks.
        $blockcontext = \context_block::instance($bnm->get_blockinstanceid());

        if ($mode === 'all') {
            $this->set_visibility_attributes($bnm);
            $this->set_edit_links($bnm, $blockcontext, $mode);
        }

        // For group indication.
        $this->groupindication = '';
        if (has_any_capability(array('block/news:delete', 'block/news:add', 'block/news:edit'), $blockcontext)) {
            $this->groupindication = $bns->get_group_indication($bnm);
        }

        if (array_key_exists($bnm->get_id(), $thumbnails)) {
            $thumb = $thumbnails[$bnm->get_id()];
            $this->thumbinfo = $thumb->get_imageinfo();
            $pathparts = array('/pluginfile.php', $blockcontext->id, 'block_news',
                    'thumbnail', $bnm->get_id(), $thumb->get_filename());
            $this->thumburl = new \moodle_url(implode('/', $pathparts));
        }

        $this->messagetype = $bnm->get_messagetype();
        $this->fulleventdate = '';
        if ($this->messagetype == message::MESSAGETYPE_EVENT) {
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

        if ($summarylength) {
            $this->message = shorten_text(strip_tags($bnm->get_message(), self::ALLOW_TAGS), $summarylength);
            $this->message = file_rewrite_pluginfile_urls($this->message, 'pluginfile.php',
                    $blockcontext->id, 'block_news', 'message', $bnm->get_id());
        } else {
            $this->message = '';
        }
    }

    public function export_for_template(\renderer_base $output) {
        if ($this->messagetype == message::MESSAGETYPE_EVENT) {
            $this->eventday = strftime('%d', $this->eventstart);
            $this->eventmonth = strftime('%b', $this->eventstart);
            $this->eventdatetime = strftime($this->eventdatetimeformat, $this->eventstart);
        } else {
            $this->thumbwidth = $this->thumbinfo['width'];
            $this->thumbheight = $this->thumbinfo['height'];
        }
        $this->formattedmessage = format_text($this->message, $this->messageformat);
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
        return $this;
    }
}
