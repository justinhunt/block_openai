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
 * Subs manage
 *
 * @package    block_openai
 * @copyright  2019 Justin Hunt  {@link http://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_openai\constants;
use block_openai\common;
use block_openai\openai;
require('../../config.php');

$courseid = optional_param('courseid', 1,PARAM_INT);
$view = optional_param('view', 0,PARAM_INT);
$id = optional_param('id', 0,PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

//set the url of the $PAGE
//note we do this before require_login preferably
//so Moodle will send user back here if it bounces them off to login first
$PAGE->set_url(constants::M_URL . '/inference.php',array('courseid'=>$courseid));
$course = get_course($courseid);
require_login($course);


//datatables css
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('pluginname', constants::M_COMP));
$PAGE->navbar->add(get_string('pluginname', constants::M_COMP));


$ok = has_capability('block/openai:managesite', $context);


$inferenceform = new \block_openai\local\form\theinferenceform();

//get our renderer
$renderer = $PAGE->get_renderer(constants::M_COMP);
echo $renderer->header();
echo $renderer->heading($SITE->fullname);


if($ok) {

    if ($inferenceform->is_cancelled()){
        redirect($returnurl);
    }else if($data = $inferenceform->get_data()) {
        $options = [];
        $finetune = $DB->get_record(constants::M_TABLE_FINETUNES, array('id' => $data->finetuneid));
        $trainingfile = $DB->get_record(constants::M_TABLE_FILES, array('id' => $finetune->file));
        if(!empty($data->prompt)) {
            $options['prompt'] = $data->prompt . $trainingfile->seperator;
            $options['model'] = $finetune->ftmodel;
            $options['stop'] = $trainingfile->stopsequence;
            if (common::is_json($data->jsonopts)) {
                $options = $options + json_decode($data->jsonopts, true);
            }
            $response = openai::custom_request($options);

            //save a copy of this inference
            if (!empty($response)) {
                if (isset($response->error)) {
                    echo $response->error->message;
                } else {
                    $rec = new \stdClass();
                    $rec->prompt = $data->prompt;
                    $rec->completion = $response;
                    $rec->finetuneid = $finetune->id;
                    $rec->fileid = $trainingfile->id;
                    $rec->jsonopts = $data->jsonopts;
                    $rec->timecreated = time();
                    $rec->timemodified = time();
                    $rec->id = $DB->insert_record(constants::M_TABLE_INFERENCES, $rec);
                }
            }//if not empty response
        }//if not empty data prompt

    }else if($view && $id) {
        $inference = $DB->get_record(constants::M_TABLE_INFERENCES, ['id' => $id]);
        $tdata = [];
        $tdata['prompt'] = $inference->prompt;
        $tdata['response'] = $inference->completion;
        echo $renderer->render_from_template('block_openai/viewinferencedetails', $tdata);

        echo $renderer->quicklink(constants::SETTING_FINETUNES, $courseid);

        echo $renderer->footer();
        return;
    }


  //display the form
    $usedata= ['courseid'=>$courseid];
    $inferenceform->set_data($usedata);


    $inferenceform->display();
    if(!empty($response)){
        if(isset($response->error)){
            echo $response->error->message;
        }else {
            $tdata = [];
            $tdata['response'] = $response;
          echo  $renderer->render_from_template('block_openai/completionresponse', $tdata);
        }
    }



}else{
    echo  get_string('nopermission', constants::M_COMP);
}

echo $renderer->quicklink( constants::SETTING_FINETUNES, $courseid);

echo $renderer->footer();