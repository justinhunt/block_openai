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
 * Script to create a plan
 */

/**
 * A form for the creation and editing of a user
 *
 * @copyright 2020 Justin Hunt (poodllsupport@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   block_openai
 */


namespace block_openai\local\form;

use \block_openai\constants;
use \block_openai\common;

require_once($CFG->dirroot.'/lib/formslib.php');



class eclistingform extends \moodleform {


    public function definition() {
        global $CFG,$COURSE;
        $mform = $this->_form;

        //difficulty
        $difficulties = ['all'=>'all','beginner'=>'beginner','intermediate'=>'Intermediate','advanced'=>'Advanced'];
        $mform->addElement('select', 'difficulty',"Difficulty",$difficulties,null,array());

        //group
        $groups = ['1'=>'Travel','2'=>'Media','3'=>'Business','4'=>'Academic','5'=>'Social','6'=>'Kids','7'=>'Vocabulary','8'=>'Pronunciation'];
        $mform->addElement('select', 'group',"Group",$groups,null,array());

        //add the action buttons
        $this->add_action_buttons(get_string('cancel'), get_string('save', constants::M_COMP));


    }
}