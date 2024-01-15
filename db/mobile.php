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
 * Remote add-ons for block_news
 *
 * @package    block_news
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'block_news' => [ // Plugin identifier.
        'handlers' => [ // Different places where the plugin will display content.
            'newspage' => [
                'delegate' => 'CoreCourseOptionsDelegate',
                'method' => 'news_page',
                'displaydata' => [
                    'title' => 'newsheading',
                    'class' => 'block_news'
                ],
                'styles' => [
                    'url' => $CFG->wwwroot . '/blocks/news/mobile.css?v=2024011600',
                    'version' => 2024011600
                ],
                'priority' => 60,
                'init' => 'news_init'
            ],
            'eventspage' => [
                'delegate' => 'CoreCourseOptionsDelegate',
                'method' => 'events_page',
                'displaydata' => [
                    'title' => 'events',
                    'class' => 'block_news'
                ],
                'priority' => 50,
                'init' => 'events_init'
            ]
        ],
        'lang' => [
            ['newsheading', 'block_news'],
            ['events', 'block_news'],
            ['eventsheading', 'block_news'],
            ['pasteventsheading', 'block_news'],
            ['nonewsyet', 'block_news'],
            ['noeventsyet', 'block_news'],
            ['nopastevents', 'block_news'],
            ['rendermsgextlink', 'block_news'],
            ['msgedithlpattach', 'block_news']
        ]
    ]
];
