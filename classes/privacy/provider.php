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
 * Privacy class for requesting user data.
 *
 * @package block_news
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news\privacy;
defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\helper;

require_once($CFG->dirroot . '/blocks/news/classes/search/news_message.php');

/**
 * Privacy for block news
 *
 * @package block_news
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {
    const FILEAREA_TYPES = ['message', 'messageimage', 'attachment'];

    /**
     * Provides meta data that is stored about a user with block_news.
     *
     * @param collection $collection A collection of meta data items to be added to.
     * @return collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('block_news_messages', [
                'user'           => 'privacy:metadata:block_news_messages:user',
                'title'          => 'privacy:metadata:block_news_messages:title',
                'message'        => 'privacy:metadata:block_news_messages:message',
                'link'           => 'privacy:metadata:block_news_messages:link',
                'messageformat'  => 'privacy:metadata:block_news_messages:messageformat',
                'messagedate'    => 'privacy:metadata:block_news_messages:messagedate',
                'messagevisible' => 'privacy:metadata:block_news_messages:messagevisible',
                'messagerepeat'  => 'privacy:metadata:block_news_messages:messagerepeat',
                'hideauthor'     => 'privacy:metadata:block_news_messages:hideauthor',
                'timemodified'   => 'privacy:metadata:block_news_messages:timemodified',
                'messagetype'    => 'privacy:metadata:block_news_messages:messagetype',
                'eventstart'     => 'privacy:metadata:block_news_messages:eventstart',
                'eventend'       => 'privacy:metadata:block_news_messages:eventend',
                'eventlocation'  => 'privacy:metadata:block_news_messages:eventlocation',
                'imagedesc'      => 'privacy:metadata:block_news_messages:imagedesc',
                'imagedescnotnecessary' => 'privacy:metadata:block_news_messages:imagedescnotnecessary'
        ], 'privacy:metadata:block_news_messages');
        $collection->link_subsystem('core_files', 'privacy:metadata:core_files');

        // The block_news_subscriptions table store which forum user is subscribed to by email.
        $collection->add_database_table('block_news_subscriptions', [
            'userid' => 'privacy:metadata:block_news_subscriptions:userid',
            'subscribed' => 'privacy:metadata:block_news_subscriptions:subscribed',
        ], 'privacy:metadata:block_news_subscriptions');

        return $collection;
    }

    /**
     * Removes personally-identifiable data from a user id for export.
     *
     * @param int $userid User id of a person
     * @param \stdClass $user Object representing current user being considered
     * @return string 'You' if the two users match, 'Somebody else' otherwise
     * @throws \coding_exception
     */
    protected static function you_or_somebody_else($userid, $user) {
        if ($userid == $user->id) {
            return get_string('privacy_you', 'block_news');
        } else {
            return get_string('privacy_somebodyelse', 'block_news');
        }
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {block_news_messages} m ON m.blockinstanceid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel AND m.userid = :userid";
        $contextlist->add_from_sql($sql, ['subuserid' => $userid, 'userid' => $userid, 'contextlevel' => CONTEXT_BLOCK]);

        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {block_news_subscriptions} sub ON sub.blockinstanceid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel AND sub.userid = :userid";
        $contextlist->add_from_sql($sql, ['userid' => $userid, 'contextlevel' => CONTEXT_BLOCK]);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        foreach ($contextlist as $context) {
            $sql = 'SELECT *
                      FROM {block_news_messages}
                     WHERE userid = :userid AND blockinstanceid = :instanceid
                  ORDER BY id ASC';
            $params = [
                    'instanceid' => $context->instanceid,
                    'userid'     => $user->id,
            ];

            $contextdata = helper::get_context_data($context, $user);
            $contextdata->news = [];

            $recordset = $DB->get_recordset_sql($sql, $params);
            foreach ($recordset as $record) {
                $message = writer::with_context($context)->rewrite_pluginfile_urls([$record->id],
                        'block_news', 'message', $record->id, $record->message);
                $data = (object) [
                        'title'          => $record->title,
                        'user'           => self::you_or_somebody_else($record->userid, $user),
                        'link'           => $record->link,
                        'message'        => $message,
                        'messageformat'  => $record->messageformat,
                        'messagedate'    => transform::date($record->timemodified),
                        'messagevisible' => transform::yesno($record->messagevisible),
                        'hideauthor'     => transform::yesno($record->hideauthor),
                        'messagerepeat'  => transform::yesno($record->messagerepeat),
                        'messageformat'  => $record->messageformat,
                        'timemodified'   => transform::datetime($record->timemodified),
                        'messagetype'    => $record->messagetype,
                        'eventstart'     => transform::datetime($record->eventstart),
                        'eventend'       => transform::datetime($record->eventend),
                        'eventlocation'  => $record->eventlocation,
                        'imagedesc'      => $record->imagedesc,
                        'imagedescnotnecessary' => $record->imagedescnotnecessary
                ];

                $contextdata->news[$record->title] = $data;
                writer::with_context($context)->export_area_files([], 'block_news', 'message', $record->id);
                writer::with_context($context)->export_area_files([], 'block_news', 'attachment', $record->id);
                writer::with_context($context)->export_area_files([], 'block_news', 'messageimage', $record->id);
            }

            static::export_block_news_subscription_data($user, $context);
            writer::with_context($context)->export_data([], $contextdata);
            $recordset->close();
        }
    }

    /**
     * Store all information about all subscription that we have detected this user to have access to in block news.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param \context_block $context The instance of the block news context.
     *
     * @return void
     */
    protected static function export_block_news_subscription_data($user, \context_block $context) {
        global $DB;
        $sql = "SELECT *
                  FROM {block_news_subscriptions}
                 WHERE userid = :userid
                       AND blockinstanceid = :blockinstanceid";
        $params = [
            'userid' => $user->id,
            'blockinstanceid' => $context->instanceid,
        ];
        $subscriptions = $DB->get_recordset_sql($sql, $params);
        if ($subscriptions) {
            foreach ($subscriptions as $subscription) {
                $subscriptiondata = (object) [
                    'userid' => self::you_or_somebody_else($subscription->userid, $user),
                    'subscribed' => transform::yesno($subscription->subscribed),
                ];
                // Store the block news subscription.
                $area = get_string('blocknewssubscriptions', 'block_news') . '-' . $subscription->id;
                writer::with_context($context)->export_data(
                    [$area], $subscriptiondata);
            }
        }
        $subscriptions->close();
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context $context The specific context to delete data for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_BLOCK) {
            return;
        }

        // Update message related.
        $sql = 'UPDATE {block_news_messages}
                   SET userid = :userid
                 WHERE blockinstanceid = :instanceid';
        $param = [
                'instanceid' => $context->instanceid,
                'userid'     => get_admin()->id
        ];
        $DB->execute($sql, $param);

        // Update attached files.
        list($filearea, $params) = $DB->get_in_or_equal(self::FILEAREA_TYPES, SQL_PARAMS_NAMED);
        $sqlfile = "UPDATE {files}
                       SET userid = :userid
                     WHERE contextid = :contextid AND component = :component
                           AND filearea {$filearea}";
        $paramfile = [
                'userid'    => get_admin()->id,
                'contextid' => $context->id,
                'component' => 'block_news'
        ];
        $DB->execute($sqlfile, array_merge($paramfile, $params));

        // Delete subscriptions.
        $sql = "DELETE
                  FROM {block_news_subscriptions}
                 WHERE blockinstanceid = :instanceid";
        $param = [
            'instanceid' => $context->instanceid
        ];
        $DB->execute($sql, $param);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_BLOCK) {
                continue;
            }
            $sql = 'UPDATE {block_news_messages}
                       SET userid = :adminid
                     WHERE blockinstanceid = :instanceid AND userid = :userid';
            $param = [
                    'instanceid' => $context->instanceid,
                    'adminid'    => get_admin()->id,
                    'userid'     => $userid
            ];
            $DB->execute($sql, $param);

            // Delete subscriptions by user id.
            $sql = "DELETE
                      FROM {block_news_subscriptions}
                     WHERE blockinstanceid = :instanceid AND userid = :userid";
            $param = [
                    'instanceid' => $context->instanceid,
                    'userid'     => $userid
            ];
            $DB->execute($sql, $param);
        }

        // Update attached files.
        list($filearea, $params) = $DB->get_in_or_equal(self::FILEAREA_TYPES, SQL_PARAMS_NAMED);
        $sqlfile = "UPDATE {files}
                       SET userid = :adminid
                     WHERE component = :component AND userid = :userid
                           AND filearea {$filearea}";
        $paramfile = [
                'adminid'   => get_admin()->id,
                'userid'    => $userid,
                'component' => 'block_news'
        ];
        $DB->execute($sqlfile, array_merge($paramfile, $params));
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist Containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_block) {
            return;
        }

        $params = [
                'instanceid' => $context->instanceid,
                'blockname'  => 'news'
        ];

        // Select user from block news messages table.
        $sql = "SELECT bnm.userid
                  FROM {block_instances} bi
                  JOIN {block_news_messages} bnm ON bnm.blockinstanceid = bi.id
                 WHERE bi.id = :instanceid AND bi.blockname = :blockname";
        $userlist->add_from_sql('userid', $sql, $params);

        // Get list of users for "block_news_subscriptions" table.
        $sql = "SELECT sub.userid
                  FROM {block_instances} bi
                  JOIN {block_news_subscriptions} sub ON sub.blockinstanceid = bi.id
                 WHERE bi.id = :instanceid
                       AND bi.blockname = :blockname";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();

        if ($context instanceof \context_block && ($blockinstance = static::get_instance_from_context($context))) {
            // Update messages.
            list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $params = array_merge([
                    'blockinstanceid' => $context->instanceid,
                    'adminid'         => get_admin()->id
            ], $userinparams);
            $sql = "UPDATE {block_news_messages}
                       SET userid = :adminid
                     WHERE blockinstanceid = :blockinstanceid AND userid {$userinsql}";
            $DB->execute($sql, $params);

            // Update attached files.
            list($filearea, $params) = $DB->get_in_or_equal(self::FILEAREA_TYPES, SQL_PARAMS_NAMED);
            $sqlfile = "UPDATE {files}
                           SET userid = :adminid
                         WHERE component = :component AND userid {$userinsql}
                               AND filearea {$filearea} AND contextid = :contextid";
            $paramfile = [
                    'adminid'   => get_admin()->id,
                    'component' => 'block_news',
                    'contextid' => $context->id
            ];
            $DB->execute($sqlfile, array_merge($paramfile, $params, $userinparams));

            // Delete subscriptions.
            $sql = "DELETE
                      FROM {block_news_subscriptions}
                     WHERE blockinstanceid = :instanceid AND userid {$userinsql}";
            $DB->execute($sql, array_merge(['instanceid' => $context->instanceid], $userinparams));
        }
    }

    /**
     * Get the block instance record for the specified context.
     *
     * @param   \context_block $context The context to fetch
     * @return  \stdClass
     */
    protected static function get_instance_from_context(\context_block $context) {
        global $DB;

        return $DB->get_record('block_instances', ['id' => $context->instanceid, 'blockname' => 'news']);
    }
}
