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
 * Renderer code for messages in full and short (block) layouts.
 *
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Full message class (for display in the single message or all messages pages).
 *
 * @package block_news
 */
class block_news_message_full implements renderable {

    /**
     * Build full message data
     *
     * @param block_news_message $bnm
     * @param integer $previd
     * @param integer $nextid
     * @param block_news_system $bns
     * @param string $mode
     * @param array $images List of images for this block, keyed by message ID.
     */
    public function __construct($bnm, $previd, $nextid, $bns, $mode, array $images = []) {
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

        $this->message = $bnm->get_message();
        $this->messagedate = userdate($bnm->get_messagedate(),
            get_string('dateformat', 'block_news'));
        $this->messagevisible = $bnm->get_messagevisible();
        $usr = $bnm->get_user();
        if ($bnm->get_hideauthor() || $usr == null) {
            $this->author = '';
        } else {
            $this->author = fullname($usr);
        }

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

        if ($mode == 'one') { // Single message.
            if ($previd == -1) {
                $this->prevurl = 'end';
            } else {
                $this->prevurl = $CFG->wwwroot . '/blocks/news/message.php?m=' . $previd;
            }

            if ($nextid == -1) {
                $this->nexturl = 'end';
            } else {
                $this->nexturl = $CFG->wwwroot.'/blocks/news/message.php?m=' . $nextid;
            }
        } else if ($mode == 'all') { // All messages - dont display prev/next links.
            $this->prevurl = null;
            $this->nexturl = null;
        } else {
            print_error('errorinvalidmode', 'block_news', $mode);
        }

        // Context for access checks.
        $blockcontext = context_block::instance($bnm->get_blockinstanceid());

        // If a feed message (newsfeedid != 0) dont show edit etc icons.
        if ($bnm->get_newsfeedid() == 0) {
            if (has_capability('block/news:hide', $blockcontext)) {
                $this->hideicon = new pix_icon('t/' . $this->showhideact, $this->showhideact);
                    // Eg 't/hide', 'hide'.
                $this->hideurl = $CFG->wwwroot . '/blocks/news/message.php?m=' . $bnm->get_id()
                    . '&action=hide&mode=' . $mode;
            }

            if (has_capability('block/news:add', $blockcontext)) {
                $this->editicon = new pix_icon('t/edit',
                        get_string('edit', 'block_news', $bnm->get_title()));
                $this->editurl = $CFG->wwwroot . '/blocks/news/edit.php?m=' . $bnm->get_id()
                     . '&mode=' . $mode;
            }

            if (has_capability('block/news:delete', $blockcontext)) {
                $this->deleteicon = new pix_icon('t/delete',
                        get_string('delete', 'block_news', $bnm->get_title()));
                $this->deleteurl = $CFG->wwwroot.'/blocks/news/message.php?m=' . $bnm->get_id()
                    . '&action=delete&mode=' . $mode;
            }
        }

        // For group indication.
        $this->groupindication = '';
        if (has_any_capability(array('block/news:delete', 'block/news:add', 'block/news:edit'), $blockcontext)) {
            $this->groupindication = $bns->get_group_indication($bnm);
        }

        // For attachments.
        $this->blockinstanceid = $bnm->get_blockinstanceid();
        $this->messageformat = $bnm->get_messageformat();
        $this->id = $bnm->get_id();
        if (array_key_exists($this->id, $images)) {
            $image = $images[$this->id];
            $this->imageinfo = $image->get_imageinfo();
            $pathparts = array('/pluginfile.php', $blockcontext->id, 'block_news',
                    'messageimage', $this->id, $image->get_filename());
            $this->imageurl = new moodle_url(implode('/', $pathparts));
        }

    }
}


/**
 * Short message class (for display in the block).
 *
 * @package block_news
 */
class block_news_message_short implements renderable, templatable {

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
    /** @var moodle_url The URL of the thumbnail image */
    public $thumburl;
    /** @var string The message text with formatting and filtering applied */
    public $formattedmessage;
    /** @var int The width of the thumbnail image in pixels */
    public $thumbwidth;
    /** @var int The height of the thumbnail image in pixels */
    public $thumbheight;
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

    /**
     * Build short message data
     *
     * @param block_news_message $bnm
     * @param block_news_system $bns
     * @param int $summarylength Length of text displayed (0 = none)
     * @param int $count Sequence, eg 1 is first message in the block
     * @param array $thumbnails Thumbnail images for all messages, keyed by message ID.
     */
    public function __construct($bnm, $bns, $summarylength, $count, array $thumbnails = []) {
        global $CFG, $USER;

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
        $blockcontext = context_block::instance($bnm->get_blockinstanceid());

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
            $this->thumburl = new moodle_url(implode('/', $pathparts));
        }

