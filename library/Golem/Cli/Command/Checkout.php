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
 	 * The Git commands
 	 */
	const GIT_LS_CMD_BITBUCKET    = 'git ls-remote git@code.grrr.nl:grrr/%s';
	const GIT_LS_CMD_GITHUB       = 'git ls-remote git@github.com:grrr-amsterdam/%s.git';
	const GIT_CLONE_CMD_BITBUCKET = 'git clone git@code.grrr.nl:grrr/%s %s --recursive';
	const GIT_CLONE_CMD_GITHUB    = 'git clone git@github.com:grrr-amsterdam/%s.git %s --recursive';

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

		// First, figure out where the project lives.
		$cloneCmd = $this->_createCloneCmd($project, $destination);
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

	protected function _createCloneCmd($project, $destination) {
		if ($this->_projectLivesAtGithub($project)) {
			return sprintf(self::GIT_CLONE_CMD_GITHUB, $project, $destination);
		}
		if ($this->_projectLivesAtBitbucket($project)) {
			return sprintf(self::GIT_CLONE_CMD_BITBUCKET, $project, $destination);
		}
		return null;
	}

	protected function _projectLivesAtGithub($project) {
		return $this->_checkProjectExistence($project, self::GIT_LS_CMD_GITHUB);
	}

	protected function _projectLivesAtBitbucket($project) {
		return $this->_checkProjectExistence($project, self::GIT_LS_CMD_BITBUCKET);
	}

	protected function _checkProjectExistence($project, $cmd) {
		$lsCmd = sprintf($cmd, $project);
		$output = `$lsCmd 2>&1`;
		return strpos($output, 'Repository not found') === false;
	}
}
