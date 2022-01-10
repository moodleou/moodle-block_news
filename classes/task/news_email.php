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

namespace block_news\task;

use block_news\mail_list;
use block_news\subscription;
use block_news\system;
use block_news\message;

/**
 * A scheduled task for news.
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class news_email extends \core\task\scheduled_task {

    const EMAIL_DIVIDER =
        "---------------------------------------------------------------------\n";
    const DEBUG_VIEW_EMAILS = false;

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('process_news_email', 'block_news');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $CFG;
        // Duplicate of check in email_to_user.
        if (!empty($CFG->noemailever)) {
            mtrace("Not sending news emails because all mail is disabled.");
            return;
        }
        self::email_normal();
    }

    /**
     * Process news email.
     */
    public static function email_normal(): void {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/news/classes/mail_list.php');

        global $USER, $CFG, $PERF, $PAGE;
        mail_list::reset_static_cache();
        $output = $PAGE->get_renderer('block_news');
        mtrace('Email processing:');
        $before = microtime(true);
        $mailtime = 0;
        if (!empty($PERF->dbqueries)) {
            $beforequeries = $PERF->dbqueries;
        }
        $getsubscriberstime = 0;
        $filtersubscriberstime = 0;
        $buildemailtime = 0;
        $listtimes = [];
        $endafter = time() + get_config('block_news', 'cronlimit');
        while (true) {
            $list = new mail_list();
            if ($list->is_finished()) {
                mtrace('No more news posts to send.');
                self::add_list_times($listtimes, $list);
                break;
            }
            while ($list->next_news($news, $blockcontext, $course)) {
                mtrace($course->shortname . ' - ' . $news->get_title() . ' (blockinstanceid ' . $news->get_blockinstanceid() .
                    '): ');
                gc_collect_cycles();
                $PAGE = new \moodle_page();
                $PAGE->set_course($course);
                $sentcount = 0;
                $failedcount = 0;
                $groupskips = 0;
                try {
                    $langusers = [];
                    $innerbefore = microtime(true);
                    $subscribers = $news->get_subscribers();
                    if (count($subscribers) == 0) {
                        continue;
                    }
                    $getsubscriberstime += microtime(true) - $innerbefore;
                    self::debug("DEBUG: Subscribers before filter " . count($subscribers), '');
                    $innerbefore = microtime(true);
                    self::email_filter_subscribers($news, $subscribers);
                    $filtersubscriberstime += microtime(true) - $innerbefore;
                    self::debug(", after " . count($subscribers));
                } catch (\coding_exception $e) {
                    mtrace(' Exception while getting subscribers for news ');
                    mtrace($e->__toString());
                    continue;
                }
                foreach ($subscribers as $subscriber) {
                    $oldlang = $USER->lang;
                    $USER->lang = $subscriber->lang;
                    $lang = current_language();
                    $USER->lang = $oldlang;
                    $langusers[$lang][$subscriber->timezone][$subscriber->emailtype][$subscriber->id] = $subscriber;
                }

                $noreplyaddress = $CFG->noreplyaddress;
                $blockinstanceid = $news->get_blockinstanceid();
                $bns = system::get_block_settings($blockinstanceid);
                $images = $bns->get_images();
                $files = $bns->get_files();
                // Check whether message have group restriction.
                $groupexist = false;
                while ($list->next_message($message)) {
                    $groupids = $bns->get_groupids_by_message([$message->id]);
                    $bnm = new message($message, $groupids[$message->id]);
                    if ($bnm->get_groupids()) {
                        $groupexist = true;
                    }
                    try {
                        foreach ($langusers as $lang => $tzusers) {
                            foreach ($tzusers as $timezone => $typeusers) {
                                foreach ($typeusers as $emailtype => $users) {
                                    self::debug("DEBUG: Subscribers for lang [$lang] " .
                                            "tz [$timezone] type [$emailtype]: " .
                                            count($users));
                                    foreach ($users as $mailto) {
                                        $main = '';
                                        // Handle group restriction.
                                        if ($groupexist) {
                                            if (!isset($mailto->membergroupids) ||
                                                empty(array_intersect($bnm->get_groupids(), $mailto->membergroupids))) {
                                                $groupskips++;
                                                continue;
                                            }
                                        }
                                        $out = $news->render_main_section($bnm, $bns, $images, $files, $output,
                                            $blockcontext, $emailtype);
                                        $main .= $out;
                                        $news->build_email($subject, $plaintext, $html1, $emailtype & 1, $lang, $main);
                                        $buildemailtime += microtime(true) - $innerbefore;
                                        if (self::email_send($mailto, $noreplyaddress, $subject, $plaintext, $html1)) {
                                            $sentcount++;
                                        } else {
                                            $failedcount++;
                                        }
                                    }
                                }
                                $mailtime += microtime(true) - $innerbefore;
                            }
                        }
                    } catch (\Exception $e) {
                        mtrace($e->__toString());
                    }

                }
                self::debug("DEBUG: $sentcount emails sent, $failedcount emails failed to send, " .
                        "$groupskips subscribers skipped due to group restrictions.");
            }
            self::add_list_times($listtimes, $list);
            if (time() > $endafter) {
                mtrace('Stopping (time limit reached).');
                break;
            }
        }
        $queryinfo = '';
        if (!empty($PERF->dbqueries)) {
            $queryinfo = ', ' . ($PERF->dbqueries - $beforequeries) .
                ' queries';
        }
        $totaltime = microtime(true) - $before;
        mtrace("Email processing " .
            "complete, total time: " . sprintf('%.1Fs', $totaltime)) . $queryinfo;
        self::show_time_with_percentage('Mail sending', $mailtime, $totaltime);
        self::show_time_with_percentage('Getting subscribers', $getsubscriberstime, $totaltime);
        self::show_time_with_percentage('Filtering subscribers', $filtersubscriberstime, $totaltime);
        self::show_time_with_percentage('Building emails', $buildemailtime, $totaltime);
        foreach ($listtimes as $key => $time) {
            self::show_time_with_percentage($key, $time, $totaltime);
        }
    }

    /**
     * Utility function to trace out the proportion of time spent doing a thing.
     *
     * @param string $name Name of thing
     * @param float $time Time in seconds
     * @param float $totaltime Total time in seconds
     */
    protected static function show_time_with_percentage(string $name, float $time, float $totaltime): void {
        // Hack total time so it doesn't divide by zero.
        if ($totaltime < 0.1) {
            $totaltime = 0.1;
        }
        mtrace('  ' . $name . ': ' . sprintf('%.1Fs', $time) . ' (' .
            sprintf('%.1F', (100.0 * $time / $totaltime)) . '%)');
    }

    /**
     * @param string $text Text to output, or none if you only want to check the value
     * @param string $lf Set to '' if you don't want a linefeed
     * @return bool True if debug output is enabled
     */
    public static function debug(string $text = '', string $lf = "\n"): bool {
        static $checked = false, $debug;
        if (!$checked) {
            $debug = get_config('block_news', 'extralogging');
        }
        if (!$debug) {
            return false;
        }
        if ($text) {
            mtrace($text, $lf);
        }
        return true;
    }

    /**
     * Adds time recording from a mail_list object into an existing array of times,
     * if necessary adding new entries into the array.
     *
     * @param array $listtimes Array of times (input-output parameter)
     * @param mail_list $list List with time data
     */
    protected static function add_list_times(array &$listtimes, mail_list $list): void {
        if ($listtimes === []) {
            $listtimes = $list->get_times();
        } else {
            foreach ($list->get_times() as $key => $time) {
                $listtimes[$key] += $time;
            }
        }
    }

    /**
     * Filters a list of news subscribers to remove those who can't receive email
     * etc., and adds extra information to each one.
     *
     * @param subscription $news News
     * @param array $subscribers List of subscribers
     */
    private static function email_filter_subscribers(subscription $news, array &$subscribers): void {
        foreach ($subscribers as $subscriber) {
            if ($subscriber->emailstop || $subscriber->deleted ||
                $subscriber->auth == 'nologin' || over_bounce_threshold($subscriber)) {
                unset($subscribers[$subscriber->id]);
                continue;
            }
            $subscriber->viewfullnames = has_capability(
                'moodle/site:viewfullnames', $news->get_context(),
                $subscriber->id);
            $subscriber->emailtype =
                ($subscriber->viewfullnames ? 4 : 0) +
                ($subscriber->mailformat ? 1 : 0);
        }

    }

    /**
     * Sends an email. (Wrapper around email_to_user.)
     *
     * @param object $to User who receives email
     * @param mixed $from User or string who sent email
     * @param string $subject Subject line
     * @param string $text Text of email
     * @param string $html HTML of email or '' if plaintext only
     * @return bool Returns true if mail was sent OK and false if there was an error.
     */
    private static function email_send(object $to, $from, string $subject, string $text, string $html): bool {
        if (self::DEBUG_VIEW_EMAILS) {
            print "<div style='margin:4px; border:1px solid blue; padding:4px;'>";
            print "<h3>Email sent</h3>";
            print "<ul><li>From: <strong>" . (is_object($from) ? $from->email : $from) .
                "</strong></li>";
            print "<li>To: <strong>$to->email</strong></li>";
            print "<li>Subject: <strong>" . htmlspecialchars($subject) .
                "</strong></li></ul>";
            print $html;
            print "<pre style='border-top: 1px solid blue; padding-top: 4px; margin-top:4px;'>";
            print htmlspecialchars($text);
            print "</pre></div>";
            return false;
        }
        return email_to_user($to, $from, $subject, $text, $html, '', '');
    }
}
