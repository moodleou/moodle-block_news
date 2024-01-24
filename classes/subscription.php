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

namespace block_news;

use block_news\output\full_message;
use stdClass;

/**
 * Manage news subscription.
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subscription {
    const NOT_SUBSCRIBED = 0;
    const FULLY_SUBSCRIBED = 2;
    /** Link constant: standard link (&) */
    const PARAM_PLAIN = 2;
    /** Link constant: HTML form input fields */
    const PARAM_FORM = 3;
    /** Link constant: HTML link (&amp;) */
    const PARAM_HTML = 1;
    private $course, $bi, $newsfields;
    // Static subscription cache.
    static $subscriptioninfo;

    /**
     * Initialises the subsription.
     *
     * @param object $course Course object
     * @param int $bi The ID of block instance
     * @param object $newsfields news fields from db table.
     */
    public function __construct($course, $bi, $newsfields) {
        $this->course = $course;
        $this->bi = $bi;
        $this->newsfields = $newsfields;
    }

    /**
     * Creates a news object and all related data from a single block instance.
     *
     * @param int $blockinstanceid BLock instance ID of news
     * @return object News
     */
    public static function get_from_bi($blockinstanceid): object {
        global $COURSE, $DB;

        $csemod = block_news_get_course_mod_info($blockinstanceid);

        $courseid = $csemod->course->id;

        // Get course.
        if (!empty($COURSE->id) && $COURSE->id === $courseid) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record('course', ['id' => $courseid]);
        }
        $newsfields = $DB->get_record('block_news', ['blockinstanceid' => $blockinstanceid]);

        $result = new subscription($course, $blockinstanceid, $newsfields);
        return $result;
    }

    /**
     * Display subscribe options for this news.
     *
     * @return string HTML code for this area
     */
    public function display_subscribe_options($expectquery = false): string {
        global $USER, $PAGE;
        $out = $PAGE->get_renderer('block_news');
        $subscribed = self::NOT_SUBSCRIBED;
        $canchange = true;
        $subscriptioninfo = $this->get_subscription_info(0, $expectquery);
        if ($subscriptioninfo->subscribed) {
            $subscribed = self::FULLY_SUBSCRIBED;
            $text = get_string('subscription_help', 'block_news',
                '<strong>' . $USER->email . '</strong>');
        } else {
            $text = get_string('unsubscription_help', 'block_news');
        }
        return $out->render_news_subscribe_options($this, $text,
            $subscribed, $canchange, true);

    }

    /**
     * @param int $userid User ID or 0 for default
     * @return Genuine (non-zero) user ID
     */
    public static function get_real_userid($userid = 0) {
        global $USER;
        $userid = $userid == 0 ? $USER->id : $userid;
        if (!$userid) {
            // This can happen in cases where we are about to check whether the user is logged in.
            // In that case, let us return user 0.
            return 0;
        }
        return $userid;
    }

    /**
     * Return the subscription info of the user.
     *
     * @param int $userid User ID or 0 for current
     * @param bool $expectingquery True if expecting query (note this
     *   value is ignored if you specify a non-current userid, then it will
     *   always make queries)
     */
    public function get_subscription_info($userid = 0, $expectingquery = false): object {
        global $DB;
        $userid = $this->get_real_userid($userid);
        if (!isset($subscriptioninfo)) {
            $subscriptioninfo = [];
        }
        $key = $userid . ':' . $this->get_blockinstanceid();
        if (array_key_exists($key, $subscriptioninfo)) {
            return $subscriptioninfo[$key];
        }

        $user = (object) (['subscribed' => false, 'groupids' => []]);

        $rs = $DB->get_recordset_sql($sql =
            "SELECT bns.subscribed
                FROM
                     {block_news_subscriptions} bns
                JOIN {block_news} bn ON bn.blockinstanceid = bns.blockinstanceid
                WHERE
                      bns.blockinstanceid = ?
                      AND bns.userid = ?",
            [$this->bi, $userid]);
        foreach ($rs as $rec) {
            if ($rec->subscribed) {
                $user->subscribed = true;
            }
        }
        $rs->close();
        $subscriptioninfo[$userid] = $user;
        return $user;
    }

    /**
     * Use to obtain link parameters when linking to any page that has anything
     * to do with news.
     *
     * @return string
     */
    public function get_link_params($type): string {
        if ($type == self::PARAM_FORM) {
            $id = '<input type="hidden" name="bi" value="' . $this->bi . '" />';
        } else {
            $id = 'bi=' . $this->bi;
        }
        return $id;
    }

    /**
     * Subscribes a user from this news.
     *
     * @param $userid User ID (default current)
     */
    public function subscribe($userid = 0) {
        global $DB;
        $userid = $this->get_real_userid($userid);
        $transaction = $DB->start_delegated_transaction();
        $subrecord = new StdClass;
        $subrecord->userid = $userid;
        $subrecord->blockinstanceid = $this->bi;
        $subrecord->subscribed = 1;
        $DB->insert_record('block_news_subscriptions', $subrecord);
        $transaction->allow_commit();

        $event = \block_news\event\subscription_created::create([
            'objectid' => $subrecord->blockinstanceid,
            'context' => \context_block::instance($subrecord->blockinstanceid)
        ]);
        $event->trigger();

    }

    /**
     * Unsubscribes a user from this news.
     *
     * @param $userid User ID (default current)
     */
    public function unsubscribe($userid = 0) {
        global $DB;
        $userid = $this->get_real_userid($userid);
        $DB->delete_records('block_news_subscriptions', ['userid' => $userid,
            'blockinstanceid' => $this->bi]);

        $event = \block_news\event\subscription_deleted::create([
            'objectid' => $this->bi,
            'context' => \context_block::instance($this->bi)
        ]);
        $event->trigger();
    }

    /**
     * @param int $type PARAMS_xx constant
     * @return string Full URL to this news
     */
    public function get_url($type): string {
        global $CFG;
        return $CFG->wwwroot . '/blocks/news/all.php?' .
            $this->get_link_params($type);
    }

    /**
     * Used when selecting course inside other SQL statements.
     *
     * @param string $alias Alias of table to extract
     * @return string SQL select fields (no comma at start or end)
     */
    public static function select_course_fields($alias): string {
        return self::select_fields(['id', 'shortname', 'fullname', 'format'],
            $alias);
    }

    /**
     * Used when selecting message inside other SQL statements.
     *
     * @param string $alias Alias of table to extract
     * @return string SQL select fields (no comma at start or end)
     */
    public static function select_block_news_message_fields($alias): string {
        return self::select_fields(['id', 'blockinstanceid', 'newsfeedid', 'title', 'link', 'message', 'messageformat',
            'messagedate', 'messagevisible', 'messagerepeat', 'hideauthor', 'timemodified', 'userid', 'messagetype', 'eventstart',
            'eventend', 'eventlocation', 'imagedesc', 'imagedescnotnecessary', 'currenthash', 'mailstate'], $alias);
    }

    /**
     * Used when selecting news inside other SQL statements.
     *
     * @param string $alias Alias of table to extract
     * @return string SQL select fields (no comma at start or end)
     */
    public static function select_block_news_fields($alias): string {
        return self::select_fields(['id', 'blockinstanceid', 'title', 'nummessages',
            'summarylength', 'hidetitles', 'hidelinks', 'groupingsupport',
            'displaytype'], $alias);
    }

    /**
     * Makes a list of fields with alias in front.
     *
     * @param $fields Field
     * @param $alias Table alias (also used as field prefix) - leave blank for
     *   none
     * @return SQL SELECT list
     */
    private static function select_fields($fields, $alias = '') {
        $result = '';
        if ($alias === '') {
            $fieldprefix = '';
            $nameprefix = '';
        } else {
            $fieldprefix = $alias . '.';
            $nameprefix = $alias . '_';
        }
        foreach ($fields as $field) {
            if ($result) {
                $result .= ',';
            }
            $result .= $fieldprefix . $field . ' as ' . $nameprefix . $field;
        }
        return $result;
    }

    /**
     * Obtains list of news subscribers.
     *
     * @return array Array of partial user objects (with enough info to send
     *   email and display them).
     */
    public function get_subscribers(): array {
        global $DB;

        $userfields = \core_user\fields::for_identity($this->get_context(), true);
        $basicuserfields = \core_user\fields::for_name()
            ->including('id', 'username', 'email', 'emailstop', 'deleted', 'auth', 'timezone', 'lang', 'maildisplay', 'mailformat');
        $basicuserssql = $basicuserfields->get_sql('u', false, 'u_', '', false)->selects;

        [
            'selects' => $selects,
            'joins' => $joins,
            'params' => $joinparams
        ] = (array) $userfields->get_sql('u');

        $extrafields = \core_user\fields::get_identity_fields($this->get_context());
        $users = [];

        $rs = $DB->get_recordset_sql($sql = "
            SELECT 
                    bns.*, bnm.blockinstanceid, messagegm.groupid AS membergroupid, up.value as newsmailformat,
                    " . $basicuserssql . "
                    " . $selects . "
              FROM
                   {block_news_subscriptions} bns
        INNER JOIN {user} u ON u.id = bns.userid
         LEFT JOIN {block_news_messages} bnm ON bnm.blockinstanceid = bns.blockinstanceid
         LEFT JOIN {groups_members} messagegm ON  bns.userid = messagegm.userid
         LEFT JOIN {user_preferences} up ON  up.userid = bns.userid AND up.name = 'newsmailformat'
         " . $joins . "
             WHERE
                    bns.blockinstanceid = ?", array_merge($joinparams, [$this->bi]));
        $allowedusers = null;

        foreach ($rs as $rec) {
            if ($rec->subscribed) {
                if (!array_key_exists($rec->u_id, $users)) {
                    $user = $this->extract_subobject($rec, 'u_');
                    $user->subscribe = false;
                    $newuser = true;
                } else {
                    $user = $users[$rec->u_id];
                    $newuser = false;
                }
                if ($rec->membergroupid) {
                    $user->membergroupids[$rec->membergroupid] = $rec->membergroupid;
                }
                foreach ($extrafields as $field) {
                    $user->{$field} = $rec->{$field} ?? '';
                }
                if ($newuser) {
                    $users[$user->id] = $user;
                }
            }
        }
        $rs->close();

        return $users;
    }

    /**
     * Loops through all the fields of an object, removing those which begin
     * with a given prefix, and setting them as fields of a new object.
     *
     * @param &$object object Object
     * @param $prefix string Prefix e.g. 'prefix_'
     * @return object Object containing all the prefixed fields (without prefix)
     */
    public static function extract_subobject(&$object, $prefix): object {
        $result = [];
        foreach ((array) $object as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $result[substr($key, strlen($prefix))] = $value;
                unset($object->{$key});
            }
        }
        return (object) $result;
    }

    /**
     * @param object $user User object
     * @return string HTML that contains a link to the user's profile, with
     *   their name as text
     */
    public function display_user_link($user): string {
        global $CFG;
        $coursepart = '&amp;course=' . $this->course->id;
        return "<a href='{$CFG->wwwroot}/user/view.php?id={$user->id}" .
            "$coursepart'>" . $this->display_user_name($user) . "</a>";
    }

    /**
     * @param object $user User object
     * @return string HTML that contains a link to the user's profile, with
     *   their name as text
     */
    public function display_user_name($user): string {
        return fullname($user, has_capability(
            'moodle/site:viewfullnames', \context_block::instance($this->bi)));
    }

    /** @return string Title of news */
    public function get_title(): string {
        return $this->newsfields->title;
    }

    /**
     * @param int $userid User ID, 0 for default
     */
    public function can_manage_subscriptions($userid = 0): bool {
        $userid = $this->get_real_userid($userid);
        return has_capability('block/news:managesubscriptions', \context_block::instance($this->bi),
            $userid);
    }

    /**
     * @param $userid
     * @return bool True if user can view a list of subscribers in this news
     */
    public function can_view_subscribers($userid = 0): bool {
        $userid = $this->get_real_userid($userid);
        return has_capability('block/news:viewsubscribers', \context_block::instance($this->bi),
            $userid);
    }

    /**
     * Block news context.
     *
     * @return \context
     */
    public function get_context($forcereal = false) {
        return \context_block::instance($this->bi);
    }

    /**
     * Obtains a version of this news as an email.
     *
     * @param string &$subject Output: Message subject
     * @param string $text Output: Message plain text
     * @param string $html Output: Message HTML (or blank if not in HTML mode)
     * @param bool $ishtml True if in HTML mode
     * @param string $lang Language of receiving user
     * @param string $main main email message
     */

    public function build_email(&$subject, &$text, &$html,
        $ishtml, $lang, $main) {
        global $CFG, $USER;
        $oldlang = $USER->lang;
        $USER->lang = $lang;
        $courseid = $this->course->id;
        $shortname = $this->course->shortname;
        $subject = $shortname . ': ' . format_string($this->get_title(), true);
        $text = '';
        $html = "\n<body id='news-email'>\n\n";
        $text .= $shortname . ' -> ';
        $html .= "<div class='news-email-navbar'><a target='_blank' " .
            "href='$CFG->wwwroot/course/view.php?id=$courseid'>" .
            "$shortname</a> &raquo; ";
        $text .= format_string($this->newsfields->title, true) . "\n";
        $text .= \block_news\task\news_email::EMAIL_DIVIDER;
        $html .= "<a target='_blank' " .
            "href='$CFG->wwwroot/blocks/news/all.php?" .
            $this->get_link_params(self::PARAM_HTML) . "'>" .
            format_string($this->get_title(), true) . '</a>';
        $html .= '</div>';
        $html .= $main;
        $text .= $main;
        $text .= "\n" . \block_news\task\news_email::EMAIL_DIVIDER;
        $text .= get_string("unsubscribeshort", "block_news");
        $text .= ": $CFG->wwwroot/blocks/news/subscribe.php?" .
            $this->get_link_params(self::PARAM_PLAIN) . "\n";
        $html .= "<hr size='1' noshade='noshade' />" .
            "<div class='news-email-unsubscribe'>" .
            "<a href='$CFG->wwwroot/blocks/news/subscribe.php?" .
            $this->get_link_params(self::PARAM_HTML) . "'>" .
            get_string('unsubscribe', 'block_news') . '</a></div>';
        $html .= '</body>';

        $USER->lang = $oldlang;
        if (!$ishtml) {
            $html = '';
        }
    }

    /**
     * Display main message for email.
     *
     * @return string HTML code for this area
     */
    public function render_main_section($bnm, $bns, $images, $files, $output, $blockcontext, $ishtml): string {
        $article = '';
        $attatch = '';

        $message = new full_message($bnm, null, null, $bns, 'all', $images, false, $files);
        $context = $message->export_for_template($output);
        if ($context->isnews) {
            $byau = ($context->author) ? ' by ' . $context->author : '';
            if ($ishtml) {
                $article .= '<p class="author">' . $context->messagedate . $byau . '</p>';
            } else {
                $article .= $context->messagedate . $byau;
                $article .= "\n" . \block_news\task\news_email::EMAIL_DIVIDER;
            }
        }
        if ($ishtml) {
            $article .= '<h2 class="title">' . $context->title . '</h2>';
        } else {
            $article .= "\n" . $context->title;
            if ($context->hasattachments) {
                $article .= "\n" . self::render_attachments($context->attachments, false) . "\n";
                $article .= "\n" . \block_news\task\news_email::EMAIL_DIVIDER;
            }
        }

        $publicimg = '';

        if ($context->imageurl) {
            $image = $images[$bnm->get_id()];
            $pathparts = ['/blocks/news/files.php', $blockcontext->id, 'block_news',
                'messageimage', $bnm->get_id(), $image->get_filename()];
            $imageurl = new \moodle_url(implode('/', $pathparts));
            $publicimg .= ' <img alt="" src="' . $imageurl->out() . '"/>';
        }
        $article .= $publicimg;
        if (!$context->isnews) {
            $event = '';
            $eventdatecss = '
                text-align: center;
                width: 56px;
                display: inline-block;
                float: left;
                margin-right: 17px;
            ';
            $eventdatemonthcss = '
                display: block;
                background-color: #fff;
                border: 1px solid #002158;
                color: #000;
                font-size: 1.71428em;
                padding-bottom: 9px;
            ';
            $eventdatedaycss = '
                display: block;
                background-color: #002158;
                font-size: 1.14286em;
                color: #fff;
                text-transform: uppercase;
                font-weight: 700;
                padding: 1px 0;
            ';
            if ($ishtml) {
                $event .= '<time  style="' . $eventdatecss . '" class="block_news_event_date" href="' . $context->eventdatetime .
                    '">' .
                    '<span style="' . $eventdatemonthcss . '" class="block_news_event_date_month">' . $context->eventmonth .
                    '</span>' .
                    '<span style="' . $eventdatedaycss . '" class="block_news_event_date_day">' . $context->eventday . '</span>'
                    . '</time>';
                $event .= '<div class="block_news_event_fulldate">' . $context->fulleventdate . '</div>';
                if ($context->eventlocation) {
                    $event .= '<div class="block_news_event_location">' . $context->eventlocation . '</div>';
                }
            } else {
                $event .= $context->fulleventdate;
                if ($context->eventlocation) {
                    $event .= $context->eventlocation;
                }
                $event .= "\n" . \block_news\task\news_email::EMAIL_DIVIDER;
            }

            $article .= $event;
        }
        $context->formattedmessage = str_replace('/pluginfile.php/', '/blocks/news/files.php/',
            $context->formattedmessage);
        if ($ishtml) {
            $article .= '<div class="block-news-message-text">' . $context->formattedmessage . '</div>';
        } else {
            $article .= "\n" . format_text_email($context->formattedmessage, $context->messageformat);
        }
        if ($ishtml) {
            if ($context->hasattachments) {
                $attatch .= self::render_attachments($context->attachments, true);
            }
            $article .= $attatch;
        }

        $link = '';
        if ($context->isnews) {
            if ($context->link) {
                if ($ishtml) {
                    $link .= '<a href="' . $context->link . '" class="block-news-message-extlink">';
                    if ($context->str) {
                        get_string('attachment', 'block_news');
                    }
                    $link .= '</a>';
                } else {
                    $link .= $context->link;
                }
            }
        }
        $article .= $link;

        if ($ishtml) {
            $article .= '<div class="block_news_group_indication">' . $context->groupindication . '</div>';
        } else {
            $article .= $context->groupindication;
        }
        return $article;
    }

    /**
     * Display attachments of message.
     *
     * @return string HTML code for this area
     */
    private static function render_attachments($attachments, $ishtml): string {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $lf = ' ';
        $out = $lf;
        if (count($attachments) == 1) {
            $attachmentlabel = get_string('attachment', 'block_news');
        } else {
            $attachmentlabel = get_string('attachments', 'block_news');
        }
        if ($ishtml) {
            $out .= '<span class="accesshide news-attachments-label">' . $attachmentlabel .
                '</span><ul class="news-attachments">';
        }

        foreach ($attachments as $attachment) {
            $alt = $attachment->filename;
            $iconsrc = $attachment->iconsrc;
            if ($ishtml) {
                $out .= '<li><a href="' . $attachment->url . '">' .
                    '<img src="' . $iconsrc . '" alt="' . $alt . '" /> <span class="break-filename">' . '</span></a> </li>';
            } else {
                $out .= $alt;
            }
        }
        if ($ishtml) {
            $out .= '</ul>' . $lf;
        }
        return $out;
    }

    /**
     * Gets ID of block instance
     *
     * @return int Block instance id
     */
    public function get_blockinstanceid(): int {
        return $this->bi;
    }

    /**
     * Gets the unsubscribe link for a user.
     *
     * @param int $userid User id
     * @return string Link URL
     */
    public function get_unsubscribe_link(int $userid): string {
        return (new \moodle_url('/blocks/news/subscribe.php', [
            'bi' => $this->bi,
            'user' => $userid,
            'key' => $this->get_unsubscribe_key($userid),
        ]))->out(false);
    }

    /**
     * Gets the unsubscribe key for a user on this news block.
     *
     * @param int $userid User id
     * @return string Key text
     */
    public function get_unsubscribe_key(int $userid): string {
        return hash('sha256', system::get_key_salt() . $userid . ':' . $this->bi);
    }
}
