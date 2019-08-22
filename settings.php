<?php

if ($hassiteconfig) { // needs this condition or there is error on login page
    //$ADMIN->add('authplugins', new admin_externalpage('auth_collabcosso', get_string('somethingstring', 'auth_collabcosso'), new moodle_url('/auth/collabcosso/config.htm')));
    
    $settings->add(new admin_setting_configtext('auth_collabcosso_salt', get_string('salt', 'auth_collabcosso'), get_string('salt_desc', 'auth_collabcosso'), null));
   
    $settings->add(new admin_setting_configselect('auth_collabcosso_method', get_string('hashingalgorithm', 'auth_collabcosso'), get_string('hashingalgorithm_desc', 'auth_collabcosso'), "MD5" ,array( "MD5" => "MD5", "SHA256" =>"SHA265")));
    
    $settings->add(new admin_setting_configcheckbox('auth_collabcosso_verbose', get_string('verboselogging', 'auth_collabcosso'), get_string('verboselogging_desc', 'auth_collabcosso'), 0));

    $settings->add(new admin_setting_configtext('auth_collabcosso_timedrift', get_string('timedrift', 'auth_collabcosso'), get_string('timedrift_desc', 'auth_collabcosso'), 60, PARAM_INT));
}

?>