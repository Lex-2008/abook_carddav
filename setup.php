<?php
/**
 * plugins/abook_carddav/setup.php -- Main setup script
 *
 * SquirrelMail Address Book CardDAV Backend
 * Copyright (C) 2021 Aleksei Shpakovsky
 * This program is licensed under GPLv3. See COPYING for details
 * based on:
 * SquirrelMail Address Book Backend template
 * Copyright (C) 2004 Tomas Kuliavas <tokul@users.sourceforge.net>
 * This program is licensed under GPL. See COPYING for details
 */

// make sure SM_PATH is defined
if (!defined('SM_PATH'))  {
    define('SM_PATH','../../');
}

/**
 * init function
 */
function squirrelmail_plugin_init_abook_carddav() {
  global $squirrelmail_plugin_hooks;

  $squirrelmail_plugin_hooks['abook_init']['abook_carddav'] = 'abook_carddav_init';
  $squirrelmail_plugin_hooks['abook_add_class']['abook_carddav'] = 'abook_carddav_class';
  $squirrelmail_plugin_hooks['optpage_loadhook_display']['abook_carddav'] = 'abook_carddav_optpage';

}

function abook_get_password($data, $opt){
	    require_once(SM_PATH . 'functions/auth.php');
	    require_once(SM_PATH . 'functions/strings.php');
	    switch ($opt) {
	    case '0': return sqauth_read_password();
	    case '1': return OneTimePadDecrypt($data, base64_encode(sqauth_read_password()));
	    case '2': return $data;
	    }
}

function abook_set_password($password, $opt){
	global $username, $data_dir;
	switch ($opt) {
	case '0': $data = ''; break;
	case '1':
		if(preg_match('/^\**$/', $password)) { return; }
		require_once(SM_PATH . 'functions/auth.php');
		require_once(SM_PATH . 'functions/strings.php');
		$data = OneTimePadEncrypt($password, base64_encode(sqauth_read_password()));
		break;
	case '2': $data = $password; break;
	}
	setPref($data_dir, $username, 'plugin_abook_carddav_password', $data);
}

/**
 * Initialized address book backend
 */
function abook_carddav_init(&$argv) {
  global $username, $data_dir;
    // Get the arguments
    $hookName = &$argv[0];
    $abook = &$argv[1];
    $r = &$argv[2];

    bindtextdomain ('abook_carddav', SM_PATH . 'locale');
    textdomain ('abook_carddav');

    // TODO: consider multiple uris
    $abook_uri = getPref($data_dir, $username, 'plugin_abook_carddav_abook_uri');
    $abook_base_uri = getPref($data_dir, $username, 'plugin_abook_carddav_base_uri');
    $abook_username = getPref($data_dir, $username, 'plugin_abook_carddav_username');
    $abook_password_text = getPref($data_dir, $username, 'plugin_abook_carddav_password');
    $abook_password_opt = getPref($data_dir, $username, 'plugin_abook_carddav_password_opt', '2');
    $abook_password = abook_get_password($abook_password_text, $abook_password_opt);
    $abook_writeable = getPref($data_dir, $username, 'plugin_abook_carddav_writeable');
    $abook_listing = getPref($data_dir, $username, 'plugin_abook_carddav_listing');
    if(substr($abook_uri, 0,4) == 'http'){
	    $r=$abook->add_backend('carddav',array(
		    'name'=>_("CardDAV Address Book"),
		    'abook_uri'=>$abook_uri,
		    'base_uri'=>$abook_base_uri,
		    'username'=>$abook_username,
		    'password'=>$abook_password,
		    'writeable'=>$abook_writeable,
		    'listing'=>$abook_listing,
	    ));
    }

    bindtextdomain ('squirrelmail', SM_PATH . 'locale');
    textdomain ('squirrelmail');
}

function abook_carddav_class() {
  global $username, $data_dir;
    bindtextdomain ('abook_carddav', SM_PATH . 'locale');
    textdomain ('abook_carddav');

    // load file only if $abook_uri is set
    $abook_uri = getPref($data_dir, $username, 'plugin_abook_carddav_abook_uri');
    if(substr($abook_uri, 0,4) == 'http'){
	    require_once(SM_PATH . 'plugins/abook_carddav/abook_class.php');
    }

    bindtextdomain ('squirrelmail', SM_PATH . 'locale');
    textdomain ('squirrelmail');
}

