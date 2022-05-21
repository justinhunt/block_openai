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



class thefinetuneform extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'typeheading', "Create Fine Tune");

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'type','finetune');
        $mform->setType('type', PARAM_TEXT);

        $mform->addElement('text', 'name', 'Name', array('size'=>70));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $options  = common::fetch_trainingfiles_list();
        $mform->addElement('select', 'file', 'The File', $options);

        $options  = common::fetch_models_list();
        $mform->addElement('select', 'model', 'The Model', $options);
        $mform->setDefault('model', 'curie');

        $mform->addElement('text', 'description', 'Description', array('size'=>70));
        $mform->setType('description', PARAM_TEXT);
       // $mform->addRule('description', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'jsonopts', 'JSON opts', array('size'=>50));
        $mform->setType('jsonopts', PARAM_TEXT);
        $mform->setDefault('jsonopts', '{}');


        //add the action buttons
        $this->add_action_buttons(get_string('cancel'), get_string('save', constants::M_COMP));

    }
}