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

namespace block_openai;

use block_openai\constants;

defined('MOODLE_INTERNAL') || die();


/**
 *
 * This is a class containing constants and static functions for general use around the plugin
 *
 * @package   block_newtemplate
 * @since      Moodle 3.4
 * @copyright  2018 Justin Hunt (https://poodll,com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
class common
{


    //this merges the local config and admin config settings to make it easy to assume there is a setting
    //and to get it.
    public static function fetch_best_config($blockid=0){
	    global $DB;

        $config = get_config(constants::M_COMP);
        $local_config = false;
        if($blockid > 0) {
            $configdata = $DB->get_field('block_instances', 'configdata', array('id' => $blockid));
            if($configdata){
                $local_config = unserialize(base64_decode($configdata));
            }

            if($local_config){
                $localvars = get_object_vars($local_config);
                foreach($localvars as $prop=>$value){
                    $config->{$prop}=$value;
                }
            }
        }
        return $config;
    }


    public static function fetch_settings_url($setting, $courseid=1){
        global $CFG;

        //type specific settings
        switch($setting) {



            case constants::SETTING_FINETUNES:

                return new \moodle_url(constants::M_URL . '/finetune.php',
                    array('courseid'=>$courseid));

            case constants::SETTING_INFERENCE:

                return new \moodle_url(constants::M_URL . '/inference.php',
                    array('courseid'=>$courseid));

            case constants::SETTING_NONE:
            default:
        }
    }

    public static function fetch_settings_title($setting){
        //type specific settings
        switch($setting) {

            case constants::SETTING_FINETUNES:
                return 'Fine Tunes';

            case constants::SETTING_INFERENCE:
                return 'Inference';

            case constants::SETTING_NONE:
            default:
        }
    }


    public static function fetch_finetunes(){
        global $DB;
        $openai_finetunes = openai::list_finetunes();
        $finetunes = $DB->get_records(constants::M_TABLE_FINETUNES,array());
        //TO DO merge and return
        return $finetunes;

    }

    public static function fetch_trainingfiles(){
        global $DB;
        $trainingfiles = $DB->get_records(constants::M_TABLE_FILES,array());
        return $trainingfiles;
    }

    public static function fetch_inferences(){
        global $DB;
        $inferences= $DB->get_records(constants::M_TABLE_INFERENCES,array());
        return $inferences;
    }

    public static function fetch_purposes_list(){
        return array('search'=>'search','answers'=>'answers','classifications'=>'classifications','fine-tune'=>'fine-tune');
    }

    public static function fetch_models_list(){
        return array('ada'=>'ada','babbage'=>'babbage','curie'=>'curie','davinci'=>'davinci');
    }

    public static function fetch_trainingfiles_list(){
        global $DB;
        $files = $DB->get_records(constants::M_TABLE_FILES,array());
        $ret = [];
        foreach($files as $file){
            $ret[$file->id]=$file->name . " ($file->openaiid)";
        }
        return $ret;
    }

    public static function fetch_finetunes_list($statusready=false){
        global $DB;
        $params = array();
        if($statusready){
            $params['status']=1;
        }
        $finetunes = $DB->get_records(constants::M_TABLE_FINETUNES,$params);
        $ret = [];
        foreach($finetunes as $finetune){
            if($finetune->status==0){
                $ret[$finetune->id]=$finetune->name . " ($finetune->openaiid)";
            }else{
                $ret[$finetune->id]=$finetune->name . " ($finetune->ftmodel)";
            }
        }
        return $ret;
    }



    //register an adhoc task to generate DB
    public static function register_createtenants_tasks($tenantcount) {
        for($t=0;$t<$tenantcount;$t++) {
            $task = new \block_openai\task\openai_createtenants();
            $task->set_component(constants::M_COMP);

            $customdata = new \stdClass();
            $customdata->tenantcount = 1;
            $customdata->tenantjobno = $t+1 . "/" . $tenantcount;

            $task->set_custom_data($customdata);
            // queue it
            \core\task\manager::queue_adhoc_task($task);
        }
        return true;
    }

    //see if this is truly json or some error
    public static function is_json($string) {
        if (!$string) {
            return false;
        }
        if (empty($string)) {
            return false;
        }
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }



}//end of class
