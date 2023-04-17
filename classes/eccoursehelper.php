<?php

namespace block_openai;

class eccoursehelper
{


function process_all_api($units,$formdata, $moodlecourseid)
{
    global $DB, $CFG;

    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->libdir . '/filelib.php');
    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->libdir . '/completionlib.php');
    require_once($CFG->libdir . '/plagiarismlib.php');
    require_once($CFG->dirroot . '/course/modlib.php');

    $moodlecourse = $DB->get_record('course', array('id' => $moodlecourseid));

    //purge from $units, what is not in formdata
    foreach($units as $ukey=>$unit){
        if(!in_array($unit->unitid,$formdata->unitid)){
            unset($units[$ukey]);
            continue;
        }
        foreach($unit->videos as $vkey=>$video){
            //before we remove videos extract the discussion question which might be associated with unselected vid
            foreach($video->dquestions as $dkey=>$dquestion){
                if(in_array($dquestion->dquestionid,$formdata->dquestionid)){
                    $unit->solospeakingtopic =$dquestion->questiontext;
                    break;
                }
            }
            if(!in_array($video->videoid,$formdata->videoid)){
                unset($unit->videos[$vkey]);
                continue;
            }

        }
        //add here the form data model answer and keywords
        $unitindex = $formdata->unitindex[$unit->unitid];
        $unit->solomodelanswer = $formdata->solomodelanswer[$unitindex];
        $unit->solokeywords = $formdata->solokeywords[$unitindex];
    }

    ////now all the data is nicely in the units array, and we can create units in the course
    //  var_dump($units);
    // print_r($units);
    //  die;

    foreach ($units as $unit) {
        $this->create_moodle_unit($unit, $moodlecourse);
    }
}



function create_moodle_unit($unit, $course)
{

    $coursesection = course_create_section($course, 0);
    course_update_section($course, $coursesection, array('name' => $unit->name));


    //EnglishCentral
    $currentvideo=0;
    foreach ($unit->videos as $video) {
        $currentvideo++;

        //the first two videos are EC, unless there are only two videos, in which case the first is EC
        if ($currentvideo <= 2 && $currentvideo < count($unit->videos)) {

            // The EC Video
            $formdata = ['name' => $video->topic, 'modulename' => 'englishcentral', 'course' => $course->id, 'add' => 'englishcentral', 'sr' => 0];
            $activitydata = $formdata;
            $activitydata['videoid'] = $video->videoid;
            $this->create_moodle_item($activitydata, $formdata, $course, $coursesection);
        }else {

            //The MiniLesson
            $formdata = ['name' => 'MiniLesson: ' . $video->topic, 'modulename' => 'minilesson', 'course' => $course->id, 'add' => 'minilesson', 'sr' => 0];
            $activitydata = $formdata;
            $extradata = $video;
            $this->create_moodle_item($activitydata, $formdata, $course, $coursesection, $extradata);
        }
    }

    //Solo
    $formdata = ['name' => 'Speaking Time ', 'modulename' => 'solo', 'course' => $course->id, 'add' => 'solo', 'sr' => 0];
    $activitydata = $formdata;
    $extradata = [$unit->solospeakingtopic, $unit->solomodelanswer,$unit->solokeywords];
    $this->create_moodle_item($activitydata, $formdata, $course, $coursesection, $extradata);

    //Page
    $formdata = ['name' => 'Notes: ' . $unit->name, 'modulename' => 'page', 'course' => $course->id, 'add' => 'page', 'sr' => 0];
    $activitydata = $formdata;
    $extradata = $unit->videos;
    $this->create_moodle_item($activitydata, $formdata, $course, $coursesection, $extradata);

    return;
/*
    //Minilesson
    $formdata = ['name' => 'MiniLesson', 'modulename' => 'minilesson', 'course' => $course->id, 'add' => 'minilesson', 'sr' => 0];
    $activitydata = $formdata;
    //$activitydata['cquestions']=$unit->cquestions;
    create_moodle_item($activitydata, $formdata, $course, $coursesection);

    return;

    //Wordcards
    $formdata = ['name' => 'Wordcards', 'modulename' => 'wordcards', 'course' => $course->id, 'add' => 'wordcards', 'sr' => 0];
    $activitydata = $formdata;
    $activitydata['allwords'] = $unit->unitwords;
    create_moodle_item($activitydata, $formdata, $course, $coursesection);

    //Solo
    $formdata = ['name' => 'Solo', 'modulename' => 'solo', 'course' => $course->id, 'add' => 'solo', 'sr' => 0];
    $activitydata = $formdata;
    //$activitydata['dquestions']=$unit->dquestions;
    create_moodle_item($activitydata, $formdata, $course, $coursesection);

*/
}

