# abook_carddav
SquirrelMail CardDAV Address book backend

Based on [carddavclient][].

[carddavclient]: https://github.com/mstilkerich/carddavclient/

## Requirements

Basically, same as [carddavclient][]:

* PHP 7.1 (TODO: exact version? - tested with 7.4.17) with the following extensions:
json,
iconv,
openssl (only to connect to https backends),
mbstring,
xmlreader,
xmlwriter.
  On most distros they're likely to be installed by default, on Alpine you need to install relevant `php7-*` packages.

* SquirrelMail 1.4.22 (TODO: exact version? - tested with 1.4.23 SVN),
  most likely **NOT** compatible with SquirrelMail 1.5.

## Installation

* (optional) build carddavclient (`vendor` directory).
  There's a `build.sh` script which uses `Dockerfile.build` in `build` directory.

* Copy everything to a `abook_carddav` subdirectory in `plugins` directory of your SquirrelMail installation.

* Open `{your.squirrelmail.installation}/plugins/abook_carddav/discover.php` -
  it should show you "Loading...  ...ok!  Config...  ...ok!" on top.
  If not - check your web server's error log.

## Configuration

Each user configures the plugin for themselves separately.

* Open `{your.squirrelmail.installation}/plugins/abook_carddav/discover.php`,
  provide hostname, URL, or whatever you know about your CardDAV server,
  together with your username and password.
  Press "Submit" button.

* After a second you will get to a page with a hopefully huge log -
  scroll to the very bottom and look for line "Addressbooks discovered".

* If you see a number greater than 0 - success! -
  copy any pair of "Addressbook URI" and "Base URL" values,
  and paste them in the SquirrelMail options, "Display Preferences" page,
  "CardDAV Address Book" section - together with your username and password.

## Usage

* If listing is allowed in settings, "Addresses" shows all contacts in your CardDAV address book, who have an email address.

## Gotchas

* SquirrelMail uses "Nickname" field as "primary key" when editing and deleting contacts.
  Hence, when addressbook is in "writeable" state, it presents URI (CardDAV internal unique identifier) to SquirrelMail in "nickname" field,
  and company name in "Info"/label field.
  When "writeable" state is disabled, then "nickname" field is used for company name,
  and "Info" field shows some phone numbers.
  Also, in non-writeable state, each contact is repeated as many times as many email addresses it has.

* "Address Autocompletion" plugin has an option to pre-load Contacts.
  Note that this option is not compatible with addressbook plugins which have listing disabled.
