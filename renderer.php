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
use block_news\output\view_all_page;
use block_news\output\view_page;

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
        $context = $nmsg->export_for_template($this->output);
        return $this->output->render_from_template('block_news/message_full', $context);
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
     * @param string $label Text to override the default label
     */
    public function render_add($blockinstanceid, $label = null) {
        if (is_null($label)) {
            $label = get_string('msgblockadd', 'block_news');
        }
        return $this->action_link(
                new moodle_url('/blocks/news/edit.php', array('bi' => $blockinstanceid)),
                $label, null, array('title' => get_string('msgblockaddalt', 'block_news')));
    }

    /**
     * Renders view_all_page component using template.
     *
     * @param view_all_page $page
     * @return bool|string
     */
    public function render_view_all_page(view_all_page $page) {
        $context = $page->export_for_template($this->output);
        return $this->output->render_from_template('block_news/view_all_page', $context);
    }

    /**
     * Renders view_page component using template.
     *
     * @param view_page $page
     * @return bool|string
     */
    public function render_view_page(view_page $page) {
        $context = $page->export_for_template($this->output);
        return $this->output->render_from_template('block_news/view_page', $context);
    }

    /**
     * Returns html for an edit form for display in the block.
     *
     * @param system $bns The block system object
     * @return string
     */
    public function render_edit_form(system $bns) {
        global $CFG;

        $context = new \stdClass();

        // Hidden inputs.
        $context->sesskey = sesskey();
        $context->bid = $bns->get_blockinstanceid();

        $feeds = $bns->get_feeds();
        $context->feedurls = '';
        foreach ($feeds as $feed) {
            $context->feedurls .= $feed->feedurl . "\n";
        }

        // Submit button.
        $context->savelabel = get_string('savechanges');

        // The main form.
        $context->action = $CFG->wwwroot . '/blocks/news/savesettings.php';

        return $this->render_from_template('block_news/editform', $context);
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
