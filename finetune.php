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
require('../../config.php');

$courseid = optional_param('courseid', 1,PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

//set the url of the $PAGE
//note we do this before require_login preferably
//so Moodle will send user back here if it bounces them off to login first
$PAGE->set_url(constants::M_URL . '/finetune.php',array('courseid'=>$courseid));
$course = get_course($courseid);
require_login($course);


//datatables css
$PAGE->requires->css(new \moodle_url('https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'));

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('pluginname', constants::M_COMP));
$PAGE->navbar->add(get_string('pluginname', constants::M_COMP));


$ok = has_capability('block/openai:managesite', $context);

$finetunes=common::fetch_finetunes();
$trainingfiles=common::fetch_trainingfiles();

//get our renderer
$renderer = $PAGE->get_renderer(constants::M_COMP);
echo $renderer->header();
echo $renderer->heading($SITE->fullname);


if($ok) {

    //finetunes
    $a_finetunetable = $renderer->fetch_finetunes_table($finetunes,$courseid);
    echo $a_finetunetable;
    //set up datatables
    $a_finetunetableprops = new \stdClass();
    $a_opts = Array();
    $a_opts['tableid'] = constants::M_ID_FINETUNES_HTMLTABLE;
    $a_opts['tableprops'] = $a_finetunetableprops;
    $PAGE->requires->js_call_amd(constants::M_COMP . "/datatables", 'init', array($a_opts));

    //files
    $trainingfilestable = $renderer->fetch_trainingfiles_table($trainingfiles,$courseid);
    echo $trainingfilestable;
    //set up datatables
    $dbv_props = new \stdClass();
    $dbv_opts = Array();
    $dbv_opts['tableid'] = constants::M_ID_TRAININGFILES_HTMLTABLE;
    $dbv_opts['tableprops'] = $dbv_props;
    $PAGE->requires->js_call_amd(constants::M_COMP . "/datatables", 'init', array($dbv_opts));



}else{
    echo  get_string('nopermission', constants::M_COMP);
}

echo $renderer->footer();