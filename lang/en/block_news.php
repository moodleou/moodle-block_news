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
 * @package block_news
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // It must be included from a Moodle page.
}

// General.
$string['news:addinstance'] = 'Add a new news block';
$string['news:myaddinstance'] = 'Add a new News block to Dashboard';
$string['news:viewsubscribers'] = 'View subscribers';
$string['news:managesubscriptions'] = 'Manage subscriptions';
$string['pluginname'] = 'News';
$string['defaultblocktitle'] = '(new News block)';
$string['newsandeventsblocktitle'] = 'News and events';
$string['addnewmessage'] = 'Add a new message';
$string['editmessage'] = 'Edit message';
$string['confirmdeletion'] = 'Confirm deletion of \'{$a}\'?';
$string['dateformat'] = '%d %b %Y'; // See http://php.net/manual/en/function.strftime.php.
$string['dateformatlong'] = '%d %b %Y %H:%M';
$string['new'] = 'new';
$string['attachments'] = 'Attachments';
$string['attachment'] = 'Attachment';
$string['donotmailafter'] = 'Do not mail after (hours)';
$string['configdonotmailafter'] = 'To prevent causing a mail flood if the server cron does not run
for a time, the news will not send out emails for messages that are older than this many hours.';
$string['cronlimit'] = 'Time limit for normal email sending';
$string['cronlimit_desc'] = 'Amount of time spent per scheduled task run for sending out normal emails. After this time limit, sending will resume the next time the task runs.';
$string['replytouser'] = 'Use email address in reply';
$string['configreplytouser'] = 'When a message is mailed out, should it contain the user\'s
email address so that recipients can reply personally? Even if set to
\'Yes\' users can choose in their profile to keep their email address secret.';
$string['subscribe'] = 'Subscribe';
$string['subscribers'] = 'Subscribers';
$string['newssubscription'] = 'News subscription';
$string['subscribetonews'] = 'Subscribe to news';
$string['subscribetonews'] = 'Subscribe to news';
$string['unsubscribeshort'] = 'Unsubscribe';
$string['unsubscribe'] = 'Unsubscribe from this news';
$string['subscription'] = 'Email subscription';
$string['subscription_help'] = 'You receive messages from this news feed via email to {$a}.';
$string['subscribe_already'] = 'You are already subscribed.';
$string['unsubscribe_already'] = 'You are already unsubscribed.';
$string['subscribe_confirm'] = 'You have been subscribed.';
$string['unsubscribe_confirm'] = 'You have been unsubscribed.';
$string['error_subscribeparams'] = 'Parameters incorrect';
$string['unsubscription_help'] = 'You do not currently receive messages from this news feed by email. If you would like to, click ‘Subscribe to news’.';
$string['blocknewssubscriptions'] = 'Subscriptions';
$string['privacy:metadata:preference:newsmailformat'] = 'Handle news email type';
$string['newsitem'] = 'News item';
$string['event'] = 'Event';
$string['messagetype'] = 'Type';
$string['messageimage'] = 'Image';
$string['delete'] = 'Delete {$a}';
$string['edit'] = 'Edit {$a}';
$string['posted'] = 'Posted:';
$string['newsheading'] = 'News';
$string['eventsheading'] = 'Upcoming events';
$string['editheading'] = 'News and events';
$string['pasteventsheading'] = 'Past events';
$string['nonewsyet'] = 'No news messages have been posted to this website.';
$string['noeventsyet'] = 'There are no upcoming events to display.';
$string['nopastevents'] = 'There are no past events to display';
$string['nosubscribers'] = 'There are no subscribers yet for this news block.';
$string['news'] = 'News';
$string['events'] = 'Events';
$string['confirmbulkunsubscribe'] = 'Are you sure you want to unsubscribe the users in the list
below? (This cannot be undone.)';
$string['unsubscribeselected'] = 'Unsubscribe selected users';
$string['unsubscribe_nopermission'] = 'You do not have permission to unsubscribe other users.';
$string['viewsubscribers'] = 'View subscribers';


