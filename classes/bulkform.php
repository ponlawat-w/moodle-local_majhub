<?php
namespace majhub;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');

class maj_user_bulk_action_form extends \moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $syscontext = \context_system::instance();
        $actions = array(0=>get_string('choose').'...');
        if (has_capability('moodle/user:update', $syscontext)) {
            $actions[1] = get_string('givepoints','local_majhub');
        }
        if (has_capability('moodle/user:update', $syscontext)) {
            $actions[2] = get_string('removepoints','local_majhub');
        }
        //points to add/remove
        $mform->addElement('text', 'reason', get_string('reason', 'local_majhub'),array('size'=>64));
        $mform->addElement('text', 'points', get_string('allocatepoints', 'local_majhub'),array('size'=>4));
        $mform->setType('points', PARAM_INT); 
        $mform->setType('reason', PARAM_TEXT); 
        $mform->setDefault('points', 0);
        $mform->setDefault('reason', 'a very good reason');
        
        //action button
        $objs = array(); 
        $objs[] =& $mform->createElement('select', 'action', null, $actions);
        $objs[] =& $mform->createElement('submit', 'doaction', get_string('go'));
        $mform->addElement('group', 'actionsgrp', get_string('withselectedusers'), $objs, ' ', false);
    }
}