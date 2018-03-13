<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:28
 */

namespace mod_fluencybuilder\output;


class json_renderer extends \plugin_renderer_base {


    /**
     * Return json for sessions (session = array of taskids)
     * Depending on the settings for the fluencybuilder instance
     * we add screens for consent and session selection etc
     *
     * @param string $title
     * @param string $context
     * @param array $items
     * @param stdClass $fluencybuilder
     * @return stdClass
     */
    public function render_result($attemptid,$message) {
        $result = new \stdClass;
        $result->attemptid = $attemptid;
        $result->message = $message;

        return json_encode($result);
    }
}