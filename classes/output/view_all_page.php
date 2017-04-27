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
 * Output component for View All page.
 *
 * @package    block_news
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_news\output;

use block_news\message;
use block_news\system;

defined('MOODLE_INTERNAL') || die();

class view_all_page implements \renderable, \templatable {

    /** @var short_message[] */
    public $news = [];
    /** @var short_message[] */
    public $upcomingevents = [];
    /** @var short_message[] */
    public $pastevents = [];

    /**
     * Creates view_all_page object with the messages to display on the page.
     *
     * @param system $bns
     * @param message[] $news
     * @param message[] $upcomingevents
     * @param message[] $pastevents
     */
    public function __construct(system $bns, array $news, array $upcomingevents, array $pastevents) {
        $summarylength = $bns->get_summarylength();
        $thumbnails = $bns->get_images('thumbnail');
        $images = $bns->get_images();
        foreach ($news as $newsmessage) {
            $this->news[] = new short_message($newsmessage, $bns, $summarylength, 0, $thumbnails, $images, 'all');
        }
        foreach ($upcomingevents as $upcomingevent) {
            $this->upcomingevents[] = new short_message($upcomingevent, $bns, $summarylength, 0, [], [], 'all');
        }
        foreach ($pastevents as $pastevent) {
            $this->pastevents[] = new short_message($pastevent, $bns, $summarylength, 0, [], [], 'all');
        }
    }

    /**
     * Return the context data for the template.
     *
     * Loops over all contained messages and returns export_for_template() for each.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $context = [
            'news' => [],
            'upcomingevents' => [],
            'pastevents' => []
        ];
        foreach ($this->news as $news) {
            $context['news'][] = $news->export_for_template($output);
        }
        foreach ($this->upcomingevents as $upcomingevent) {
            $context['upcomingevents'][] = $upcomingevent->export_for_template($output);
        }
        foreach ($this->pastevents as $pastevent) {
            $context['pastevents'][] = $pastevent->export_for_template($output);
        }
        return $context;
    }
}
