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
}

/**
 * Initialized address book backend
 */
function abook_carddav_init(&$argv) {
    // Get the arguments
    $hookName = &$argv[0];
    $abook = &$argv[1];
    $r = &$argv[2];

    // FIXME: if you want to include translations with your plugin
    //        change this 'locale' to 'plugins/plugin-name/locale'
    bindtextdomain ('abook_carddav', SM_PATH . 'locale');
    textdomain ('abook_carddav');

    // FIXME: add your backend init options in array()
    $r=$abook->add_backend('carddav',array('name'=>_("Address Book Template")));

    bindtextdomain ('squirrelmail', SM_PATH . 'locale');
    textdomain ('squirrelmail');
}

function abook_carddav_class() {
    // FIXME: if you want to include translations with your plugin
    //        change this 'locale' to 'plugins/plugin-name/locale'
    bindtextdomain ('abook_carddav', SM_PATH . 'locale');
    textdomain ('abook_carddav');

    require_once(SM_PATH . 'plugins/abook_carddav/abook_class.php');

    bindtextdomain ('squirrelmail', SM_PATH . 'locale');
    textdomain ('squirrelmail');
}

/**
 * shows plugin's version
 * @return string
 */
function abook_carddav_version() {
  return '1.0';
}
?>
