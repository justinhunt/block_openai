<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace block_openai\output;

use \block_openai\constants;
use \block_openai\common;
use \block_openai\openai;


class renderer extends \plugin_renderer_base {


    /**
     * Return HTML to display limited header
     */
    public function header(){
        return $this->output->header();
    }

    //In this function we prepare and display the content that goes in the block
    function fetch_block_content($courseid){
        global $USER;


        //show our intro text
        $content = '';
        $content .= '<br />' . get_string('welcomeuser', constants::M_COMP,$USER) . '<br />';

        $items = [];
//determine which options we show to users

        $items[]= constants::SETTING_FINETUNES;
        $items[]= constants::SETTING_INFERENCE;
        $items[]= constants::SETTING_CHAT;
        $items[]= constants::SETTING_ECCOURSE;

        $settings = [];
        foreach ($items as $item){
            $link =common::fetch_settings_url($item,$courseid);
            $displayname =common::fetch_settings_title($item);
            $setting=['url'=>$link->out(false),'displayname'=>$displayname];
            $settings[]=$setting;
        }

        $data=['settings'=>$settings];
        $content .= $this->render_from_template('block_openai/tilescontainer', $data);

        //show our link to the view page

        //$content .= \html_writer::link($link, get_string('gotoadminpage', constants::M_COMP));
        return $content;
    }

    //In this function we prepare and display the content for the page
    function display_view_page($blockid, $courseid){
        global $USER;

        $content = '';
        $content .= '<br />' . get_string('welcomeuser', constants::M_COMP,$USER) . '<br />';
        $content .= $this->fetch_dosomething_button($blockid,$courseid);
        $content .= $this->fetch_triggeralert_button();

        //a page must have a header
        echo $this->output->header();
        //and of course our page content
        echo $content;
        //a page must have a footer
        echo $this->output->footer();
    }


    function fetch_triggeralert_button(){
        //these are attributes for a simple html button.
        $attributes = array();
        $attributes['type']='button';
        $attributes['id']= \html_writer::random_id(constants::M_COMP . '_');
        $attributes['class']=constants::M_COMP . '_triggerbutton';
        $button = \html_writer::tag('button',get_string('triggeralert', constants::M_COMP),$attributes);

        //we attach an event to it. The event comes from a JS AMD module also in this plugin
        $opts=array('buttonid' => $attributes['id']);
        $this->page->requires->js_call_amd(constants::M_COMP . "/triggeralert", 'init', array($opts));

        //we want to make our language strings available to our JS button too
        //strings for JS
        $this->page->requires->strings_for_js(array(
            'triggeralert_message'
        ),
            constants::M_COMP);

        //finally return our button for display
        return $button;
    }

    //return a button that will allow user to add a new sub
    function fetch_addfinetune_button(){
        $thebutton = new \single_button(
            new \moodle_url(constants::M_URL . '/manage.php',array('type'=>'finetune')),
            "Create FineTune", 'get');
        return $thebutton;
    }


    //return a button that will allow user to add a new sub
    function fetch_addtrainingfile_button(){
        $thebutton = new \single_button(
            new \moodle_url(constants::M_URL . '/manage.php',array('type'=>'trainingfile')),
            "Create Training File", 'get');
        return $thebutton;
    }

    //return a button that will allow user to add a new inference
    function fetch_addinference_button($courseid=0){
        $url =common::fetch_settings_url(constants::SETTING_INFERENCE,$courseid);
        $displayname ="Create Inference";


        $thebutton = new \single_button(
            new \moodle_url($url),
            $displayname, 'get');
        return $thebutton;
    }



