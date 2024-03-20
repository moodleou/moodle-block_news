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
    // Must be included from a Moodle page.
}

use block_news\system;

/**
 * block edit form definition
 * @package blocks
 * @subpackage news
 */
class block_news_edit_form extends block_edit_form {
    // Define option value for grouping support by group.
    const OPTIONVALUEBYGROUP = 2;

    /**
     * standard config form function
     * all field names must be prefixed by 'config_' which is stripped off in data
     * received in block_news::instance_config_save()
     */
    protected function specific_definition($mform) {
        $bns = $this->block->bns;
        $urlstxt = '';

        // Set feeds for text area.
        $frecs = $bns->get_feeds();
        foreach ($frecs as $frec) {
            $urlstxt .= $frec->feedurl;
            $urlstxt .= "\n";
        }

        // Fields for editing block title and contents.
        // Block settings.
        if ($this->page->course->format !== 'ousubject') {
            $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

            $mform->addElement('text', 'config_title', get_string('configtitle', 'block_news'));
            $mform->setType('config_title', PARAM_TEXT);
            $mform->setDefault('config_title', $bns->get_title());
            $mform->addRule('config_title', null, 'maxlength', 40, 'server');

            $choicesnm = [
                '1' => 'Latest message only',
                '2' => '2 most recent',
                '3' => '3 most recent',
                '4' => '4 most recent',
                '5' => '5 most recent'
            ];
            $mform->addElement('select', 'config_nummessages',
                get_string('confignummessages', 'block_news'), $choicesnm);
            $mform->setDefault('config_nummessages', $bns->get_nummessages());

            $choicessl = [
                '0' => 'None',
                '40' => 'Short',
                '100' => 'Medium',
                '500' => 'Long'
            ];
            $mform->addElement('select', 'config_summarylength',
                get_string('configsummarylength', 'block_news'), $choicessl);
            $mform->setDefault('config_summarylength', $bns->get_summarylength());

            $mform->addElement('textarea', 'config_feedurls',
                get_string("configfeedurls", "block_news"), 'wrap="virtual" rows="5" cols="128"');
            $mform->setType('config_title', PARAM_TEXT);
            $mform->setDefault('config_feedurls', $urlstxt);

            $mform->addElement('selectyesno', 'config_displaytype',
                get_string('separateintoeventsandnewsitems', 'block_news'));
            $mform->setDefault('config_displaytype', $bns->get_displaytype());

            $mform->addElement('selectyesno', 'config_hidetitles',
                get_string('confighidetitles', 'block_news'));
            $mform->setDefault('config_hidetitles', $bns->get_hidetitles());

            $mform->addElement('selectyesno', 'config_hidelinks',
                get_string('confighidelinks', 'block_news'));
            $mform->setDefault('config_hidelinks', $bns->get_hidelinks());

            $choicesgrouping = [
                '0' => get_string('configgroupingoptionnotenable', 'block_news'),
                self::OPTIONVALUEBYGROUP => get_string('configgroupingoptiongroup', 'block_news')
            ];

            $mform->addElement('select', 'config_groupingsupport',
                get_string('configgroupingsupport', 'block_news'), $choicesgrouping);
            $mform->setDefault('config_groupingsupport', $bns->get_groupingsupport());
            $mform->addHelpButton('config_groupingsupport', 'configgroupingsupport', 'block_news');
        } else {
            $mform->addElement('textarea', 'config_feedurls',
                get_string("configfeedurls", "block_news"), 'wrap="virtual" rows="5" cols="128"');
            $mform->setType('config_title', PARAM_TEXT);
            $mform->setDefault('config_feedurls', $urlstxt);
        }
    }

