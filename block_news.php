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

use block_news\system;
use block_news\message;
use block_news\output\short_message;

/**
 * News block main class.
 *
 * @package block_news
 * @copyright 2012 The Open University
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @author OU developers
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_news extends block_base {

    /** @var system System object */
    public system $bns;

    public function init() {
        $this->title = get_string('pluginname', 'block_news');
    }

    /**
     * multiple instance control
     * note: administrator can override
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * return true if the block has settings.php file.
     */
    public function has_config() {
        return true;
    }

    /**
     * Display block content
     */
    public function get_content() {
        global $CFG, $COURSE, $USER, $SESSION;

        if ($this->content !== null) {
            return $this->content;
        }

        $blockinstanceid = $this->instance->id;
        $blockcontext = context_block::instance($blockinstanceid);

        $this->content = new stdClass;

        $output = $this->page->get_renderer('block_news'); // Looks for class xxx_renderer.
        $this->content->footer = '';

        $context = context_course::instance($this->page->course->id);
        $isediting = $this->page->user_is_editing()
                && has_capability('moodle/course:manageactivities', $context)
                && strpos($this->page->bodyclasses, 'format-ousubject') !== false;

        // Show Add if permittted.
        if (has_capability('block/news:add', $blockcontext)) {
            $this->content->footer .= $output->container_start('block_news_addmsg');
            $this->content->footer .= $output->render_add($blockinstanceid);
            $this->content->footer .= $output->container_end();
            $canaddnews = 'blocknewscanadd';// Extra class added on some links if edit permission.
        } else {
            $canaddnews = null;
        }
        $nummsgs = $this->bns->get_nummessages();
        if ($this->bns->get_displaytype() == system::DISPLAY_DEFAULT) {
            $msgs = $this->bns->get_messages_limited($nummsgs);
            $events = null;
        } else {
            $msgs = $this->bns->get_messages_limited($nummsgs, message::MESSAGETYPE_NEWS);
            $events = $this->bns->get_messages_limited($nummsgs, message::MESSAGETYPE_EVENT);
        }

        $sumlen = $this->bns->get_summarylength();
        if ($msgs || $events || $this->bns->get_displaytype() == system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS) {
            $c = 1;
            $this->content->text = $output->open_news_block_custom_wrapper();
            $msglistclasses = 'block_news_msglist';
            if ($this->bns->get_displaytype() == system::DISPLAY_DEFAULT) {
                $msglistclasses .= ' block_news_noeventlist';
                if ($COURSE->format === 'ousubject') {
                    $this->content->text .= $output->heading(get_string('newsheading', 'block_news'), 3, 'news-heading');
                }
            }
            $this->content->text .= $output->container_start($msglistclasses);

            $newmsg = false;

            if (!$this->bns->get_hideimages()) {
                $thumbnails = $this->bns->get_images('thumbnail');
                $images = $this->bns->get_images();
            } else {
                $thumbnails = [];
                $images = [];
            }

            if (empty($msgs)) {
                $this->content->text .= get_string('nonewsyet', 'block_news');
            } else {
                foreach ($msgs as $msg) {
                    // Check whether there are news posts the user is not likely to have seen.
                    if (!empty($SESSION->news_block_views) &&
                            !empty($SESSION->news_block_views[$msg->get_id()])
                    ) {
                        $courseaccess = time();
                    } else {
                        if (isset($USER->lastcourseaccess[$COURSE->id])) {
                            $courseaccess = $USER->lastcourseaccess[$COURSE->id];
                        } else {
                            $courseaccess = false;
                        }
                    }

                    $msgdate = $msg->get_messagedate();
                    $msguserid = $msg->get_userid();

                    if ($USER->id != $msguserid && (!$courseaccess || $msgdate > $courseaccess)) {
                        $newmsg = true;
                    }

                    $msgwidget = new short_message($msg, $this->bns, $sumlen, $c, $thumbnails, $images);
                    $this->content->text .= $output->render($msgwidget);
                    $c++;
                }
            }

            if ($newmsg) {
                $this->content->text .= $output->render_new_icon($blockinstanceid);
            }

            $this->content->text .= $output->container_end();

            if ($this->bns->get_displaytype() == system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS) {
                $this->content->text .= $output->container_start('block_news_eventlist');
                $this->content->text .= $output->heading(get_string('eventsheading', 'block_news'), 3);
                if (empty($events)) {
                    $this->content->text .= get_string('noeventsyet', 'block_news');
                } else {
                    foreach ($events as $event) {
                        $eventwidget = new short_message($event, $this->bns, $sumlen, $c, $thumbnails, $images);
                        $this->content->text .= $output->render($eventwidget);
                    }
                }
                $this->content->text .= $output->container_end();
            }
            $this->content->text .= $output->close_news_block_custom_wrapper();
            // Main footer.
            $this->content->footer .= $output->container_start(
                $canaddnews ? $canaddnews . ' block_news_viewall' : 'block_news_viewall',
            );
            $this->content->footer .= $output->render_view_all($blockinstanceid, $this->bns->get_viewall_label());
            $this->content->footer .= $output->container_end();
        } else {
            $this->content->text = '';
            if ($this->bns->get_displaytype() == system::DISPLAY_DEFAULT &&
                    $COURSE->format === 'ousubject') {
                $this->content->text .= $output->heading(get_string('newsheading', 'block_news'), 3, 'news-heading');
            }
            $this->content->text .= $output->container(
                    get_string('msgblocknonews', 'block_news'), null, 'msgblocknonews');
            if (has_capability('block/news:viewhidden', $blockcontext)) {
                $this->content->footer .= $output->container_start('block_news_viewall');
                $this->content->footer .= $output->render_view_all($blockinstanceid, $this->bns->get_viewall_label());
                $this->content->footer .= $output->container_end();
            }
        }

        // If feeds allowed on site, display icon.
        if (isset($CFG->enablerssfeeds) && $CFG->enablerssfeeds) {
            $this->content->footer .= $output->container_start(
                $canaddnews ? $canaddnews . ' block_news_rss' : 'block_news_rss',
            );
            $pi = new pix_icon('i/rss', 'RSS');
            $this->content->footer .= $output->action_icon(
                    $this->bns->get_feed_url(), $pi, null, ['title' => 'RSS']);
            $this->content->footer .= $output->container_end();
        }

        return $this->content;
    }

    /**
     * instance delete
     */
    public function instance_delete() {
        $this->bns->delete();
    }

    /**
     * instance settings
     */
    public function specialization() {
        $blockinstanceid = $this->instance->id;
        $this->bns = system::get_block_settings($blockinstanceid);

        // Display title of this instance on config page (its put in block header and on
        // block edit page header).
        $t = $this->bns->get_title();
        if (!empty($t)) {
            $this->title = $t;
        }
    }

    /**
     *  store config data for instance
     */
    public function instance_config_save($data, $nolongerused = false) {
        global $DB;

        $this->bns->save($data);

        // Force some config data (necessary for backup/restore).
        $config = new stdClass;
        $config->title = $data->title;
        $config->id = $this->instance->id;
        $DB->set_field('block_instances', 'configdata', base64_encode(serialize($config)),
            ['id' => $this->instance->id]);
    }

    public function instance_create() {
        // Force some initial data for the block.
        $data = new stdClass();
        $data->title = get_string('defaultblocktitle', 'block_news');
        $data->feedurls = '';

        $this->instance_config_save($data);

        return true;
    }

    /**
     *  limit contexts from which block can be created
     */
    public function applicable_formats() {
        return ['course' => true, 'mod' => true, 'my' => true];
    }
}
