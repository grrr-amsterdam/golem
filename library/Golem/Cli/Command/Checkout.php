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

		$gitHelper = new Golem_GitHelper;
		$cloneCmd = $gitHelper->createCloneCmd($project, $destination);
		if (!$cloneCmd) {
			Garp_Cli::errorOut('Project not found.');
			return false;
		}

		exec($cloneCmd, $output, $returnValue);
		if (0 !== $returnValue) {
			// no warning necessary, the command's error passes thru
			return false;
		}

		$this->_toolkit->enterProject($project);
		if (Garp_Cli::confirm('Should I add a vhost for this project?')) {
			$this->_toolkit->executeCommand('vhost', array('add', $destination));
		}
		$this->_toolkit->executeCommand('folders', array('createRequired'));
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
