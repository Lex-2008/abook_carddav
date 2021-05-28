<?php
/**
 * plugins/abook_backend_template/setup.php -- Main setup script
 *
 * SquirrelMail Addrress Book backend template
 * Copyright (C) 2004 Tomas Kuliavas <tokul@users.sourceforge.net>
 * This program is licensed under GPL. See COPYING for details
 *
 * $Id: setup.php,v 1.1.1.1 2004/03/21 10:36:27 tomas Exp $
 */

// make sure SM_PATH is defined
if (!defined('SM_PATH'))  {
    define('SM_PATH','../../');
}

/**
 * init function
 */
function squirrelmail_plugin_init_abook_backend_template() {
  global $squirrelmail_plugin_hooks;

  $squirrelmail_plugin_hooks['abook_init']['abook_backend_template'] = 'abook_backend_template_init';
  $squirrelmail_plugin_hooks['abook_add_class']['abook_backend_template'] = 'abook_backend_template_class';
}

/**
 * Initialized address book backend
 */
function abook_backend_template_init(&$argv) {
    // Get the arguments
    $hookName = &$argv[0];
    $abook = &$argv[1];
    $r = &$argv[2];

    // FIXME: if you want to include translations with your plugin
    //        change this 'locale' to 'plugins/plugin-name/locale'
    bindtextdomain ('abook_template', SM_PATH . 'locale');
    textdomain ('abook_template');

    // FIXME: add your backend init options in array()
    $r=$abook->add_backend('template',array('name'=>_("Address Book Template")));

    bindtextdomain ('squirrelmail', SM_PATH . 'locale');
    textdomain ('squirrelmail');
}

function abook_backend_template_class() {
    // FIXME: if you want to include translations with your plugin
    //        change this 'locale' to 'plugins/plugin-name/locale'
    bindtextdomain ('abook_template', SM_PATH . 'locale');
    textdomain ('abook_template');

    require_once(SM_PATH . 'plugins/abook_backend_template/abook_class.php');

    bindtextdomain ('squirrelmail', SM_PATH . 'locale');
    textdomain ('squirrelmail');
}

/**
 * shows plugin's version
 * @return string
 */
function abook_backend_template_version() {
  return '1.0';
}
?>