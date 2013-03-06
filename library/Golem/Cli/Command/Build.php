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
	 * @return Void
	 */
	public function main(array $args = array()) {
		if (empty($args[0])) {
			Garp_Cli::errorOut('Insufficient arguments.');
			$this->help();
		} elseif (strtolower($args[0]) === 'help') {
			$this->help();
			return;
		} else {
			// make sure we're in the right directory
			chdir($this->_toolkit->getRc()->getData(Golem_Rc::WORKSPACE));
			
			if (array_key_exists('svn', $args)) {
				$versionControl = 'svn';
			} else {
				$versionControl = 'git';
			}

			$projectName = $args[0];
			$projectRepo = isset($args[1]) ? $args[1] : null;
			$strategyClassName = 'Golem_Cli_Command_BuildProject_Strategy_'.ucfirst(strtolower($versionControl));
			$strategy = new $strategyClassName($projectName, $projectRepo);
			return $strategy->build();
		}
	}


	/**
 	 * Help
 	 * @return Boolean
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' golem build <projectname> <repository>', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('For projects that use Subversion, add option --svn:');
		Garp_Cli::lineOut(' golem build <projectname> <repository> --svn', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		return true;
	}
}