function create_moodle_item($activitydata, $formdata, $course, $section, $extradata = false)
{

    $this->notifyUser('creating ' . $activitydata['modulename'] . ' with title: ' . $activitydata['name']);
    //create the bare bones item
    $cmid =  $this->create_base_activity($activitydata, $formdata, $course, $section, $extradata);

    //here we do any post activity creation setup (eg add words to wordcards, or videos to EC)
    switch ($activitydata['modulename']) {
        case 'readaloud':
            $this->extend_base_activity_Readaloud($activitydata, $cmid);
            break;
        case 'minilesson':
            $this->extend_base_activity_Minilesson($activitydata, $cmid, $extradata, $section);
            break;
        case 'wordcards':
            $this->extend_base_activity_Wordcards($activitydata, $cmid);
            break;
        case 'solo':
            $this->extend_base_activity_Solo($activitydata, $cmid);
            break;
        case 'pchat':
            $this->extend_base_activity_Pchat($activitydata, $cmid);
            break;
        case 'englishcentral':
            $this->extend_base_activity_EnglishCentral($activitydata, $cmid);
            break;
        case 'assign':
            $this->extend_base_activity_Assign($activitydata, $cmid);
            break;
        case 'page':
            $this->extend_base_activity_Page($activitydata, $cmid);
            break;
    }
}


function create_base_activity($activitydata, $formdata, $course, $section, $extradata)
{
    //build a form. Poodll short form constructors (poodllshortforms) just take a name attribute and thats it
    //they have a tab for the real conten
    $modulename = $activitydata['modulename'];
    $poodllshortforms = ['readaloud', 'minilesson', 'wordcards', 'solo', 'pchat', 'englishcentral', 'assign', 'page'];
    if (in_array($modulename, $poodllshortforms)) {
        $mform = new \enrol_poodllprovider\shortmodform(null, null, 'post', '', null, true, $formdata);
        $mform->set_data($formdata);
        $fromform = (object)$formdata;

        //its just convenient to use this, we could put it all in this mod
        $fromform = \enrol_poodllprovider\helper::fetch_extrafields($fromform, $course);

        switch ($modulename) {
            case 'readaloud':
                $fromform = $this->setupReadaloud($activitydata, $fromform);
                break;
            case 'minilesson':
                $fromform = $this->setupMinilesson($activitydata, $fromform);
                break;
            case 'wordcards':
                $fromform = $this->setupWordcards($activitydata, $fromform);
                break;
            case 'solo':
                $fromform = $this->setupSolo($activitydata, $fromform, $extradata);
                break;
            case 'pchat':
                $fromform = $this->setupPchat($activitydata, $fromform);
                break;
            case 'englishcentral':
                $fromform = $this->setupEnglishCentral($activitydata, $fromform);
                break;
            case 'assign':
                $fromform = $this->setupAssign($activitydata, $fromform);
                break;
            case 'page':
                $fromform = $this->setupPage($activitydata, $fromform, $extradata);
                break;
        }
        $fromform->section = $section->section;
        $fromform = add_moduleinfo($fromform, $course);
        return $fromform->coursemodule;

    }
    //should never get here
    return false;
}

function setupReadaloud($activitydata, $fromform)
{
    return $fromform;
}

function setupEnglishCentral($activitydata, $fromform)
{
    //we will only do one video per ec activity
    //$videocount = count(explode('|', $activity[$pmap['videoids']]));
    $videocount = 1;
    $fromform->watchgoal = $videocount;
    $fromform->learngoal = $videocount * 5;
    $fromform->speakgoal = $videocount * 5;
    $fromform->studygoal = $videocount * 10;
    $fromform->completiongoals = 1;
    $fromform->completion= COMPLETION_TRACKING_AUTOMATIC;
    $fromform->completionusegrade =null;
    return $fromform;
}

function setupWordcards($activitydata, $fromform)
{

    return $fromform;
}

