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



//set the url of the $PAGE
//note we do this before require_login preferably
//so Moodle will send user back here if it bounces them off to login first
$PAGE->set_url(constants::M_URL . '/chat.php',array());
require_login();


//datatables css
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('pluginname', constants::M_COMP));
$PAGE->navbar->add(get_string('pluginname', constants::M_COMP));


$ok = has_capability('block/openai:managesite', $context);


$chatform = new \block_openai\local\form\chatform();
$chatresponse ="";

//get our renderer
$renderer = $PAGE->get_renderer(constants::M_COMP);
echo $renderer->header();
echo $renderer->heading($SITE->fullname);



if($ok) {

    if ($chatform->is_cancelled() ){
        redirect($CFG->wwwroot . '/blocks/openai/chat.php');
    }else if($data = $chatform->get_data() ) {

        $message_lines = preg_split('/\n/',$data->messages);
        foreach($message_lines as $message) {
            $messages[] = json_decode( $message );
        }
        $chatresponse = openai::chatrequest($messages);

    }


    //display the form
   // $usedata= ['courseid'=>$courseid];
   // $eccourseform->set_data($usedata);


    $chatform->display();

    if(!empty($chatresponse)) {
        echo $renderer->display_chat_output($chatresponse);
    }



}else{
    echo  get_string('nopermission', constants::M_COMP);
}

//echo $renderer->quicklink( constants::SETTING_FINETUNES, $courseid);

echo $renderer->footer();