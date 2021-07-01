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
 * Tests the feed_url_mask class.
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feed_url_mask_test extends \advanced_testcase {

    protected function setUp(): void {
        \tool_datamasking\testing_utils::load_mapping_table_fixtures();
    }

    protected function tearDown(): void {
        \tool_datamasking\mapping_tables::reset();
    }

    /**
     * Tests executing the mask.
     */
    public function test_execute(): void {
        $mask = new feed_url_mask();

        // No change for other URL.
        $this->assertEquals([], $mask->execute([],
                (object)['feedurl' => 'https://example.org']));
        // No change for URL mostly matching pattern but with no/blank username.
        $baseurl = 'https://example.org/blocks/news/feed.php?';
        $this->assertEquals([], $mask->execute([], (object)['feedurl' => $baseurl . 'bi=1']));
        $this->assertEquals([], $mask->execute([], (object)['feedurl' => $baseurl . 'bi=1&username=']));
        // Change (mapped value) for username in either order.
        $this->assertEquals(['feedurl' => $baseurl . 'bi=1&username=xyz1'],
                $mask->execute([], (object)['feedurl' => $baseurl . 'bi=1&username=abc1']));
        $this->assertEquals(['feedurl' => $baseurl . 'username=uvw2&bi=2'],
                $mask->execute([], (object)['feedurl' => $baseurl . 'username=def2&bi=2']));
        // Change (unmapped value).
        $this->assertEquals(['feedurl' => $baseurl . 'bi=1&username=aaa00000'],
                $mask->execute([], (object)['feedurl' => $baseurl . 'bi=1&username=q999']));
    }

    /**
     * Tests the get_affected_fields function.
     */
    public function test_get_affected_fields(): void {
        $mask = new feed_url_mask();
        $this->assertEquals(['feedurl'], $mask->get_affected_fields());
    }

    /**
     * Tests the description text.
     */
    public function test_get_description_text(): void {
        $mask = new feed_url_mask();
        $this->assertEquals('Replace the OUCU in certain feed URLs with an OUCU from appropriate ' .
                'mapping table', $mask->get_description_text());
    }
}
