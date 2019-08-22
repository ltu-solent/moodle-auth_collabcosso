<?php

defined('MOODLE_INTERNAL') || die();

@date_default_timezone_set('UTC');	

require_once($CFG->libdir.'/authlib.php');

class auth_plugin_collabcosso extends auth_plugin_base {
    
    function __construct() {
        $this->authtype = 'collabcosso';
        $this->config = get_config('auth/collabcosco');
    }

    function user_login($username, $password) {
        return false;
    }

    function can_reset_password() {
        return false;
    }

    function can_signup() {
        return false;
    }

    function can_confirm() {
        return false;
    }

    function can_change_password() {
        return false;
    }

    function can_edit_profile() {
        return false;
    }
    
    function is_internal() {
        return false;
    }
    
    function loginpage_hook() 
    {      
        global $CFG, $USER, $SESSION;

        $username =     optional_param('u', null, PARAM_TEXT);
        $time =         optional_param('t', null, PARAM_ALPHANUMEXT);
        $hash =         optional_param('h', null, PARAM_BASE64);
        $redirect =     optional_param('r', null, PARAM_LOCALURL);

        if ($CFG->auth_collabcosso_verbose)
        {   
            if (!is_null($username) || !is_null($time) || !is_null($hash) || !is_null($redirect))
            {
                $ssolog_username =      isset($username) ? $username : 'null';
                $ssolog_time =          isset($time) ? $time : 'null';
                $ssolog_hash =          isset($hash) ? $hash : 'null';
                $ssolog_redirect =      isset($redirect) ? $redirect : 'null';

                \auth_collabcosso\event\collabcosso_verbose::create(array(
                    'context' => context_system::instance(),
                    'other' => array(
                        'message' => sprintf(get_string('sso_parameterlog', 'auth_collabcosso'), $ssolog_username, $ssolog_time, $ssolog_hash, $ssolog_redirect)
                    )
                ))->trigger();
            }
        }
        
        if (is_null($username) || is_null($time) || is_null($hash) || is_null($redirect))
        {
            return false;
        }

        if (empty($CFG->auth_collabcosso_salt))
        {                            
            \auth_collabcosso\event\collabcosso_failure::create(array(
                'context' => context_system::instance(),
                'other' => array(
                    'message' => sprintf(get_string('sso_failure_settingincorrect', 'auth_collabcosso'), get_string('salt', 'auth_collabcosso'))
                )
            ))->trigger();
            
            return false;
        }

        if (empty($CFG->auth_collabcosso_method))
        {
            \auth_collabcosso\event\collabcosso_failure::create(array(
                'context' => context_system::instance(),
                'other' => array(
                    'message' => sprintf(get_string('sso_failure_settingincorrect', 'auth_collabcosso'), get_string('hashingalgorithm', 'auth_collabcosso'))
                )
            ))->trigger();

            return false;
        }

        $userData = get_complete_user_data('username', $username);

        if (empty($userData))
        {                    
            \auth_collabcosso\event\collabcosso_failure::create(array(
               'context' => context_system::instance(),
               'other' => array(
                   'message' => sprintf(get_string('sso_failure_unknownuser', 'auth_collabcosso'), $username)
               )
           ))->trigger();
            
            return false;
        }

        $strToHash = $username . $time . $redirect . $CFG->auth_collabcosso_salt;

        $expectedHash = hash($CFG->auth_collabcosso_method, $strToHash);
        
        if (strcmp($hash,$expectedHash) !== 0)
        {
            \auth_collabcosso\event\collabcosso_failure::create(array(
                'context' => context_user::instance($userData->id),
                'other' => array(
                    'message' => get_string('sso_failure_hashinvalid', 'auth_collabcosso')
                )
            ))->trigger();
            
            return false;
        }

        if (isloggedin()) 
        {
            if ($CFG->auth_collabcosso_verbose)
            {
                \auth_collabcosso\event\collabcosso_verbose::create(array(
                    'context' => context_user::instance($userData->id),
                    'other' => array(
                        'message' => get_string('sso_verbose_hashinvalid', 'auth_collabcosso')
                    )
                ))->trigger();                    
            }
            
            $url = sprintf("%s/%s", $CFG->wwwroot, $redirect);

            redirect($url);
        }
        
        $dbits = explode("-",$time);
        
        $timestamp = strtotime(sprintf("%s-%s-%s %s:%s:%s", $dbits[2],$dbits[1],$dbits[0],$dbits[3],$dbits[4],$dbits[5]));

        $now = time();
        
        if ($now - $timestamp >= $CFG->local_collabcows_timedrift)
        {            
            \auth_collabcosso\event\collabcosso_failure::create(array(
                'context' => context_system::instance(),
                'other' => array(
                    'message' => get_string('sso_failure_linkexpired', 'auth_collabcosso')
                )
            ))->trigger(); 

            return false;
        }

        if (!empty($userData->suspended)) 
        {                        
            \auth_collabcosso\event\collabcosso_failure::create(array(
                'context' => context_user::instance($userData->id),
                'other' => array(
                    'message' => get_string('sso_failure_accountsuspended', 'auth_collabcosso')
                )
            ))->trigger();
            
            return false;
        }
        
        if ($userData->auth === 'nologin' || !is_enabled_auth($userData->auth))
        {
            \auth_collabcosso\event\collabcosso_failure::create(array(
                'context' => context_user::instance($userData->id),
                'other' => array(
                    'message' => get_string('sso_failure_authunavailable', 'auth_collabcosso')
                )
            ))->trigger();

            return false;
        }
        
        complete_user_login($userData);
        
        if (user_not_fully_set_up($userData)) 
        {
            $urltogo = sprintf("%s//user//edit.php", $CFG->wwwroot);
        }
        else
        {
            if (stripos($redirect, $CFG->wwwroot) === 0)
            {
                $urltogo = $redirect;
            }
            else
            {
                $urltogo = sprintf("%s/%s", $CFG->wwwroot, $redirect);
            }                        
        }

        \auth_collabcosso\event\collabcosso_success::create(array(
            'context' => context_user::instance($userData->id),
            'other' => array(
                'message' => get_string('sso_success_complete', 'auth_collabcosso')
            )
        ))->trigger();
        
        redirect($urltogo);
    }
    
    function config_form($config, $err, $user_fields) {
        include "config.html";
    }
    
    function process_config($config) {
        return true;
    }
}

?>