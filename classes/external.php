<?php

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use block_openai\constants;
use block_openai\common;
use block_openai\openai;

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
        $systemrole = new \stdClass();
        $systemrole->role="system";
        $systemrole->content=$taskparam1;

        $userrole =new \stdClass();
        $userrole->role="user";
        $userrole->content = "Answer the following question in 5 to 7 sentences of easy English: " . trim($taskparam2);
        $messages=[];
        $messages[]=$systemrole;
        $messages[]=$userrole;
        $chatresponse = openai::chatrequest($messages);
        return $chatresponse;
    }

    public static function fetch_completion_returns() {
        return new external_value(PARAM_TEXT);
    }


}