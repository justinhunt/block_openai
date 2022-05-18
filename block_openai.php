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
 * Poodll NET kanri
 *
 * @package    block_openai
 * @copyright  Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_openai\constants;
use block_openai\common;

class block_openai extends block_base {

    function init() {
        $this->title = get_string('pluginname', constants::M_COMP);
    }

    function get_content() {
        global $CFG, $OUTPUT;

        //we only show this to super people
        if (!has_capability('block/openai:managesite', $this->context)) {
            return null;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $this->content->text = '';

        //get the course this block is on
        $course = $this->page->course;


        //get the block instance settings (position , id  etc)
        $instancesettings = $this->instance;

        //get the admin config (that we define in settings.php)
        $adminconfig = get_config(constants::M_COMP);
        //get the instance config (that we define in edit_form)
        $localconfig = $this->config;
        //get best config. our helper class to merge local and admin configs
        $bestconfig = common::fetch_best_config($instancesettings->id);


        $renderer = $this->page->get_renderer(constants::M_COMP);
        $this->content->text = $renderer->fetch_block_content($course->id);
        return $this->content;
    }

    //This is a list of places where the block may or may not be added by the admin
    public function applicable_formats() {
        return array('my'=>true,'course'=>true);
    }

    //Can we have more than one instance of the block?
    public function instance_allow_multiple() {
          return false;
    }

    function has_config() {return true;}

}