        $this->messagetype = $bnm->get_messagetype();
        $this->fulleventdate = '';
        if ($this->messagetype == block_news_message::MESSAGETYPE_EVENT) {
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
            $this->message = shorten_text(strip_tags($bnm->get_message()), $summarylength);
        } else {
            $this->message = '';
        }
    }

    public function export_for_template(renderer_base $output) {
        if ($this->messagetype == block_news_message::MESSAGETYPE_EVENT) {
            $this->eventday = strftime('%d', $this->eventstart);
            $this->eventmonth = strftime('%b', $this->eventstart);
            $this->eventdatetime = strftime($this->eventdatetimeformat, $this->eventstart);
        } else {
            $this->thumbwidth = $this->thumbinfo['width'];
            $this->thumbheight = $this->thumbinfo['height'];
        }
        $this->formattedmessage = format_text($this->message, $this->messageformat);
        return $this;
    }
}


/**
 * Main renderer.
 *
 * @package block_news
 */
class block_news_renderer extends plugin_renderer_base {

    /**
     * Wrapper hook for news messages start
     */
    public function open_news_block_custom_wrapper() {
        return '';
    }

    /**
     * Wrapper hook for news messages end
     */
    public function close_news_block_custom_wrapper() {
        return '';
    }

    /**
     * Return HTML for the heading section of a news block page (all.php/message.php)
     *
     * @param block_news_system $bns Block news instance record
     * @param string $title Page title
     * @param boolean $showfeed Show the subscribe to RSS feed link?
     * @param boolean $canmanage User can add message?
     * @return string
     */
    public function render_message_page_header($bns, $title, $showfeed, $canmanage) {
        $head = $this->render_message_page_title($title);
        if ($showfeed || $canmanage) {
            $head .= $this->output->container_start('block_news_top');
            if ($canmanage) {
                $head .= $this->render_message_page_add($bns);
            }
            if ($showfeed) {
                $head .= $this->render_message_page_subscribe($bns);
            }
            $head .= $this->output->container_end();
        }

        return $head;
    }

    /**
     * Return HTMl for news block page title
     *
     * @param string $title
     * @param block_news_system $bns Block news instance record
     * @return string
     */
    public function render_message_page_title($title, $bns = null) {
        return $this->output->heading($title);
    }

    /**
     * Subscribe to news feed link on all page
     *
     * @param block_news_system $bns Block news instance record
     * @return string
     */
    public function render_message_page_subscribe($bns) {
        $pi = new pix_icon('i/rss', 'RSS');
        $feed = $this->output->container_start('', 'block_news_rss_all');
        $feed .= $this->output->action_icon($bns->get_feed_url(), $pi);
        $feed .= $this->output->container_end();
        return $feed;
    }

    /**
     * Create message link on all page
     *
     * @param block_news_system $bns Block news instance record
     * @return string
     */
    public function render_message_page_add($bns) {
        $params = array(
            'bi' => $bns->get_blockinstanceid(),
            'mode' => 'all'
        );
        $url = new moodle_url('/blocks/news/edit.php', $params);
        return $this->output->single_button($url, get_string('addnewmessage', 'block_news'), 'post',
                array('id' => 'block_news_add'));
    }