    function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'blockid', $this->block->instance->id);
        $mform->setType('blockid', PARAM_INT);
        $mform->addElement('hidden', 'blockname', $this->optional_param('blockname', null, PARAM_PLUGIN));
        $mform->setType('blockname', PARAM_PLUGIN);
        $mform->addElement('hidden', 'blockregion', $this->optional_param('blockregion', null, PARAM_TEXT));
        $mform->setType('blockregion', PARAM_TEXT);
        $mform->addElement('hidden', 'pagehash', $this->optional_param('pagehash', null, PARAM_ALPHANUMEXT));
        $mform->setType('pagehash', PARAM_ALPHANUMEXT);

        // First show fields specific to this type of block.
        $this->specific_definition($mform);

        // Check if the user is on the ousubject theme, don't show the fields about where this block appears.
        if ($this->page->course->format === 'ousubject') {
            $mform->addElement('hidden', 'bui_pagetypepattern');
            $mform->addElement('hidden', 'bui_subpagepattern');
            $mform->addElement('hidden', 'bui_defaultregion');
            $mform->addElement('hidden', 'bui_subpagepattern');
            $mform->addElement('hidden', 'bui_defaultweight');
            $mform->addElement('hidden', 'bui_visible');
            $mform->addElement('hidden', 'bui_region');
            $mform->addElement('hidden', 'bui_weight');
            return;
        }

        if (!$this->block->instance->id) {
            return;
        }

        // Then show the fields about where this block appears.
        $mform->addElement('header', 'whereheader', get_string('wherethisblockappears', 'block'));

        // If the current weight of the block is out-of-range, add that option in.
        $blockweight = $this->block->instance->weight;
        $weightoptions = [];
        if ($blockweight < -block_manager::MAX_WEIGHT) {
            $weightoptions[$blockweight] = $blockweight;
        }
        for ($i = -block_manager::MAX_WEIGHT; $i <= block_manager::MAX_WEIGHT; $i++) {
            $weightoptions[$i] = $i;
        }
        if ($blockweight > block_manager::MAX_WEIGHT) {
            $weightoptions[$blockweight] = $blockweight;
        }
        $first = reset($weightoptions);
        $weightoptions[$first] = get_string('bracketfirst', 'block', $first);
        $last = end($weightoptions);
        $weightoptions[$last] = get_string('bracketlast', 'block', $last);

        $regionoptions = $this->page->theme->get_all_block_regions();
        foreach ($this->page->blocks->get_regions() as $region) {
            // Make sure to add all custom regions of this particular page too.
            if (!isset($regionoptions[$region])) {
                $regionoptions[$region] = $region;
            }
        }

        $parentcontext = context::instance_by_id($this->block->instance->parentcontextid);
        $mform->addElement('hidden', 'static', 'bui_homecontext', get_string('createdat', 'block'),
            $parentcontext->get_context_name());
        $mform->addHelpButton('bui_homecontext', 'createdat', 'block');

        // For pre-calculated (fixed) pagetype lists.
        $pagetypelist = [];

        // Parse pagetype patterns.
        $bits = explode('-', $this->page->pagetype);

        // First of all, check if we are editing blocks @ front-page or no and
        // make some dark magic if so (MDL-30340) because each page context
        // implies one (and only one) harcoded page-type that will be set later
        // when processing the form data at {@see block_manager::process_url_edit()}.

        // Front page, show the page-contexts element and set $pagetypelist to 'any page' (*)
        // as unique option. Processign the form will do any change if needed.
        if ($this->is_editing_the_frontpage()) {
            $contextoptions = [];
            $contextoptions[BUI_CONTEXTS_FRONTPAGE_ONLY] = get_string('showonfrontpageonly', 'block');
            $contextoptions[BUI_CONTEXTS_FRONTPAGE_SUBS] = get_string('showonfrontpageandsubs', 'block');
            $contextoptions[BUI_CONTEXTS_ENTIRE_SITE]    = get_string('showonentiresite', 'block');
            $mform->addElement('select', 'bui_contexts', get_string('contexts', 'block'), $contextoptions);
            $mform->addHelpButton('bui_contexts', 'contexts', 'block');
            $pagetypelist['*'] = '*'; // This is not going to be shown ever, it's an unique option.

            // Any other system context block, hide the page-contexts element,
            // it's always system-wide BUI_CONTEXTS_ENTIRE_SITE.
        } else if ($parentcontext->contextlevel == CONTEXT_SYSTEM) {

        } else if ($parentcontext->contextlevel == CONTEXT_COURSE) {
            // 0 means display on current context only, not child contexts
            // but if course managers select mod-* as pagetype patterns, block system will overwrite this option
            // to 1 (display on current context and child contexts).
        } else if ($parentcontext->contextlevel == CONTEXT_MODULE || $parentcontext->contextlevel == CONTEXT_USER) {
            // Module context doesn't have child contexts, so display in current context only.
        } else {
            $parentcontextname = $parentcontext->get_context_name();
            $contextoptions[BUI_CONTEXTS_CURRENT] = get_string('showoncontextonly', 'block', $parentcontextname);
            $contextoptions[BUI_CONTEXTS_CURRENT_SUBS] = get_string('showoncontextandsubs', 'block', $parentcontextname);
            $mform->addElement('select', 'bui_contexts', get_string('contexts', 'block'), $contextoptions);
        }
        $mform->setType('bui_contexts', PARAM_INT);

        // Generate pagetype patterns by callbacks if necessary (has not been set specifically).
        if (empty($pagetypelist)) {
            $pagetypelist = generate_page_type_patterns($this->page->pagetype, $parentcontext, $this->page->context);
            $displaypagetypewarning = false;
            if (!array_key_exists($this->block->instance->pagetypepattern, $pagetypelist)) {
                // Pushing block's existing page type pattern.
                $pagetypestringname = 'page-'.str_replace('*', 'x', $this->block->instance->pagetypepattern);
                if (get_string_manager()->string_exists($pagetypestringname, 'pagetype')) {
                    $pagetypelist[$this->block->instance->pagetypepattern] = get_string($pagetypestringname, 'pagetype');
                } else {
                    // As a last resort we could put the page type pattern in the select box
                    // However this causes mod-data-view to be added if the only option available is mod-data-*
                    // so we are just showing a warning to users about their prev setting being reset.
                    $displaypagetypewarning = true;
                }
            }
        }

        // Hide page type pattern select box if there is only one choice.
        if (count($pagetypelist) > 1) {
            if ($displaypagetypewarning) {
                $mform->addElement('static', 'pagetypewarning', '', get_string('pagetypewarning', 'block'));
            }

            $mform->addElement('select', 'bui_pagetypepattern', get_string('restrictpagetypes', 'block'), $pagetypelist);
        } else {
            $values = array_keys($pagetypelist);
            $value = array_pop($values);
            // Now we are really hiding a lot (both page-contexts and page-type-patterns),
            // specially in some systemcontext pages having only one option (my/user...)
            // so, until it's decided if we are going to add the 'bring-back' pattern to
            // all those pages or no (see MDL-30574), we are going to show the unique
            // element statically.
            // TODO: Revisit this once MDL-30574 has been decided and implemented, although
            // perhaps it's not bad to always show this statically when only one pattern is
            // available.
            if (!$this->is_editing_the_frontpage()) {
                // Try to beautify it.
                $strvalue = $value;
                $strkey = 'page-'.str_replace('*', 'x', $strvalue);
                if (get_string_manager()->string_exists($strkey, 'pagetype')) {
                    $strvalue = get_string($strkey, 'pagetype');
                }
                // Show as static (hidden has been set already).
                $mform->addElement('static', 'bui_staticpagetypepattern',
                    get_string('restrictpagetypes', 'block'), $strvalue);
            }
        }

        if ($this->page->subpage) {
            if ($parentcontext->contextlevel != CONTEXT_USER) {
                $subpageoptions = [
                    '%@NULL@%' => get_string('anypagematchingtheabove', 'block'),
                    $this->page->subpage => get_string('thisspecificpage', 'block', $this->page->subpage),
                ];
                $mform->addElement('select', 'bui_subpagepattern', get_string('subpages', 'block'), $subpageoptions);
            }
        }

        $defaultregionoptions = $regionoptions;
        $defaultregion = $this->block->instance->defaultregion;
        if (!array_key_exists($defaultregion, $defaultregionoptions)) {
            $defaultregionoptions[$defaultregion] = $defaultregion;
        }
        $mform->addElement('select', 'bui_defaultregion', get_string('defaultregion', 'block'), $defaultregionoptions);
        $mform->addHelpButton('bui_defaultregion', 'defaultregion', 'block');

        $mform->addElement('select', 'bui_defaultweight', get_string('defaultweight', 'block'), $weightoptions);
        $mform->addHelpButton('bui_defaultweight', 'defaultweight', 'block');

        // Where this block is positioned on this page.
        $mform->addElement('header', 'onthispage', get_string('onthispage', 'block'));

        $mform->addElement('selectyesno', 'bui_visible', get_string('visible', 'block'));

        $blockregion = $this->block->instance->region;
        if (!array_key_exists($blockregion, $regionoptions)) {
            $regionoptions[$blockregion] = $blockregion;
        }
        $mform->addElement('select', 'bui_region', get_string('region', 'block'), $regionoptions);

        $mform->addElement('select', 'bui_weight', get_string('weight', 'block'), $weightoptions);

        $pagefields = ['bui_visible', 'bui_region', 'bui_weight'];
        if (!$this->block->user_can_edit()) {
            $mform->hardFreezeAllVisibleExcept($pagefields);
        }
        if (!$this->page->user_can_edit_blocks()) {
            $mform->hardFreeze($pagefields);
        }

        if (!empty($this->_customdata['actionbuttons'])) {
            $this->add_action_buttons();
        }
    }

    public function validation($data, $files) {
        return system::validate_form($data);
    }

}