// Error messages.
$string['erroremptymessage'] = 'Missing entry';
$string['errorperms'] = 'Sorry, insufficient permissions to complete this operation';
$string['errortexttoolong'] = 'Text in this field is limited to {$a} characters';
$string['errorurltoolong'] = 'URLs limited to {$a} characters';
$string['errormessageaccessrestricted'] = 'Sorry, you are not a member of the group to view this message';
$string['errornomsgfound'] = 'No message found, id = {$a}';
$string['errornoupdatetime'] = 'Set minimum feed delay in Site Administration -> Plugins -> Blocks -> News';
$string['errornomaxpercron'] = 'Set cron feed limit in Site Administration -> Plugins -> Blocks -> News';
$string['errorinvalidmode'] = 'Invalid mode [ {$a} ]';
$string['errorinvalidblockinstanceid'] = 'Invalid block instance id';
$string['errorupdateblocknews'] = 'Update of block news failed';
$string['errornocsemodinfo'] = 'Could not get course/module info';
$string['errorwritefile'] = 'Unable to write cache file';
$string['erroreventstart'] = 'Event start must be in the future';
$string['erroreventend'] = 'Event end must be after event start';
$string['errorimagedesc'] = 'Please describe the image for users who cannot see it';
$string['errorimagesize'] = 'The message image must be less than {$a}KB.';
$string['errorimagedimensions'] = 'The message image must be exactly {$a->width}x{$a->height} pixels.';
$string['errorinvalidgroups'] = 'You cannot select "All participants" as well as specific groups.';

// Block config.
$string['configtitle'] = 'Block title';
$string['confignummessages'] = 'Show messages';
$string['configsummarylength'] = 'Summary';
$string['confighideimages'] = 'Hide images';
$string['confighideimages_help'] = 'If enabled, hides message images from the block and from the view all page. Images are still shown when viewing a single message.';
$string['confighidetitles'] = 'Hide titles';
$string['confighidelinks'] = 'Hide links';
$string['configfeedurls'] = 'Include messages from the listed feeds (URLs)';
$string['configgroupingsupport'] = 'Enable message restriction';
$string['configgroupingsupport_help'] = 'Enable access restriction on the news message. Not enabled: The access restriction will not be applied to the news message. Grouping: Allow only students who belong to a group within a specific grouping. Group: Allow only students who belong to a specific group, or all groups';
$string['configgroupingoptionnotenable'] = 'Not enabled';
$string['configgroupingoptiongrouping'] = 'Grouping';
$string['configgroupingoptiongroup'] = 'Group';
$string['configeditconfig'] = 'Edit configuration';
// Block.
$string['msgblocknonews'] = 'There is no news yet';
$string['msgblockadd'] = 'Add';
$string['msgblockaddalt'] = 'Add a message';
$string['msgblockviewall'] = 'View all';
$string['msgblockviewallnewsandevents'] = 'View all news and events';
$string['msgblockviewallalt'] = 'View all messages';

// Global settings - settings.php.
$string['settingsupdatetime'] = 'Minimum feed delay';
$string['settingsupdatetime_info'] = 'Minimum time between updates of a feed (eg 1 hour means feed will be updated every hour)';
$string['settingsmaxpercron'] = 'Cron feed limit';
$string['settingsmaxpercron_info'] = 'Maximum time spent per cron run on updating feeds';
$string['verbosecron'] = 'List each feed as retrieved in cron';
$string['verbosecron_info'] = 'If you turn this option on, all feeds being retrieved are shown in cron. Otherwise it only shows feeds which take longer than 5 seconds.';
$string['settingshideauthor'] = 'Hide author';
$string['settingshideauthor_info'] = 'Set default value for the hide author option when posting news.';
$string['separateintoeventsandnewsitems'] = 'Separate into events and news items';
$string['extralogging'] = 'Extra logging';
$string['extralogging_info'] = 'If you turn this option on, extra debugging information will be output in the logs.';

// Edit.
$string['imagedesc'] = 'Image description';
$string['imagedescnotnecessary'] = 'Description not necessary';
$string['msgeditpghdr'] = 'News';
$string['msgeditpgtitle'] = 'News message';
$string['msgfieldgroup'] = 'message';
$string['msgedittitle'] = 'Title';
$string['msgeditmessage'] = 'Text';
$string['msgeditformat'] = 'Format';
$string['msgeditmessagedate'] = 'Release date';
$string['msgeditvisible'] = 'Visible';
$string['msgeditselectgrouping'] = 'Select all groups in grouping';
$string['msgeditgroup'] = 'Group';
$string['msgeditpublish'] = 'Publish';
$string['msgeditrepeat'] = 'Repeated after roll-forward';
$string['msgedithideauthor'] = 'Hide author';
$string['msgeditlastupdated'] = 'Last updated';
$string['msgeditfiles'] = 'Attach files';
$string['msgedithlpattach'] = 'Attachments';
$string['msgeditimmediately'] = 'Immediately';
$string['msgeditatspecdate'] = 'At specified date';
$string['msgeditalreadypub'] = 'Already published';
$string['msgediteventstart'] = 'Event start';
$string['msgediteventend'] = 'Event end';
$string['msgeditalldayevent'] = 'All day event';
$string['msgediteventlocation'] = 'Event location';

