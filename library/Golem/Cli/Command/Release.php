<?php
/**
 * Golem_Cli_Command_Release
 * Start and finish a new release.
 * Basically a wrapper around git flow and semver.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.0.1
 * @package      Golem_Cli_Command_Release
 */
class Golem_Cli_Command_Release extends Golem_Cli_Command {

	/**
 	 * Types of version bump implemented by semver
 	 * @var Array
 	 */
	protected $_allowed_version_bumps = array('patch', 'minor', 'major');

	/**
 	 * Start a git flow release
 	 */
	public function start($args) {
		// Sanity check: do you have the right tools for the job?
		if (!$this->_required_tools_available()) {
			return false;
		}

		// Init semver (if semver is already initialized it's no problem, just ignore the output)
		$this->_exec_cmd('semver init');

		$type = isset($args[0]) ? $args[0] : 'patch';
		$semver_cmd = "semver inc ";
		if (!in_array($type, $this->_allowed_version_bumps)) {
			$semver_cmd .= "special ";
		}
		$semver_cmd .= $type;
		$this->_exec_cmd($semver_cmd);

		// Save version for use in the git flow release start command
		$version = $this->_exec_cmd('semver tag');
		$version = trim($version);

		// Stash cause we can't start the release until the git index is clean
		$git_stash_cmd = 'git stash';
		$this->_exec_cmd($git_stash_cmd);

		$git_flow_start_release_cmd = 'git flow release start ' . $version;
		$this->_exec_cmd($git_flow_start_release_cmd);

		$git_stash_pop_cmd = 'git stash pop';
		$this->_exec_cmd($git_stash_pop_cmd);

		$git_add_cmd = 'git add .semver';
		$this->_exec_cmd($git_add_cmd);

		$git_ci_cmd  = 'git commit -m "Incremented version to ' . $version . '."';
		$this->_exec_cmd($git_ci_cmd);

	}

	/**
 	 * Finish a git flow release
 	 */
	public function finish($args) {
		$version = $this->_exec_cmd('semver tag');
		$version = trim($version);
		$git_flow_finish_release_cmd = 'git flow release finish -m "Release_' . $version . '" ' . $version;
		$this->_exec_cmd($git_flow_finish_release_cmd);
	}

	/**
 	 * Execute the given command
 	 */
	protected function _exec_cmd($cmd) {
		return shell_exec($cmd);
	}

	/**
 	 * Check if semver and git flow are installed
 	 */
	protected function _required_tools_available() {
		$semver_checker = shell_exec('which semver');
		if (empty($semver_checker)) {
			Garp_Cli::errorOut('semver is not installed');
			Garp_Cli::lineOut('Install like this:');
			Garp_Cli::lineOut(' gem install semver', Garp_Cli::BLUE);
			return false;
		}
		$gitflow_checker = shell_exec('which git-flow');
		if (empty($gitflow_checker)) {
			Garp_Cli::errorOut('git-flow is not installed');
			Garp_Cli::lineOut('Get it from brew');
			Garp_Cli::lineOut(' brew install git-flow', Garp_Cli::BLUE);
			return false;
		}
		return true;
	}

	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' g release start', Garp_Cli::BLUE);
		Garp_Cli::lineOut(' g release finish', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Determine the type of version bump: (default is patch)');
		Garp_Cli::lineOut(' g release start major|minor|patch', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Note: this requires the git flow and semver commandline utilities.');
	}

}
