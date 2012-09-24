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
 * Form for editing HTML block instance configuration (with block_news.php).
 *
 * @package    blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // must be included from a Moodle page
}

require_once('block_news_system.php');

/**
 * block edit form definition
 * @package blocks
 * @subpackage news
 */
class block_news_edit_form extends block_edit_form {
    const MAXURLLEN = 255;

    /**
     * standard config form function
     * all field names must be prefixed by 'config_' which is stripped off in data
     * received in block_news::instance_config_save()
     */
    protected function specific_definition($mform) {
        $blockinstanceid=$this->block->instance->id;
        $bns=$this->block->bns;
        $urls_txt='';

        // set feeds for text area
        $frecs=$bns->get_feeds();
        foreach ($frecs as $frec) {
            $urls_txt.=$frec->feedurl;
            $urls_txt.="\n";
        }

        // Fields for editing block title and contents.
         // 'Block settings'
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_news'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->setDefault('config_title', $bns->get_title());
        $mform->addRule('config_title', null, 'maxlength', 40, 'server');

        $choices_nm=array(
                        '1'=>'Latest message only',
                        '2'=>'2 most recent',
                        '3'=>'3 most recent',
                        '4'=>'4 most recent',
                        '5'=>'5 most recent'
                        );
        $mform->addElement('select', 'config_nummessages',
            get_string('confignummessages', 'block_news'), $choices_nm);
        $mform->setDefault('config_nummessages', $bns->get_nummessages());

        $choices_sl=array(
                        '0'=>'None',
                        '40'=>'Short',
                        '100'=>'Medium',
                        '500'=>'Long'
                        );
        $mform->addElement('select', 'config_summarylength',
            get_string('configsummarylength', 'block_news'), $choices_sl);
        $mform->setDefault('config_summarylength', $bns->get_summarylength());

        $mform->addElement('textarea', 'config_feedurls',
            get_string("configfeedurls", "block_news"), 'wrap="virtual" rows="5" cols="128"');
        $mform->setType('config_title', PARAM_TEXT);
        $mform->setDefault('config_feedurls', $urls_txt);

        $mform->addElement('selectyesno', 'config_hidetitles',
            get_string('confighidetitles', 'block_news'));
        $mform->setDefault('config_hidetitles', $bns->get_hidetitles());

        $mform->addElement('selectyesno', 'config_hidelinks',
            get_string('confighidelinks', 'block_news'));
        $mform->setDefault('config_hidelinks', $bns->get_hidelinks());

        $mform->addElement('selectyesno', 'config_groupingsupport',
            get_string('configgroupingsupport', 'block_news'));
        $mform->setDefault('config_groupingsupport', $bns->get_groupingsupport());
        $mform->addHelpButton('config_groupingsupport', 'configgroupingsupport', 'block_news');
    }


    public function validation($data, $files) {
        $errors= array();

        // now do feeds
        // convert from textarea to array
        $feeds = preg_split('/\R/', $data['config_feedurls']); // splits on any of \n \r\n \r

        // just check length of each feed url (other cleanup done in block_news_system::save()
        foreach ($feeds as $feed) {
            if (strlen(trim($feed)) > self::MAXURLLEN) {
                $errors['config_feedurls'] =
                                get_string('errorurltoolong', 'block_news', self::MAXURLLEN);
                break;
            }
        }

        return $errors;
    }

}


// main

$id                 = optional_param('m', 0, PARAM_INTEGER);
$blockinstanceid    = optional_param('bi', 0, PARAM_INTEGER);

require_login(); // redirect to login page if not logged in

$context = get_context_instance(CONTEXT_SYSTEM);

$mform =& $this->_form;
