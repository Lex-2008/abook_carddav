<?php
/**
 * plugins/abook_backend_template/functions.php -- Functions used by plugin
 *
 * SquirrelMail Address Book Backend template
 * Copyright (C) 2004 Tomas Kuliavas <tokul@users.sourceforge.net>
 * This program is licensed under GPL. See COPYING for details
 *
 * $Id: abook_class.php,v 1.1.1.1 2004/03/21 10:36:27 tomas Exp $
 */

/**
 * address book template backend class
 */
class abook_template extends addressbook_backend {
    var $btype = 'local';
    var $bname = 'template';
    
    var $writeable = true;
      
    /* ========================== Private ======================= */
      
    /* Constructor */
    function abook_template($param) {
        $this->sname = _("New address book");
         
        if (is_array($param)) {
            if (!empty($param['name'])) {
               $this->sname = $param['name'];
            }

            if (isset($param['writeable'])) {
               $this->writeable = $param['writeable'];
            }

            if (isset($param['listing'])) {
               $this->listing = $param['listing'];
            }

            $this->open(true);
        }
        else {
            return $this->set_error('Invalid argument to constructor');
        }
    }

    /**
     *
     */
    function open() {
      // ADDME: backend open function

      return true;
    }

    /**
     *
     */
    function close() {
      // ADDME: backend close function

    }
      
      
    /* ========================== Public ======================== */

    /**
     * Search address function
     * @param expr string search expression
     */
    function search($expr) {
        $ret = array();

	// ADDME: search by nickname function

        return $ret;
    }
     
    /**
     * Lookup alias
     * @param alias string
     */
    function lookup($alias) {
        if (empty($alias)) {
            return array();
        }
         
        $alias = strtolower($alias);

	// ADDME: address lookup function

	$ret = array('nickname' => "nickname",
                         'name' => "firstname lastname",
    	            'firstname' => "firstname",
                     'lastname' => "lastname",
            		'email' => "email@address",
                	'label' => "info",
                      'backend' => $this->bnum,
                       'source' => $this->sname);

        return $ret;
    }

    /**
     * List all addresses
     * @return array
     */
    function list_addr() {
        $ret = array();

	// ADDME: list all addresses function

        array_push($ret,array('nickname' => "nickname",
                                  'name' => "firstname lastname",
                             'firstname' => "firstname",
                              'lastname' => "lastname",
                        	 'email' => "email@address",
                        	 'label' => "info",
                               'backend' => $this->bnum,
                        	'source' => $this->sname));

        return $ret;
    }

    /**
     * Add address
     * @param userdata
     * @return boolean
     */
    function add($userdata) {
        if (!$this->writeable) {
            return $this->set_error(_("Addressbook is read-only"));
        }

        /* See if user exist already */
        $ret = $this->lookup($userdata['nickname']);
        if (!empty($ret)) {
            return $this->set_error(sprintf(_("User '%s' already exist"),
                                            $ret['nickname']));
        }

	// ADDME: insert address function

	// FIXME:
	// return true if operation is succesful.
	return true;
	// Return error message if operation fails
        return $this->set_error(_("Address add operation failed"));
    }

    /**
     * Delete address
     * @param alias
     * @return boolean
     */
    function remove($alias) {
        if (!$this->writeable) {
            return $this->set_error(_("Addressbook is read-only"));
        }

	// ADD: delete address function

	// FIXME:
	// return true if operation is succesful.
	return true;
	// Return error message if operation fails
        return $this->set_error(_("Address delete operation failed"));
    }

    /**
     * Modify address
     * @param alias
     * @param userdata
     * @return boolean
     */
    function modify($alias, $userdata) {
        if (!$this->writeable) {
            return $this->set_error(_("Addressbook is read-only"));
        }

         /* See if user exist */
        $ret = $this->lookup($alias);
        if (empty($ret)) {
            return $this->set_error(sprintf(_("User '%s' does not exist"),
                                            $alias));
        }
	// ADD: modify address function

	// FIXME:
	// return true if operation is succesful.
	return true;
	// Return error message if operation fails
        return $this->set_error(_("Address modify operation failed"));
    }
} /* End of class abook_template */
?>