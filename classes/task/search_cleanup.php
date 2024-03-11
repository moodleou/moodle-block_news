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
 * Cleans up old data from the search engine.
 *
 * @package block_news
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news\task;

/**
 * Cleans up old data from the search engine.
 */
class search_cleanup extends \core\task\adhoc_task {
    /** @var int Number of seconds within which we must have seen a successful search index */
    const RECENT_INDEX_TIME = 600;

    /**
     * Deletes the provided message IDs from search.
     */
    public function execute() {
        global $DB;

        // Just in case search indexing has been turned off since this task was scheduled!
        if (!\core_search\manager::is_indexing_enabled()) {
            return;
        }

        // If search index has not run for the past 10 minutes then bail out (in case these tasks
        // are preventing it from running).
        $time = time() - self::RECENT_INDEX_TIME;
        // Note: In the task_log table, the class name does not have an initial \.
        if ($DB->count_records_select('task_log', 'classname = ?', ['core\task\search_index_task'])) {
            if (!$DB->record_exists_select('task_log', 'classname = ? AND result = ? AND timeend > ?',
                ['core\task\search_index_task', 0, $time])) {
                // Throwing an exception here should cause it to retry later.
                throw new \coding_exception('Search index task has not completed within 10 minutes');
            }
        }

        $area = \core_search\manager::generate_areaid('block_news', 'news_message');
        $cronlockfactory = \core\lock\lock_config::get_lock_factory('cron');
        if (!($lock = $cronlockfactory->get_lock('\core\task\search_index_task', 0))) {
            throw new \coding_exception('Search index task is currently running');
        }
        try {
            $ids = $this->get_custom_data();
            $search = \core_search\manager::instance();
            foreach ($ids as $id) {
                $search->delete_index_by_id($area . '-' . $id);
            }
            mtrace('Deleted ' . count($ids) . ' old news messages search data entries');
        } finally {
            $lock->release();
        }
    }

    /**
     * Returns default concurrency limit for this task.
     *
     * @return int default concurrency limit
     */
    protected function get_default_concurrency_limit(): int {
        // Don't do more than one of these at once.
        return 1;
    }

    /**
     * Call to delete the item from the search index.
     *
     * @param array $messageids Array of message ids.
     */
    public static function trigger(array $messageids): void {
        global $CFG;

        if (empty($CFG->block_news_disablesearchcleanup) && \core_search\manager::is_indexing_enabled()) {
            if (!empty($messageids)) {
                $task = new search_cleanup();
                $task->set_custom_data($messageids);
                \core\task\manager::queue_adhoc_task($task);
            }
        }
    }
}
