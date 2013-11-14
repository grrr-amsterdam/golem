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
 	 * The Git clone commands
 	 */
	const GIT_CLONE_CMD_FLOW_SERVER = 'git clone git@flow.grrr.nl:%s %s --recursive';
	const GIT_CLONE_CMD = 'git clone git@code.grrr.nl:grrr/%s %s --recursive';

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

		// Toggle old server on
		$clone_cmd = self::GIT_CLONE_CMD;
		if (array_key_exists('flow', $args)) {
			$clone_cmd = self::GIT_CLONE_CMD_FLOW_SERVER;
		}

		// Execute the clone cmd. Let errors fall thru.
		$cloneCmd = sprintf($clone_cmd, $project, $destination);
		`$cloneCmd`;

		$this->_toolkit->enterProject($project);
		if (Garp_Cli::confirm('Should I add a vhost for this project?')) {
			$this->_toolkit->executeCommand('vhost', array('add', $destination));
		}
		$this->_toolkit->executeCommand('permissions', array('set'));
		$this->_toolkit->executeCommand('git', array('setup'));
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
