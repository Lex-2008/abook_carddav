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
	// defaults
        $this->sname = _("CardDAV Address Book");
         
        if (is_array($param)) {
            if (!empty($param['name'])) { $this->sname = $param['name']; }
            if (!empty($param['abook_uri'])) { $this->abook_uri = $param['abook_uri']; }
            if (!empty($param['base_uri'])) { $this->base_uri = $param['base_uri']; }
            if (!empty($param['username'])) { $this->username = $param['username']; }
            if (!empty($param['password'])) { $this->password = $param['password']; }
            if (isset($param['writeable'])) { $this->writeable = $param['writeable']; }
            if (isset($param['listing'])) { $this->listing = $param['listing']; }
	    $this->account = new Account($this->base_uri, $this->username, $this->password, $this->base_uri);
	    $this->abook = new AddressbookCollection($this->abook_uri, $this->account);
	    $this->abook_uri_len=strlen($this->abook->getUriPath());
        }
        else {
            return $this->set_error('Invalid argument to constructor');
        }
    }

    /**
     * Given a $vcard object and its $uri, returns squirrelmail contact (array).
     * Optional $email arg overwrites the one stored in vcard.
     * Respects $this->writeable:
     * for writeable addressbooks, 'nickname' must be unique identifier -
     *   in our case, last part of uid id used
     * for non-writeable addressbooks, 'nickname' doesn't matter that much -
     *   so we put ORG there
     */
    function vcard2sq($uri, $vcard, $email=null) {
	    if($this->writeable) {
		    $nickname = substr($uri, $this->abook_uri_len);
		    $label = (string)$vcard->ORG;
	    } else {
		    $nickname = (string)$vcard->ORG;
		    $label = (string)$vcard->NOTE;
	    }
	    if(!$email) {
		    $email = (string)$vcard->EMAIL;
	    }
	    $names = $vcard->N->getParts();
	    // last,first,additional,prefix,suffix
	    return array(
		    'nickname' => $nickname,
		    'name' => (string)$vcard->FN,
		    'firstname' => (string)$names[1],
		    'lastname' => (string)$names[0],
		    'email' => $email,
		    'label' => $label,
		    'backend' => $this->bnum,
		    'source' => $this->sname);
    }

    /**
     * Run query against addressbook and return squurrelmail-type address(es)
     * Params are same as in https://mstilkerich.github.io/carddavclient/classes/MStilkerich-CardDavClient-AddressbookCollection.html#method_query
     * except the lack of 2nd parameter:
     * @param array $query
     *  The query filter conditions, for format see https://mstilkerich.github.io/carddavclient/classes/MStilkerich-CardDavClient-XmlElements-Filter.html#method___construct
     * @param bool $matchAll
     *  Whether all or any of the conditions needs to match.
     * @param int $limit
     *  Tell the server to return at most $limit results. 0 means no limit.
     * @return either:
     *         * a single address (array) - if $limit==1
     *         * or array of addresses (arrays)
     */
    function run_query($query, $match_all=false, $limit=0) {
	$ret = array();
	$all=$this->abook->query($query,["FN", "N", "EMAIL", "ORG", "NOTE"],$match_all,$limit);
	/*
	Returns an array of matched VCards:
	The keys of the array are the URIs of the vcards
	The values are associative arrays with keys etag (type: string) and vcard (type: VCard)
	*/

	foreach($all as $uri => $one) {
		$vcard = $one['vcard'];
		if(!isset($vcard->EMAIL)) { continue; }
		if($this->writeable) {
			// all one line per each vcard
			$ret[] = $this->vcard2sq($uri, $vcard);
		} else {
			foreach($vcard->EMAIL as $email) {
				// all one line per each email
				$ret[] = $this->vcard2sq($uri, $vcard, $email);
			}
		}
		if($limit == 1) { return $ret[0]; }
	}
	return $ret;
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

        /* To be replaced by advanded search expression parsing */
        if(is_array($expr)) { return; }

	if ($expr=='*') { return $this->list_addr(); }

	// list all addresses where any of these fields contains $expr.
	// wildcards are not supported.
	// Also note that we don't check for presence of email in the filter,
	// this will be filtered out inside run_query
	return $this->run_query(['FN' => "/$expr/", 'EMAIL' => "/$expr/", 'ORG' => "/$expr/", 'NOTE' => "/$expr/"]);
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

	$abook_uri_len=strlen($this->abook->getUriPath());
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
    		return $this->vcard2sq($uri, $vcard);
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
		$filter=['ORG' => "/$value/=", 'EMAIL' => "//"];
	}
	if(!isset($filter)) { return array(); }

	return $this->run_query($filter,true,1);
    }

    /**
     * List all addresses
     * @return array of addresses (arrays)
     */
    function list_addr() {
	if(!$this->listing) { return array(); }
	// list all addresses having an email
	return $this->run_query(['EMAIL' => "//"]);
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
     * Delete addresses
     * @param aliases array of nicknames to delete
     * @return boolean
     */
    function remove($aliases) {
        if (!$this->writeable) {
            return $this->set_error(_("Addressbook is read-only"));
        }

	foreach($aliases as $alias) {
		// TODO: edit this if we use different nick-naming scheme
		$uri = $this->abook->getUriPath() . $alias;
		$this->abook->deleteCard($uri);
	}

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
	$uri = $this->abook->getUriPath() . $alias;
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
	$vcard->FN = trim($names[3].' '.$names[1].' '.$names[2].' '.$names[0].' '.$names[4]);
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
