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
 * A scheduled task for news block.
 *
 * @package block_news
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news\task;

use block_news\system;

class process_feeds extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('process_feeds_task', 'block_news');
    }

    /**
     * Get News block feeds to update and process them.
     */
    public function execute() {
        // System config.
        $config = get_config('block_news');

        // Get list of feeds.
        mtrace("\n" . 'Listing news feeds for update...', '');
        $starttime = microtime(true);
        $fbrecs = system::get_feeds_to_update();
        $endtime = microtime(true);
        mtrace(' done (' . round($endtime - $starttime, 1) . 's), ' . count($fbrecs) . ' feed(s) to process');

        if (!count($fbrecs)) {
            return;
        }
        // Store the ID of deleted messages to track their index.
        $deletemessageids = [];
        mtrace('Processing feeds...');
        $beginning = microtime(true);
        $done = 0;
        foreach ($fbrecs as $fbrec) {
            $bns = system::get_block_settings($fbrec->blockinstanceid);

            // When verbose mode is enabled, show every feed while being retrieved.
            $feedinfo = $bns->get_title() . ' (' . $fbrec->blockinstanceid . '): ' .
                $fbrec->feedurl.' - previous ' .
                userdate($fbrec->feedupdated, get_string('dateformatlong', 'block_news'));
            if ($config->verbosecron) {
                mtrace($feedinfo, '');
            }

            // Update feed.
            $starttime = microtime(true);
            $returndata = $bns->update_feed($fbrec);
            if ($returndata) {
                $deletemessageids = array_merge($deletemessageids, $returndata);
            }
            $now = microtime(true);
            $done++;

            // Show time if verbose mode is enabled or if it took over 5 seconds.
            if ($config->verbosecron) {
                mtrace(' (' . round($now - $starttime, 1) . 's)');
            } else if ($now - $starttime > 5) {
                mtrace($feedinfo . ' (' . round($now - $starttime, 1) . 's)');
            }

            // See if time limit has expired.
            if ($now - $beginning > $config->block_news_maxpercron) {
                break;
            }
        }
        if (!empty($deletemessageids)) {
            // Delete the index of deleted messages.
            \block_news\task\search_cleanup::trigger($deletemessageids);
        }
        mtrace($done . ' processed in ' . round($now - $beginning) . 's');
    }
}
