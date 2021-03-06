<?php
/**
 * Golem_Cli_Command_Build
 * Builds a new project
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Build extends Golem_Cli_Command {
	/**
	 * Central start method
	 * @param Array $args Various options. Must contain;
	 * @return Boolean
	 */
	public function main(array $args = array()) {
		if (empty($args[0])) {
			Garp_Cli::errorOut('Insufficient arguments.');
			$this->help();
			return false;
		}
		if (strtolower($args[0]) === 'help') {
			$this->help();
			return true;
		}
		// make sure we're in the right directory
		chdir($this->_toolkit->getRc()->getData(Golem_Rc::WORKSPACE));

		$projectName = $args[0];
		$projectRepo = isset($args[1]) ? $args[1] : null;
		$strategy = new Golem_Cli_Command_BuildProject_Strategy_Git($projectName, $projectRepo);
		$success = $strategy->build();

		if (!$success) {
			return false;
		}

		// Enter project for subsequent commands
		$this->_toolkit->enterProject($projectName);

		if (Garp_Cli::confirm('Should I add a vhost for this project?')) {
			$this->_toolkit->executeCommand('vhost', array('add', $projectName));
		}
		$this->_toolkit->executeCommand('permissions', array('set'));
		$this->_toolkit->executeCommand('git', array('setup'));
		return $success;
	}


	/**
 	 * Help
 	 * @return Boolean
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' golem build <projectname>', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('When the repository name is not the same as the projectname:');
		Garp_Cli::lineOut(' golem build <projectname> <repository>', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		return true;
	}
}
