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
 * cross-block-instance settings
 *
 * @package    blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// ref lib/adminlib.php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // It must be included from a Moodle page
}

// typically in admin/settings.php?section=blocksettingnews
// Stored in mdl_config_plugins
// Retrieve in application with get_config('block_news', 'settingname')
// eg get_config('block_news', 'block_news_updatetime')

if ($ADMIN->fulltree) {

    $choices_u=array('3600'=>'1 hour',
                     '14400'=>'4 hours',
                     '28800'=>'8 hours',
                     '43200'=>'12 hours',
                     '86400'=>'24 hours'
                    );

    $settings->add(new admin_setting_configselect('block_news/block_news_updatetime',
                                               get_string('settingsupdatetime', 'block_news'),
                                               get_string('settingsupdatetime_info', 'block_news'),
                                               '14400',
                                               $choices_u)
                                              );

    $choices_m=array('15'=>'15 seconds',
                     '30'=>'30 seconds',
                     '60'=>'1 minute'
                    );
    for ($i=2; $i <= 5; $i++) {
        $choices_m[(string)$i*60] = $i.' minutes';
    }

    $settings->add(new admin_setting_configselect('block_news/block_news_maxpercron',
                                               get_string('settingsmaxpercron', 'block_news'),
                                               get_string('settingsmaxpercron_info', 'block_news'),
                                               '60',
                                               $choices_m)
                                              );

    $settings->add(new admin_setting_configcheckbox('block_news/verbosecron',
           get_string('verbosecron', 'block_news'),
           get_string('verbosecron_info', 'block_news'), 0));
}

