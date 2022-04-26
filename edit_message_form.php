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

use block_news\system;
use block_news\message;

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
    /** @var array File types allowed for message images */
    const IMAGE_FILE_TYPES = array('.jpg', '.png');
    /** @var array Restrictions for message image file manager */
    const IMAGE_FILE_OPTIONS = array('subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => self::IMAGE_FILE_TYPES);
    /** @var int Exact width for message images */
    const IMAGE_WIDTH = 700;
    /** @var int Exact height for message images */
    const IMAGE_HEIGHT = 330;
    /** @var int Max filesize (in bytes) for message images (currently 100KB) */
    const IMAGE_MAX_FILESIZE = 102400;

    protected $displaytype = 0;
    protected $publishstate = '';
    protected $groupingsupportbygroup = 0;

    /**
     * Overide constructor to pass in publish radio button state before
     * definition() is called
     *
     * @param array $customdata
     */
    public function __construct($customdata) {
        $this->displaytype = $customdata['displaytype'];
        $this->publishstate = $customdata['publishstate'];
        $this->groupingsupportbygroup = $customdata['groupingsupportbygroup'];
        parent::__construct();
    }


    public function definition() {
        global $COURSE, $PAGE;

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

        if ( $this->displaytype == system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS ) {
            $messagetype = array(message::MESSAGETYPE_NEWS => get_string('newsitem', 'block_news'),
                    message::MESSAGETYPE_EVENT => get_string('event', 'block_news'));
            $mform->addElement('select', 'messagetype', get_string('messagetype', 'block_news'), $messagetype);
            $mform->setDefault('messagetype', message::MESSAGETYPE_NEWS);
        }

        $mform->addElement('text', 'title', get_string('msgedittitle', 'block_news'),
            array('size' => '40'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', null, 'maxlength', 80, 'server');

        $mform->addElement('editor', 'message', get_string('msgeditmessage', 'block_news'),
            array('cols' => 50, 'rows' => 30), array('maxfiles' => EDITOR_UNLIMITED_FILES));
        $mform->addRule('message', null, 'required', null, 'client');

        if ( $this->displaytype == system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS ) {
            $mform->addElement('date_time_selector', 'eventstart',
                    get_string('msgediteventstart', 'block_news'), array('optional' => false));
            $mform->disabledIf('eventstart', 'messagetype', 'neq', message::MESSAGETYPE_EVENT);
            $mform->disabledIf('eventstart[hour]', 'alldayevent', 'checked');
            $mform->disabledIf('eventstart[minute]', 'alldayevent', 'checked');

            $mform->addElement('advcheckbox', 'alldayevent', get_string('msgeditalldayevent', 'block_news'));
            $mform->setDefault('alldayevent', 1);
            $mform->disabledIf('alldayevent', 'messagetype', 'neq', message::MESSAGETYPE_EVENT);

            $mform->addElement('date_time_selector', 'eventend',
                    get_string('msgediteventend', 'block_news'), array('optional' => false));
            $mform->disabledIf('eventend', 'messagetype', 'neq', message::MESSAGETYPE_EVENT);
            $mform->disabledIf('eventend', 'alldayevent', 'checked');

            $mform->addElement('text', 'eventlocation', get_string('msgediteventlocation', 'block_news'));
            $mform->disabledIf('eventlocation', 'messagetype', 'neq', message::MESSAGETYPE_EVENT);
            $mform->setType('eventlocation', PARAM_TEXT);
        }

        $mform->addElement('filemanager', 'attachments',
                get_string('msgedithlpattach', 'block_news'), null, array('subdirs' => 0));
        $mform->addElement('filemanager', 'messageimage', get_string('messageimage', 'block_news'),
                null, self::IMAGE_FILE_OPTIONS);
        $mform->disabledIf('messageimage', 'messagetype', 'eq', message::MESSAGETYPE_EVENT);

        $mform->addElement('text', 'imagedesc', get_string('imagedesc', 'block_news'),
                array('size' => '40'));
        $mform->setType('imagedesc', PARAM_TEXT);
        $mform->disabledIf('imagedesc', 'messagetype', 'eq', message::MESSAGETYPE_EVENT);

        $mform->addElement('checkbox', 'imagedescnotnecessary',
                get_string('imagedescnotnecessary', 'block_news'));
        $mform->setDefault('imagedescnotnecessary', 0);
        $mform->disabledIf('imagedescnotnecessary', 'messagetype',
                'eq', message::MESSAGETYPE_EVENT);

        $mform->addElement('selectyesno', 'messagevisible',
                get_string('msgeditvisible', 'block_news'));
        $mform->setDefault('messagevisible', 1);

        // If config_groupingsupport is group.
        if ($this->groupingsupportbygroup) {
            $groupingsdata = groups_get_all_groupings($COURSE->id);
            $groupings = [];
            $groupinggroups = [];
            foreach ($groupingsdata as $grouping) {
                $groupsdata = groups_get_all_groups($COURSE->id, 0, $grouping->id);
                // Simplify grouping groups data array, for JS functionality on the DOM element.
                $groupinggroups[$grouping->id] = array_keys($groupsdata);
                $groupings[$grouping->id] = $grouping->name;
            }
            $groups = groups_get_all_groups($COURSE->id);
            $groupoptions = [];
            if (!empty($groups)) {
                $groupoptions[0] = get_string('allparticipants');
                foreach ($groups as $group) {
                    $groupoptions[$group->id] = $group->name;
                }
                $mform->addElement('select', 'groupids',
                        get_string('msgeditgroup', 'block_news'), $groupoptions,
                        ['multiple' => true]);
                $mform->setDefault('groupid', 0);
                if (!empty($groupings)) {
                    $groupings[0] = '';
                    ksort($groupings);
                    // Use a data attribute to add grouping groups array to the DOM.
                    $strparams = json_encode($groupinggroups);
                    $mform->addElement('select', 'grouping',
                            get_string('msgeditselectgrouping', 'block_news'),
                            $groupings, ['data-groupinggroups' => $strparams]);

                    $PAGE->requires->js_call_amd('block_news/groupings', 'init');
                }
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
        $mform->disabledIf('hideauthor', 'messagetype', 'eq', message::MESSAGETYPE_EVENT);

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
        if ($this->displaytype == system::DISPLAY_SEPARATE_INTO_EVENT_AND_NEWSITEMS
                && $data['messagetype'] == message::MESSAGETYPE_EVENT) {
            if ($data['eventstart'] < time()) {
                $errors['eventstart'] = get_string('erroreventstart', 'block_news');
            }
            if (!$data['alldayevent'] && $data['eventend'] < $data['eventstart']) {
                $errors['eventend'] = get_string('erroreventend', 'block_news');
            }
        }
        if (!empty($data['messageimage'])) {
            $imageerrors = '';
            $draftfiles = file_get_drafarea_files($data['messageimage']);
            if ($image = reset($draftfiles->list)) { // There's only 1 file allowed, so this will give us the image.
                if ($image->size > self::IMAGE_MAX_FILESIZE) {
                    $imageerrors .= get_string('errorimagesize', 'block_news', self::IMAGE_MAX_FILESIZE / 1024);
                }
                if ($image->image_width != self::IMAGE_WIDTH || $image->image_height != self::IMAGE_HEIGHT) {
                    $errorparts = (object) ['width' => self::IMAGE_WIDTH, 'height' => self::IMAGE_HEIGHT];
                    $imageerrors .= get_string('errorimagedimensions', 'block_news', $errorparts);
                }
                if (!empty($imageerrors)) {
                    $errors['messageimage'] = $imageerrors;
                }

                if (empty($data['imagedesc']) && (!isset($data['imagedescnotnecessary']) or $data['imagedescnotnecessary'] == 0)) {
                    $errors['imagedesc'] = get_string('errorimagedesc', 'block_news');
                }
            }
        }
        if (!empty($data['groupids'])) {
            if (in_array('0', $data['groupids']) && count($data['groupids']) > 1) {
                $errors['groupids'] = get_string('errorinvalidgroups', 'block_news');
            }
        }
        return $errors;
    }

}
