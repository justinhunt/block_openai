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



class theinferenceform extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'typeheading', "Create Inference");



        $mform->addElement('hidden', 'type','inference');
        $mform->setType('type', PARAM_TEXT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $statusready = true;
        $options  = common::fetch_finetunes_list( $statusready);
        $mform->addElement('select', 'finetune', 'The Finetune', $options);

        $mform->addElement('text', 'prompt', 'Prompt', array('size'=>70));
        $mform->setType('prompt', PARAM_TEXT);
       // $mform->addRule('description', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'jsonopts', 'JSON opts', array('size'=>70));
        $mform->setType('jsonopts', PARAM_TEXT);
        $mform->setDefault('jsonopts', '{"max_tokens": 412, "temperature": 0.5, "top_p": 1, "n": 1}');


        //add the action buttons
        $this->add_action_buttons(get_string('cancel'), get_string('save', constants::M_COMP));

    }
}