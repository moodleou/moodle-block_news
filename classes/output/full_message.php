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
 * Full message class.
 *
 * @package block_news
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news\output;

defined('MOODLE_INTERNAL') || die();

use block_news\message;

/**
 * Full message class (for display in the single message or all messages pages).
 *
 * @package block_news
 */
class full_message extends renderable_message implements \templatable {

    /** @var string URL for the previous message */
    public $prevurl;
    /** @var string URL for the next message */
    public $nexturl;
    /** @var int Block instance ID */
    public $blockinstanceid;
    /** @var int Message ID */
    public $id;
    /** @var bool Are there visibility notes to display? */
    public $hasnotes;
    /** @var bool Are there attachements? */
    public $hasattachments;
    /** @var array Attached files */
    public $attachments;
    /** @var string Rendered "previous" link */
    public $prevlink;
    /** @var string Rendered "previous" link for mobile view*/
    public $prevlinkmobile;
    /** @var string Rendered "next" link */
    public $nextlink;
    /** @var string Rendered "next" link for mobile view*/
    public $nextlinkmobile;
    /** @var bool True if this is a news message, false if it's an event */
    public $isnews;
    /** @var string Source URL for the message (if from a feed) */
    public $link;
    /** @var \pix_icon Icon to display with link */
    public $linkicon;
    /** @var array Files array to reduce calling get_file_storage() */
    public $allfiles;

    /**
     * Build full message data
     *
     * @param message $bnm
     * @param integer $previd
     * @param integer $nextid
     * @param \block_news\system $bns
     * @param string $mode
     * @param array $images List of images for this block, keyed by message ID.
     * @param bool $webserviceurls Output URLs for web services, instead of browser links?
     * @param array $files List of files for this block, keyed by message ID.
     */
    public function __construct(message $bnm, $previd, $nextid, $bns, $mode, array $images = [],
            $webserviceurls = false, array $files = []) {
        global $CFG;

        $this->classes = '';
        $this->notes = array();
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

        $this->viewlink = $bnm->get_link();

        $blockcontext = \context_block::instance($bnm->get_blockinstanceid());
        if ($webserviceurls) {
            $rewritefile = 'webservice/pluginfile.php';
        } else {
            $rewritefile = 'pluginfile.php';
        }
        $this->message = file_rewrite_pluginfile_urls($bnm->get_message(), $rewritefile,
                $blockcontext->id, 'block_news', 'message', $bnm->get_id());
        $this->messagedate = userdate($bnm->get_messagedate(),
                get_string('dateformat', 'block_news'));
        $this->messagevisible = $bnm->get_messagevisible();
        $this->messagetype = $bnm->get_messagetype();
        if ($this->messagetype == message::MESSAGETYPE_EVENT) {
            $this->set_event_data($bnm);
        }
        $usr = $bnm->get_user();
        if ($bnm->get_hideauthor() || $usr == null) {
            $this->author = '';
        } else {
            $this->author = fullname($usr);
        }

        $this->set_visibility_attributes($bnm);

        if ($mode == 'one') { // Single message.
            if ($previd == -1) {
                $this->prevurl = 'end';
            } else {
                $this->prevurl = $CFG->wwwroot . '/blocks/news/message.php?m=' . $previd;
            }

            if ($nextid == -1) {
                $this->nexturl = 'end';
            } else {
                $this->nexturl = $CFG->wwwroot . '/blocks/news/message.php?m=' . $nextid;
            }
        } else {
            if ($mode == 'all') { // All messages - dont display prev/next links.
                $this->prevurl = null;
                $this->nexturl = null;
            } else {
                print_error('errorinvalidmode', 'block_news', $mode);
            }
        }

        // Context for access checks.
        $blockcontext = \context_block::instance($bnm->get_blockinstanceid());

        $this->set_edit_links($bnm, $blockcontext, $mode);

        // For group indication.
        $this->groupindication = '';
        if (has_any_capability(array('block/news:delete', 'block/news:add', 'block/news:edit'), $blockcontext)) {
            $this->groupindication = $bns->get_group_indication($bnm);
        }

        // For attachments.
        $this->webserviceurls = $webserviceurls;
        $this->blockinstanceid = $bnm->get_blockinstanceid();
        $this->messageformat = $bnm->get_messageformat();
        $this->id = $bnm->get_id();
        if (array_key_exists($this->id, $images)) {
            $image = $images[$this->id];
            $this->imageinfo = $image->get_imageinfo();
            $this->imagedesc = $bnm->get_imagedesc();
            if ($this->webserviceurls) {
                $this->imageurl = \moodle_url::make_webservice_pluginfile_url($blockcontext->id, 'block_news',
                    'messageimage', $this->id, '/', $image->get_filename())->out(false);
            } else {
                $this->imageurl = \moodle_url::make_pluginfile_url($blockcontext->id, 'block_news',
                    'messageimage', $this->id, '/', $image->get_filename())->out(false);
            }
        }

        $this->allfiles = $files;
    }

