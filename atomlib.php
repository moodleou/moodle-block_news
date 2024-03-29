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
 * News block System/Config class
 * This library provides functions to generate Atom feeds and tries to follow the
 * same API as lib/rsslib.php
 *
 * @package    blocks
 * @subpackage news
 * @copyright 2011 The Open University
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Jon Sharp <jonathans@catalyst-eu.net>(adapted for news block)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
    // It must be included from a Moodle page
}

/** Return the common atom headers in XML
 *
 * @param string $nsi Special namespace uri '...news:expires'
 * @param string $uniqueid For atom:id
 * @param string $linkself For atom:link (self)
 * @param string $linkalt For atom:link (alternate)
 * @param integer $updated For atom:updated
 * @param string $title For atom:title
 * @param string $description For atom:subtitle
 * @return mixed
 */
function atom_standard_header($nsi, $uniqueid, $linkself, $linkalt, $updated,
    $title = null, $description = null) {

    global $OUTPUT;

    $status = true;
    $result = "";

    if (!$site = get_site()) {
        $status = false;
    }

    if ($status) {

        //Calculate title, link and description
        if (empty($title)) {
            $title = format_string($site->fullname);
        }

        //xml headers
        $result .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $result .= "<feed xmlns=\"http://www.w3.org/2005/Atom\"".$nsi.">\n";

        //open the channel
        //write channel info
        $result .= atom_full_tag('id', 1, false, htmlspecialchars($uniqueid));
        $result .= atom_full_tag('updated', 1, false, date_format_rfc3339($updated));
        $result .= atom_full_tag('title', 1, false, htmlspecialchars(html_to_text($title)));
        $result .= atom_full_tag('link', 1, false, null, array('href'=>$linkself, 'rel'=>'self'));
        $result .= atom_full_tag('link', 1, false, null, array('href'=>$linkalt,
                                    'rel'=>'alternate'));

        if (!empty($description)) {
            $result .= atom_full_tag('subtitle', 1, false, $description);
        }
        $result .= atom_full_tag('generator', 1, false, 'Moodle');
        $today = getdate();
        $result .= atom_full_tag('rights', 1, false, '&#169; '. $today['year'] .' '.
            format_string($site->fullname));

        //write image info
        $atompix = $OUTPUT->image_url('i/rsssitelogo');

        //write the info
        $result .= atom_full_tag('logo', 1, false, $atompix);

    }

    if (!$status) {
        return false;
    } else {
        return $result;
    }
}


/**
 * Return an entry in XML
 * @param array $items StdClass of atom:entry elements
 * @return mixed
 */

function atom_add_items($items) {

    $result = '';
    $xhtmlattr = array('type'       => 'xhtml');

    if (!empty($items)) {
        foreach ($items as $item) {
            $result .= atom_start_tag('entry', 1, true);
            $result .= atom_full_tag('title', 2, false,
                        htmlspecialchars(html_to_text($item->title)));
            $result .= atom_full_tag('link', 2, false, null, array('href' => $item->link,
                'rel'=>'alternate'));
            $result .= atom_full_tag('updated', 2, false, date_format_rfc3339($item->pubdate));
            //Include the author if exists
            if (isset($item->author)) {
                $result .= atom_start_tag('author', 2, true);
                $result .= atom_full_tag('name', 3, false, $item->author);
                $result .= atom_end_tag('author', 2, true);
            }
            $result .= atom_full_tag('content', 2, false,
                '<div xmlns="http://www.w3.org/1999/xhtml">'
                    .clean_text($item->content, FORMAT_HTML).'</div>',
                $xhtmlattr);
            $result .= atom_full_tag('id', 2, false, $item->link);
            if (isset($item->tags)) {
                $tagdata = array();
                if (isset($item->tagscheme)) {
                    $tagdata['scheme'] = $item->tagscheme;
                }
                foreach ($item->tags as $tag) {
                    $tagdata['term'] = $tag;
                    $result .= atom_full_tag('category', 2, true, false, $tagdata);
                }
            }
            $result .= atom_end_tag('entry', 1, true);

        }
    } else {
        $result = false;
    }
    return $result;
}


/**
 * Return the common footers in XML (typically args are null - they are present
 * for conformity with RSS lib)
 *
 * @param $title
 * @param $link
 * @param $description
 * @return string
 */
function atom_standard_footer($title = null, $link = null, $description = null) {

    $result = '';

    ////Close the feed
    $result .= '</feed>';

    return $result;
}


/**
 * Return the start tag in XML
 *
 * @param string $tag Tag name
 * @param integer $level Indentation (for output formatting)
 * @param boolean $endline
 * @param array $attributes Tag attributes
 * @return string
 */
function atom_start_tag($tag, $level=0, $endline=false, $attributes=null) {
    if ($endline) {
        $endchar = "\n";
    } else {
        $endchar = "";
    }
    $attrstring = '';
    if (!empty($attributes) && is_array($attributes)) {
        foreach ($attributes as $key => $value) {
            $attrstring .= " ".$key."=\"".htmlspecialchars($value)."\"";
        }
    }
    return str_repeat(" ", $level*2)."<".$tag.$attrstring.">".$endchar;
}


/**
 * Return the end tag in XML
 *
 * @param string $tag Tag name
 * @param integer $level Indentation
 * @param boolean $endline
 * @return string
 */
function atom_end_tag($tag, $level=0, $endline=true) {
    if ($endline) {
        $endchar = "\n";
    } else {
        $endchar = "";
    }
    return str_repeat(" ", $level*2)."</".$tag.">".$endchar;
}


/**
 * Return the start tag, the contents and the end tag in one call
 *
 * @param string $tag Tag name
 * @param integer $level Indentation
 * @param boolean $endline
 * @param $content
 * @param $attributes
 * @return string
 */
function atom_full_tag($tag, $level, $endline, $content, $attributes = null) {
    $st = atom_start_tag($tag, $level, $endline, $attributes);
    if ($content === false) {
        $st = preg_replace('~>$~', ' />', $st);
        return $st;
    }
    $co = preg_replace("/\r\n|\r/", "\n", $content ?? '');
    $et = atom_end_tag($tag, 0, true);

    return $st.$co.$et;
}


/**
 * Convert secs to RFC3339 compliant format
 *
 * @param integer $timestamp Seconds since start of epoch
 * @return string Formatted date
 */
function date_format_rfc3339($timestamp=0) {

    $date = date('Y-m-d\TH:i:s', $timestamp);

    $matches = array();

    if (preg_match('/^([\-+])(\d{2})(\d{2})$/', date('O', $timestamp), $matches)) {
        $date .= $matches[1].$matches[2].':'.$matches[3];
    } else {
        $date .= 'Z';
    }
    return $date;
}
