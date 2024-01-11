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
 * Search Engine Advanced PHPUnit test case.
 *
 * @package   block_news
 * @category  phpunit
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/search/engine/solr/tests/fixtures/testable_engine.php');
require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');

/**
 * Search Engine Advanced PHPUnit test case.
 *
 * @package   block_news
 * @category  phpunit
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class search_engine_advance_testcase extends \advanced_testcase {
    /**
     * @var string|null Area id.
     */
    protected $newsmessageareaid = null;

    /** @var \search_solr\engine Search engine. */
    protected $engine;

    /** @var \core_search\manager Search manager. */
    protected $search;

    /**
     * Converts recordset to array, indexed numberically (0, 1, 2).
     *
     * @param \moodle_recordset $rs Record set to convert.
     * @return \stdClass[] Array of converted records.
     */
    protected static function recordset_to_array(\moodle_recordset $rs): array {
        $result = [];
        foreach ($rs as $rec) {
            $result[] = $rec;
        }
        $rs->close();
        return $result;
    }

    /**
     * Sets up the Solr search engine for testing.
     * @return bool True when we setup successfully.
     */
    protected function solr_setup(): bool {
        set_config('enableglobalsearch', true);
        set_config('searchengine', 'solr');

        if (!function_exists('solr_get_version')) {
            $this->markTestSkipped('Solr extension is not loaded.');
        }

        if (!defined('TEST_SEARCH_SOLR_HOSTNAME') || !defined('TEST_SEARCH_SOLR_INDEXNAME') ||
            !defined('TEST_SEARCH_SOLR_PORT')) {
            $this->markTestSkipped('Solr extension test server not set.');
        }

        set_config('server_hostname', TEST_SEARCH_SOLR_HOSTNAME, 'search_solr');
        set_config('server_port', TEST_SEARCH_SOLR_PORT, 'search_solr');
        set_config('indexname', TEST_SEARCH_SOLR_INDEXNAME, 'search_solr');

        if (defined('TEST_SEARCH_SOLR_USERNAME')) {
            set_config('server_username', TEST_SEARCH_SOLR_USERNAME, 'search_solr');
        }

        if (defined('TEST_SEARCH_SOLR_PASSWORD')) {
            set_config('server_password', TEST_SEARCH_SOLR_PASSWORD, 'search_solr');
        }

        if (defined('TEST_SEARCH_SOLR_SSLCERT')) {
            set_config('secure', true, 'search_solr');
            set_config('ssl_cert', TEST_SEARCH_SOLR_SSLCERT, 'search_solr');
        }

        if (defined('TEST_SEARCH_SOLR_SSLKEY')) {
            set_config('ssl_key', TEST_SEARCH_SOLR_SSLKEY, 'search_solr');
        }

        if (defined('TEST_SEARCH_SOLR_KEYPASSWORD')) {
            set_config('ssl_keypassword', TEST_SEARCH_SOLR_KEYPASSWORD, 'search_solr');
        }

        if (defined('TEST_SEARCH_SOLR_CAINFOCERT')) {
            set_config('ssl_cainfo', TEST_SEARCH_SOLR_CAINFOCERT, 'search_solr');
        }

        // Use the testable engine fixture.
        $this->engine = new \search_solr\testable_engine();
        $this->search = \testable_core_search::instance($this->engine);

        // Cleanup before doing anything on it as the index it is out of this test control.
        $this->search->delete_index();

        // Add moodle fields if they don't exist.
        $schema = new \search_solr\schema();
        $schema->setup(false);

        return true;
    }

    /**
     * Carries out a raw Solr query using the Solr basic query syntax.
     *
     * This is used to test data contained in the index without going through Moodle processing.
     *
     * @param string $q Search query
     * @param string[] $expected Expected titles of results, in alphabetical order
     */
    public function assert_raw_solr_query_result(string $q, array $expected): void {
        $solr = $this->engine->get_search_client_public();
        $query = new \SolrQuery($q);
        $results = $solr->query($query)->getResponse()->response->docs;
        $titles = [];
        if ($results) {
            $titles = array_map(function($x) {
                return $x->title;
            }, $results);
            sort($titles);
        }

        $this->assertEquals($expected, $titles);
    }
}
