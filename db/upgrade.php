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

// Ref http://docs.moodle.org/dev/XMLDB_creating_new_DDL_functions.

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // It must be included from a Moodle page.
}

function xmldb_block_news_upgrade($oldversion) {
    global $DB;

    $result = true;
    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    // Add a new column newcol to the mdl_question_myqtype.
    if ($result && $oldversion < 2011062700) {
        // Changing precision of field currenthash on table block_news_feeds to (40).
        $table = new xmldb_table('block_news_feeds');
        $field = new xmldb_field('currenthash', XMLDB_TYPE_CHAR, '40', null, null, null, null,
            'feedurl');

        // Launch change of precision for field currenthash.
        $dbman->change_field_precision($table, $field);

        // News savepoint reached.
        upgrade_block_savepoint(true, 2011062700, 'news');
    }

    if ($oldversion < 2011071400) {
        // Changing type of field feedurl on table block_news_feeds to text.
        $table = new xmldb_table('block_news_feeds');

        // Changing type of field link on table block_news_messages to text.
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('link', XMLDB_TYPE_TEXT, 'small', null, null,
                                                                    null, null, 'title');

        // Launch change of type for field link.
        $dbman->change_field_type($table, $field);

        // News savepoint reached.
        upgrade_block_savepoint(true, 2011071400, 'news');
    }

    if ($oldversion < 2011071800) {
        // Define field publish to be dropped from block_news_messages.
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('publish');

        // Conditionally launch drop field publish.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // News savepoint reached.
        upgrade_block_savepoint(true, 2011071800, 'news');
    }

    if ($result && $oldversion < 2012031400) {
        // Changing precision of field title on table block_news_feeds to (80).
        $table = new xmldb_table('block_news');
        $field = new xmldb_field('title', XMLDB_TYPE_CHAR, '80', null, null, null, null,
                        'blockinstanceid');

        // Launch change of precision for field title.
        $dbman->change_field_precision($table, $field);

        // News savepoint reached.
        upgrade_block_savepoint(true, 2012031400, 'news');
    }

    if ($oldversion < 2012033000) {
        // Define and add the groupingsupport field.
        $table = new xmldb_table('block_news');
        $field = new xmldb_field('groupingsupport');
        $type = XMLDB_TYPE_INTEGER;
        $field->set_attributes($type, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'hidelinks');
        $dbman->add_field($table, $field);

        // Define and add the message groupingid field.
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('groupingid');
        $field->set_attributes($type, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'userid');
        $dbman->add_field($table, $field);

        // News savepoint reached.
        upgrade_block_savepoint(true, 2012033000, 'news');
    }

    if ($result && $oldversion < 2015081300) {
        // The title field is inconsistent in length and not-null state.
        $table = new xmldb_table('block_news');
        $field = new xmldb_field('title', XMLDB_TYPE_CHAR, '80', null, XMLDB_NOTNULL, null, null,
                'blockinstanceid');

        // Set length and not null to match expected.
        $dbman->change_field_precision($table, $field);
        $dbman->change_field_notnull($table, $field);

        // Savepoint reached.
        upgrade_block_savepoint(true, 2015081300, 'news');
    }

    if ($result && $oldversion < 2016111501) {
        // Define field groupid to be added to block_news_messages.
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'groupingid');

        // Conditionally launch add field groupid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // News savepoint reached.
        upgrade_block_savepoint(true, 2016111501, 'news');
    }

    if ($result && $oldversion < 2016122000) {
        // Define field messagetype to be added to block_news_messages.
        // Whether message is a 'news item' (1) or calendar event (2).
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('messagetype', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'messageformat');

        // Conditionally launch add field messagetype.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // News savepoint reached.
        upgrade_block_savepoint(true, 2016122000, 'news');
    }

    if ($oldversion < 2017011100) {

        // Define field displaytype to be added to block_news.
        $table = new xmldb_table('block_news');
        $field = new xmldb_field('displaytype', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'groupingsupport');

        // Conditionally launch add field displaytype.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // News savepoint reached.
        upgrade_block_savepoint(true, 2017011100, 'news');
    }

    if ($oldversion < 2017013100) {

        // Define field eventstart to be added to block_news_messages.
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('eventstart', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'messagetype');

        // Conditionally launch add field eventstart.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field eventend to be added to block_news_messages.
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('eventend', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'eventstart');

        // Conditionally launch add field eventend.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field eventlocation to be added to block_news_messages.
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('eventlocation', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'eventend');

        // Conditionally launch add field eventlocation.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // News savepoint reached.
        upgrade_block_savepoint(true, 2017013100, 'news');
    }

    if ($oldversion < 2017061400) {

        $transaction = $DB->start_delegated_transaction();
        // Define table block_news_message_groups to be created.
        $table = new xmldb_table('block_news_message_groups');

        // Adding fields to table block_news_message_groups.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('messageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_news_message_groups.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('messageid', XMLDB_KEY_FOREIGN, array('messageid'), 'block_news_messages', array('id'));
        $table->add_key('groupid', XMLDB_KEY_FOREIGN, array('groupid'), 'groups', array('id'));

        // Conditionally launch create table for block_news_message_groups.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Migrate group links to new table.
        $groupmessages = $DB->get_records_select('block_news_messages', 'groupid != ?', [0]);
        foreach ($groupmessages as $message) {
            $messagegroup = (object) ['messageid' => $message->id, 'groupid' => $message->groupid];
            $DB->insert_record('block_news_message_groups', $messagegroup, false, true);
        }
        $groupingmessages = $DB->get_records_select('block_news_messages', 'groupingid != ?', [0]);
        foreach ($groupingmessages as $message) {
            $groups = $DB->get_records('groupings_groups', ['groupingid' => $message->groupingid]);
            foreach ($groups as $group) {
                $messagegroup = (object) ['messageid' => $message->id, 'groupid' => $group->groupid];
                $DB->insert_record('block_news_message_groups', $messagegroup, false, true);
            }
        }

        // Define field groupid to be dropped from block_news_messages.
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('groupid');

        // Conditionally launch drop field groupid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field groupingid to be dropped from block_news_messages.
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('groupingid');

        // Conditionally launch drop field groupingid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Switch blocks in "grouping" mode to "group" mode.
        $DB->set_field('block_news', 'groupingsupport', 2, ['groupingsupport' => 1]);

        // Fix invalid foreign keys.
        $DB->set_field('block_news_messages', 'newsfeedid', null, ['newsfeedid' => 0]);
        $transaction->allow_commit();
        upgrade_block_savepoint(true, 2017061400, 'news');
    }

    if ($oldversion < 2020072202) {

        // Define field imagedesc to be added to block_news_messages.
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('imagedesc', XMLDB_TYPE_TEXT, null, null, null, null, null, 'eventlocation');

        // Conditionally launch add field imagedesc.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('imagedescnotnecessary', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'imagedesc');

        // Conditionally launch add field imagedescnotnecessary.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // News savepoint reached.
        upgrade_block_savepoint(true, 2020072202, 'news');
    }

    if ($oldversion < 2020101300) {

        // Define field currenthash to be added to block_news_messages.
        $table = new xmldb_table('block_news_messages');
        $field = new xmldb_field('currenthash', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'imagedescnotnecessary');

        // Conditionally launch add field currenthash.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // News savepoint reached.
        upgrade_block_savepoint(true, 2020101300, 'news');
    }

    return $result;

}