    /**
     * Generate HTML for full message
     * @param block_news_message_full $nmsg Renderable data
     * @return string HTML
     */
    protected function render_block_news_message_full(block_news_message_full $nmsg) {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        $blockcontext = context_block::instance($nmsg->blockinstanceid);
        $nmsg->message = file_rewrite_pluginfile_urls($nmsg->message, 'pluginfile.php',
            $blockcontext->id, 'block_news', 'message', $nmsg->id);

        $out = '';

        // Link to previous message (outside box).
        if ($nmsg->prevurl && $nmsg->prevurl !== 'end') {
            $out .= $this->output->container_start('prevlink');
            $out .= link_arrow_left(get_string('rendermsgprev', 'block_news'),
                    $nmsg->prevurl);
            $out .= $this->output->container_end();
        }

        // Start main message box.
        $out .= $this->output->container_start('main');
        $out .= $this->output->box($nmsg->messagedate, 'messagedate ' . $nmsg->classes);
        $out .= $this->output->box(format_string($nmsg->title), 'title ' . $nmsg->classes);

        if (!empty($nmsg->link)) {
            $out .= $this->output->box_start('link');
            $out .= $this->output->action_link($nmsg->link, $nmsg->link);
            $out .= $this->output->box_end('link');
        }

        $out .= $this->output->container(format_string($nmsg->author), 'author ' . $nmsg->classes);

        if (!empty($nmsg->imageurl)) {
            $imageattrs = array(
                'src' => $nmsg->imageurl->out(),
                'alt' => '',
                'width' => $nmsg->imageinfo['width'],
                'height' => $nmsg->imageinfo['height']
            );
            $out .= $this->output->box(html_writer::empty_tag('img', $imageattrs), 'messageimage ' . $nmsg->classes);
        }
        $out .= $this->output->container(format_text($nmsg->message, $nmsg->messageformat),
            'message ' . $nmsg->classes);

        // Attached files.
        $fs = get_file_storage();
        $files = $fs->get_area_files($blockcontext->id, 'block_news', 'attachment',
            $nmsg->id, "timemodified", false);

        if ($files) {
            $out .= html_writer::start_tag('div', array('class' => 'news-message-attachments'));
            $out .= html_writer::tag('h3', get_string('msgedithlpattach', 'block_news'), null);
            $out .= html_writer::start_tag('ul');
            foreach ($files as $file) {
                $out .= html_writer::start_tag('li');
                $filename = $file->get_filename();
                $mimetype = $file->get_mimetype();
                $iconimage = html_writer::empty_tag('img',
                        array('src' => $this->output->pix_url(file_mimetype_icon($mimetype)),
                        'alt' => $mimetype, 'class' => 'icon'));
                $path = moodle_url::make_pluginfile_url($blockcontext->id, 'block_news',
                    'attachment', $nmsg->id, '/', $filename, true);
                $out .= html_writer::tag('a', $iconimage, array('href' => $path));
                $out .= html_writer::tag('a', s($filename), array('href' => $path));
                $out .= html_writer::end_tag('li');
            }
            $out .= html_writer::end_tag('ul');
            $out .= html_writer::end_tag('div');
        }

        $out .= $this->output->box_start('editicons');
        if (isset($nmsg->hideicon) && isset($nmsg->hideurl)) {
            $out .= $this->output->container_start('hideurl');
            $out .= $this->output->action_icon($nmsg->hideurl, $nmsg->hideicon);
            $out .= $this->output->container_end();
        }
        if (isset($nmsg->editicon) && isset($nmsg->editurl)) {
            $out .= $this->output->container_start('editurl');
            $out .= $this->output->action_icon($nmsg->editurl, $nmsg->editicon);
            $out .= $this->output->container_end();
        }
        if (isset($nmsg->deleteicon) && isset($nmsg->deleteurl)) {
            $out .= $this->output->container_start('deleteurl');
            $out .= $this->output->action_icon($nmsg->deleteurl, $nmsg->deleteicon);
            $out .= $this->output->container_end();
        }
        $out .= $this->output->box_end();

        $out .= $this->output->box_start('notes');
        foreach ($nmsg->notes as $note) {
            $out .= $this->output->container($note, 'note ');
        }
        $out .= $this->output->box_end();
        $out .= $this->output->container($nmsg->groupindication, 'block_news_group_indication');
        $out .= $this->output->container_end();
        if ($nmsg->nexturl && $nmsg->nexturl !== 'end') {
            $out .= $this->output->container_start('nextlink');
            $out .= link_arrow_right(get_string('rendermsgnext', 'block_news'),
                    $nmsg->nexturl);
            $out .= $this->output->container_end();
        }

        return $this->output->container($out, 'block_news_message ' . $nmsg->classes);
    }

    /**
     * @param string $date News date
     * @param string $title News title
     * @return string HTML code for displaying the news heading.
     */
    protected function render_block_news_message_heading($date, $title) {
        return $this->output->heading($date . ' ' . $title, 3);
    }

    /**
     * @param moodle_url $url URL of the view link
     * @return string HTML code for displaying the view link.
     */
    protected function render_block_news_message_link($url, $extralinktext = '') {
        return $this->output->action_link($url,
                get_string('rendermsgview', 'block_news') . $extralinktext);
    }
    /**
     * @param block_news_message_short $nmsg Renderable data
     * @return string HTML
     */
    protected function render_block_news_message_short(block_news_message_short $nmsg) {
        $context = $nmsg->export_for_template($this->output);
        if ($nmsg->messagetype == block_news_message::MESSAGETYPE_EVENT) {
            $template = 'block_news/event_short';
        } else {
            $template = 'block_news/message_short';
        }
        return $this->output->render_from_template($template, $context);
    }

    /**
     * Adds (new) to the News block title if there are new messages
     * @param string $newstitle News block title
     * @return string text
     */
    public function render_block_news_new_messages($newstitle) {
        return $newstitle . ' ('.get_string('new', 'block_news') . ')';
    }

    /**
     * Renders the 'view all' link at bottom of block.
     *
     * @param int $blockinstanceid Instance id of block
     */
    public function render_view_all($blockinstanceid) {
        return $this->action_link(
                new moodle_url('/blocks/news/all.php', array('bi' => $blockinstanceid)),
                get_string('msgblockviewall', 'block_news'),
                null,
                array('title' => get_string('msgblockviewallalt', 'block_news')));
    }

    /**
     * Renders the 'add' link at bottom of block.
     *
     * @param int $blockinstanceid Instance id of block
     */
    public function render_add($blockinstanceid) {
        return $this->action_link(
                new moodle_url('/blocks/news/edit.php', array('bi' => $blockinstanceid)),
                get_string('msgblockadd', 'block_news'),
                null, array('title' => get_string('msgblockaddalt', 'block_news')));
    }

    /**
     * Called prior to header outputs
     * Should not return anything
     *
     * @param stdClass $bns
     */
    public function pre_header($bns) {
        // Does nothing as standard.
    }
}
