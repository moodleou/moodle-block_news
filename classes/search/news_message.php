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
 * Search area for block_news blocks
 *
 * @package block_news
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news\search;

use block_news\system;
use block_news\message;

defined('MOODLE_INTERNAL') || die();

/**
 * Search area for block_news blocks
 *
 * @package block_news
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class news_message extends \core_search\base_block {

    public function get_recordset_by_timestamp($modifiedfrom = 0) {
        global $DB;

        return $DB->get_recordset_sql("
                SELECT bnm.id AS id, bi.configdata, bn.title, bi.id AS instance, bnm.title AS messagetitle,
                       bnm.message AS message, bnm.messageformat, bnm.hideauthor, bnm.timemodified AS timemodified,
                       bnm.userid AS userid, bnm.messagedate, c.id AS contextid, co.id AS courseid
                  FROM {block_news_messages} bnm
                  JOIN {block_news} bn ON bn.blockinstanceid = bnm.blockinstanceid
                  JOIN {block_instances} bi ON bn.blockinstanceid = bi.id
                  JOIN {context} c ON c.instanceid = bi.id AND c.contextlevel = ?
                  JOIN {context} coursecontext ON coursecontext.id = bi.parentcontextid AND coursecontext.contextlevel = ?
                  JOIN {course} co ON co.id = coursecontext.instanceid
                 WHERE bi.blockname = 'news'
                       AND bnm.timemodified >= ?
              ORDER BY bnm.timemodified ASC",
                array(CONTEXT_BLOCK, CONTEXT_COURSE, $modifiedfrom));
    }

    public function get_document($record, $options = array()) {

        // Create empty document.
        $doc = \core_search\document_factory::instance($record->id,
                $this->componentname, $this->areaname);

        // Get stdclass object with data from DB.
        $data = unserialize(base64_decode($record->configdata));

        // Get content.
        $content = content_to_text($record->message, $record->messageformat);
        $doc->set('content', $content);

        // Title.
        $doc->set('title', content_to_text($record->messagetitle, false));

        // Set standard fields.
        $doc->set('contextid', $record->contextid);
        $doc->set('type', \core_search\manager::TYPE_TEXT);
        $doc->set('courseid', $record->courseid);
        $doc->set('modified', $record->timemodified);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);

        if (!$record->hideauthor && $record->userid) {
            $doc->set('userid', $record->userid);
        }

        return $doc;
    }

    public function uses_file_indexing() {
        return true;
    }

    public function attach_files($document) {
        $fs = get_file_storage();

        $context = \context::instance_by_id($document->get('contextid'));

        $files = $fs->get_area_files($context->id, 'block_news', 'message');
        foreach ($files as $file) {
            $document->add_stored_file($file);
        }
    }

    public function check_access($id) {
        global $USER, $DB;

        // Load message.
        $sql = system::get_message_sql_start() .
                'WHERE m.id = ?';
        $mrec = $DB->get_record_sql($sql, array('id' => $id), IGNORE_MISSING);
        if (!$mrec) {
            return \core_search\manager::ACCESS_DELETED;
        }

        // Check parent class to verify block access.
        $parentresult = parent::check_access($mrec->blockinstanceid);
        if ($parentresult != \core_search\manager::ACCESS_GRANTED) {
            return $parentresult;
        }

        // Check groups.
        $groupids = $DB->get_fieldset_select('block_news_message_groups', 'groupid', 'messageid = ?', [$mrec->id]);
        $bnm = new message($mrec, $groupids);
        $blockcontext = \context_block::instance($mrec->blockinstanceid);
        if (!$bnm->is_visible_to_students()) {
            if (!has_capability('block/news:viewhidden', $blockcontext)) {
                return \core_search\manager::ACCESS_DENIED;
            }
        }

        $bns = system::get_block_settings($mrec->blockinstanceid);
        if ($bns->get_groupingsupport() == $bns::RESTRICTBYGROUP &&
                !has_capability('moodle/site:accessallgroups', $blockcontext)) {
            $messagegroups = $bnm->get_groupids();

            if (!empty($messagegroups)) {
                $coursesql = "
                SELECT coursecontext.instanceid
                  FROM {block_instances} bi
                  JOIN {context} coursecontext ON coursecontext.id = bi.parentcontextid AND coursecontext.contextlevel = ?
                 WHERE bi.blockname = 'news' AND bi.id = ?";
                $thecourseid = $DB->get_field_sql($coursesql, array(CONTEXT_COURSE, $mrec->blockinstanceid, MUST_EXIST));

                $allowedgroups = [];
                $allowedgroups = array_merge($allowedgroups, $bns->get_groupids($USER->id, $thecourseid));

                // Check that at least one group the message is visible to is accessible to the user.
                if (empty(array_intersect($messagegroups, $allowedgroups))) {
                    return \core_search\manager::ACCESS_DENIED;
                }
            }
        }
        return \core_search\manager::ACCESS_GRANTED;
    }

    public function get_doc_url(\core_search\document $doc) {
        return new \moodle_url('/blocks/news/message.php', array('m' => $doc->get('itemid')));
    }

    public function get_context_url(\core_search\document $doc) {
        return $this->get_doc_url($doc);
    }
}
