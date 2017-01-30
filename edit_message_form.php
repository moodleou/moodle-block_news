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
    // Must be included from a Moodle page.
}

require_once($CFG->libdir.'/formslib.php');

 /**
  * message edit form definition
  * @package blocks
  * @subpackage news
  */
class block_news_edit_message_form extends moodleform {
    /**
     * Maximum width/height of thumbnail images.
     * @var int
     */
    const THUMBNAIL_MAX_EDGE = 340;
    const IMAGE_FILE_TYPES = array('.jpg', '.png');
    const IMAGE_FILE_OPTIONS = array('subdirs' => 0, 'maxbytes' => 50 * 1024, 'areamaxbytes' => 50 * 1024,
            'maxfiles' => 1, 'accepted_types' => self::IMAGE_FILE_TYPES);

    protected $displaytype = 0;
    protected $publishstate = '';
    protected $groupingsupportbygrouping = 0;
    protected $groupingsupportbygroup = 0;

    /**
     * Overide constructor to pass in publish radio button state before
     * definition() is called
     *
     * @param $publishstate
     */
    public function __construct($customdata) {
        $this->displaytype = $customdata['displaytype'];
        $this->publishstate = $customdata['publishstate'];
        $this->groupingsupportbygrouping = $customdata['groupingsupportbygrouping'];
        $this->groupingsupportbygroup = $customdata['groupingsupportbygroup'];
        parent::__construct();
    }


