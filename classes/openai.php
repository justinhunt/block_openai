<?php

namespace local_cpapi;

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//                                                                       //
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Open AI Block
 *
 * @package    block_openai
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt  http://poodll.com
 */
namespace block_openai;
use block_openai\constants;

/**
 *
 *
 * Open AI
 *
 * @abstract
 * @copyright  2021 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class openai {

    const OPENAIENGINES = "https://api.openai.com/v1/engines";
    const OPENAICHAT = "https://api.openai.com/v1/chat/completions";
    const OPENAISYS = "https://api.openai.com/v1";

    public static function request_grammar_correction($originaltext){
        $engine = "text-curie-001";
        $prompt = "Correct this to standard English:" . PHP_EOL;


        $postdata = [
            "prompt" => $prompt . $originaltext,
            "max_tokens" => 800,
            "temperature" => 0,
            "top_p" => 1,
            "presence_penalty" => 0,
            "frequency_penalty"=> 0,
            "best_of"=> 1,
            "stream" => false,
        ];

        // Send the request & save response to $resp
        $requrl =  self::OPENAIENGINES . "/" . $engine . "/completions";

        $response = self::curl_fetch($requrl,$postdata, 'post');
        return $response;

    }

    public static  function create_trainingfile($text,$purpose){
        global $CFG;

        $postdata = [
            "purpose" => $purpose
        ];


        //determine the temp directory
        $tempfile = $CFG->tempdir . "/" . random_string(5);
        $ret = file_put_contents($tempfile, $text);
        $postdata['file'] = curl_file_create($tempfile);


        // Send the request & save response to $resp
        $requrl =  self::OPENAISYS .  "/files";

        $response = self::native_curl_upload($requrl,$postdata);
        return $response;

    }

    public static  function delete_trainingfile($trainingfile){
        $requrl =  self::OPENAISYS .  "/file/" . $trainingfile;
        $response = self::curl_delete($requrl);
        return $response;
    }

    public static  function delete_finetune($model){
        $requrl =  self::OPENAISYS .  "/models/" . $model;
        $response = self::curl_delete($requrl);
        return $response;
    }

    public static  function list_finetunes(){
        $requrl =  self::OPENAISYS .  "/fine-tunes";
        $response = self::curl_fetch($requrl,null, 'get');
        return $response;
    }

    public static  function list_finetunes_details($finetuneid){
        $requrl =  self::OPENAISYS .  "/fine-tunes/" . $finetuneid;
        $response = self::curl_fetch($requrl,null, 'get');
        return $response;
    }

    public static  function create_finetune($trainingfile,$model){
        $requrl =  self::OPENAISYS .  "/fine_tuning/jobs";

        $postdata = [
            "training_file" => $trainingfile,
            "model"=>$model
        ];
        $response = self::curl_fetch($requrl,$postdata, 'post');
        return $response;
    }

    public static function custom_request($options){

        $default = [
            "max_tokens" => 16,
            "temperature" => 1,
            "top_p" => 1,
            "presence_penalty" => 0.75,
            "frequency_penalty"=> 0.75,
            "best_of"=> 1,
            "stream" => false,
        ];
        $postdata = array_merge($default,$options);

        // Send the request & save response to $resp
        $requrl =  self::OPENAISYS . "/completions";

        $response = self::curl_fetch($requrl,$postdata, 'post');
        return $response;

    }

    public static function request($engine, $prompt, $max_tokens){

        $postdata = [
            "prompt" => $prompt,
            "max_tokens" => $max_tokens,
            "temperature" => 0.7,
            "top_p" => 1,
            "presence_penalty" => 0.75,
            "frequency_penalty"=> 0.75,
            "best_of"=> 1,
            "stream" => false,
        ];

        // Send the request & save response to $resp
        $requrl =  self::OPENAIENGINES . "/" . $engine . "/completions";

        $response = self::curl_fetch($requrl,$postdata, 'post');
        return $response;

    }

    public static function chatrequest($messages,$model="gpt-4"){

        $postdata = [
            "model" => $model,
            "messages" => $messages,
            /*"max_tokens" => $max_tokens,*/
            "temperature" => 0.7,
            "top_p" => 1,
            "presence_penalty" => 0.75,
            "frequency_penalty"=> 0.75,
            "n"=> 1,
            "stream" => false,
        ];

        // Send the request & save response to $resp
        $requrl =  self::OPENAICHAT;

        $response = self::curl_fetch($requrl,$postdata, 'post');
        return $response;

    }

    public static function search($engine, $documents, $query){

        $postdata = [
            "max_tokens" => 10,
            "temperature" => 0.7,
            "top_p" => 1,
            "presence_penalty" => 0.75,
            "frequency_penalty"=> 0.75,
            "documents" => $documents,
            "query" => $query
        ];

        // Send the request & save response to $resp
        $requrl =  self::OPENAIENGINES . "/" . $engine . "/search";

        $response = self::curl_fetch($requrl,$postdata, 'post');
        return $response;


    }

    //we use curl to fetch details from openai
    public static function native_curl_upload($url,$postdata=false)
    {
        global $CFG;
        $config = get_config(constants::M_COMP);
        if(isset($config->openaiapikey) && $config->openaiapikey) {
            $secret = $config->openaiapikey;
        }else{
            $secret = 'nosecret';
        }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true, // return the transfer as a string of the return value
                CURLOPT_TIMEOUT => 0,   // The maximum number of seconds to allow cURL functions to execute.
                CURLOPT_POST => true,   // This line must place before CURLOPT_POSTFIELDS
                CURLOPT_POSTFIELDS => $postdata // The full data to post
            ));

            $headers = [];
            $headers[]='Authorization: Bearer ' . $secret;

            // Set Header
            if (!empty($headers)) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            }
            $response = curl_exec($curl);
            $errno = curl_errno($curl);
            if ($errno) {
                return false;
            }
            curl_close($curl);

            if(self::is_json($response)){
                $resultobj = json_decode($response);
                return $resultobj;

            }else{
                return $response;
            }

    }

    //we use curl to fetch details from openai
    public static function curl_delete($url)
    {
        global $CFG;
        $config = get_config(constants::M_COMP);
        if(isset($config->openaiapikey) && $config->openaiapikey) {
            $secret = $config->openaiapikey;
        }else{
            $secret = 'nosecret';
        }

        require_once($CFG->libdir.'/filelib.php');
        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $secret);
        $result = $curl->delete($url);

        if(self::is_json($result)){
            $resultobj = json_decode($result);
            return $resultobj;
        }
        return $result;
    }

    //we use curl to fetch details from openai
    public static function curl_fetch($url,$postdata=false, $method='get')
    {
        global $CFG;
        $config = get_config(constants::M_COMP);
        if(isset($config->openaiapikey) && $config->openaiapikey) {
            $secret = $config->openaiapikey;
        }else{
            $secret = 'nosecret';
        }

        require_once($CFG->libdir.'/filelib.php');
        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $secret);
        $curl->setHeader(array('Content-type: application/json'));


        if($method=='post') {
            $result = $curl->post($url, json_encode($postdata));
        }else{
            $result = $curl->get($url, $postdata);
        }
        if(self::is_json($result)){
            $resultobj = json_decode($result);
            if(isset($resultobj->choices) && count($resultobj->choices)>0){
                //chat returns a different format response in a "message" property
                if(isset($resultobj->choices[0]->message)){
                    $text = $resultobj->choices[0]->message->content;
                }else {
                    $text = $resultobj->choices[0]->text;
                }
                return trim($text);
            }else{
                return $resultobj;
            }
        }
        return $result;
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

}