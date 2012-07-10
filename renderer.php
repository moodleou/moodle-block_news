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
 * Renderer code for messages in full and short (block) layouts
 *
 * @package    blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Full message class (for display in the single message or all messages pages)
 * @package    blocks
 * @subpackage news
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
     */
    public function __construct($bnm, $previd, $nextid, $bns, $mode) {
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
            $this->showhideact = 'hide'; // 'wrong way round'
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

        if ($mode == 'one') { // single message
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
        } else if ($mode == 'all') { // all messages - dont display prev/next links
            $this->prevurl = null;
            $this->nexturl = null;
        } else {
            print_error('errorinvalidmode', 'block_news', $mode);
        }

        // if a feed message (newsfeedid != 0) dont show edit etc icons
        if ($bnm->get_newsfeedid() == 0) {
            // context for access checks
            $blockcontext = get_context_instance(CONTEXT_BLOCK, $bnm->get_blockinstanceid());
            if (has_capability('block/news:hide', $blockcontext)) {
                $this->hideicon = new pix_icon('t/' . $this->showhideact, $this->showhideact);
                    // eg 't/hide', 'hide'
                $this->hideurl = $CFG->wwwroot . '/blocks/news/message.php?m=' . $bnm->get_id()
                    . '&action=hide&mode=' . $mode;
            }

            if (has_capability('block/news:add', $blockcontext)) {
                $this->editicon = new pix_icon('t/edit', 'edit');
                $this->editurl = $CFG->wwwroot . '/blocks/news/edit.php?m=' . $bnm->get_id()
                     . '&mode=' . $mode;
            }

            if (has_capability('block/news:delete', $blockcontext)) {
                $this->deleteicon = new pix_icon('t/delete', 'delete');
                $this->deleteurl = $CFG->wwwroot.'/blocks/news/message.php?m=' . $bnm->get_id()
                    . '&action=delete&mode=' . $mode;
            }
        }

        // FOR ATTACHMENTS
        $this->blockinstanceid = $bnm->get_blockinstanceid();
        $this->messageformat = $bnm->get_messageformat();
        $this->id = $bnm->get_id();

    }
}


/**
 * Short message class (for display in the block)
 * @package    blocks
 * @subpackage news
 */
class block_news_message_short implements renderable {

    /**
     * Build short message data
     *
     * @param block_news_message $bnm
     * @param block_news_system $bns
     * @param int $summarylength Length of text displayed (0 = none)
     * @param int $count Sequence, eg 1 is first message in the block
     */
    public function __construct($bnm, $bns, $summarylength, $count) {
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

        if ($summarylength) {
            $this->message = shorten_text(strip_tags($bnm->get_message()), $summarylength);
        } else {
            $this->message = '';
        }
        $this->messagedate = userdate($bnm->get_messagedate(),
                                                get_string('dateformat', 'block_news'));
        $this->messagevisible = $bnm->get_messagevisible();
        $this->messageformat = $bnm->get_messageformat();
        $this->accesshide = get_string('rendermsgaccesshide', 'block_news', $count); //View news 2

        $usr = $bnm->get_user();
        if ($bnm->get_hideauthor() || $usr == null) {
            $this->author = '';
        } else {
            $this->author = fullname($usr);
        }
    }
}


