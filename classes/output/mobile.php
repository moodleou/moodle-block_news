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
 * Mobile output components for news
 *
 * @package    block_news
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news\output;

defined('MOODLE_INTERNAL') || die();

use block_news\message;
use block_news\system;

/**
 * Ouput components to generate mobile app screens.
 */
class mobile {

    /**
     * @var int There are no more pages of messages to load.
     */
    const MOREMESSAGES_NO = 0;

    /**
     * @var int There are more pages of messages to load.
     */
    const MOREMESSAGES_YES = 1;

    /**
     * @var int There are more pages of messages to load, but we need to switch to "past events" mode first.
     */
    const MOREMESSAGES_SWITCHMODE = 2;

    /**
     * Get the IDs of courses that have an instance of the news block.
     */
    public static function news_init(array $args) : array {
        global $DB, $CFG;
        $courses = enrol_get_users_courses($args['userid'], true);
        $args = (object)$args;
        $foldername = $args->appversioncode >= 3950 ? 'ionic5/' : 'ionic3/';

        list($sqlin, $params) = $DB->get_in_or_equal(array_keys($courses));
        $sql = "SELECT DISTINCT con.instanceid
                  FROM {block_instances} bi
                       JOIN {context} con ON bi.parentcontextid = con.id
                 WHERE con.instanceid $sqlin AND bi.blockname = ? AND con.contextlevel = ?";
        $params[] = 'news';
        $params[] = CONTEXT_COURSE;
        $courseids = $DB->get_fieldset_sql($sql, $params);
        return [
            'restrict' => [
                'courses' => $courseids
            ],
            'javascript' => file_get_contents($CFG->dirroot . '/blocks/news/appjs/' . $foldername . 'news_init.js')
        ];
    }

    /**
     * Get the IDs of courses that have an instance of the news block in news and events mode.
     */
    public static function events_init(array $args) : array {
        global $DB;
        $courses = enrol_get_users_courses($args['userid'], true);
        list($sqlin, $params) = $DB->get_in_or_equal(array_keys($courses));
        $sql = "SELECT DISTINCT con.instanceid
                  FROM {block_instances} bi
                       JOIN {block_news} bn ON bi.id = bn.blockinstanceid
                       JOIN {context} con ON bi.parentcontextid = con.id
                 WHERE con.instanceid $sqlin AND con.contextlevel = ? AND bn.displaytype = ?";
        $params[] = CONTEXT_COURSE;
        $params[] = system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS;
        $courseids = $DB->get_fieldset_sql($sql, $params);
        return [
            'restrict' => [
                'courses' => $courseids
            ]
        ];
    }

    /**
     * Get a page of news messages
     *
     * @param int $courseid
     * @param int $type
     * @param int $pagenum
     * @param bool $pastevents
     * @return array [full_message[], int] List of messages, and a flag indicating if there are more to come.
     */
    public static function get_messages(int $courseid, int $type = message::MESSAGETYPE_NEWS, int $pagenum = 0,
            bool $pastevents = false) {
        global $PAGE, $DB;
        $sql = "SELECT bi.id
                  FROM {block_instances} bi
                       JOIN {block} b ON bi.blockname = b.name
                       LEFT JOIN {block_positions} bp ON bp.blockinstanceid = bi.id
                           AND bp.contextid = ?
                           AND bp.pagetype = ?
                           AND bp.subpage = ?
                 WHERE parentcontextid = ?
                       AND blockname = ?
              ORDER BY COALESCE(bp.region, bi.defaultregion),
                       COALESCE(bp.weight, bi.defaultweight),
                       bi.id";
        $course = get_course($courseid);
        $context = \context_course::instance($course->id);
        $params = [$context->id, 'course-view-' . $course->format, '', $context->id, 'news'];
        $blockinstanceid = $DB->get_field_sql($sql, $params, IGNORE_MULTIPLE);

        $bns = system::get_block_settings($blockinstanceid);
        $blockcontext = \context_block::instance($blockinstanceid, CONTEXT_BLOCK);
        $viewhidden = has_capability('block/news:viewhidden', $blockcontext);
        $messages = $bns->get_messages_all($viewhidden, system::MOBILE_PAGE_SIZE, $pagenum, $type,
                'eventstart ASC, messagedate DESC', $pastevents);
        $renderer = $PAGE->get_renderer('block_news');
        $messagedata = [];
        if ($messages) {
            $wstoken = required_param('wstoken', PARAM_ALPHANUM);
            $images = $bns->get_images();
            $files = $bns->get_files();
            foreach ($messages as $message) {
                $msgwidget = new full_message($message, null, null, $bns, 'all',
                    $images, $wstoken, $files);
                $msgwidget->id = $message->get_id();
                $messagedata[] = $msgwidget->export_for_template($renderer);
            }
        }
        $countsofar = system::MOBILE_PAGE_SIZE * ($pagenum + 1);
        $moremessages = self::MOREMESSAGES_NO;
        if ($type === message::MESSAGETYPE_NEWS) {
            // Are the more news messages than those on the current page?
            if ($bns->get_message_count($viewhidden, $type) > $countsofar) {
                $moremessages = self::MOREMESSAGES_YES;
            }
        } else {
            if ($pastevents) {
                // Are the more past events than those on the current page?
                if ($bns->get_message_count($viewhidden, $type, true) > $countsofar) {
                    $moremessages = self::MOREMESSAGES_YES;
                }
            } else {
                $upcomingcount = $bns->get_message_count($viewhidden, $type);
                if ($upcomingcount > $countsofar) {
                    // Are the more upcoming events than those on the current page?
                    $moremessages = self::MOREMESSAGES_YES;
                } else if ($bns->get_message_count($viewhidden, $type, true) > 0) {
                    // Are there any past events?
                    $moremessages = self::MOREMESSAGES_SWITCHMODE;
                }
            }
        }
        return ['messages' => $messagedata, 'moremessages' => $moremessages];
    }

