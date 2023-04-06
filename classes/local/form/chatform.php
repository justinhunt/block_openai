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



class chatform extends \moodleform {


    public function definition() {
        global $CFG,$COURSE;
        $mform = $this->_form;

        $mform->addElement('textarea', 'messages', 'Messages', array('wrap'=>'virtual','rows'=>6,'style'=>'width: 100%;'));
        $mform->setType('messages', PARAM_RAW);
        /*
        $defaultmessages =
'{"role": "system", "content": "You are a helpful assistant."}
{"role": "user", "content": "Who won the world series in 2020?"}
{"role": "assistant", "content": "The Los Angeles Dodgers won the World Series in 2020."}
{"role": "user", "content": "Where was it played?"}';
*/

 $defaultmessages = '{"role": "system", "content": "You are a 25 year old advanced Colombian learner of English."}
{"role": "user", "content": "In approximately 5 sentences of easy English, tell me about a dangerous experience in your past."}';

        $mform->setDefault('messages',$defaultmessages);
        //add the action buttons
        $this->add_action_buttons(get_string('cancel'), get_string('save', constants::M_COMP));


    }
}