function setupMinilesson($activitydata, $fromform)
{
    $fromform->transcriber = \mod_minilesson\constants::TRANSCRIBER_POODLL;
    $fromform->completiongoals = 1;
    $fromform->completion= COMPLETION_TRACKING_AUTOMATIC;
    $fromform->completionusegrade =1;
    return $fromform;
}

function setupSolo($activitydata, $fromform, $extradata)
{
    $englishvoices = ["Matthew", "Joey", "Joanna", "Olivia"];
    list($solospeakingtopic, $solomodelanswer,$solokeywords) = $extradata;
    $fromform->convlength = 1;
    $fromform->activitysteps = \mod_solo\constants::M_SEQ_PRM;
    $fromform->relevancegrade = \mod_solo\constants::RELEVANCE_QUITE;
    $fromform->suggestionsgrade = \mod_solo\constants::SUGGEST_GRADE_USE;
    $fromform->speakingtopic = $solospeakingtopic;
    $fromform->modeltts = $solomodelanswer;
    $fromform->targetwords = $solokeywords;
    $fromform->modelttsvoice = $englishvoices[array_rand($englishvoices)];
    $fromform->topicttsvoice = $englishvoices[array_rand($englishvoices)];
    $fromform->completiongoals = 1;
    $fromform->completion= COMPLETION_TRACKING_AUTOMATIC;
    $fromform->completionusegrade =1;

    return $fromform;
}

function setupPchat($activitydata, $fromform)
{
    return $fromform;
}

function setupAssign($activitydata, $fromform)
{
    return $fromform;
}

function setupPage($activitydata, $fromform, $unitvideos)
{
    //$fromform->page['text']=json_encode($unitvideos,JSON_PRETTY_PRINT);
    foreach ($unitvideos as $unitvideo) {
        $unitvideo->transcript = nl2br($unitvideo->transcript);
        $unitvideo->transcript = str_replace('\n', ' ', $unitvideo->transcript);
    }
    $fromform->content = json_encode($unitvideos, JSON_PRETTY_PRINT);
    $fromform->display = false;
    $fromform->printintro = false;
    $fromform->printheading = '';
    $fromform->printlastmodified = false;
    $fromform->completion= false;
    return $fromform;
}

function extend_base_activity_Readaloud($activitydata, $cmid)
{
    return true;
}

function extend_base_activity_EnglishCentral($activitydata, $cmid)
{
    global $DB, $CFG;

    list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'englishcentral');
    $ecid = $cm->instance;
    $ret = \mod_englishcentral\utils::add_video($ecid, $activitydata['videoid']);

}

function extend_base_activity_Wordcards($activity, $cmid)
{
    global $DB, $CFG;
    list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'wordcards');
    $modid = $cm->instance;
    $translations = '';
    for ($number = 1; $number <= 10; $number++) {
        if (!empty($activity[$pmap['term' . $number]])) {
            $ret = \mod_wordcards\utils::save_newterm($modid, $activity[$pmap['term' . $number]],
                $activity[$pmap['def' . $number]],
                $translations,
                $activity[$pmap['def' . $number]],
                $activity[$pmap['model' . $number]],);
        }
    }
}

function extend_base_activity_WordcardTerm($activity, $cmid)
{
    global $DB, $CFG;
    list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'wordcards');
    $modid = $cm->instance;
    $translations = '';
    for ($number = 1; $number <= 10; $number++) {
        if (!empty($activity[$pmap['term' . $number]])) {
            $ret = \mod_wordcards\utils::save_newterm($modid, $activity[$pmap['term' . $number]],
                $activity[$pmap['def' . $number]],
                $translations,
                $activity[$pmap['def' . $number]],
                $activity[$pmap['model' . $number]],);
        }
    }
}

