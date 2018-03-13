<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 19:31
 */

namespace mod_fluencybuilder\fbquestion;


class textpromptform extends baseform
{

    public $type = 'textchoice';
    public $typestring = 'textchoice';

    public function custom_definition() {


        $this->add_audio_prompt_upload(get_string('audioitemfile','fluencybuilder'));

    }

}