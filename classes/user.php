<?php
/**
 *  MAJ Hub
 *  
 *  @author  Justin Hunt
 *  @version $Id: extension.php 162 2012-12-03 07:03:42Z malu $
 */
namespace majhub;

require_once __DIR__.'/../../../user/selector/lib.php';


/*
 * This class displays either all the Moodle users allowed to use a service,
 * either all the other Moodle users.
 */
class majhub_user_selector extends \user_selector_base {

   /** @var boolean Whether the conrol should allow selection of many users, or just one. */
    protected $multiselect = false;
    /** @var int The height this control should have, in rows. */
    protected $rows = 5;

    public function __construct($name, $options) {
        parent::__construct($name, $options);
    }
    
      /**
     * Find allowed or not allowed users of a service (depend of $this->displayallowedusers)
     * @global object $DB
     * @param <type> $search
     * @return array
     */
    public function find_users($search) {
        global $DB;
        //by default wherecondition retrieves all users except the deleted, not
        //confirmed and guest
        list($wherecondition, $params) = $this->search_sql($search, 'u');


        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

            $sql = " FROM {user} u
                 WHERE $wherecondition
                       AND u.deleted = 0 AND NOT (u.auth='webservice') ";
 
       

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }


    
        $groupname = get_string('potentialcontributors', 'local_majhub');
      

        return array($groupname => $availableusers);
    }
    
     /**
     * This options are automatically used by the AJAX search
     * @global object $CFG
     * @return object options pass to the constructor when AJAX search call a new selector
     */
    protected function get_options() {
        global $CFG;
        $options = parent::get_options();
        $options['file'] = '/local/majhub/classes/user.php'; //need to be set, otherwise
                                                        // the /user/selector/search.php
                                                        //will fail to find this user_selector class
        return $options;
    }
}