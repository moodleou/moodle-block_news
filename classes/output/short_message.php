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
    const ALLOW_TAGS = '<a><br><p><div><span><ol><ul><li><strong><b><i><em><h1><h2><h3><h4><h5><h6><u><strike><sub><sup>';
    /**
     * Build short message data
     *
     * @param message $bnm
     * @param \block_news\system $bns
     * @param int $summarylength Length of text displayed (0 = none)
     * @param int $count Sequence, eg 1 is first message in the block
     * @param array $thumbnails Thumbnail images for all messages, keyed by message ID.
     * @param array $images Images for all messages, keyed by message ID.
     * @param null|string $mode Render mode, 'all' if rendering for the "View all" page.
     */
    public function __construct(message $bnm, $bns, $summarylength, $count, array $thumbnails = [], $images = [], $mode = null) {
        global $CFG;

        $this->classes = '';
        if ($bns->get_hidetitles()) {
            $this->title = '';
        } else {
            $this->title = $bnm->get_title();
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
        if (array_key_exists($bnm->get_id(), $images)) {
            $image = $images[$bnm->get_id()];
            $this->imageinfo = $image->get_imageinfo();
            $pathparts = array('/pluginfile.php', $blockcontext->id, 'block_news',
                    'messageimage', $bnm->get_id(), $image->get_filename());
            $this->imageurl = new \moodle_url(implode('/', $pathparts));
        }

        $this->messagetype = $bnm->get_messagetype();
        $this->fulleventdate = '';
        if ($this->messagetype == message::MESSAGETYPE_EVENT) {
            $this->set_event_data($bnm);
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
            $this->imagewidth = $this->imageinfo['width'];
            $this->imageheight = $this->imageinfo['height'];
        }
        $this->formattedmessage = format_text($this->message, $this->messageformat);
        $this->export_actions_for_template($output);
        return $this;
    }
}
