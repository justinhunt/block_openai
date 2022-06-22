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
 * poodll netadmin related management functions
 *
 * @package    block_netadmin
 * @copyright  2019 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require('../../config.php');

use block_openai\constants;
use block_openai\common;
use block_openai\openai;

$id        = optional_param('id', 0, PARAM_INT);
$delete    = optional_param('delete', 0, PARAM_BOOL);
$confirm    = optional_param('confirm', 0, PARAM_BOOL);
$type    = optional_param('type', 'plan', PARAM_TEXT);//plan/ sub school /assigntenant / createtenants
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

require_login();

$category = null;



$context = context_system::instance();
require_capability('block/openai:manageservices', $context);

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    switch($type) {
        case 'finetune':
        case 'trainingfile':
            $returnurl = new moodle_url(constants::M_URL . '/finetune.php', array());
            break;
        case 'run':
        default:
            $returnurl = new moodle_url(constants::M_URL . '/run.php', array());
            break;
    }
}


$PAGE->set_context($context);
$baseurl = new moodle_url(constants::M_URL. '/manage.php', array('id' => $id, 'type'=>$type));
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$renderer = $PAGE->get_renderer(constants::M_COMP);

if ($delete && $id) {
    $PAGE->url->param('delete', 1);
    switch($type){

        case 'finetune':
            if ($confirm and confirm_sesskey()) {
                $finetune = $DB->get_record(constants::M_TABLE_FINETUNES,array('id'=>$id));
                if($finetune && $finetune->status==1) {
                    openai::delete_finetune($finetune->ftmodel);
                    $DB->delete_records(constants::M_TABLE_FINETUNES,array('id'=>$id));
                }
                redirect($returnurl);
            }
            $strheading = "Deleting Fine Tune";
            $PAGE->navbar->add($strheading);
            $PAGE->set_title($strheading);
            $PAGE->set_heading($SITE->fullname);
            echo $renderer->header();
            echo $renderer->heading($strheading);
            $yesurl = new moodle_url($baseurl . '/manage.php', array('id' => $id, 'delete' => 1,'type'=>'finetune',
                'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl->out_as_local_url()));
            $message = "Really delete Fine tune?";
            echo $renderer->confirm($message, $yesurl, $returnurl);
            echo $renderer->footer();
            die;
        case 'trainingfile':
            if ($confirm and confirm_sesskey()) {
                $trainingfile = $DB->get_record(constants::M_TABLE_FILES,array('id'=>$id));
                if($trainingfile) {
                    openai::delete_trainingfile($trainingfile->openaiid);
                    $DB->delete_records(constants::M_TABLE_FILES,array('id'=>$id));
                }
                redirect($returnurl);
            }
            $strheading = "Deleting Training File";
            $PAGE->navbar->add($strheading);
            $PAGE->set_title($strheading);
            $PAGE->set_heading($SITE->fullname);
            echo $renderer->header();
            echo $renderer->heading($strheading);
            $yesurl = new moodle_url($baseurl . '/manage.php', array('id' => $id, 'delete' => 1,'type'=>'trainingfile',
                'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl->out_as_local_url()));
            $message = "Really delete training file";
            echo $renderer->confirm($message, $yesurl, $returnurl);
            echo $renderer->footer();
            die;

        case 'inference':
            $inference = $DB->get_record(constants::M_TABLE_INFERENCES,array('id'=>$id));
            if(!$inference){
                redirect($returnurl);
            }
            if ($confirm and confirm_sesskey()) {

                if($inference) {
                    $DB->delete_records(constants::M_TABLE_INFERENCES,array('id'=>$id));
                }
                redirect($returnurl);
            }
            $strheading = "Deleting Inference";
            $PAGE->navbar->add($strheading);
            $PAGE->set_title($strheading);
            $PAGE->set_heading($SITE->fullname);
            echo $renderer->header();
            echo $renderer->heading($strheading);
            $yesurl = new moodle_url($baseurl . '/manage.php', array('id' => $id, 'delete' => 1,'type'=>'inference',
                'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl->out_as_local_url()));
            $message = "Really delete Inference '" . $inference->prompt . "' ?";
            echo $renderer->confirm($message, $yesurl, $returnurl);
            echo $renderer->footer();
            die;

    }

}


switch($type){

    case 'trainingfile':
        $editform = new \block_openai\local\form\thefileform();
        break;
    case 'finetune':
        $editform = new \block_openai\local\form\thefinetuneform();
        break;


}


if ($editform->is_cancelled()){
    redirect($returnurl);
}else if($data = $editform->get_data()) {

    switch($type){

        case 'finetune':
            if (!$data->id) {
                $success=false;
                $trainingfile = $DB->get_record(constants::M_TABLE_FILES,array('id'=>$data->file));

                $ret = openai::create_finetune($trainingfile->openaiid,$data->model);
                if($ret && isset($ret->id)) {
                    $data->openaiid = $ret->id;
                    $data->timemodified = time();
                    $data->timecreated = time();
                    $success = $DB->insert_record(constants::M_TABLE_FINETUNES, $data);
                }else{
                    $payload = $ret;
                }
                if(!$success){
                    $payload = "it seemed to FAIL: " . $ret->error->message;
                    redirect($returnurl,$payload,3,\core\output\notification::NOTIFY_WARNING);
                }

            } else {
                $update = new \stdClass();
                $update->timemodified=time();
                $update->description = $data->description;
                $update->name = $data->name;
                $result = $DB->update_record(constants::M_TABLE_FINETUNES, $update);
            }
            break;

        case 'trainingfile':
            if (!$data->id) {
                $success=false;
                $ret = openai::create_trainingfile($data->content,$data->purpose);
                if($ret && isset($ret->id)) {
                    $data->openaiid = $ret->id;
                    $data->timemodified=time();
                    $data->timecreated=time();
                    $success = $DB->insert_record(constants::M_TABLE_FILES, $data);
                }else{
                    $payload = $ret;
                }
                if(!$success){
                    $payload = "it seemed to FAIL:"  . $ret->error->message;
                    redirect($returnurl,$payload,3,\core\output\notification::NOTIFY_WARNING);
                }

            } else {
                $update = new \stdClass();
                $update->timemodified=time();
                $update->description = $data->description;
                $update->name = $data->name;
                $result = $DB->update_record(constants::M_TABLE_FILES, $update);
            }
            break;



    }


    // Redirect to where we were before.
    redirect($returnurl);

}

switch($type){


    case 'finetune':
        if ($id) {
            $usedata = $DB->get_record(constants::M_TABLE_FINETUNES, array('id' => $id));
            $editform->set_data($usedata);
        }
        break;
    case 'trainingfile':
        if ($id) {
            $usedata = $DB->get_record(constants::M_TABLE_FILES, array('id' => $id));
            $editform->set_data($usedata);
        }
        break;

    default:

}

$strheading = 'Training-Files and Fine-Tunes';
$PAGE->set_title($strheading);
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add($strheading);


echo $renderer->header();
echo $renderer->heading($strheading);
$editform->display();
echo $renderer->footer();

