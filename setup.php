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
    $abook_password = getPref($data_dir, $username, 'plugin_abook_carddav_password');
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
    $abook_password = getPref($data_dir, $username, 'plugin_abook_carddav_password');
    $abook_writeable = getPref($data_dir, $username, 'plugin_abook_carddav_writeable');
    $abook_listing = getPref($data_dir, $username, 'plugin_abook_carddav_listing');
    sq_change_text_domain('abook_carddav');
    $optpage_data['grps']['abook_carddav'] = _("CardDAV Address Book");
    $optpage_data['vals']['abook_carddav'][] = array(
	    'name'    => 'plugin_abook_carddav_abook_uri',
	    'caption' => _("Addressbool URI"),
	    'type'    => SMOPT_TYPE_STRING,
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
	    'comment' => _("Use <a href=\"../plugins/abook_carddav/discover.php\">discover</a> page to get these values"),
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
    );
    $optpage_data['vals']['abook_carddav'][] = array(
	    'name'    => 'plugin_abook_carddav_writeable',
	    'caption' => _("Writeable"),
	    'type'    => SMOPT_TYPE_BOOLEAN,
	    'trailing_text' => _("nickname field used for vcard URI"),
	    'initial_value' => $abook_writeable,
    );
    $optpage_data['vals']['abook_carddav'][] = array(
	    'name'    => 'plugin_abook_carddav_listing',
	    'caption' => _("Listing allowed"),
	    'type'    => SMOPT_TYPE_BOOLEAN,
	    'trailing_text' => _("otherwise, only search can be used"),
	    'initial_value' => $abook_listing,
    );
}


/**
 * shows plugin's version
 * @return string
 */
function abook_carddav_version() {
  return '1.0';
}
?>
