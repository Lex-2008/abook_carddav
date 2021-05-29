<?php
/**
 * plugins/abook_carddav/abook_class.php -- main class
 *
 * SquirrelMail Address Book CardDAV Backend
 * Copyright (C) 2021 Aleksei Shpakovsky
 * This program is licensed under GPLv3. See COPYING for details
 * based on:
 * SquirrelMail Address Book Backend template
 * Copyright (C) 2004 Tomas Kuliavas <tokul@users.sourceforge.net>
 * This program is licensed under GPL. See COPYING for details
 */

require 'quickconfig.php';
require 'vendor/autoload.php';

use MStilkerich\CardDavClient\{Account, AddressbookCollection, Config};
use MStilkerich\CardDavClient\Services\{Discovery, Sync, SyncHandler};

use Psr\Log\{AbstractLogger, NullLogger, LogLevel};
use Sabre\VObject\Component\VCard;

Config::init();

/**
 * address book carddav backend class
 */
class abook_carddav extends addressbook_backend {
    var $btype = 'local';
    var $bname = 'carddav';
    var $writeable = true;
      
    /* ========================== Private ======================= */
      
    /* Constructor */
    function abook_carddav($param) {
        $this->sname = _("carddav address book");
         
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

            return $this->open(true);
        }
        else {
            return $this->set_error('Invalid argument to constructor');
        }
    }

    /**
     *
     */
    function open() {
      // backend open function
      $this->account = new Account(DISCOVERY_URI, USERNAME, PASSWORD);
	// Use stored addressbook uri if it exists
        if(defined('abook_uri')){
                $this->abook = new AddressbookCollection(abook_uri, $this->account);
                // TODO: check that it's valid
              return true;
        }

	// Discover the addressbooks for that account
	try {
	    $discover = new Discovery();
	    $abooks = $discover->discoverAddressbooks($this->account);
	} catch (\Exception $e) {
	    return $this->set_error("!!! Error during addressbook discovery: " . $e->getMessage());
	}
	if (count($abooks) <= 0) {
	    return $this->set_error("Cannot proceed because no addressbooks were found - exiting");
	}
	$this->abook = $abooks[0];
	// HINT: use this line to get your discovered addressbook URI
	// echo "discovered: " . $this->abook->getUri();
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
     * Search addressesbook for entries where any field matches expr.
     * It's expected to support * and ? wildcards, and search all fields in any position.
     * Note that currently, it does not.
     * @param expr string search expression.
     * @return array of addresses (arrays)
     */
    function search($expr) {
        $ret = array();

        /* To be replaced by advanded search expression parsing */
        if(is_array($expr)) { return; }

	// list all addresses where any of these fields contains $expr.
	// wildcards not supported.
	// Also note that we don't check for presence of email here,
	// this will be filtered out later
	$all=$this->abook->query(['FN' => "/$expr/", 'EMAIL' => "/$expr/", 'ORG' => "/$expr/", 'NOTE' => "/$expr/"],["FN", "N", "EMAIL", "ORG"]);
	/*
	Returns an array of matched VCards:
	The keys of the array are the URIs of the vcards
	The values are associative arrays with keys etag (type: string) and vcard (type: VCard)
	*/

	$abook_uri_len=strlen($this->abook->getUriPath());
	foreach($all as $uri => $one) {
		$vcard = $one['vcard'];
		if(!isset($vcard->EMAIL)) { continue; }
		$names = $vcard->N->getParts();
		// last,first,additional,prefix,suffix
		array_push($ret,array(
			      'nickname' => substr($uri, $abook_uri_len),
                                  'name' => (string)$vcard->FN,
                             'firstname' => (string)$names[1],
                              'lastname' => (string)$names[0],
                        	 'email' => (string)$vcard->EMAIL,
                        	 'label' => (string)$vcard->ORG,
                               'backend' => $this->bnum,
                        	'source' => $this->sname));
	}


        return $ret;
    }
     
    /**
     * Lookup by the indicated field
     *
     * @param string  $value Value to look up
     * @param integer $field The field to look in, should be one
     *                       of the SM_ABOOK_FIELD_* constants
     *                       defined in functions/constants.php
     *                       (OPTIONAL; defaults to nickname field)
     *                       NOTE: uniqueness is only guaranteed
     *                       when the nickname field is used here;
     *                       otherwise, the first matching address
     *                       is returned.
     * @return a single address (array)
     */
    function lookup($value, $field=SM_ABOOK_FIELD_NICKNAME) {

        if (empty($value)) {
            return array();
        }

	if($field == SM_ABOOK_FIELD_NICKNAME) {
		// TODO: edit this if we use different nick-naming scheme
		$uri = $this->abook->getUriPath() . $value;
		$one = $this->abook->getCard($uri);
		/* returns Associative array with keys:
			etag(string): Entity tag of the returned card
			vcf(string): VCard as string
			vcard(VCard): VCard as Sabre/VObject VCard
		 */
		$vcard = $one['vcard'];
		$names = $vcard->N->getParts();
		return array(
			'nickname' => substr($uri, $abook_uri_len),
			'name' => (string)$vcard->FN,
			'firstname' => (string)$names[1],
			'lastname' => (string)$names[0],
			'email' => (string)$vcard->EMAIL,
			'label' => (string)$vcard->ORG,
			'backend' => $this->bnum,
			'source' => $this->sname);
	}
	if($field == SM_ABOOK_FIELD_FIRSTNAME) {
		// TODO: this will be harder
	}
	if($field == SM_ABOOK_FIELD_LASTNAME) {
		$filter=['N' => "/$value;/^", 'EMAIL' => "//"];
	}
	if($field == SM_ABOOK_FIELD_EMAIL) {
		$filter=['EMAIL' => "/$value/="];
	}
	if($field ==  SM_ABOOK_FIELD_LABEL) {
		$filter=['ORG' => "/$value/="];
	}
	if(!isset($filter)) { return array(); }

	$all=$this->abook->query($filter,["FN", "N", "EMAIL", "ORG"],true,1);
	/*
	Returns an array of matched VCards:
	The keys of the array are the URIs of the vcards
	The values are associative arrays with keys etag (type: string) and vcard (type: VCard)
	 */
	$abook_uri_len=strlen($this->abook->getUriPath());
	foreach($all as $uri => $one) {
		$vcard = $one['vcard'];
		if(!isset($vcard->EMAIL)) { continue; }
		$names = $vcard->N->getParts();
		// last,first,additional,prefix,suffix
		return array(
			'nickname' => substr($uri, $abook_uri_len),
			'name' => (string)$vcard->FN,
			'firstname' => (string)$names[1],
			'lastname' => (string)$names[0],
			'email' => (string)$vcard->EMAIL,
			'label' => (string)$vcard->ORG,
			'backend' => $this->bnum,
			'source' => $this->sname);
	}
    }

    /**
     * List all addresses
     * @return array of addresses (arrays)
     */
    function list_addr() {
        $ret = array();

	// list all addresses having an email
	$all=$this->abook->query(['EMAIL' => "//"],["FN", "N", "EMAIL", "ORG"]);
	/*
	Returns an array of matched VCards:
	The keys of the array are the URIs of the vcards
	The values are associative arrays with keys etag (type: string) and vcard (type: VCard)
	*/

	$abook_uri_len=strlen($this->abook->getUriPath());
	foreach($all as $uri => $one) {
		$vcard = $one['vcard'];
		// if(!isset($vcard->EMAIL)) { continue; }
		$names = $vcard->N->getParts();
		// last,first,additional,prefix,suffix
		array_push($ret,array(
			      'nickname' => substr($uri, $abook_uri_len),
                                  'name' => (string)$vcard->FN,
                             'firstname' => (string)$names[1],
                              'lastname' => (string)$names[0],
                        	 'email' => (string)$vcard->EMAIL,
                        	 'label' => (string)$vcard->ORG,
                               'backend' => $this->bnum,
                        	'source' => $this->sname));
	}

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
	/*
        $ret = $this->lookup($userdata['nickname']);
        if (!empty($ret)) {
            return $this->set_error(sprintf(_("User '%s' already exist"),
                                            $ret['nickname']));
        }
	 */
	try {
	    $vcard =  new VCard([
		'FN'  => $userdata['firstname'] . ' ' . $userdata['lastname'],
		'N'   => [$userdata['lastname'], $userdata['firstname'], '', '', ''],
		'EMAIL' => $userdata['email'],
		'ORG' => $userdata['label'],
	    ]);

		// insert address function
		$this->abook->createCard($vcard);

		// return true if operation is succesful.
		return true;
	} catch (\Exception $e) {
		// Return error message if operation fails
		return $this->set_error(_("Address add operation failed: ") . $e->getMessage());
       }
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

	// TODO: edit this if we use different nick-naming scheme
	$uri = $this->abook->getUriPath() . $value;
	$abook->deleteCard($uri);

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
	// TODO: edit this if we use different nick-naming scheme
	$uri = $this->abook->getUriPath() . $value;
	$one = $this->abook->getCard($uri);
	/* returns Associative array with keys:
		etag(string): Entity tag of the returned card
		vcf(string): VCard as string
		vcard(VCard): VCard as Sabre/VObject VCard
	 */
	$vcard = $one['vcard'];
	// TODO: if no vcard
	$names = $vcard->N->getParts();
	// last,first,additional,prefix,suffix
	$names[0]=$userdata['lastname'];
	$names[1]=$userdata['firstname'];
	$vcard->N = $names;
	if($names[2]){
		$vcard->FN = trim($names[3].' '.$names[1].' '.$names[2].' '.$names[0].' '.$names[4]);
	} else {
		$vcard->FN = trim($names[3].' '.$names[1].' '.$names[0].' '.$names[4]);
	}
	// [prefix=3] first=1 [additional=2] last=0 [suffix=4]
	$vcard->EMAIL = $userdata['email'];
	$vcard->ORG = $userdata['label'];
	$this->abook->updateCard($uri, $vcard, $one['etag']);

	// FIXME:
	// return true if operation is succesful.
	return true;
	// Return error message if operation fails
        return $this->set_error(_("Address modify operation failed"));
    }
} /* End of class abook_carddav */
?>
