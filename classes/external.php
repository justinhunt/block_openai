<?php

use block_openai\constants;
use block_openai\common;


class block_openai_external extends external_api {

    //------------ fetch completion---------------//
    public static function fetch_completion_parameters() {
        return new external_function_parameters(
                array(
                        'completiontask' => new external_value(PARAM_TEXT, 'The course name'),
                        'taskparam1' => new external_value(PARAM_TEXT, 'Task Param 1'),
                        'taskparam2' => new external_value(PARAM_TEXT, 'Task Param 2'),
                        'taskparam3' => new external_value(PARAM_TEXT, 'Task Param 3'),
                        'taskparam4' => new external_value(PARAM_TEXT, 'Task Param 4')
                )
        );
    }

    public static function fetch_completion($completiontask,$taskparam1, $taskparam2,$taskparam3,$taskparam4)
    {
        global $CFG, $DB, $USER;

        $errormessage="";

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::fetch_completion_parameters(),
                ['completiontask' => $completiontask,
                    'taskparam1' => $taskparam1,
                    'taskparam2' => $taskparam2,
                    'taskparam3' => $taskparam3,
                    'taskparam4' => $taskparam4
                ]);

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('block/openai:manageservices', $context)) {
            throw new moodle_exception('nopermission');
        }
/*
        //create the course and course category for the sub
        $results=false;
        $plan=common::fetch_plan_by_upstreamid($upstreamplan);
        if($plan) {
            //existingsub ?
            if($sub=common::fetch_sub_by_upstreamsubid($upstreamsub)){
                $results = common::update_client_site($sub,$schoolname,$plan,$expiretime);
                if ($results) {
                    //we return details for consistency sake, though they do not change
                    $obj = json_decode($sub->jsonfields());
                    if(isset($obj->ldetails)){
                        $details = json_encode($obj->details) ;
                        $ret = new \stdClass();
                        $ret->details = $details;
                        $ret->message = '';
                        $ret->error = false;
                        return json_encode($ret);
                    }
                }else{
                    $errormessage="unable to update client site for school:" . $schoolname;
                }//end of if results
            }else {
                $results = common::create_client_site($plan, $schoolname,$expiretime);
                if ($results) {
                    //if we have a category and course then we create an entry in our subs table
                    //this is so we can list, edit etc in response to ui and api requests
                    $subscription = common::create_sub($plan, $schoolname, $upstreamuser, $upstreamsub, json_encode($results), $expiretime);
                    $ret = new \stdClass();
                    $ret->details = $results;
                    $ret->message = '';
                    $ret->error = false;
                    return json_encode($ret);
                }else{
                    $errormessage="unable to create site for school:" . $schoolname;
                }//end if if results
            }//end of existingsub?
        }else{
            $errormessage="The submitted upstream plan id is unknown:" . $upstreamplan;
        }//end of if plan

        //if we get here, our mission failed, and lets report that
        $ret = new \stdClass();
        $ret->details=false;
        $ret->message='Unable to create site:' . $errormessage;
        $ret->error=true;
        return json_encode($ret);
*/
    }

    public static function fetch_completion_returns() {
        return new external_value(PARAM_RAW);
        //return new external_value(PARAM_INT, 'group id');
    }


}