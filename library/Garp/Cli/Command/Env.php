<?php
/**
 * Garp_Cli_Command_Env
 * Sets up the environment after deploying.
 * Override this command in the App namespace to do project-specific setup.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Cli_Command
 */
class Garp_Cli_Command_Env extends Garp_Cli_Command {

	public function setup(array $args = array()) {
		// Perform app-specific tasks
		$this->_init();

		// This one's free: inserting required snippets
		$snippetCmd = new Garp_Cli_Command_Snippet();
		$snippetCmd->create(array('from', 'file'));
	}

	/**
 	 * Toggle wether the app is under construction
 	 * @param Array $args Accept "false", "0", 0, and false as disablers.
 	 */
	public function setUnderConstruction(array $args = array()) {
		$enabled = empty($args) ? true : !in_array(current($args), array(0, false, 'false', '0'));
		Garp_Cli::lineOut(Zend_Registry::get('config')->app->name .
 		   	' is' . ($enabled ? '' : ' no longer') . ' under construction');
		return Garp_Application::setUnderConstruction($enabled);
	}

	protected function _init() {
		// overwrite in App namespace
	}

	/**
 	 * Help
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' g Env setup', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('To enable under construction:');
		Garp_Cli::lineOut(' g Env setUnderConstruction', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('To disable under construction:');
		Garp_Cli::lineOut(' g Env setUnderConstruction false', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
	}
}

