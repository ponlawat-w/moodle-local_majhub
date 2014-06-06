<?php
namespace majhub;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');
require_once($CFG->dirroot.'/user/filters/profilefield.php');

class maj_user_bulk_action_form extends \moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $syscontext = \context_system::instance();
        
        //points to add/remove
        $mform->addElement('header', 'pointsheader', get_string('pointsheader', 'local_majhub'));
        $mform->addElement('text', 'reason', get_string('reason', 'local_majhub'),array('size'=>64));
        $mform->addElement('text', 'points', get_string('allocatepoints', 'local_majhub'),array('size'=>4));
        $mform->setType('points', PARAM_INT); 
        $mform->setType('reason', PARAM_TEXT); 
        $mform->setDefault('points', 0);
        $mform->setDefault('reason', 'a very good reason');
        $mform->setExpanded('pointsheader');
        
        
         //profile fields
        $advanced=false;
        $profilefilter = new \user_filter_profilefield('profile', get_string('profile'), $advanced);
        $profile_fields = $profilefilter->get_profile_fields();
        if($profile_fields){
        	$mform->closeHeaderBefore('profileheader');
        	$mform->addElement('header', 'profileheader', get_string('profileheader', 'local_majhub'));
			$mform->addElement('select', 'profilefield', get_string('profilefield', 'local_majhub'), $profile_fields);
		   // $mform->addElement('text', 'profilefield', get_string('profilefield', 'local_majhub'),array('size'=>64));
			$mform->addElement('text', 'profilefieldvalue', get_string('profilefieldvalue', 'local_majhub'),array('size'=>64));
			$mform->setType('profilefield', PARAM_TEXT); 
			$mform->setType('profilefieldvalue', PARAM_TEXT); 
			$mform->setDefault('profilefieldvalue', 0);
			$mform->setExpanded('profileheader');
        }
        
        //close the above headers
        $mform->closeHeaderBefore('action');	
    
        
        //actions
        $actions = array(0=>get_string('choose').'...');
        if (has_capability('moodle/user:update', $syscontext)) {
            $actions[1] = get_string('givepoints','local_majhub');
        }
        if (has_capability('moodle/user:update', $syscontext)) {
            $actions[2] = get_string('removepoints','local_majhub');
        }
        if (has_capability('moodle/user:update', $syscontext) && $profile_fields) {
            $actions[3] = get_string('setprofilefield','local_majhub');
        }
        
        //action button
        $objs = array(); 
        $objs[] =& $mform->createElement('select', 'action', null, $actions);
        $objs[] =& $mform->createElement('submit', 'doaction', get_string('go'));
        $mform->addElement('group', 'actionsgrp', get_string('withselectedusers'), $objs, ' ', false);
   

       
   
    }
}