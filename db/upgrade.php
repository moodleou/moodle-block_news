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
 * DB upgrade applyer
 *
 * @package    blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_block_news_upgrade($oldversion) {
    global $DB;

    $result = true;
    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2025012000) {
        // Define field hideimages to be added to block_news.
        $table = new xmldb_table('block_news');
        $field = new xmldb_field('hideimages', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null,
            '0', 'hidelinks');

        // Conditionally launch add field hideimages.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2025012000, 'news');
    }

    return $result;

}
