<?php
/**
 * golem.php
 * Main Golem interface.
 *
 * @author    Harmen Janssen | grrr.nl
 * @version   1.0
 * @package   Golem
 */
define('GOLEM_APPLICATION_PATH', realpath(dirname(__FILE__).'/../application'));

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

// Autoloading is not yet setup. Include required classes manually
$requiredClasses = array('Rc', 'Toolkit', 'Exception');
foreach ($requiredClasses as $class) {
	require_once(GOLEM_APPLICATION_PATH.'/../library/Golem/'.$class.'.php');
}

$golemRc = new Golem_Rc(GOLEM_APPLICATION_PATH.Golem_Toolkit::GOLEMRC);
$golemToolkit = Golem_Toolkit::getInstance($golemRc);

try {
	$success = $golemToolkit->main();
} catch (Exception $e) {
	$success = false;
	Garp_Cli::errorOut($e->getMessage());
}
Garp_Cli::halt($success);

// @todo Recreate tab completion functionality in Golem