// Message render.
$string['rendermsggroupindication'] = 'Not available unless you belong to one of the following groups: <strong>{$a}</strong>';
$string['rendermsghidden'] = 'This message has been hidden from students';
$string['rendermsgfuture'] = 'This message does not display to students until {$a}';
$string['rendermsgnext'] = 'Next (newer) message';
$string['rendermsgprev'] = 'Previous (older) message';
$string['rendereventnext'] = 'Next (listed) event';
$string['rendereventprev'] = 'Previous (listed) event';
$string['rendermsgview'] = '(View)';
$string['rendermsgextlink'] = 'View original message';
$string['rss'] = 'RSS';


// Event render.
$string['fulleventdate'] = '{$a->start} to {$a->end}';

// Message class.
$string['msgclassconfdel'] = 'Are you sure you want to delete the message \'{$a}\'? This action cannot be undone';

// Access permissions.
$string['news:add'] = 'Add a message';
$string['news:edit'] = 'Edit a message';
$string['news:hide'] = 'Hide or show a message';
$string['news:delete'] = 'Delete a message';
$string['news:viewhidden'] = 'View hidden and future messages';

$string['eventmessage_created'] = 'Added news message';
$string['eventmessage_updated'] = 'Edited news message';
$string['eventmessage_deleted'] = 'Deleted news message';

// Scheduled tasks.
$string['process_feeds_task'] = 'Refresh News block feeds';
$string['process_news_email'] = 'News email sending job';


// Global search.
$string['search:news_message'] = 'News messages';

// Privacy.
$string['privacy:metadata:block_news_messages'] = 'Information about the news message';
$string['privacy:metadata:block_news_messages:user'] = 'The person who last edited the message';
$string['privacy:metadata:block_news_messages:title'] = 'Message title';
$string['privacy:metadata:block_news_messages:message'] = 'Message contents';
$string['privacy:metadata:block_news_messages:link'] = 'Message URL';
$string['privacy:metadata:block_news_messages:messagetype'] = 'Message type';
$string['privacy:metadata:block_news_messages:messageformat'] = 'Format of message';
$string['privacy:metadata:block_news_messages:messagedate'] = 'Date of message';
$string['privacy:metadata:block_news_messages:messagevisible'] = 'Yes if message is visible, no if it’s hidden';
$string['privacy:metadata:block_news_messages:messagerepeat'] = 'Yes if repeat this message is visible, no if it’s hidden';
$string['privacy:metadata:block_news_messages:hideauthor'] = 'Yes if hide author name is chosen';
$string['privacy:metadata:block_news_messages:timemodified'] = 'Time at which message was edited';
$string['privacy:metadata:block_news_messages:eventend'] = 'Date end of event';
$string['privacy:metadata:block_news_messages:eventstart'] = 'Date start of event';
$string['privacy:metadata:block_news_messages:eventlocation'] = 'Event location';
$string['privacy:metadata:block_news_messages:imagedesc'] = 'Image description';
$string['privacy:metadata:block_news_messages:imagedescnotnecessary'] = 'Image description is not necessary';
$string['privacy:metadata:block_news_subscriptions'] = 'Information about the subscriptions for each news block.';
$string['privacy:metadata:block_news_subscriptions:userid'] = 'User ID';
$string['privacy:metadata:block_news_subscriptions:subscribed'] =
    'This field is usually 1. It indicates that the user has chosen to subscribe to the news. In the case of initial-subscription news it may be 0, indicating that the user has chosen to unsubscribe. (If the user has not changed the default state, there would be no row for them in this table.)';

$string['privacy:metadata:core_files'] = 'Files attached to news message';
$string['privacy_you'] = 'You';
$string['privacy_somebodyelse'] = 'Somebody else';

$string['feed_url_mask'] = 'Replace the OUCU in certain feed URLs with an OUCU from appropriate mapping table';
// News subscriptions.
$string['eventsubscription_created'] = 'Subscribe to news';
$string['eventsubscription_deleted'] = 'Unsubscribe to news';
$string['eventsubscribe_log'] = 'The user with id {$a->userid} subscribe to news with id {$a->objectid}.';
$string['eventunsubscribe_log'] = 'The user with id {$a->userid} unsubscribe to news with id {$a->objectid}.';