    //Fetch assigned tenants table
    function fetch_finetunes_table($finetunes,$courseid){
        global $DB;

        $params=['courseid'=>$courseid];
        $baseurl = new \moodle_url(constants::M_URL . '/finetune.php', $params);
        $trainingfiles = common::fetch_trainingfiles_list();

        //add sub button
        $context = \context_system::instance();
        if(has_capability('block/' . constants::M_NAME. ':managesite', $context)) {
            $abutton = $this->fetch_addfinetune_button();
            $addnewbutton = $this->render($abutton);
        }else{
            $addnewbutton ='';
        }

        $openai_finetunes = false;
        //For debugging use this to find out why a fine tune is failing. Probably you hit the pay wall
/*
        $openai_finetunes = openai::list_finetune_jobs();
        foreach($openai_finetunes->data as $oaf) {
            if($oaf->status=='failed'){
                //set break point here
                $deets = openai::list_finetune_job_details($oaf->id);
            }
        }
*/

        $data = array();
        foreach($finetunes as $finetune) {

            $status = $finetune->status;
            if($status==0) {
                if($openai_finetunes==false){$openai_finetunes = openai::list_finetune_jobs();}
                if ($openai_finetunes && isset($openai_finetunes->data) && count($openai_finetunes->data) > 0) {
                    foreach ($openai_finetunes->data as $ft) {
                        if ($ft->id == $finetune->openaiid) {
                            if(!is_null($ft->fine_tuned_model)){
                                $finetune->ftmodel = $ft->fine_tuned_model;
                                $finetune->status=1;
                                $DB->update_record(constants::M_TABLE_FINETUNES,$finetune);
                            }elseif($ft->status=='failed'){
                                //if its failed just update the visual as failed
                                $finetune->status=2;
                            }
                            $status = $finetune->status;
                            break;
                        }
                    }
                }
            }

            $fields = array();
            $fields[] = $finetune->id;
            $fields[] =  $finetune->name;
            $fields[] =  $finetune->openaiid;
            $fields[] =   $finetune->ftmodel;
            $fields[] = $trainingfiles[$finetune->file];
            switch($status) {
                case 0: $fields[] = 'PENDING'; break;
                case 1: $fields[] = 'Ready'; break;
                case 2: $fields[] = 'FAILED'; break;
            }
            $fields[] = strftime('%d %b %Y', $finetune->timecreated);

            $buttons = array();
            $urlparams = array('id' => $finetune->id,'type'=>'finetune','returnurl' => $baseurl->out_as_local_url());
            $buttons[] = \html_writer::link(new \moodle_url(constants::M_URL . '/manage.php',
                $urlparams + array('delete' => 1)),
                $this->output->pix_icon('t/delete', get_string('delete')),
                array('title' => get_string('delete')));


            $fields[] = implode(' ', $buttons);

            $data[] = $row = new \html_table_row($fields);
        }

        $table = new \html_table();
        $table->head  = array(get_string('id', constants::M_COMP),
            get_string('name', constants::M_COMP),
            'openaiid',
            'FT Model',
            'Training File',
            'Status',
            get_string('created', constants::M_COMP),
            get_string('action'));
        $table->colclasses = array('leftalign name', 'leftalign size','centeralign action');

        $table->id = constants::M_ID_FINETUNES_HTMLTABLE;
        $table->attributes['class'] = 'admintable generaltable';
        $table->data  = $data;

        //return add button and table
        $heading = $this->output->heading('Fine Tunes',3);
        return   $heading  . $addnewbutton .  \html_writer::table($table);

    }

