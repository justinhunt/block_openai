<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/16
 * Time: 19:31
 */

namespace block_openai;

defined('MOODLE_INTERNAL') || die();

class constants
{
//component name, db tables, things that define app
const M_COMP='block_openai';
const M_NAME='openai';
const M_URL='/blocks/openai';
const M_CLASS='block_openai';
const M_HOOKCOUNT =5;

const SETTING_NONE ='none';
const SETTING_FINETUNES ='finetune';
const SETTING_RUN ='run';


const M_TABLE_FILES ='block_openai_file';
const M_TABLE_FINETUNES = 'block_openai_finetune';
const M_ID_FINETUNES_HTMLTABLE = 'id_finetunes';
const M_ID_TRAININGFILES_HTMLTABLE = 'id_trainingfiles';

}