/**
 * block_news renderer
 * @package    blocks
 * @subpackage news
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
     * Generate HTML for full message
     * @param block_news_message_full $nmsg Renderable data
     * @return string HTML
     */
    protected function render_block_news_message_full(block_news_message_full $nmsg) {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        $blockcontext = get_context_instance(CONTEXT_BLOCK, $nmsg->blockinstanceid);
        $nmsg->message = file_rewrite_pluginfile_urls($nmsg->message, 'pluginfile.php',
            $blockcontext->id, 'block_news', 'message', $nmsg->id);

        $out = '';
        $out .= $this->output->box($nmsg->messagedate, 'messagedate ' . $nmsg->classes);
        $out .= $this->output->box(format_string($nmsg->title), 'title ' . $nmsg->classes);

        if (!empty($nmsg->link)) {
            $out .= $this->output->box_start('link');
            $out .= $this->output->action_link($nmsg->link, $nmsg->link);
            $out .= $this->output->box_end('link');
        }

        $out .= $this->output->container(format_string($nmsg->author), 'author ' . $nmsg->classes);

        $out .= $this->output->container(format_text($nmsg->message, $nmsg->messageformat),
            'message ' . $nmsg->classes);

        $out .= $this->output->box_start(null, 'prevnextlinks');
        if ($nmsg->nexturl) {
            if ($nmsg->nexturl == 'end') {
                $out .= html_writer::tag('span', '&nbsp;', // At latest message
                    array('id' => 'nextlink', 'class' => 'endlist'));
            } else {
                $out .= html_writer::tag('a', $this->output->larrow(). ' ' .
                    get_string('rendermsgnext', 'block_news'),
                    array('href' => $nmsg->nexturl, 'id' => 'nextlink'));
            }
        }

        if ($nmsg->prevurl) {
            if ($nmsg->prevurl == 'end') {
                $out .= html_writer::tag('span', '&nbsp;', // At earliest message
                    array('id' => 'prevlink', 'class' => 'endlist'));
            } else {
                $out .= html_writer::tag('a',
                    get_string('rendermsgprev', 'block_news') .
                    ' ' . $this->output->rarrow(),
                    array('href' => $nmsg->prevurl, 'id' => 'prevlink'));
            }
        }

        $out .= $this->output->box_end('prevnextlinks');

        // attached files
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

        $out .= $this->output->box_start(null, 'editicons');
        if (isset($nmsg->hideicon) && isset($nmsg->hideurl)) {
            $out .= $this->output->container_start('', 'hideurl');
            $out .= $this->output->action_icon($nmsg->hideurl, $nmsg->hideicon);
            $out .= $this->output->container_end();
        }
        if (isset($nmsg->editicon) && isset($nmsg->editurl)) {
            $out .= $this->output->container_start('', 'editurl');
            $out .= $this->output->action_icon($nmsg->editurl, $nmsg->editicon);
            $out .= $this->output->container_end();
        }
        if (isset($nmsg->deleteicon) && isset($nmsg->deleteurl)) {
            $out .= $this->output->container_start('', 'deleteurl');
            $out .= $this->output->action_icon($nmsg->deleteurl, $nmsg->deleteicon);
            $out .= $this->output->container_end();
        }
        $out .= $this->output->box_end(null, 'editicons');

        $out .= $this->output->box_start(null, 'notes');
        foreach ($nmsg->notes as $note) {
            $out .= $this->output->container($note, 'note ');
        }
        $out .= $this->output->box_end(null, 'notes');

        return $this->output->container($out, 'block_news_message ' . $nmsg->classes);
    }

    /**
     * @param block_news_message_short $nmsg Renderable data
     * @return string HTML
     */
    protected function render_block_news_message_short(block_news_message_short $nmsg) {
        global $CFG;

        $out = '';
        $out .= $this->output->container_start('block_news_msg');

        $date = html_writer::tag('span', $nmsg->messagedate, array('class' => 'block_news_msg_messagedate'));
        $title = html_writer::tag('span', format_string($nmsg->title), array('class' => 'block_news_msg_title'));
        if (!empty($nmsg->link)) {
            $title = $this->output->action_link($nmsg->link, $title);
        }

        $out .= $this->output->heading($date . $title, 3);

        if (!empty($nmsg->author)) {
            $out .= $this->output->container(format_string($nmsg->author),
                'block_news_msg_author');
        }
        $out .= $this->output->container(format_text($nmsg->message, $nmsg->messageformat),
                'block_news_msg_message');

        // (View)
        $out .= $this->output->container_start('link');
        $out .= $this->output->action_link($nmsg->viewlink,
            get_string('rendermsgview', 'block_news'));
        $out .= $this->output->container_end();
        $out .= $this->output->container($nmsg->accesshide, 'accesshide');
        $out .= $this->output->container_end();

        return $this->output->container($out, 'block_news '.$nmsg->classes);
    }
}
