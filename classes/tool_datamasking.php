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
 * Implementation of data masking for this plugin.
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_datamasking implements \tool_datamasking\plugin {
    public function build_plan(\tool_datamasking\plan $plan): void {
        $plan->table('block_news_feeds')->add((new feed_url_mask())->set_negative_tags(
                [\tool_datamasking\tool_datamasking::TAG_SKIP_ID_MAPPING])->
                set_solo_tags([\tool_datamasking\tool_datamasking::TAG_SOLO_ID_MAPPING]));
    }
}
