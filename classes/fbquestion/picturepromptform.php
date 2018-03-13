<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 19:31
 */

namespace mod_fluencybuilder\fbquestion;


class picturepromptform extends baseform
{
    public $type = 'picturechoice';
    public $typestring = 'picturechoice';

    public function custom_definition() {

        $this->add_picture_item_upload(get_string('pictureitemfile','fluencybuilder'));

    }
}