    /**
     * Return the news area page
     *
     * @param array $args Web service args, courseid is required.
     * @return array Web service response: template, javascript and initial page of messages.
     */
    public static function news_page(array $args) {
        global $CFG, $OUTPUT, $PAGE;
        $args = (object) $args;
        $foldername = $args->appversioncode >= 3950 ? 'ionic5/' : 'ionic3/';

        $PAGE->set_course(get_course($args->courseid));
        $messagedata = self::get_messages($args->courseid);
        $html = $OUTPUT->render_from_template('block_news/' . $foldername . 'mobile_news_page', ['timestamp' => time()]);
        $blockinstanceid = block_news_get_top_news_block($args->courseid);
        $pageurl = new \moodle_url('/blocks/news/all.php', ['bi' => $blockinstanceid]);
        return [
            'templates' => [
                [
                    'id' => 'newspage',
                    'html' => $html
                ]
            ],
            'javascript' => file_get_contents($CFG->dirroot . '/blocks/news/appjs/' . $foldername . 'newspage.js'),
            'otherdata' => [
                'pageurl' => $pageurl->out(false),
                'messages' => json_encode($messagedata['messages']),
                'moreMessages' => $messagedata['moremessages'],
                'courseid' => $args->courseid,
                'targetMessage' => !empty($args->messageid) ? $args->messageid : 0
            ]
        ];
    }

    /**
     * Return the events area page
     *
     * @param array $args Web service args, courseid is required.
     * @return array Web service response: template, javascript and initial page of messages.
     */
    public static function events_page(array $args) {
        global $CFG, $OUTPUT, $PAGE;
        $PAGE->set_course(get_course($args['courseid']));
        $foldername = $args['appversioncode'] >= 3950 ? 'ionic5/' : 'ionic3/';

        $messagedata = self::get_messages($args['courseid'], message::MESSAGETYPE_EVENT);
        $html = $OUTPUT->render_from_template('block_news/' . $foldername . 'mobile_events_page', ['timestamp' => time()]);
        $blockinstanceid = block_news_get_top_news_block($args['courseid']);
        $pageurl = new \moodle_url('/blocks/news/all.php', ['bi' => $blockinstanceid]);
        return [
            'templates' => [
                [
                    'id' => 'eventspage',
                    'html' => $html
                ]
            ],
            'javascript' => file_get_contents($CFG->dirroot . '/blocks/news/appjs/' . $foldername . 'eventspage.js'),
            'otherdata' => [
                'pageurl' => $pageurl->out(false),
                'messages' => json_encode($messagedata['messages']),
                'moreMessages' => $messagedata['moremessages'],
                'courseid' => $args['courseid'],
                'pastEvents' => '[]'
            ]
        ];
    }
}
