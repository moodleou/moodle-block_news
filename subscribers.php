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
 * Show all subscribers to the news.
 *
 * @package block_news
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_news\system;
require_once('../../config.php');
require_once('lib.php');

$bi = required_param('bi', PARAM_INT);
$unsubscribe = optional_param('unsubscribe', '', PARAM_RAW);

$pageparams = ['bi' => $bi];

$thisurl = new moodle_url('/blocks/news/subscribers.php', $pageparams);
$bns = system::get_block_settings($bi);

$title = $bns->get_title();
$isprison = class_exists('\auth_prison\util') && \auth_prison\util::is_prison_vle();
$title = $isprison && $bns->get_displaytype() == system::DISPLAY_DEFAULT || empty($title) ?
    get_string('pluginname', 'block_news') : $title;
$csemod = block_news_init_page($bi, $title, 0);
$output = $PAGE->get_renderer('block_news');
$blockcontext = context_block::instance($bi);

$urlparams = ['bi' => $bi];
$PAGE->set_url('/blocks/news/subscribers.php', $urlparams);

$title .= ': ' . get_string('viewsubscribers', 'block_news');
$PAGE->set_title($csemod->cseshortname . ': ' . $title);
$PAGE->set_heading($csemod->csefullname);
$PAGE->navbar->add(get_string('subscribers', 'block_news'));

$news = \block_news\subscription::get_from_bi($bi);
$subscribers = $news->get_subscribers();
$canmanage = $news->can_manage_subscriptions();

// Only need manage_subscribe.js if there are subscribers to manage and we're not on the confirm unsubscribe page.
if ($canmanage && count($subscribers) > 0 && !$unsubscribe) {
    $PAGE->requires->js_call_amd('block_news/manage_subscribe', 'manageSubscribe', [
            'stringSelectall' => get_string('selectall'),
            'stringDeselectall' => get_string('deselectall')
    ]);
}

if ($unsubscribe) {
    if (!$canmanage) {
        throw new moodle_exception('unsubscribe_nopermission', 'block_news');
    }
    $confirmarray = ['bi' => $bi, 'confirmunsubscribe' => 1];
    $list = '<ul class="block_news_unsubcribelist">';
    echo $OUTPUT->header();
    foreach (array_keys($_POST) as $key) {
        $matches = array();
        if (preg_match('~^user([0-9]+)$~', $key, $matches)) {
            $confirmarray[$key] = 1;
            $user = $DB->get_record('user', ['id' => $matches[1]],
                '*', MUST_EXIST);
            $list .= '<li>' . $news->display_user_link($user) . '</li>';
        }
    }
    $list .= '</ul>';
    print $OUTPUT->confirm(get_string('confirmbulkunsubscribe', 'block_news'),
        new single_button(new moodle_url('/blocks/news/subscribers.php', $confirmarray),
            get_string('unsubscribeselected', 'block_news'), 'post'),
        new single_button(new moodle_url('/blocks/news/subscribers.php',
            ['bi' => $bi]),
            get_string('cancel'), 'get'));
    print $list;

    print $OUTPUT->footer();
    exit;
}
if (optional_param('confirmunsubscribe', 0, PARAM_INT)) {
    if (!$canmanage) {
        throw new moodle_exception('unsubscribe_nopermission', 'block_news');
    }
    $subscribers = $news->get_subscribers();
    $transaction = $DB->start_delegated_transaction();
    foreach (array_keys($_POST) as $key) {
        $matches = [];
        if (preg_match('~^user([0-9]+)$~', $key, $matches)) {
            // Use the subscribe list to check this user is on it. That
            // means they can't unsubscribe users in different groups.
            if (array_key_exists($matches[1], $subscribers)) {
                $news->unsubscribe($matches[1]);
            }
        }
    }
    $transaction->allow_commit();
    redirect('subscribers.php?' . $news->get_link_params(\block_news\subscription::PARAM_PLAIN));
}
echo $OUTPUT->header();
if (count($subscribers) == 0) {
    print '<p>' . get_string('nosubscribers', 'block_news') . '</p>';
} else {
    $extracolumns = \core_user\fields::for_identity($blockcontext, true)->get_required_fields();
    // Get name/link for each subscriber (this is used twice).
    foreach ($subscribers as $user) {
        $user->link = $news->display_user_link($user);
    }
    $table = new html_table;
    $table->head = [get_string('user')];
    foreach ($extracolumns as $field) {
        $table->head[] = \core_user\fields::get_display_name($field);
        $table->align[] = 'left';
    }

    $table->data = [];
    if ($canmanage) {
        print '<form action="subscribers.php" method="post"><div id="block-news-subscription-list">' .
            $news->get_link_params(\block_news\subscription::PARAM_FORM);
    }
    $gotsome = false;
    foreach ($subscribers as $user) {
        $row = [];
        $name = $user->link;
        $name = "<input type='checkbox' name='user{$user->id}' " .
            "value='1' id='check{$user->id}'/> " .
            "<label for='check{$user->id}'>$name</label>";
        $gotsome = true;
        $row[] = $name;
        foreach ($extracolumns as $field) {
            $row[] = s($user->{$field});
        }
        if ($user->link) {// CC Inline control structures are not allowed.
            $table->data[] = $row;
        }

    }
    print html_writer::table($table);

    if ($canmanage) {
        if ($gotsome) {
            print '<div id="block-news-buttons"><input type="submit" ' .
                'name="unsubscribe" value="' .
                get_string('unsubscribeselected', 'block_news') . '" /></div>';
        }
        print '</div></form>';
    }
}
print link_arrow_left($news->get_title(), $news->get_url(\block_news\subscription::PARAM_HTML));

echo $OUTPUT->footer();
