<html>
<body>
<p>Loading...</p>
<?php
require 'vendor/autoload.php';

use MStilkerich\CardDavClient\{Account, AddressbookCollection, Config};
use MStilkerich\CardDavClient\Services\{Discovery, Sync, SyncHandler};

use Psr\Log\{AbstractLogger, NullLogger, LogLevel};
use Sabre\VObject\Component\VCard;
?>
<p>...ok!</p>

<p>Config...</p>
<?php
// This is just a sample logger for demo purposes. You can use any PSR-3 compliant logger,
// there are many implementations available (e.g. monolog)
class StdoutLogger extends AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
        // if ($level !== LogLevel::DEBUG) {
            $ctx = empty($context) ? "" : json_encode($context);
            echo htmlspecialchars($message . $ctx, ENT_COMPAT | ENT_SUBSTITUTE) . "\n";
        // }
    }
}

$log = new StdoutLogger();
$httplog = new StdoutLogger(); // parameter could simply be omitted for the same effect

// Initialize the library. Currently, only objects for logging need to be provided, which are two optional logger
// objects implementing the PSR-3 logger interface. The first object logs the log messages of the library itself, the
// second can be used to log the HTTP traffic. If no logger is given, no log output will be created. For that, simply
// call Config::init() and the library will internally use NullLogger objects.
Config::init($log, $httplog);
?>
<p>...ok!</p>

<fieldset><legend>Discover</legend>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	?>
	<form method="post">
	<table width="100%" cellpadding="2" cellspacing="0" border="0">

	<tr>
	<td align="right" valign="middle">URI to discover:</td>
	<td align="left"><input type="text" name="url"> 
	Probably hostname is enough, maybe with protocol (defaults to <b>https</b>).
	</td>
	</tr>

	<tr>
	<td align="right" valign="middle">Username:</td>
	<td align="left"><input type="text" name="username"> 
	</td>
	</tr>

	<tr>
	<td align="right" valign="middle">Password:</td>
	<td align="left"><input type="text" name="password"> 
	</td>
	</tr>

	</table>
	<input type="submit">
	</form>
	</fieldset></body></html>
	<?php
	exit(0);
}
// rest is happening for POST method

?>

<p>Creating account...</p>
<?php

// Now create an Account object that contains credentials and discovery information
$account = new Account($_POST['url'], $_POST['username'], $_POST['password']);

?>
<p>...ok!</p>

<p>Discovering addressbooks... (<b>NOTE</b>: log below might contain your password, sometimes in easy-reversable base64 encoding!)</p>
<pre>
<?php
// Discover the addressbooks for that account
try {
    $discover = new Discovery();
    $abooks = $discover->discoverAddressbooks($account);
} catch (\Exception $e) {
    $log->error("!!! Error during addressbook discovery: " . htmlspecialchars($e->getMessage(), ENT_COMPAT | ENT_SUBSTITUTE));
    exit(1);
}
?>
</pre>

<?php
echo '<p>Addressbooks discovered: ' , count($abooks) , '</p>';
if (count($abooks) < 1) {
	echo '<p>Please try again</p></fieldset></body></html>';
	exit(0);
}
?>

<p>Getting properties...</p>

<pre>
<?php
foreach ($abooks as $abook) { $abook->refreshProperties(); }
?>
</pre>
<p>...ok!</p>

</fieldset>

<p>Addressbooks discovered: <b><?php echo count($abooks) ?></b></p>

<ul>
<?php
foreach ($abooks as $abook) {
	echo "<li> Name: ", $abook->getName();
	echo "<br> <b>Addressbook URI</b>: ", $abook->getUri();
	echo "<br> <b>Base URL</b>: ", $abook->getAccount()->getUrl();
	echo "</li>";
}
?>
</ul>

<?php
$any='';
if (count($abooks) > 1) {
    $any='one pair of';
}
?>
	<p>Use <?php echo $any ?> Addressbook URI and Base URL from the above and paste them at <a href="../../src/options.php?optpage=display">CardDAV Address Book settings</a></p>
</body></html>
