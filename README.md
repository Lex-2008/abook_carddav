# abook_carddav
SquirrelMail CardDAV Address book backend plugin

Based on [carddavclient][].

[carddavclient]: https://github.com/mstilkerich/carddavclient/

## Requirements

Basically, same as [carddavclient][]:

* PHP 7.1 or 7.4 (tested with 7.4.17) with the following extensions:
json,
iconv,
openssl (only to connect to https backends),
mbstring,
xmlreader,
xmlwriter.
  On most distros they're likely to be installed by default, on Alpine you need to install relevant `php7-*` packages.

* SquirrelMail 1.4.22 (tested with 1.4.23 SVN),
  most likely **NOT** compatible with SquirrelMail 1.5.

## Installation

* (optional) build carddavclient (`vendor` directory).
  There's a `build.sh` script which uses `Dockerfile.build` in `build` directory.

* Copy everything to a `abook_carddav` subdirectory in `plugins` directory of your SquirrelMail installation.

* Open `{your.squirrelmail.installation}/plugins/abook_carddav/discover.php` -
  it should show you "Loading...  ...ok!  Config...  ...ok!" on top.
  If not - check your web server's error log.

* Enable the plugin as usual (via `conf.pl`).

## Configuration

Each user configures the plugin for themselves separately.

* Open `{your.squirrelmail.installation}/plugins/abook_carddav/discover.php`,
  provide hostname, URL, or whatever you know about your CardDAV server,
  together with your username and password.
  For example, in case of Baikal server running on `https://baikal.example.com:8000/`,
  discovery URL is `https://baikal.example.com:8000/dav.php`.
  Valid username and password are required to find addressbooks available to a specific user.
  Press "Submit" button.

* After a second or few you will get to a page with a hopefully huge log -
  scroll to the very bottom and look for line "Addressbooks discovered".

* If you see a number greater than 0 - success! -
  copy any pair of "Addressbook URI" and "Base URL" values,
  and paste them in the SquirrelMail options, "Display Preferences" page,
  "CardDAV Address Book" section - together with your username and password.

## Usage

* If listing is allowed in settings, "Addresses" shows all contacts in your CardDAV address book, who have an email address.

* Otherwise, you can always yse addressbook search.

* "Address Autocompletion" plugin has an option to pre-load Contacts.
  Note that this option is not compatible with addressbook plugins which have listing disabled.


Note on Vcard fields
--------------------

In addition to "email", Vcard format supports quite a lot of fields
(name, org, title, notes, address, phone, etc),
while SquirrelMail - much less (name, nickname, info).

Moreover, SquirrelMail uses "nickname" field as unique key to identify
address book entries when editing/deleting them. I.e. when user clicks a button
to delete an entry, SquirrelMail tells addressbook backend: "please
delete user with this nickname". For CardDAV servers, such unique keys are
otherwise meaningless URIs.

Also SquirrelMail supports only one email address per contact, while vcard
can have multiple.

So I came up with this otherwise strange idea: add an option (checkbox)
whether addressbook is writeable, and make field contents depend on it:

* when addressbook is in read-only mode, "nickname" field in SquirrelMail
shows content of "organisation" field in vcard.

* when addressbook is write-enabled, "nickname" field contains vcard URI,
and "info" field contains value of "organisation" field from vcard (which
you actually can edit).

Moreover, when addressbook is in read-only mode, each vcard is repeated as
many times as there are email addresses in it, and also "info" field has
different phone numbers (but you can't edit them).

Sounds messy, but works nice in my case :)


Note about password storage
---------------------------

This plugin has three options regarding password storage:

* use same password for CardDav account as for IMAP (usually it's the
password you enter to login to SquirrelMail) - obviously it's the best
option from the password storage point of view, but only if your CardDav
and IMAP accounts have the same password (note that usernames might
differ).

* _encrypt_ your CardDav password using your IMAP password - probably the
best option in all other cases, but remember that you will have to
re-enter your CardDav password (for it to be re-encrypted) in case your
IMAP password changes. _Encryption_ used here is basically XORing CardDav
password with sha256 checksum of IMAP password and storing the result.

* No encryption, CardDav password is stored in your prefs file in plain text.


Obviously, in last case if someone gets hold of your prefs file (malicious
server admin, php script, or via backups) - they can read your CardDav
password from it.

In second case, in addition to copy of your prefs file, they need to know
your IMAP password - and then they can find out your CardDav password.

If anyone has a better (more secure) idea of password storage - please let
me know!


Also, when user switches from first option ("use IMAP password") to last
one ("store password in plaintext") - their IMAP password appears in prefs
file in plain text. Fixing this is first item on my list.


Some more notes
---------------

* Currently, CardDav server/account is configured by each user
individually, although it should be pretty easy to add an option of
admin-specified "global" or per-user CardDav server/account.

* Also, it should be pretty simple to implement multiple CardDav address
books (just call add_account function multiple times with different
arguments), but I don't have good idea how to implement it UI-wise.

* It should be easy to adapt this plugin to other SquirrelMail versions, if
someone finds a compatibility issue.
