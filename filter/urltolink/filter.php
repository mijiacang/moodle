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
 * Filter converting URLs in the text to HTML links
 *
 * @package    filter
 * @subpackage urltolink
 * @copyright  2010 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class filter_urltolink extends moodle_text_filter {

    /**
     * @var array global configuration for this filter
     *
     * This might be eventually moved into parent class if we found it
     * useful for other filters, too.
     */
    protected static $globalconfig;

    /**
     * Apply the filter to the text
     *
     * @see filter_manager::apply_filter_chain()
     * @param string $text to be processed by the text
     * @param array $options filter options
     * @return string text after processing
     */
    public function filter($text, array $options = array()) {
        if (!isset($options['originalformat'])) {
            debugging('filter_urltolink requires originalformat option to be provided', DEBUG_DEVELOPER);
            return $text;
        }
        if (in_array($options['originalformat'], explode(',', $this->get_global_config('formats')))) {
            $this->convert_urls_into_links($text);
        }
        return $text;
    }

    ////////////////////////////////////////////////////////////////////////////
    // internal implementation starts here
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the global filter setting
     *
     * If the $name is provided, returns single value. Otherwise returns all
     * global settings in object. Returns null if the named setting is not
     * found.
     *
     * @param mixed $name optional config variable name, defaults to null for all
     * @return string|object|null
     */
    protected function get_global_config($name=null) {
        $this->load_global_config();
        if (is_null($name)) {
            return self::$globalconfig;

        } elseif (array_key_exists($name, self::$globalconfig)) {
            return self::$globalconfig->{$name};

        } else {
            return null;
        }
    }

    /**
     * Makes sure that the global config is loaded in $this->globalconfig
     *
     * @return void
     */
    protected function load_global_config() {
        if (is_null(self::$globalconfig)) {
            self::$globalconfig = get_config('filter_urltolink');
        }
    }

    /**
     * Given some text this function converts any URLs it finds into HTML links
     *
     * @param string $text Passed in by reference. The string to be searched for urls.
     */
    protected function convert_urls_into_links(&$text) {
        //I've added img tags to this list of tags to ignore.
        //See MDL-21168 for more info. A better way to ignore tags whether or not
        //they are escaped partially or completely would be desirable. For example:
        //<a href="blah">
        //&lt;a href="blah"&gt;
        //&lt;a href="blah">
        $filterignoretagsopen  = array('<a\s[^>]+?>');
        $filterignoretagsclose = array('</a>');
        filter_save_ignore_tags($text,$filterignoretagsopen,$filterignoretagsclose,$ignoretags);

        // Check if we support unicode modifiers in regular expressions. Cache it.
        // TODO: this check should be a environment requirement in Moodle 2.0, as far as unicode
        // chars are going to arrive to URLs officially really soon (2010?)
        // Original RFC regex from: http://www.bytemycode.com/snippets/snippet/796/
        // Various ideas from: http://alanstorm.com/url_regex_explained
        // Unicode check, negative assertion and other bits from Moodle.
        static $unicoderegexp;
        if (!isset($unicoderegexp)) {
            $unicoderegexp = @preg_match('/\pL/u', 'a'); // This will fail silently, returning false,
        }

        //todo: MDL-21296 - use of unicode modifiers may cause a timeout
        if ($unicoderegexp) { //We can use unicode modifiers
            $text = preg_replace('#(?<!=["\'])(((http(s?))://)(((([\pLl0-9]([\pLl0-9]|-)*[\pLl0-9]|[\pLl0-9])\.)+([\pLl]([\pLl0-9]|-)*[\pLl0-9]|[\pLl]))|(([0-9]{1,3}\.){3}[0-9]{1,3}))(:[\pL0-9]*)?(/([\pLl0-9\.!$&\'\(\)*+,;=_~:@-]|%[a-fA-F0-9]{2})*)*(\?([\pLl0-9\.!$&\'\(\)*+,;=_~:@/?-]|%[a-fA-F0-9]{2})*)?(\#[\pLl0-9\.!$&\'\(\)*+,;=_~:@/?-]*)?)(?<![,.;])#iu',
                                 '<a href="\\1" class="_blanktarget">\\1</a>', $text);
            $text = preg_replace('#(?<!=["\']|//)((www\.([\pLl0-9]([\pLl0-9]|-)*[\pLl0-9]|[\pLl0-9])\.)+([\pLl]([\pLl0-9]|-)*[\pLl0-9]|[\pLl])(:[\pL0-9]*)?(/([\pLl0-9\.!$&\'\(\)*+,;=_~:@-]|%[a-fA-F0-9]{2})*)*(\?([\pLl0-9\.!$&\'\(\)*+,;=_~:@/?-]|%[a-fA-F0-9]{2})*)?(\#[\pLl0-9\.!$&\'\(\)*+,;=_~:@/?-]*)?)(?<![,.;])#iu',
                                 '<a href="http://\\1" class="_blanktarget">\\1</a>', $text);
        } else { //We cannot use unicode modifiers
            $text = preg_replace('#(?<!=["\'])(((http(s?))://)(((([a-z0-9]([a-z0-9]|-)*[a-z0-9]|[a-z0-9])\.)+([a-z]([a-z0-9]|-)*[a-z0-9]|[a-z]))|(([0-9]{1,3}\.){3}[0-9]{1,3}))(:[a-zA-Z0-9]*)?(/([a-z0-9\.!$&\'\(\)*+,;=_~:@-]|%[a-f0-9]{2})*)*(\?([a-z0-9\.!$&\'\(\)*+,;=_~:@/?-]|%[a-fA-F0-9]{2})*)?(\#[a-z0-9\.!$&\'\(\)*+,;=_~:@/?-]*)?)(?<![,.;])#i',
                                 '<a href="\\1" class="_blanktarget">\\1</a>', $text);
            $text = preg_replace('#(?<!=["\']|//)((www\.([a-z0-9]([a-z0-9]|-)*[a-z0-9]|[a-z0-9])\.)+([a-z]([a-z0-9]|-)*[a-z0-9]|[a-z])(:[a-zA-Z0-9]*)?(/([a-z0-9\.!$&\'\(\)*+,;=_~:@-]|%[a-f0-9]{2})*)*(\?([a-z0-9\.!$&\'\(\)*+,;=_~:@/?-]|%[a-fA-F0-9]{2})*)?(\#[a-z0-9\.!$&\'\(\)*+,;=_~:@/?-]*)?)(?<![,.;])#i',
                                 '<a href="http://\\1" class="_blanktarget">\\1</a>', $text);
        }

        if (!empty($ignoretags)) {
            $ignoretags = array_reverse($ignoretags); /// Reversed so "progressive" str_replace() will solve some nesting problems.
            $text = str_replace(array_keys($ignoretags),$ignoretags,$text);
        }
    }
}