function extend_base_activity_Minilesson($activity, $cmid, $video, $section)
{
    global $DB, $CFG;
    list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'minilesson');
    $englishvoices = ["Matthew", "Joey", "Joanna", "Olivia"];
    $modid = $cm->instance;
    $moduleinstance = $DB->get_record(\mod_minilesson\constants::M_TABLE, array('id' => $modid));
    $cquestions = $video->cquestions;


    $itemrecord = new \stdClass();
    $itemrecord->id = $modid;
    $itemrecord->name = "question";
    $itemrecord->itemid = 0;
    $itemrecord->type = 'multichoice';//multiaudio
    $itemrecord->itemorder = 0;
    $itemrecord->iteminstructions = "Choose the correct answer.";
    $itemrecord->itemtext = ''; // the question
    $itemrecord->layout = 0;
    $itemrecord->addmedia = 1;
    $itemrecord->addiframe = 0;
    $itemrecord->addttsaudio = 1;
    $itemrecord->addtextarea = 0;
    $itemrecord->addyoutubeclip = 0;
    $itemrecord->addttsdialog = 0;
    $itemrecord->itemttsdialogvisible = 1;
    $itemrecord->addttspassage = 0;
    $itemrecord->itemttsvoice = $englishvoices[array_rand($englishvoices)];
    $itemrecord->itemttsautoplay = 0;
    $itemrecord->itemtextarea_editor = array("text" => "", "format" => 1);
    $itemrecord->itemttsdialogvoicea = $englishvoices[array_rand($englishvoices)];
    $itemrecord->itemttsdialogvoiceb = $englishvoices[array_rand($englishvoices)];
    $itemrecord->itemttsdialogvoicec = $englishvoices[array_rand($englishvoices)];
    $itemrecord->itemttspassagevoice = $englishvoices[array_rand($englishvoices)];
    $itemrecord->visible = 1;
    $itemrecord->customint1 = 0;
    $itemrecord->customint2 = 0;
    $itemrecord->customint3 = 0;
    $itemrecord->customint4 = 0;
    $itemrecord->customtext1 = '';
    $itemrecord->customtext2 = '';
    $itemrecord->customtext3 = '';
    $itemrecord->customtext4 = '';
    $itemrecord->customdata1 = "";
    $itemrecord->customdata2 = "";
    $itemrecord->customdata3 = "";
    $itemrecord->customdata4 = "";
    $itemrecord->correctanswer = 1;
    $itemrecord->submitbutton = "Save item";

    //first add the video as iframe
    $theitemrecord = clone $itemrecord;
    $theitemrecord->addiframe = 1;
    $theitemrecord->{\mod_minilesson\constants::MEDIAIFRAME} = "<iframe src='$video->videourl'></iframe>";
    $theitemrecord->{\mod_minilesson\constants::MEDIAIFRAME} = '<div class="embed-responsive embed-responsive-16by9">
        <video controls  class="embed-responsive-item" poster="' . $video->demopic . '"> 
        <source src="' . $video->videourl . '" type="video/mp4"> </div>';

    $theitemrecord->type = 'page';
    $theitemrecord->name = $video->topic;
    $theitemrecord->iteminstructions = "Watch the video carefully. Then answer the questions that follow.";
    $theitem = \mod_minilesson\utils::fetch_item_from_itemrecord($theitemrecord, $moduleinstance);
    $olditem = false;
    $theitem->deaccent();
    $theitem->update_create_langmodel($olditem);
    $theitem->update_create_phonetic($olditem);
    $theitem->update_insert_item();

    $unittype = $section->section % 4;

    for ($qindex = 0; $qindex < count($cquestions); $qindex++) {
        $theitemrecord = clone $itemrecord;
        $q = $cquestions[$qindex];

        switch ($unittype) {
            case 1:
            case 3:
                //add the picture as iframe
                /*
                $theitemrecord->addiframe=1;
                $theitemrecord->{\mod_minilesson\constants::MEDIAIFRAME}=
                    '<div class="embed-responsive embed-responsive-16by9">
                    <img class="embed-responsive-item" src="'.$video->demopic.'"></div>';
                */
                $theitemrecord->type = 'multichoice';
                $theitemrecord->name = (strlen($q->qtext) > 35) ? substr($q->qtext, 0, 32) . '...' : $q->qtext;
                $theitemrecord->itemtext = $q->qtext;
                $theitemrecord->itemtts = $q->qtext;
                $theitemrecord->itemttsvoice = $englishvoices[array_rand($englishvoices)];
                $theitemrecord->itemttsautoplay = 1;
                $theitemrecord->correctanswer = $q->correct ? $q->correct : 1;
                $theitemrecord->{\mod_minilesson\constants::CONFIRMCHOICE} = 1;
                $theitemrecord->{\mod_minilesson\constants::POLLYVOICE} = $englishvoices[array_rand($englishvoices)];
                //its always listening, but can they read as well. In unit one, yes
                if ($unittype == 1) {
                    $theitemrecord->{\mod_minilesson\constants::LISTENORREAD} = \mod_minilesson\constants::LISTENORREAD_LISTENANDREAD;
                } else {
                    $theitemrecord->{\mod_minilesson\constants::LISTENORREAD} = \mod_minilesson\constants::LISTENORREAD_LISTEN;
                }
                for ($a = 0; $a < count($q->answers); $a++) {
                    $theitemrecord->{'customtext' . ($a + 1)} = ucfirst($q->answers[$a]);
                }

                break;


            case 2:
            case 4:
            default:
                $theitemrecord->type = 'multiaudio';
                $theitemrecord->name = (strlen($q->qtext) > 35) ? substr($q->qtext, 0, 32) . '...' : $q->qtext;
                $theitemrecord->itemtext = $q->qtext;
                $theitemrecord->itemtts = $q->qtext;
                $theitemrecord->itemttsvoice = $englishvoices[array_rand($englishvoices)];
                $theitemrecord->itemttsautoplay = 1;
                $theitemrecord->{\mod_minilesson\constants::POLLYVOICE} = $englishvoices[array_rand($englishvoices)];
                //show dots or text
                if ($unittype == 2) {
                    $theitemrecord->{\mod_minilesson\constants::SHOWTEXTPROMPT} = \mod_minilesson\constants::TEXTPROMPT_WORDS;
                } else {
                    $theitemrecord->{\mod_minilesson\constants::SHOWTEXTPROMPT} = \mod_minilesson\constants::TEXTPROMPT_DOTS;
                }
                for ($a = 0; $a < count($q->answers); $a++) {
                    $theitemrecord->{'customtext' . ($a + 1)} = ucfirst($q->answers[$a]);
                }
                break;
        }

        $theitem = \mod_minilesson\utils::fetch_item_from_itemrecord($theitemrecord, $moduleinstance);
        $olditem = false;

        //remove bad accents and things that mess up transcription (kind of like clear but permanent)
        $theitem->deaccent();

        //get passage hash
        $theitem->update_create_langmodel($olditem);

        //lets update the phonetics
        $theitem->update_create_phonetic($olditem);

        $result = $theitem->update_insert_item();
    }

}

