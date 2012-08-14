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
 * Message edit form (with edit.php)
 *
 * @package    blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Stacey Walker <stacey@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

 /**
  * message edit form definition
  * @package blocks
  * @subpackage news
  */
class block_news_edit_message_form extends moodleform {
    protected $publishstate='';
    protected $groupingsupport = 0;

    /**
     * Overide constructor to pass in publish radio button state before
     * definition() is called
     *
     * @param $publishstate
     */
    public function __construct($customdata) {
        $this->publishstate = $customdata['publishstate'];
        $this->groupingsupport = $customdata['groupingsupport'];
        parent::__construct();
    }


    public function definition() {
        global $COURSE;

        $mform =& $this->_form;

        // hiddens
        $mform->addElement('hidden', 'bi');
        $mform->setType('bi', PARAM_INT);

        $mform->addElement('hidden', 'm');
        $mform->setType('m', PARAM_INT);

        $mform->addElement('hidden', 'newsfeedid');
        $mform->setType('newsfeedid', PARAM_INT);

        $mform->addElement('hidden', 'mode');
        $mform->setType('mode', PARAM_RAW);

        // fileset header
        $mform->addElement('header', 'displayinfo', null);

        $mform->addElement('text', 'title', get_string('msgedittitle', 'block_news'),
            array('size'=>'40'));
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', null, 'maxlength', 80, 'server');

        $mform->addElement('editor', 'message', get_string('msgeditmessage', 'block_news'),
            array('cols' => 50, 'rows' => 30), array('maxfiles' => EDITOR_UNLIMITED_FILES));
        $mform->addRule('message', null, 'required', null, 'client');

        $mform->addElement('filemanager', 'attachments',
            get_string('msgedithlpattach', 'block_news'), null, array('subdirs' => 0));

        $mform->addElement('selectyesno', 'messagevisible',
                                                 get_string('msgeditvisible', 'block_news'));
        $mform->setDefault('messagevisible', 1);

        if ($this->groupingsupport) {
            $groupingsdata = groups_get_all_groupings($COURSE->id);
            if ($groupingsdata != false) {
                $groupings["0"] = get_string('allparticipants');
                foreach ($groupingsdata as $grouping) {
                    $groupings[$grouping->id] = $grouping->name;
                }
                $mform->addElement('select', 'groupingid',
                    get_string('msgeditgrouping', 'block_news'), $groupings);
                $mform->setDefault('groupingid', 0);
            }
        }

        // publish radio buttons - content determined by value passed in constructor
        $attributes=array('class'=>'publish_radioopt');
        $radioarray=array();
        if ($this->publishstate == "ap") { // already publsihed
            // leave out 'immediately'
            $radioarray[] = $mform->createElement('radio', 'publish', '',
                get_string('msgeditatspecdate', 'block_news'), 1, $attributes);
            $radioarray[] = $mform->createElement('radio', 'publish', '',
                get_string('msgeditalreadypub', 'block_news'), 2, $attributes);
        } else {
            $radioarray[] = $mform->createElement('radio', 'publish', '',
                get_string('msgeditimmediately', 'block_news'), 0, $attributes);
            $radioarray[] = $mform->createElement('radio', 'publish', '',
                get_string('msgeditatspecdate', 'block_news'), 1, $attributes);
            // leave out 'already published'
        }
        $mform->addGroup($radioarray, 'radioar',
            get_string('msgeditpublish', 'block_news'),
            array(' '), false);

        // add date_time selector in optional area
        $mform->addElement('date_time_selector', 'messagedate',
                    get_string('msgeditmessagedate', 'block_news'), array('optional'=>false));
        $mform->disabledIf('messagedate', 'publish', 'neq', 1);
        $mform->setAdvanced('optional');

        $mform->addElement('checkbox', 'messagerepeat',
                    get_string('msgeditrepeat', 'block_news'));
        $mform->disabledIf('messagerepeat', 'publish', 'neq', 1);
        $mform->setDefault('messagerepeat', 0);

        $mform->addElement('selectyesno', 'hideauthor',
            get_string('msgedithideauthor', 'block_news'));
        $mform->setDefault('hideauthor', 0);

        $mform->addElement('static', 'lastupdated',
                    get_string('msgeditlastupdated', 'block_news'));

        $this->add_action_buttons();
    }

}
