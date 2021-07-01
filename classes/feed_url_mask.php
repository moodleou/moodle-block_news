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
 * Data masking mask to catch OUCUs in feed URLs.
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feed_url_mask extends \tool_datamasking\mask {

    /** @var \tool_datamasking\mapping_table Mapping table */
    protected $mapping = null;

    public function execute(array $options, \stdClass $rec): array {
        if (!$this->mapping) {
            // Set up the mapping and pattern spec (in case not defined elsewhere).
            $this->mapping = \tool_datamasking\mapping_tables::get(\tool_datamasking\mapping_tables::OUCU);
            $this->mapping->pattern(\tool_datamasking\identifier_pattern::OUCU_PATTERN_SPEC);
        }

        // Example URL https://learn2acct.open.ac.uk/blocks/news/feed.php?bi=166766&username=abc2.
        if (preg_match('~^(.*/blocks/news/feed\.php\?.*username=)([a-z]+[0-9]+)(.*)$~', $rec->feedurl, $matches)) {
            [1 => $before, 2 => $username, 3 => $after] = $matches;
            $newusername = $this->mapping->map($username, true);
            return ['feedurl' => $before . $newusername . $after];
        } else {
            return [];
        }
    }

    public function get_affected_fields(): array {
        return ['feedurl'];
    }

    public function get_description_text(): string {
        return get_string('feed_url_mask', 'block_news');
    }
}
