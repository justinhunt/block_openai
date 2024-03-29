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


        // add course categories.
        $displaylist = \core_course_category::make_categories_list(\core_course\management\helper::get_course_copy_capabilities());
        $mform->addElement('autocomplete', 'category', get_string('coursecategory'), $displaylist);
        $mform->addRule('category', null, 'required', null, 'client');
        $mform->addHelpButton('category', 'coursecategory');

        //EC Videos per Unit
        //unit solo model answer
        $prompts = ["1"=>"1","2"=>"2","3"=>"3","4"=>"4","5"=>"5","all"=>"all"];
        $mform->addElement('select', "videosperec","Videos per EC activity",$prompts,null,array());

        //add activity checkboxes
        $items=['add_ec_videos','add_minilessons','add_solos','add_notes'];
        foreach($items as $item) {
            $mform->addElement('advcheckbox',$item, null, ucwords(str_replace('_',' ',$item)), array(), array(0, true));
            $mform->setType($item, PARAM_BOOL);
            $mform->setDefault($item, true);
        }

        //add course images
        $demopics=[];
        foreach ($this->units as $unit) {
            foreach ($unit->videos as $video) {
                $demopics[]=['url'=>$video->demopic,
                    'html'=>\html_writer::tag('img', '', array('src' => $video->demopic,'width'=>150))];
            }
        }
        $bannerurlarray = array();
        for ($i = 0; $i < count($demopics); $i++) {
            $bannerurlarray[] =& $mform->createElement('radio', 'bannerurl', '', $demopics[$i]['html'], $demopics[$i]['url']);
        }
        $mform->addGroup($bannerurlarray, 'bannerurl', "Course Banner Image", array(' '), FALSE);

        $mform->addElement('hidden', 'eccourseid');
        $mform->setType('eccourseid', PARAM_INT);
        $mform->setDefault('bannerurl', $demopics[0]['url']);

        foreach ($this->units as $unit){
            //unit
            $mform->addElement('static', "unit","Unit","---------------------------------------------------------");
            $unitindex++;
            $mform->addElement('advcheckbox', "unitid[$unitindex]", null, strtoupper($unit->name), array(), array(0, $unit->unitid));
            $mform->setType("unitid[$unitindex]", PARAM_INT);
            $mform->setDefault("unitid[$unitindex]", $unit->unitid);
            //hidden unitindex
            $mform->addElement('hidden', "unitindex[$unit->unitid]" );
            $mform->setDefault("unitindex[$unit->unitid]",$unitindex);
            $mform->setType("unitindex[$unit->unitid]",PARAM_INT);

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

            //unit solo model answer
            $prompts = ["Emma"=>"You are a 25 year old female ESL student from Australia who likes sports and going out.",
                "Salli"=>"You are a 50 year old female corporate executive from the Mexico who likes business, hamburgers and discussing politics.",
                "Kevin"=>"You are a 25 year old male ESL student from Japan who likes travelling and vegan food.",
                "Matthew"=>"You are a 40 year old male ESL student from France who likes learning new languages, going to the gym and making pottery."];
            $mform->addElement('select', "prompts[$unitindex]","Prompts",$prompts,null,array());
            $mform->addElement('button',"somebutton[$unitindex]","Generate Model Answer",array('class'=>'writemodelanswer','data-unitindex'=>$unitindex,'data-targetfield'=>'solomodelanswer'));
            $mform->addElement('textarea', "solomodelanswer[$unitindex]", 'Solo Model Answer', array('wrap'=>'virtual','style'=>'width: 100%;'));
            $mform->setType("solomodelanswer[$unitindex]", PARAM_TEXT);
            $mform->setDefault("solomodelanswer[$unitindex]", 'How are you?');


            //unit solo keywords
            $mform->addElement('button',"somebutton[$unitindex]","Generate Keywords",array('class'=>'writekeywords','data-unitindex'=>$unitindex,'data-targetfield'=>'solokeywords'));
            $mform->addElement('textarea', "solokeywords[$unitindex]", 'Solo Keywords', array('wrap'=>'virtual','style'=>'width: 100%;'));
            $mform->setType("solokeywords[$unitindex]", PARAM_TEXT);
            $mform->setDefault("solokeywords[$unitindex]", 'akeyword');

        }




        //add the action buttons
        $this->add_action_buttons(get_string('cancel'), get_string('save', constants::M_COMP));

    }
}