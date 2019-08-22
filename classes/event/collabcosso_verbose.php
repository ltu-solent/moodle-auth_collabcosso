<?php

namespace auth_collabcosso\event;

defined('MOODLE_INTERNAL') || die();

class collabcosso_verbose extends \core\event\base 
{
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }
 
    public static function get_name() {         
        return get_string('sso_verbose', 'auth_collabcosso');
    }
 
    public function get_description() {        
        return $this->other['message']; 
    }
}