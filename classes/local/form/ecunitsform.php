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



class ecunitsform extends \moodleform {


    public function definition() {
        global $CFG,$COURSE;
        $mform = $this->_form;
        $this->units = $this->_customdata['units'];
        $unitindex=0;
        $videoindex=0;
        $dquestionindex=0;
        foreach ($this->units as $unit){
            //unit
            $mform->addElement('static', "unit","Unit","---------------------------------------------------------");
            $unitindex++;
            $mform->addElement('advcheckbox', "unitid[$unitindex]", null, strtoupper($unit->name), array(), array(0, $unit->unitid));
            $mform->setType("unitid[$unitindex]", PARAM_INT);
            $mform->setDefault("unitid[$unitindex]", $unit->unitid);

            //unit videos
            $mform->addElement('static', "unitvideos","Unit Videos","");
            $unitvideoindex=0;
            foreach($unit->videos as $video){
                $unitvideoindex++;
                $videoindex++;
                $mform->addElement('advcheckbox', "videoid[$videoindex]",null, $video->topic . " <a href='$video->detailsurl' target='_blank'>[check]</a>", array(), array(0, $video->videoid));
                $mform->setType("videoid[$videoindex]", PARAM_INT);
                if($unitvideoindex<5) {
                    $mform->setDefault("videoid[$videoindex]", $video->videoid);
                }else{
                    $mform->setDefault("videoid[$videoindex]", 0);
                }
            }
            //unit discussion questions
            $mform->addElement('static', "discussionquestions","Discussion Questions","");
            $dquestionarray=array();
            foreach($unit->videos as $video){
                foreach($video->dquestions as $dquestion) {
                    $dquestionindex++;
                    $dquestionarray[] = $mform->createElement('radio', "dquestionid[$unitindex]", '',  $dquestion->questiontext, $dquestion->dquestionid, []);
                    $mform->setType("dquestionid[$dquestionindex]", PARAM_INT);
                }
            }
            $mform->setDefault("dquestionid[$unitindex]", $dquestion->dquestionid);
            $mform->addGroup($dquestionarray, 'dquestions_' . $unitindex, '', array(' '), false);

            //unit model answer and keywords
            $mform->addElement('textarea', "solomodelanswer[$unit->unitid]", 'Solo Model Answer', array('wrap'=>'virtual','style'=>'width: 100%;'));
            $mform->setType("solomodelanswer[$unit->unitid]", PARAM_TEXT);
            $mform->setDefault("solomodelanswer[$unit->unitid]", 'How are you?');

            $mform->addElement('textarea', "solokeywords[$unit->unitid]", 'Solo Keywords', array('wrap'=>'virtual','style'=>'width: 100%;'));
            $mform->setType("solokeywords[$unit->unitid]", PARAM_TEXT);
            $mform->setDefault("solokeywords[$unit->unitid]", 'akeyword');

        }

        $mform->addElement('hidden', 'eccourseid');
        $mform->setType('eccourseid', PARAM_INT);


        //add the action buttons
        $this->add_action_buttons(get_string('cancel'), get_string('save', constants::M_COMP));

    }
}