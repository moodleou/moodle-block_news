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
 * Form for editing news block instances.
 *
 * @package blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Stacey Walker <stacey@catalyst-eu.net>
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use block_news\system;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // It must be included from a Moodle page.
}

/**
 * Standard attachments handler
 *
 * This code is used when users view messages in the VLE.
 * When they view images and attachments in feeds, files.php is used instead.
 *
 * @param course $course
 * @param $birecordorcm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 */
function block_news_pluginfile($course, $birecordorcm, $context, $filearea, $args,
                                $forcedownload) {

    if ($context->contextlevel != CONTEXT_BLOCK) {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    $newsid = (int)array_shift($args);
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/block_news/$filearea/$newsid/$relativepath$filename";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    if ($parentcontext = context::instance_by_id($birecordorcm->parentcontextid)) {
        if ($parentcontext->contextlevel == CONTEXT_USER) {
            // Force download on all personal pages including /my/
            // because we do not have reliable way to find out from where this is used.
            $forcedownload = true;
        }
    } else {
        // Weird, there should be parent context, better force dowload then.
        $forcedownload = true;
    }

    \core\session\manager::write_close();
    send_stored_file($file, 60 * 60, 0, $forcedownload);
}

/**
 * Calls require_login and adds standard breadcrumb item.
 * @param int $blockinstanceid
 * @param string $title Block title or null for default
 * @param int $displaytype The block's displaytype (block_news\system::DISPLAY_* constant)
 * @return StdClass Course/Module identity information (useful for Form redirect logic)
 */
function block_news_init_page($blockinstanceid, $title, $displaytype = system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS, $islogin = true) {
    global $PAGE, $CFG;

    $csemod = block_news_get_course_mod_info($blockinstanceid);
    if (!$csemod) {
        return null;
    }
    if ($displaytype == system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS) {
        $PAGE->add_body_class('block-news-all-news-and-events');
        $PAGE->set_pagelayout('base');
    } else {
        $PAGE->set_pagelayout('incourse');
    }
    // Coursecontext set_context should have been set to block context, but this causes problems.
    // Store the block context in a global so we can access it when needed in other plugins.
    $CFG->block_news_blockcontext = context_block::instance($blockinstanceid);
    $coursecontext = context_course::instance($csemod->course->id);
    $PAGE->set_context($coursecontext);
    if ($islogin) {
        require_course_login($csemod->course);
    }

    if (empty($title)) {
        $title = get_string('pluginname', 'block_news');
    }

    $renderer = $PAGE->get_renderer('block_news');
    $PAGE->navbar->add($renderer->get_breadcrumb_title($blockinstanceid, $displaytype, $title),
            new moodle_url('/blocks/news/all.php', array('bi' => $blockinstanceid)));

    return $csemod;
}

/**
 * Extract names and ids of courses and modules in the current context
 *
 * @param integer $blockinstanceid
 * @return StdClass Course/Module identity information
 */
function block_news_get_course_mod_info($blockinstanceid) {
    global $DB;

    $cse = null;
    $csemod = new StdClass();

    // Context instance.
    $cibi = context_block::instance($blockinstanceid);
    if (!$cibi) {
        return null;
    }

    $ctxa = explode('/', $cibi->path); // E.g: /1/46/53/318.
    unset($ctxa[0]);
    $ctxin = implode(', ', $ctxa);  // E.g: 1, 46, 53, 318.

    // Create "IN" SQL.
    list($insql, $params) = $DB->get_in_or_equal($ctxa, SQL_PARAMS_QM);

    // Get course related to the context.
    $sql = 'SELECT {course}.* FROM {context}
             INNER JOIN {course} ON {context}.instanceid = {course}.id
             WHERE {context}.contextlevel = '.CONTEXT_COURSE;
    $sql .= " AND {context}.id $insql";
    $cse = $DB->get_record_sql($sql, $params);

    // Get module info via course_module table to get extra info if block is
    // called from a module (glossary, page etc).
    $sql = 'SELECT * FROM {context}
         INNER JOIN {course_modules} ON {context}.instanceid = {course_modules}.id
         WHERE {context}.contextlevel = '.CONTEXT_MODULE;
    $sql .= " AND {context}.id $insql";
    $cmrec = $DB->get_record_sql($sql, $params);

    if (!empty($cmrec)) {
        $csemodid = $cmrec->instanceid;
    }

    $mi = get_fast_modinfo($cse);
    $coursecategory = $DB->get_record('course_categories', array('id' => $cse->category));

    // Build info object.
    $csemod->course = $cse;
    $csemod->context = $cibi;
    $csemod->cseid = $cse->id;
    $csemod->cseshortname = $cse->shortname;
    $csemod->csefullname = $cse->fullname;
    $csemod->categoryid = $coursecategory->id;
    $csemod->categoryname = $coursecategory->name;
    if (!empty($csemodid)) {
        $csemod->modid = $mi->cms[$csemodid]->id;
        $csemod->modtype = $mi->cms[$csemodid]->modname;
        $csemod->modname = $mi->cms[$csemodid]->name;
    }

    return $csemod;
}

