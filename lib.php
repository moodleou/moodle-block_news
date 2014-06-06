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
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // It must be included from a Moodle page
}

/**
 * Standard attachments handler
 *
 * @param course $course
 * @param $birecord_or_cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 */
function block_news_pluginfile($course, $birecord_or_cm, $context, $filearea, $args,
                                $forcedownload) {

    if ($context->contextlevel != CONTEXT_BLOCK) {
        send_file_not_found();
    }

    require_course_login($course);

    $fs = get_file_storage();

    $filename = array_pop($args);
    $newsid = (int)array_shift($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/block_news/$filearea/$newsid/$relativepath$filename";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    if ($parentcontext = context::instance_by_id($birecord_or_cm->parentcontextid)) {
        if ($parentcontext->contextlevel == CONTEXT_USER) {
            // force download on all personal pages including /my/
            //because we do not have reliable way to find out from where this is used
            $forcedownload = true;
        }
    } else {
        // weird, there should be parent context, better force dowload then
        $forcedownload = true;
    }

    \core\session\manager::write_close();
    send_stored_file($file, 60*60, 0, $forcedownload);
}

/**
 * Calls require_login and adds standard breadcrumb item.
 * @param int $blockinstanceid
 * @param string $title Block title or null for default
 * @return StdClass Course/Module identity information (useful for Form redirect logic)
 */
function block_news_init_page($blockinstanceid, $title) {
    global $PAGE;

    $csemod = block_news_get_course_mod_info($blockinstanceid);
    if (!$csemod) {
        return null;
    }
    $PAGE->set_pagelayout('incourse');
    // coursecontext set_context should have been set to block context, but this causes problems
    $coursecontext = context_course::instance($csemod->course->id);
    $PAGE->set_context($coursecontext);
    require_course_login($csemod->course);

    if (empty($title)) {
        $title = get_string('pluginname', 'block_news');
    }
    $PAGE->navbar->add($title,
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

    // context instance
    $cibi = context_block::instance($blockinstanceid);
    if (!$cibi) {
        return null;
    }

    $ctxa = explode('/', $cibi->path); // /1/46/53/318
    unset($ctxa[0]);
    $ctxin = implode(', ', $ctxa);  // 1, 46, 53, 318

    // create "IN" SQL
    list($insql, $params) = $DB->get_in_or_equal($ctxa, SQL_PARAMS_QM);

    // get course related to the context
    $sql = 'SELECT {course}.* FROM {context}
             INNER JOIN {course} ON {context}.instanceid = {course}.id
             WHERE {context}.contextlevel = '.CONTEXT_COURSE;
    $sql .= " AND {context}.id $insql";
    $cse = $DB->get_record_sql($sql, $params);

    // get module info via course_module table to get extra info if block is
    // called from a module (glossary, page etc)
    $sql = 'SELECT * FROM {context}
         INNER JOIN {course_modules} ON {context}.instanceid = {course_modules}.id
         WHERE {context}.contextlevel = '.CONTEXT_MODULE; // 70 = CONTEXT_MODULE
    $sql .= " AND {context}.id $insql";
    $cmrec = $DB->get_record_sql($sql, $params);

    if (!empty($cmrec)) {
        $csemodid = $cmrec->instanceid;
    }

    $mi = get_fast_modinfo($cse);
    $coursecategory = $DB->get_record('course_categories', array('id' => $cse->category));

    // build info object
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

    // adjust
    $oldhours = $oldinfo['hours'];
    $minutes = $newinfo['minutes'];
    $seconds = $newinfo['seconds'];
    $month = $newinfo['mon'];
    $day = $newinfo['mday'];
    $year = $newinfo['year'];
    $a = mktime($oldhours, $minutes, $seconds, $month, $day, $year);

    // adjust by 1 day forward/backward
    if ($a < $newtime) {
        $b = strtotime('+1 day', $a);
    } else if ($a > $newtime) {
        $b = strtotime('-1 day', $a);
    }

    // return the candidate closest
    // to the original estimate
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
    $sql = "SELECT bp.blockinstanceid
              FROM {block_positions} bp
              JOIN {block_instances} bi ON bi.id = bp.blockinstanceid
             WHERE bi.parentcontextid = :parentcontextid AND bi.blockname = 'news'
          ORDER BY bp.weight ASC";
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

        return $groupings;
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