    //Fetch training files table
    function fetch_trainingfiles_table($trainingfiles,$courseid){
        global $DB;

        $params=['courseid'=>$courseid];
        $baseurl = new \moodle_url(constants::M_URL . '/finetune.php', $params);


        //add sub button
        $context = \context_system::instance();
        if(has_capability('block/' . constants::M_NAME. ':managesite', $context)) {
            $abutton = $this->fetch_addtrainingfile_button();
            $addnewbutton = $this->render($abutton);
        }else{
            $addnewbutton ='';
        }

        $data = array();
        foreach($trainingfiles as $trainingfile) {
            $fields = array();
            $fields[] = $trainingfile->id;
            $fields[] =  $trainingfile->name;
            $fields[] = $trainingfile->openaiid;
            $fields[] = $trainingfile->exampleprompt;
            $fields[] = strftime('%d %b %Y', $trainingfile->timecreated);

            $buttons = array();
            $urlparams = array('id' => $trainingfile->id,'type'=>'trainingfile','returnurl' => $baseurl->out_as_local_url());
            $buttons[] = \html_writer::link(new \moodle_url(constants::M_URL . '/manage.php',
                $urlparams + array('delete' => 1)),
                $this->output->pix_icon('t/delete', get_string('delete')),
                array('title' => get_string('delete')));


            $fields[] = implode(' ', $buttons);

            $data[] = $row = new \html_table_row($fields);
        }

        $table = new \html_table();
        $table->head  = array(get_string('id', constants::M_COMP),
            get_string('name', constants::M_COMP),
            "openaiid",
            "Example Prompt",
            get_string('created', constants::M_COMP),
            get_string('action'));
        $table->colclasses = array('leftalign name', 'leftalign size','centeralign action');

        $table->id = constants::M_ID_TRAININGFILES_HTMLTABLE;
        $table->attributes['class'] = 'admintable generaltable';
        $table->data  = $data;

        //return add button and table
        $heading = $this->output->heading('Training Files',3);
        return   $heading  . $addnewbutton .  \html_writer::table($table);

    }


    //Fetch inferences table
    function fetch_inferences_table($inferences,$courseid){
        global $DB;

        $trainingfiles = common::fetch_trainingfiles_list();
        $finetunes = common::fetch_finetunes_list();

        $params=['courseid'=>$courseid];
        $baseurl = new \moodle_url(constants::M_URL . '/finetune.php', $params);


        //add sub button
        $context = \context_system::instance();
        if(has_capability('block/' . constants::M_NAME. ':managesite', $context)) {
            $abutton = $this->fetch_addinference_button($courseid); //TO DO
            $addnewbutton = $this->render($abutton);
        }else{
            $addnewbutton ='';
        }

        $data = array();
        foreach($inferences as $inference) {
            $fields = array();
            $fields[] = $inference->id;
            $fields[] = shorten_text($finetunes[$inference->finetuneid],50);
            $fields[] = shorten_text($trainingfiles[$inference->fileid],50);
            $fields[] =  shorten_text($inference->prompt,80);
            $fields[] = strftime('%d %b %Y', $inference->timemodified);
            $fields[] = strftime('%d %b %Y', $inference->timecreated);

            $buttons = array();
            $urlparams = array('id' => $inference->id,'type'=>'inference','returnurl' => $baseurl->out_as_local_url());
            $buttons[] = \html_writer::link(new \moodle_url(constants::M_URL . '/manage.php',
                $urlparams + array('delete' => 1)),
                $this->output->pix_icon('t/delete', get_string('delete')),
                array('title' => get_string('delete')));

            $buttons[] = \html_writer::link(new \moodle_url(constants::M_URL . '/inference.php',
                $urlparams + array('view' => 1)),
                $this->output->pix_icon('i/preview', get_string('preview')),
                array('title' => get_string('view')));


            $fields[] = implode(' ', $buttons);

            $data[] = $row = new \html_table_row($fields);
        }

        $table = new \html_table();
        $table->head  = array(get_string('id', constants::M_COMP),
            "FineTune",
            "File",
            "Prompt",
            get_string('modified', constants::M_COMP),
            get_string('created', constants::M_COMP),
            get_string('action'));
        $table->colclasses = array('leftalign name', 'leftalign size','centeralign action');

        $table->id = constants::M_ID_INFERENCES_HTMLTABLE;
        $table->attributes['class'] = 'admintable generaltable';
        $table->data  = $data;

        //return add button and table
        $heading = $this->output->heading('Inferences',3);
        return   $heading  . $addnewbutton .  \html_writer::table($table);

    }

    public function display_chat_output($chatresponse){

        $heading = $this->output->heading('Chat Response',3);
        $body = \html_writer::div($chatresponse,'block_openai_chat_response');
        return $heading  . $body;
    }

    public function quicklink($type, $courseid){

        $link =common::fetch_settings_url($type,$courseid);
        $displayname =common::fetch_settings_title($type);

        $setting=['url'=>$link->out(false),'displayname'=>$displayname];
        return $this->render_from_template('block_openai/quicklink', $setting);
    }

}