function extend_base_activity_Solo($activity, $cmid)
{

}

function extend_base_activity_Pchat($activity, $cmid)
{

}

function extend_base_activity_Assign($activity, $cmid)
{

}

function extend_base_activity_Page($activity, $cmid)
{

}


function create_new_unit($ccunit)
{
    $currentunit = new \stdClass();
    $currentunit->videos = [];
    $currentunit->unitwords = [];
    $currentunit->name = $ccunit->name;
    $currentunit->unitid = $ccunit->unitID;
    if (isset ($ccunit->keyTopics)) {
        $currentunit->topic = $ccunit->keyTopics;
    }
    return $currentunit;
}



function create_unitword($unitword)
{
    $w = new \stdClass();
    $w->term = $unitword['qterm'];
    $w->def = $unitword['qdef'];
    return $w;
}


function notifyUser($message)
{
    echo $message . '<br>';
}


    /**
     * creates an empty Moodle course
     *
     */
 function create_empty_moodle_course($fullname, $shortname, $idnumber, $category) {
        global $CFG, $DB;

            require_once("$CFG->dirroot/course/lib.php");
            $ret=['success'=>false,'message'=>'','id'=>0];

            $courseconfig = get_config('moodlecourse');
            $template = new \stdClass();
            $template->summary        = '';
            $template->summaryformat  = FORMAT_HTML;
            $template->format         = $courseconfig->format;
            $template->numsections    = 0;//$courseconfig->numsections;
            $template->newsitems      = 0;//$courseconfig->newsitems;
            $template->showgrades     = $courseconfig->showgrades;
            $template->showreports    = $courseconfig->showreports;
            $template->maxbytes       = $courseconfig->maxbytes;
            $template->groupmode      = $courseconfig->groupmode;
            $template->groupmodeforce = $courseconfig->groupmodeforce;
            $template->visible        = $courseconfig->visible;
            $template->lang           = $courseconfig->lang;
            $template->enablecompletion = $courseconfig->enablecompletion;
            $template->groupmodeforce = $courseconfig->groupmodeforce;
            $template->startdate      = usergetmidnight(time());
            if ($courseconfig->courseenddateenabled) {
                $template->enddate    = usergetmidnight(time()) + $courseconfig->courseduration;
            }

            $newcourse = clone($template);
            $newcourse->fullname  = $fullname;
            $newcourse->shortname = $shortname;
            $newcourse->idnumber  = $idnumber;
            $newcourse->category  = $category;

            // Detect duplicate data once again, above we can not find duplicates
            // in external data using DB collation rules...
            if ($DB->record_exists('course', array('shortname' => $newcourse->shortname))) {
                $ret['message']="can not insert new course, duplicate shortname detected: ".$newcourse->shortname;
                return $ret;

            } else if (!empty($newcourse->idnumber) and $DB->record_exists('course', array('idnumber' => $newcourse->idnumber))) {
                $ret['message']="can not insert new course, duplicate idnumber detected: ".$newcourse->idnumber;
                return $ret;
            }
            $c = create_course($newcourse);
            $ret['message']="created course: $c->id, $c->fullname, $c->shortname, $c->idnumber, $c->category";
            $ret['success']=true;
            $ret["id"]=$c->id;
            return $ret;

    }


