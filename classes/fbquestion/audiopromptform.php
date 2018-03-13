<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 19:32
 */

namespace mod_fluencybuilder\fbquestion;


class audiopromptform extends baseform
{

    public $type = 'audiochoice';
    public $typestring = 'audiochoice';

    public function custom_definition() {

        $this->add_audio_prompt_upload(get_string('addaudiopromptfile','fluencybuilder'));

        $this->add_audio_model_upload(get_string('addaudiomodelfile','fluencybuilder'));
    }

}