/**
 * Adjust new messagedate time based on old and
 * new course start dates
 *
 * @param integer $oldtime old messagedate
 * @param integer $offset difference between startdates
 * @return integer new message date
 */
function block_news_get_new_time($oldtime, $offset) {
    $newtime = $oldtime + $offset;
    $newinfo = getdate($newtime);
    $oldinfo = getdate($oldtime);

    if ($newinfo['hours'] === $oldinfo['hours']) {
        return $newtime;
    }

    // Adjust.
    $oldhours = $oldinfo['hours'];
    $minutes = $newinfo['minutes'];
    $seconds = $newinfo['seconds'];
    $month = $newinfo['mon'];
    $day = $newinfo['mday'];
    $year = $newinfo['year'];
    $a = mktime($oldhours, $minutes, $seconds, $month, $day, $year);

    // Adjust by 1 day forward/backward.
    if ($a < $newtime) {
        $b = strtotime('+1 day', $a);
    } else if ($a > $newtime) {
        $b = strtotime('-1 day', $a);
    }

    // Return the candidate closest
    // to the original estimate.
    $offseta = abs($newtime - $a);
    $offsetb = abs($newtime - $a);
    if ($offseta < $offsetb) {
        return $a;
    } else {
        return $b;
    }
}

/**
 * Obtain the top news block given the course short name.
 *
 * @param integer $courseid
 * @param string $shortname Course shortname.
 * @return integer News block instance id, or zero if no news blocks found.
 */
function block_news_get_top_news_block($courseid) {
    global $DB;

    $context = context_course::instance($courseid);

    // Get a list of news blocks sorted by weight, i.e. which one is at the top.
    $sql = "SELECT bi.id,
              CASE WHEN bp.id IS NOT NULL THEN bp.weight ELSE bi.defaultweight END as blockweight
              FROM {block_instances} bi
         LEFT JOIN {block_positions} bp ON bi.id = bp.blockinstanceid
             WHERE bi.parentcontextid = :parentcontextid AND bi.blockname = 'news'
          ORDER BY blockweight, bi.id";
    $params = array('parentcontextid' => $context->id);
    $newsblocks = $DB->get_records_sql($sql, $params, 0, 1);

    if (empty($newsblocks)) {
        return 0;
    }

    return key($newsblocks);
}

/**
 * Get a list of the groupsings that apply in the current context for use when working
 * out which messages to display.  This will be because the user is a member of particular
 * groupings and groupings support is enabled or some groupings have been specified in a
 * querystring and specified using set_user_groupingids().
 * @return string - Comma delimited string of groupingids, empty if none.
 */
function block_news_get_groupingids($courseid, $userid) {
    global $DB;

    $context = context_course::instance($courseid);
    if (has_capability('moodle/site:accessallgroups', $context, $userid)) {
        // If the user has the allgroups capability they can see everything.
        $allgroupings = groups_get_all_groupings($courseid);
        $groupings = array();
        foreach ($allgroupings as $grouping) {
            $groupings[] = $grouping->id;
        }

        // Sort by id (just to produce reliable order for unit test).
        sort($groupings);
        return implode(',', $groupings);
    }

    $sql = 'SELECT DISTINCT({groupings}.id)
              FROM {user}
              JOIN {groups_members} ON {user}.id = {groups_members}.userid
              JOIN {groupings_groups} ON {groups_members}.groupid = {groupings_groups}.groupid
              JOIN {groupings} ON {groupings_groups}.groupingid = {groupings}.id
             WHERE {user}.id = ? AND {groupings}.courseid = ?
          ORDER BY {groupings}.id ASC';
    $results = $DB->get_records_sql($sql, array($userid, $courseid));

    $groupings = '';
    foreach ($results as $result) {
        // Add a comma delimiter if $groupings already has a value.
        if ($groupings !== '') {
            $groupings .= ',';
        }
        $groupings .= $result->id;
    }

    return $groupings;
}