function abook_carddav_optpage() {
  global $optpage_data;
  global $username, $data_dir;

    $abook_uri = getPref($data_dir, $username, 'plugin_abook_carddav_abook_uri');
    $abook_base_uri = getPref($data_dir, $username, 'plugin_abook_carddav_base_uri');
    $abook_username = getPref($data_dir, $username, 'plugin_abook_carddav_username');
    $abook_password_opt = getPref($data_dir, $username, 'plugin_abook_carddav_password_opt', '2');
    switch($abook_password_opt){
	    case '0': $abook_password = ''; break;
	    case '1': $abook_password = '*******'; break;
	    case '2': $abook_password = getPref($data_dir, $username, 'plugin_abook_carddav_password'); break;
    }
    $abook_writeable = getPref($data_dir, $username, 'plugin_abook_carddav_writeable');
    $abook_listing = getPref($data_dir, $username, 'plugin_abook_carddav_listing');
    sq_change_text_domain('abook_carddav');
    $optpage_data['grps']['abook_carddav'] = _("CardDAV Address Book");
    $optpage_data['vals']['abook_carddav'][] = array(
	    'name'    => 'plugin_abook_carddav_abook_uri',
	    'caption' => _("Addressbool URI"),
	    'type'    => SMOPT_TYPE_STRING,
	    'size'    => SMOPT_SIZE_HUGE,
	    'initial_value' => $abook_uri,
    );
    $optpage_data['vals']['abook_carddav'][] = array(
	    'name'    => 'plugin_abook_carddav_base_uri',
	    'caption' => _("Base URL"),
	    'type'    => SMOPT_TYPE_STRING,
	    'initial_value' => $abook_base_uri,
    );
    $optpage_data['vals']['abook_carddav'][] = array(
	    'name'    => 'plugin_abook_carddav_dicsover_link',
	    'caption' => _("Hint"),
	    'type'    => SMOPT_TYPE_COMMENT,
	    'comment' => _("Use <a href=\"../plugins/abook_carddav/discover.php\">discover</a> page to find out these values"),
    );
    $optpage_data['vals']['abook_carddav'][] = array(
	    'name'    => 'plugin_abook_carddav_username',
	    'caption' => _("Username"),
	    'type'    => SMOPT_TYPE_STRING,
	    'initial_value' => $abook_username,
    );
    $optpage_data['vals']['abook_carddav'][] = array(
	    'name'    => 'plugin_abook_carddav_password',
	    'caption' => _("Password"),
	    'type'    => SMOPT_TYPE_STRING,
	    'initial_value' => $abook_password,
	    'save'    => 'plugin_abook_carddav_password_save',
    );
    $optpage_data['vals']['abook_carddav'][] = array(
	    'name'    => 'plugin_abook_carddav_password_opt',
	    'caption' => _("Password type/encryption"),
	    'type'    => SMOPT_TYPE_STRLIST,
	    'posvals' => array('0' => _("Use same password for CardDAV as for IMAP (above option is ignored)"),
	                       '1' => _("Encrypt your CardDAV password using your IMAP password"),
	                       '2' => _("Store your CardDAV password in plaintext (least secure)")),
	    'initial_value' => $abook_password_opt,
	    'save'    => 'plugin_abook_carddav_password_opt_save',
    );
    $optpage_data['vals']['abook_carddav'][] = array(
	    'name'    => 'plugin_abook_carddav_writeable',
	    'caption' => _("Writeable"),
	    'type'    => SMOPT_TYPE_BOOLEAN,
	    'trailing_text' => _("(nickname field used for vcard URI)"),
	    'initial_value' => $abook_writeable,
    );
    $optpage_data['vals']['abook_carddav'][] = array(
	    'name'    => 'plugin_abook_carddav_listing',
	    'caption' => _("Listing allowed"),
	    'type'    => SMOPT_TYPE_BOOLEAN,
	    'trailing_text' => _("(otherwise, only search can be used)"),
	    'initial_value' => $abook_listing,
    );
}

function plugin_abook_carddav_password_save($option){
	global $username, $data_dir;
	$opt = getPref($data_dir, $username, 'plugin_abook_carddav_password_opt', '2');
	abook_set_password($option->$new_value, $opt);
}

function plugin_abook_carddav_password_opt_save($option){
	global $username, $data_dir;
	// get current plassword
	$abook_password_text = getPref($data_dir, $username, 'plugin_abook_carddav_password');
	$abook_password_opt = getPref($data_dir, $username, 'plugin_abook_carddav_password_opt', '2');
	$abook_password = abook_get_password($abook_password_text, $abook_password_opt);
	save_option($option);
	// reencrypt it
	abook_set_password($abook_password, $option->$new_value);
}


/**
 * shows plugin's version
 * @return string
 */
function abook_carddav_version() {
  return '1.1';
}
?>
