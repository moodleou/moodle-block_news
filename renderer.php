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

use block_news\system;
use block_news\message;
use block_news\output\short_message;
use block_news\output\full_message;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
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
     * @param system $bns Block news instance record
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
     * @param system $bns Block news instance record
     * @return string
     */
    public function render_message_page_title($title, $bns = null) {
        return $this->output->heading($title);
    }

    /**
     * Subscribe to news feed link on all page
     *
     * @param system $bns Block news instance record
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
     * @param system $bns Block news instance record
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
     *
     * @param full_message $nmsg Renderable data
     * @return string HTML
     */
    protected function render_full_message(full_message $nmsg) {
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
     * @param short_message $nmsg Renderable data
     * @return string HTML
     */
    protected function render_short_message(short_message $nmsg) {
        $context = $nmsg->export_for_template($this->output);
        if ($nmsg->messagetype == message::MESSAGETYPE_EVENT) {
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
     * @param int $label The label text for the view all link.
     */
    public function render_view_all($blockinstanceid, $label) {
        return $this->action_link(
                new moodle_url('/blocks/news/all.php', array('bi' => $blockinstanceid)),
                $label,
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
     * Renders view_all_page component using template.
     *
     * @param \block_news\output\view_all_page $page
     * @return bool|string
     */
    public function render_view_all_page(block_news\output\view_all_page $page) {
        $context = $page->export_for_template($this->output);
        return $this->output->render_from_template('block_news/view_all_page', $context);
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
