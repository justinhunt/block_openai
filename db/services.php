<?php
/**
 * Services definition.
 *
 * @package mod_readaloud
 * @author  Justin Hunt - poodll.com
 */

$functions = array(
    'block_openai_fetch_completion' => array(
        'classname'   => 'block_openai_external',
        'methodname'  => 'fetch_completion',
        'description' => 'fetch a completion',
        'capabilities'=> 'block/openai:manageservices',
        'type'        => 'write',
        'ajax'        => true,
    )
);