<?php

use block_openai\constants;

class block_openai_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        //get admin settings config
        $config =get_config(constants::M_COMP);

        // A sample string variable with a default value.
        //we need to prefix all our settings with config_ for the block to do its magic of saving and fetching them
        //for us
        $mform->addElement('text', 'config_sometext', get_string('blockstring', constants::M_COMP));
        $mform->setDefault('config_sometext', $config->sometext);
        $mform->setType('config_sometext', PARAM_TEXT);

    }
}
