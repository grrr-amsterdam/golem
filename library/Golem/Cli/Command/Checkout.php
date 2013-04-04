<?php
/**
 * Golem_Cli_Command_Checkout
 * Checkout a new project.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Checkout extends Golem_Cli_Command {
	/**
 	 * The Git clone command
 	 */
	const GIT_CLONE_CMD = 'git clone git@flow.grrr.nl:%s %s --recursive';

	/**
 	 * Checkout a project
 	 * @param Array $args
 	 * @return Boolean
 	 */
	public function main(array $args = array()) {
		if (empty($args[0])) {
			$this->help();
			return false;
		}
		// Make sure we're in the right directory
		chdir($this->_toolkit->getRc()->getData(Golem_Rc::WORKSPACE));

		$project = $destination = $args[0];
		if (!empty($args[1])) {
			$destination = $args[1];
		}
		// Execute the clone cmd. Let errors fall thru.
		$cloneCmd = sprintf(self::GIT_CLONE_CMD, $project, $destination);
		`$cloneCmd`;

		if (Garp_Cli::confirm('Should I add a vhost for this project?')) {
			$this->_toolkit->executeCommand('vhost', array('add', $destination));
		}
		return true;
	}

	/**
 	 * Help
 	 * @return Boolean
 	 */
	public function help(array $args = array()) {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' golem checkout <projectname> [<destination-folder>]', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		return true;
	}
}
