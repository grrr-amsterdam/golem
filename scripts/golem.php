<?php
/**
 * golem.php
 * Main Golem interface.
 *
 * @author    Harmen Janssen | grrr.nl
 * @version   1.0
 * @package   Golem
 */
$here = dirname(__FILE__);

// Check if APPLICATION_ENV is passed along as an argument.
// @todo Is APPLICATION_ENV wel echt nodig op dit punt? Eigenlijk alleen voor
// commando's die application.ini gebruiken...
foreach ($_SERVER['argv'] as $key => $arg) {
	if (substr($arg, 0, 17) === '--APPLICATION_ENV' ||
		substr($arg, 0, 3)  === '--e') {
		$keyAndVal = explode('=', $arg);
		define('APPLICATION_ENV', trim($keyAndVal[1]));
		// Remove APPLICATION_ENV from the arguments list
		array_splice($_SERVER['argv'], $key, 1);
	}
}

// Define application environment if it was not passed along as an argument
if (!defined('APPLICATION_ENV')) {
	if (getenv('APPLICATION_ENV')) {
		define('APPLICATION_ENV', getenv('APPLICATION_ENV'));
	} else {
		require_once($here.'/../library/Garp/Cli.php');
		Garp_Cli::errorOut('APPLICATION_ENV is not set. Please set it as a shell variable or pass it along as an argument, like so: --e=development');
		exit(1);
	} 
}

// @todo init is vooral voor cli commands binnen Garp handig, bv het opzetten van een hosts file of het uitchecken van 
// een project is hier niet bij gebaat. Moet het dan al wel op dit punt gedaan worden?
require_once($here.'/../garp/application/init.php');

// Check .golemrc for cached settings
// @todo Move this to the golem reconfigure command mentioned below.
define('GOLEMRC', APPLICATION_PATH.'/data/.golemrc');
$golemRc = new Golem_Rc(GOLEMRC);
if (!$golemRc->exists()) {
	// First timer, eh?
	Garp_Cli::lineOut('');
	Garp_Cli::lineOut('WELCOME TO GOLEM!');
	Garp_Cli::lineOut('Since this is your first time, I\'m going to ask you a few simple questions.');
	Garp_Cli::lineOut('Defaults are shown in parentheses.');
	Garp_Cli::lineOut('');
	$golemRc->askForData();
	if ($golemRc->write()) {
		Garp_Cli::lineOut('Your settings have been saved. If you ever want to reconfigure, simply run golem reconfigure');
	} else {
		Garp_Cli::errorOut('There was trouble saving your .golemrc file. Make sure '.APPLICATION_PATH.'/data/ is writable.');
		Garp_Cli::lineOut('For now we\'ll continue with uncached data. Next time you will have to configure golem again.');
	}
}

exit(0);






// Create application, bootstrap, and run
$application = new Garp_Application(
	APPLICATION_ENV, 
	APPLICATION_PATH.'/configs/application.ini'
);
$application->bootstrap();
// save the application in the registry, so it can be used by commands.
Zend_Registry::set('application', $application);

/**
 * Report errors, since we're in CLI.
 * Note that log_errors = 1, which outputs to STDERR. display_errors however outputs to STDOUT. In a CLI
 * environment this results in a double error. display_errors is therefore set to 0 so that STDERR is 
 * the only stream showing errors.
 * @see http://stackoverflow.com/questions/9001911/why-are-php-errors-printed-twice
 */
error_reporting(-1);
ini_set('log_errors', 0);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 'stderr');

/**
 * Process the command
 */
$args = Garp_Cli::parseArgs($_SERVER['argv']);
if (empty($args[0])) {
	Garp_Cli::errorOut('No command given.');
	Garp_Cli::errorOut('Usage: php garp.php <command> [args,..]');
	exit;
}

/* Construct command classname */
$classArgument = ucfirst($args[0]);
$commandName = 'Garp_Cli_Command_' . $classArgument;
if (isset($classLoader)) {
	if ($classLoader->isLoadable('App_Cli_Command_' . $classArgument)) {
		$commandName = 'App_Cli_Command_' . $classArgument;
	}
}
unset($args[0]);

if (isset($classLoader) && !$classLoader->isLoadable($commandName)) {
	Garp_Cli::errorOut('Silly developer. This is not the command you\'re looking for.');
	exit;
}
$command = new $commandName();
if (!$command instanceof Garp_Cli_Command) {
	Garp_Cli::errorOut('Error: '.$commandName.' is not a valid Command. Command must implement Garp_Cli_Command.');
	exit;
}

/**
 * Helper functionality for the bash-completion script: look for the --complete flag.
 * If it's present, dump a space-separated list of public methods.
 */
if (array_key_exists('complete', $args)) {
	$publicMethods = $command->getPublicMethods();
	Garp_Cli::lineOut(implode(' ', $publicMethods));
} else {
	$command->main($args);
}
exit(0);
