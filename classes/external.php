<?php

use block_openai\constants;
use block_openai\common;


class block_openai_external extends external_api {

    //------------ DELETE ITEM ---------------//
    public static function update_sub_parameters() {
        return new external_function_parameters(
                array(
                        'schoolname' => new external_value(PARAM_TEXT, 'The course name'),
                        'upstreamuser' => new external_value(PARAM_TEXT, 'The upstreamuser'),
                        'upstreamsub' => new external_value(PARAM_TEXT, 'The upstreamsub'),
                        'upstreamplan' => new external_value(PARAM_TEXT, 'The upstreamplan'),
                        'expiretime' => new external_value(PARAM_INT, 'The expire time'),
                )
        );
    }

    public static function update_sub($schoolname,$upstreamuser, $upstreamsub,$upstreamplan,$expiretime)
    {
        global $CFG, $DB, $USER;

        $errormessage="";

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::update_sub_parameters(),
                ['schoolname' => $schoolname,
                    'upstreamuser' => $upstreamuser,
                    'upstreamsub' => $upstreamsub,
                    'upstreamplan' => $upstreamplan,
                    'expiretime'=>$expiretime
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
    }

    public static function update_sub_returns() {
        return new external_value(PARAM_RAW);
        //return new external_value(PARAM_INT, 'group id');
    }


}