    public function definition() {
        global $COURSE;

        $mform =& $this->_form;

        // Hiddens.
        $mform->addElement('hidden', 'bi');
        $mform->setType('bi', PARAM_INT);

        $mform->addElement('hidden', 'm');
        $mform->setType('m', PARAM_INT);

        $mform->addElement('hidden', 'newsfeedid');
        $mform->setType('newsfeedid', PARAM_INT);

        $mform->addElement('hidden', 'mode');
        $mform->setType('mode', PARAM_RAW);

        // Fileset header.
        $mform->addElement('header', 'displayinfo', null);

        $mform->addElement('text', 'title', get_string('msgedittitle', 'block_news'),
            array('size' => '40'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', null, 'maxlength', 80, 'server');

        $mform->addElement('editor', 'message', get_string('msgeditmessage', 'block_news'),
            array('cols' => 50, 'rows' => 30), array('maxfiles' => EDITOR_UNLIMITED_FILES));
        $mform->addRule('message', null, 'required', null, 'client');

        $mform->addElement('filemanager', 'attachments',
            get_string('msgedithlpattach', 'block_news'), null, array('subdirs' => 0));

        if ( $this->displaytype == block_news_system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS ) {
            $messagetype = array(block_news_message::MESSAGETYPE_NEWS => get_string('newsitem', 'block_news'),
                    block_news_message::MESSAGETYPE_EVENT => get_string('event', 'block_news'));

            $mform->addElement('select', 'messagetype', get_string('messagetype', 'block_news'), $messagetype);
            $mform->setDefault('messagetype', block_news_message::MESSAGETYPE_NEWS);

            $mform->addElement('date_time_selector', 'eventstart',
                    get_string('msgediteventstart', 'block_news'), array('optional' => false));
            $mform->disabledIf('eventstart', 'messagetype', 'neq', block_news_message::MESSAGETYPE_EVENT);
            $mform->disabledIf('eventstart[hour]', 'alldayevent', 'checked');
            $mform->disabledIf('eventstart[minute]', 'alldayevent', 'checked');

            $mform->addElement('advcheckbox', 'alldayevent', get_string('msgeditalldayevent', 'block_news'));
            $mform->setDefault('alldayevent', 1);
            $mform->disabledIf('alldayevent', 'messagetype', 'neq', block_news_message::MESSAGETYPE_EVENT);

            $mform->addElement('date_time_selector', 'eventend',
                    get_string('msgediteventend', 'block_news'), array('optional' => false));
            $mform->disabledIf('eventend', 'messagetype', 'neq', block_news_message::MESSAGETYPE_EVENT);
            $mform->disabledIf('eventend', 'alldayevent', 'checked');

            $mform->addElement('text', 'eventlocation', get_string('msgediteventlocation', 'block_news'));
            $mform->disabledIf('eventlocation', 'messagetype', 'neq', block_news_message::MESSAGETYPE_EVENT);
            $mform->setType('eventlocation', PARAM_TEXT);
        }

        if (theme_osep\util::is_osep_design($COURSE)) {
            $mform->addElement('filemanager', 'messageimage', get_string('messageimage', 'block_news'),
                    null, self::IMAGE_FILE_OPTIONS);
            $mform->disabledIf('messageimage', 'messagetype', 'eq', block_news_message::MESSAGETYPE_EVENT);
        }

        $mform->addElement('selectyesno', 'messagevisible',
                get_string('msgeditvisible', 'block_news'));
        $mform->setDefault('messagevisible', 1);

        // If config_groupingsupport is grouping.
        if ($this->groupingsupportbygrouping) {
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

        // If config_groupingsupport is group.
        if ($this->groupingsupportbygroup) {
            $groupsdata = groups_get_all_groups($COURSE->id);
            $groups = array();
            if ($groupsdata != false) {
                $groups[0] = get_string('allparticipants');
                foreach ($groupsdata as $group) {
                    $groups[$group->id] = $group->name;
                }
                $mform->addElement('select', 'groupid',
                    get_string('msgeditgroup', 'block_news'), $groups);
                $mform->setDefault('groupid', 0);
            }
        }

        // Publish radio buttons - content determined by value passed in constructor.
        $attributes = array('class' => 'publish_radioopt');
        $radioarray = array();
        if ($this->publishstate == "ap") { // Already publsihed.
            // Leave out 'immediately'.
            $radioarray[] = $mform->createElement('radio', 'publish', '',
                get_string('msgeditatspecdate', 'block_news'), 1, $attributes);
            $radioarray[] = $mform->createElement('radio', 'publish', '',
                get_string('msgeditalreadypub', 'block_news'), 2, $attributes);
        } else {
            $radioarray[] = $mform->createElement('radio', 'publish', '',
                get_string('msgeditimmediately', 'block_news'), 0, $attributes);
            $radioarray[] = $mform->createElement('radio', 'publish', '',
                get_string('msgeditatspecdate', 'block_news'), 1, $attributes);
            // Leave out 'already published'.
        }
        $mform->addGroup($radioarray, 'radioar',
            get_string('msgeditpublish', 'block_news'),
            array(' '), false);

        // Add date_time selector in optional area.
        $mform->addElement('date_time_selector', 'messagedate',
                    get_string('msgeditmessagedate', 'block_news'), array('optional' => false));
        $mform->disabledIf('messagedate', 'publish', 'neq', 1);
        $mform->setAdvanced('optional');

        $mform->addElement('checkbox', 'messagerepeat',
                    get_string('msgeditrepeat', 'block_news'));
        $mform->disabledIf('messagerepeat', 'publish', 'neq', 1);
        $mform->setDefault('messagerepeat', 0);

        $mform->addElement('selectyesno', 'hideauthor',
            get_string('msgedithideauthor', 'block_news'));
        $mform->setDefault('hideauthor', (int) get_config('block_news', 'block_news_hideauthor'));
        $mform->disabledIf('hideauthor', 'messagetype', 'eq', block_news_message::MESSAGETYPE_EVENT);

        $mform->addElement('static', 'lastupdated',
                    get_string('msgeditlastupdated', 'block_news'));

        $this->add_action_buttons();
    }

    /**
     * Ensure that if an event is added with an end date, the end is after the start.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($this->displaytype == block_news_system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS
                && $data['messagetype'] == block_news_message::MESSAGETYPE_EVENT) {
            if ($data['eventstart'] < time()) {
                $errors['eventstart'] = get_string('erroreventstart', 'block_news');
            }
            if (!$data['alldayevent'] && $data['eventend'] < $data['eventstart']) {
                $errors['eventend'] = get_string('erroreventend', 'block_news');
            }
        }
        return $errors;
    }

}
