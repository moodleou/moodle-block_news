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
 * Behat steps for news block
 *
 * @package    block_news
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No MOODLE_INTERNAL check.

use Behat\Gherkin\Node\TableNode;
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps for the news block.
 *
 * Note that all these steps contain the word 'news'.
 *
 * @package block_news
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_news extends behat_base {

    /**
     * Generate news messages
     *
     * @Given /^the following news messages exist on course "([^"]*)":$/
     *
     * @param string $courseshortname Shortname of the course to post the messages in
     * @param TableNode $messagetable List of messages to create
     */
    public function the_following_news_messages_exist($courseshortname, TableNode $messagetable) {
        global $DB;
        $generator = testing_util::get_data_generator()->get_plugin_generator('block_news');
        $course = $DB->get_record('course', ['shortname' => $courseshortname], '*', MUST_EXIST);
        $coursecontext = context_course::instance($course->id);
        $block = $DB->get_record('block_instances', ['blockname' => 'news', 'parentcontextid' => $coursecontext->id],
                '*', MUST_EXIST);
        $messages = $messagetable->getColumnsHash();
        foreach ($messages as $message) {
            $generator->create_block_new_message($block, (object) $message);
        }
    }

    /**
     * Check that the message with the given title contains an image with the given name.
     *
     * @Then /^I should see image "([^"]+)" in news message "([^"]+)"$/
     *
     * @param string $imagename The filename of the image
     * @param string $messagetitle The title of the message
     */
    public function i_should_see_image_in_news_message($imagename, $messagetitle) {
        if ($imagename === 'thumbnail.jpg') {
            $titlecontainer = "span[contains(@class, 'block_news_msg_title')][contains(text(), '$messagetitle')]/../../";
        } else {
            $titlecontainer = "div[contains(@class, 'box title')][contains(text(), '$messagetitle')]/following-sibling::";
        }
        $xpath = "//" . $titlecontainer . "div[contains(@class, 'messageimage')]/img[contains(@src, '$imagename')]";
        $this->find('xpath', $xpath);
    }
}