    public function export_for_template(\renderer_base $output) {
        $blockcontext = \context_block::instance($this->blockinstanceid);
        $messagewithfiles = file_rewrite_pluginfile_urls($this->message, 'pluginfile.php',
                   $blockcontext->id, 'block_news', 'message', $this->id);
        $this->formattedmessage = format_text($messagewithfiles, $this->messageformat);
        if ($this->messagetype == message::MESSAGETYPE_EVENT) {
            $this->isnews = false;
            $this->eventday = strftime('%d', $this->eventstart);
            $this->eventmonth = strftime('%b', $this->eventstart);
            $this->eventdatetime = strftime($this->eventdatetimeformat, $this->eventstart);
            $this->classes .= ' block_news_event ';
            $fullnextlabel = 'rendereventnext';
            $fullprevlabel = 'rendereventprev';
        } else {
            $this->isnews = true;
            if (isset($this->imageinfo)) {
                $this->imagewidth = $this->imageinfo['width'];
                $this->imageheight = $this->imageinfo['height'];
            }
            $fullnextlabel = 'rendermsgnext';
            $fullprevlabel = 'rendermsgprev';
        }
        $this->formattedmessage = format_text($this->message, $this->messageformat);
        if ($this->nexturl && $this->nexturl != 'end') {
            $this->nextlink = link_arrow_right(get_string($fullnextlabel, 'block_news'),
                    $this->nexturl, false, 'block-news-desktop-nextprev');
            $this->nextlinkmobile = link_arrow_right(get_string('next'),
                    $this->nexturl, false, 'block-news-mobile-nextprev');
        }
        if ($this->prevurl && $this->prevurl != 'end') {
            $this->prevlink = link_arrow_left(get_string($fullprevlabel, 'block_news'),
                    $this->prevurl, false, 'block-news-desktop-nextprev');
            $this->prevlinkmobile = link_arrow_left(get_string('previous'),
                    $this->prevurl, false, 'block-news-mobile-nextprev');
        }

        $fs = get_file_storage();

        if ($this->allfiles && array_key_exists($this->id, $this->allfiles)) {
            $files = $this->allfiles[$this->id];
        } else {
            $files = $fs->get_area_files($blockcontext->id, 'block_news', 'attachment',
                    $this->id, "timemodified", false);
        }

        if ($files) {
            foreach ($files as $file) {
                $filename = $file->get_filename();
                $mimetype = $file->get_mimetype();
                $icon = new \pix_icon(file_mimetype_icon($mimetype), $mimetype);
                if ($this->webserviceurls) {
                    $url = \moodle_url::make_webservice_pluginfile_url($blockcontext->id, 'block_news',
                        'attachment', $this->id, '/', $filename, true);
                } else {
                    $url = \moodle_url::make_pluginfile_url($blockcontext->id, 'block_news',
                        'attachment', $this->id, '/', $filename, true);
                }
                $attachment = (object) [
                    'filename' => $filename,
                    'icon' => $icon->export_for_template($output),
                    'iconsrc' => $output->image_url($icon->pix, $icon->component)->out(false),
                    'iconalt' => $mimetype,
                    'url' => $url->out()
                ];
                $this->attachments[] = $attachment;
            }
        }
        $this->hasattachments = !empty($this->attachments);
        $this->hasnotes = !empty($this->notes);
        $linkicon = new \pix_icon('icon', '', 'mod_url', ['class' => 'iconlarge']);
        $this->linkicon = $linkicon->export_for_template($output);
        $this->export_actions_for_template($output);
        return $this;
    }
}
