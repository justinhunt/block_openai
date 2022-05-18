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

        $data = array();
        foreach($finetunes as $finetune) {
            $fields = array();
            $fields[] = $finetune->id;
            $fields[] =  $finetune->name;
            $fields[] =  $finetune->openaiid;
            $fields[] = $trainingfiles[$finetune->file];
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
            'Training File',
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

    //Fetch assigned tenants table
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
            $fields[] = strftime('%d %b %Y', $trainingfile->timecreated);

            $buttons = array();
            $urlparams = array('id' => $trainingfile->id,'type'=>'finetune','returnurl' => $baseurl->out_as_local_url());
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


}