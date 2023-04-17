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

$eccourseid = optional_param('eccourseid', 0,PARAM_INT);


//set the url of the $PAGE
//note we do this before require_login preferably
//so Moodle will send user back here if it bounces them off to login first
$PAGE->set_url(constants::M_URL . '/eccourse.php',array('ecid'=>$eccourseid));
require_login();


//datatables css
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('pluginname', constants::M_COMP));
$PAGE->navbar->add(get_string('pluginname', constants::M_COMP));


$ok = has_capability('block/openai:managesite', $context);


$eccourseform = new \block_openai\local\form\eccourseform();


//get our renderer
$renderer = $PAGE->get_renderer(constants::M_COMP);
$PAGE->requires->js_call_amd(constants::M_COMP . "/ajaxhelper", 'init', array());


//cache the units array, since its a pain to wait and expensive
$cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, 'block_openai', 'courseunits');

if(!$ok) {
    echo $renderer->header();
    echo $renderer->heading($SITE->fullname);
    echo  get_string('nopermission', constants::M_COMP);
    echo $renderer->footer();
    return;
}

    if ($eccourseform->is_cancelled() ){
        redirect($CFG->wwwroot . '/blocks/openai/eccourse.php');
    }else if($data = $eccourseform->get_data() ) {

        $eccoursehelper=new \block_openai\eccoursehelper();


        $key = $data->eccourseid;
        try {
            $parsed_course = $cache->get($key);
        }catch(\Exception $e){
            $parsed_course =false;
        }
        if(!$parsed_course) {
            $parsed_course = $eccoursehelper->parse_into_units_from_api($key);
            $cache->set($key,$parsed_course);
        }

        $ecunitsform = new \block_openai\local\form\ecunitsform(null,array('units'=>$parsed_course['units']));
        $usedata= ['eccourseid'=>$data->eccourseid];
        $ecunitsform->set_data($usedata);

        echo $renderer->header();
        echo $renderer->heading($SITE->fullname);
        $ecunitsform->display();
        echo $renderer->footer();
        return;

    }else if($eccourseid>0) {
        $eccoursehelper=new \block_openai\eccoursehelper();

        //fetch units
        $key = $eccourseid;
        try {
            $parsed_course = $cache->get($key);
        }catch(\Exception $e){
            $parsed_course =false;
        }
        if(!$parsed_course) {
            $parsed_course = $eccoursehelper->parse_into_units_from_api($key);
            $cache->set($key,$parsed_course);
        }


        $ecunitsform = new \block_openai\local\form\ecunitsform(null,array('units'=>$parsed_course['units']));

        if($ecunitsform->is_cancelled() ){
            redirect($CFG->wwwroot . '/blocks/openai/eccourse.php');

        }elseif($formdata =$ecunitsform->get_data()){
            echo $renderer->header();
            echo $renderer->heading($SITE->fullname);

            //TO DO - extend parse_into_units_from_api to save the course name and deets as well, so we can get that here
            $fullname=$parsed_course['name'];
            $shortname=strtolower(preg_replace('/[^a-zA-Z0-9-_\.]/','_',$parsed_course['name']));
            $idnumber=$eccourseid;
            $category="1";
            $coursedetails = $parsed_course['details'];
            $ret = $eccoursehelper->create_empty_moodle_course($fullname, $shortname, $idnumber, $category, $coursedetails) ;
            if(!$ret['success']) {
                echo $ret['message'];
                echo $renderer->footer();
                return;
            }

            $eccoursehelper->process_all_api($parsed_course['units'],$formdata, $ret['id']);

            echo "<a href='" . $CFG->wwwroot . '/course/view.php?id=' . $ret['id'] . "' class='btn btn-secondary'>Visit New Course: " . $parsed_course['name'] . "</a>";

            echo $renderer->footer();
            return;
           // $data = $ecunitsform->get_data();
           // print_r($data);
        }
        echo $renderer->header();
        echo $renderer->heading($SITE->fullname);
        echo $renderer->footer();
        return;
    }


echo $renderer->header();
echo $renderer->heading($SITE->fullname);

$eccourseform->display();
echo $renderer->footer();



