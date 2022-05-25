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

/**
 * Tests the tool_datamasking class for this plugin.
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_datamasking_test extends \advanced_testcase {
    use \tool_datamasking\phpunit_clear_statics;

    protected function setUp(): void {
        \tool_datamasking\testing_utils::load_mapping_table_fixtures();
    }

    /**
     * Tests actual behaviour of the masking applied in this plugin.
     */
    public function test_behaviour(): void {
        global $DB;

        $this->resetAfterTest();

        // Set up data to be masked.
        $DB->insert_record('block_news_feeds', ['blockinstanceid' => '1', 'feedurl' =>
                'https://example.org/some/other/feed']);
        $DB->insert_record('block_news_feeds', ['blockinstanceid' => '1', 'feedurl' =>
                'https://example.org/blocks/news/feed.php?bi=123&username=abc1']);
        $DB->insert_record('block_news_feeds', ['blockinstanceid' => '1', 'feedurl' =>
                'https://example.org/blocks/news/feed.php?bi=123&username=qqq999']);

        // Before checks.
        $blocknewsfeedssql = 'SELECT feedurl FROM {block_news_feeds} ORDER BY id';
        $this->assertEquals([
                'https://example.org/some/other/feed',
                'https://example.org/blocks/news/feed.php?bi=123&username=abc1',
                'https://example.org/blocks/news/feed.php?bi=123&username=qqq999'
                ], $DB->get_fieldset_sql($blocknewsfeedssql));

        // Run the full masking plan including this plugin.
        \tool_datamasking\api::get_plan()->execute();

        // After checks.
        $this->assertEquals([
                'https://example.org/some/other/feed',
                'https://example.org/blocks/news/feed.php?bi=123&username=xyz1',
                'https://example.org/blocks/news/feed.php?bi=123&username=aaa00000'
                ], $DB->get_fieldset_sql($blocknewsfeedssql));
    }
}
