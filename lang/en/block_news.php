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
 * Strings for component
 *
 * @package    blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // It must be included from a Moodle page
}

// general
$string['news:addinstance'] = 'Add a new news block';
$string['pluginname'] = 'News';
$string['defaultblocktitle'] = '(new News block)';
$string['addnewmessage'] = 'Add new message';
$string['allmessages'] = 'All messages';
$string['editmessage'] = 'Edit message';
$string['confirmdeletion'] = 'Confirm deletion of \'{$a}\'?';
$string['dateformat'] = '%d %b %Y'; // http://php.net/manual/en/function.strftime.php
$string['dateformatlong'] = '%d %b %Y %H:%M';
$string['new'] = 'new';

// error messages
$string['erroremptymessage'] = 'Missing entry';
$string['errorperms'] = 'Sorry, insufficient permissions to complete this operation';
$string['errortexttoolong'] = 'Text in this field is limited to {$a} characters';
$string['errorurltoolong'] = 'URLs limited to {$a} characters';
$string['errornomsgfound'] = 'No message found, id = {$a}';
$string['errornoupdatetime'] = 'Set minimum feed delay in Site Administration -> Plugins -> Blocks -> News';
$string['errornomaxpercron'] = 'Set cron feed limit in Site Administration -> Plugins -> Blocks -> News';
$string['errorinvalidmode'] = 'Invalid mode [ {$a} ]';
$string['errorinvalidblockinstanceid'] = 'Invalid block instance id';
$string['errorupdateblocknews'] = 'Update of block news failed';
$string['errornocsemodinfo'] = 'Could not get course/module info';
$string['errorwritefile'] = 'Unable to write cache file';

// block config
$string['configtitle'] = 'Block title';
$string['confignummessages'] = 'Show messages';
$string['configsummarylength'] = 'Summary';
$string['confighidetitles'] = 'Hide titles';
$string['confighidelinks'] = 'Hide links';
$string['configfeedurls'] = 'Include messages from the listed feeds (URLs)';
$string['configgroupingsupport'] = 'Grouping support';
$string['configgroupingsupport_help'] = 'Allows news items to be displayed only to members of a specified grouping.  Please note that this is to help in managing news items and is not a security feature (users can access the news items if determined to do so).';
// block
$string['msgblocknonews'] = 'There is no news yet';
$string['msgblockadd'] = 'Add';
$string['msgblockaddalt'] = 'Add a message';
$string['msgblockviewall'] = 'View all';
$string['msgblockviewallalt'] = 'View all messages';

// global settings - settings.php
$string['settingsupdatetime'] = 'Minimum feed delay';
$string['settingsupdatetime_info'] = 'Minimum time between updates of a feed (eg 1 hour means feed will be updated every hour)';
$string['settingsmaxpercron'] = 'Cron feed limit';
$string['settingsmaxpercron_info'] = 'Maximum time spent per cron run on updating feeds';
$string['verbosecron'] = 'List each feed as retrieved in cron';
$string['verbosecron_info'] = 'If you turn this option on, all feeds being retrieved are shown in cron. Otherwise it only shows feeds which take longer than 5 seconds.';
$string['settingshideauthor'] = 'Hide author';
$string['settingshideauthor_info'] = 'Set default value for the hide author option when posting news.';

// edit
$string['msgeditpghdr'] = 'News';
$string['msgeditpgtitle'] = 'News message';
$string['msgfieldgroup'] = 'message';
$string['msgedittitle'] = 'Title';
$string['msgeditmessage'] = 'Text';
$string['msgeditformat'] = 'Format';
$string['msgeditmessagedate'] = 'Release date';
$string['msgeditvisible'] = 'Visible';
$string['msgeditgrouping'] = 'Grouping';
$string['msgeditpublish'] = 'Publish';
$string['msgeditrepeat'] = 'Repeated after roll-forward';
$string['msgedithideauthor'] = 'Hide author';
$string['msgeditlastupdated'] = 'Last updated';
$string['msgeditfiles'] = 'Attach files';
$string['msgedithlpattach'] = 'Attachments';
$string['msgeditimmediately'] = 'Immediately';
$string['msgeditatspecdate'] = 'At specified date';
$string['msgeditalreadypub'] = 'Already published';

// message render
$string['rendermsghidden'] = 'This message has been hidden from students';
$string['rendermsgfuture'] = 'This message does not display to students until {$a}';
$string['rendermsgnext'] = 'Next (newer) message';
$string['rendermsgprev'] = 'Previous (older) message';
$string['rendermsgview'] = '(View)';
$string['rendermsgaccesshide'] = 'View {$a}';

// message class
$string['msgclassconfdel'] = 'Are you sure you want to delete the message \'{$a}\'? This action cannot be undone';

// access permissions
$string['news:add'] = 'Add a message';
$string['news:edit'] = 'Edit a message';
$string['news:hide'] = 'Hide or show a message';
$string['news:delete'] = 'Delete a message';
$string['news:viewhidden'] = 'View hidden and future messages';