/*
 * This will produce an array of units, each with (i)an array of videos (each containing cquestion and dquestion arrays)
 * and (ii) an array of unitwords
 *
 *
 */
function parse_into_units_from_api($ec_courseid)
{
    $ec = new \stdClass();
    $ec->config = get_config('mod_englishcentral');

    $auth = new \mod_englishcentral\auth($ec);
    //$content = $auth->fetch_dialog_content(15495);
    $cc = $auth->fetch_course_content($ec_courseid);

    $parsed_course = ['name'=>$cc->name,'id'=>$ec_courseid,'units'=>[]];

    foreach ($cc->courseUnits as $ccunit) {
        $currentunit = $this->create_new_unit($ccunit);

        foreach ($ccunit->activities as $ccact) {
            //is this a video for us
            //[activityTypeID] => 9 ??
            if (isset($ccact->dialogID) && $ccact->activityTypeID==11) {
                $dialog = $auth->fetch_dialog_content($ccact->dialogID);

                $vid =  $this->create_new_video_from_api($dialog);
                if (isset($dialog->activityTests)) {
                    foreach ($dialog->activityTests as $activityTest) {
                        switch ($activityTest->activityTypeID) {
                            case 24: //comprehension
                                foreach ($activityTest->questions as $question) {
                                    $vid->cquestions[] =  $this->create_cquestion_from_api($question);
                                }
                                break;
                            case 55: //discussion
                                foreach ($activityTest->questions as $question) {
                                    $vid->dquestions[] =  $this->create_dquestion_from_api($question);
                                }
                        }
                    }
                    $currentunit->videos[] = $vid;
                } else {
                    echo "no activity tests: " . $dialog->dialogID . PHP_EOL;
                   // print_r($dialog);
                }
            }
        }
        if (count($currentunit->videos) > 0) {
            $parsed_course['units'][]= $currentunit;
        }
    }

    return $parsed_course;
}

function create_new_video_from_api($dialog)
{
    $currentvideo = new \stdClass();
    $currentvideo->topic = $dialog->title;
    $currentvideo->videoid = $dialog->dialogID;
    $currentvideo->videourl = $dialog->mediumVideoURL;
    $currentvideo->detailsurl = $dialog->videoDetailsURL;
    $currentvideo->demopic = $dialog->demoPictureURL;
    $currentvideo->transcript = "";
    $currentvideo->cquestions = [];
    $currentvideo->dquestions = [];
    return $currentvideo;
}

function create_cquestion_from_api($cquestion)
{
    $q = new \stdClass();
    $q->qtype = 0;
    $q->qtext = $cquestion->questionText;
    $q->answers = [];
    $q->correct = 1;
    foreach ($cquestion->answers as $answer) {
        $q->answers[] = $answer->answerText;
        if ($answer->correct == 1) {
            $q->correct = $answer->sequence;
        }
    }
    return $q;
}

function create_dquestion_from_api($dquestion)
{
    $d = new \stdClass();
    $d->questiontext =$dquestion->questionText;
    $d->dquestionid=$dquestion->activityDataQuestionID;
    return $d;
}


}