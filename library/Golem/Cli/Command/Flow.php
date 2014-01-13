<?php
/**
 * Golem_Cli_Command_Flow
 * Git-flow shortcuts
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.0.1
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Flow extends Golem_Cli_Command {

	/**
 	 * Start a new release branch
 	 */
	public function startRelease(array $args = array()) {
		// Sanity check: do you have the right tools for the job?
		if (!$this->_required_tools_available()) {
			return false;
		}

		$this->_bump_version($args);
		$version = $this->_get_current_version();

		// Stash cause we can't start the release until the git index is clean
		$git_stash_cmd = 'git stash';
		$this->_exec_cmd($git_stash_cmd);

		$git_flow_start_release_cmd = 'git flow release start ' . $version;
		$this->_exec_cmd($git_flow_start_release_cmd);

		$git_stash_pop_cmd = 'git stash pop';
		$this->_exec_cmd($git_stash_pop_cmd);

		$git_add_cmd = 'git add .semver';
		$this->_exec_cmd($git_add_cmd);

		// Commit semver
		$git_ci_cmd  = 'git commit -m "Incremented version to ' . $version . '."';
		$this->_exec_cmd($git_ci_cmd);
		return true;
	}

	/**
 	 * Finish current git flow release
 	 */
	public function finishRelease() {
		$version = $this->_get_current_version();
		$branch = $this->_get_current_branch();
		$prefix = $this->_get_gitflow_prefix('release');
		if ($branch != $prefix . $version) {
			Garp_Cli::errorOut("I'm sorry, you're not on the (right) release branch.");
			Garp_Cli::lineOut("Expected branch: $prefix$version");
			Garp_Cli::lineOut("Got: $branch");
			Garp_Cli::lineOut("Please start the release first using");
			Garp_Cli::lineOut(' g release start', Garp_Cli::BLUE);
			return false;
		}
		Garp_Cli::lineOut('Finishing release ' . $version);
		$git_flow_finish_release_cmd = 'git flow release finish -m "Release_' . $version . '" ' . $version;
		$this->_exec_cmd($git_flow_finish_release_cmd);
		return true;
	}

	public function hotfix(array $args = array()) {
		
	}

	public function feature(array $args = array()) {
		
	}

	/**
 	 * Execute the given command
 	 */
	protected function _exec_cmd($cmd) {
		return shell_exec($cmd);
	}

	/**
 	 * Bump semver
 	 * @param Array $args Original arguments to the cli command. Might contain type of version increase.
 	 * @return Void
 	 */
	protected function _bump_version(array $args = array()) {
		// Init semver (if semver is already initialized it's no problem, just ignore the output)
		$this->_exec_cmd('semver init');

		// Can be minor, major, or 'special'
		$type = isset($args[0]) ? $args[0] : 'minor';
		$semver_cmd = "semver inc ";
		if (!in_array($type, array('minor', 'major'))) {
			$semver_cmd .= "special ";
		}
		$semver_cmd .= $type;
		$this->_exec_cmd($semver_cmd);
	}		

	/**
 	 * Get current semver
 	 * @return String 
 	 */
	protected function _get_current_version() {
		$version = $this->_exec_cmd('semver tag');
		$version = trim($version);
		return $version;
	}		

	/**
 	 * Get current Git branch
 	 * @return String
 	 */
	protected function _get_current_branch() {
		$branches = $this->_exec_cmd('git branch');
		$branches = explode("\n", $branches);
		$branches = preg_grep('/^\*/', $branches);
		if (!count($branches)) {
			return null;
		}
		// there can be only one
		$branch = current($branches);
		$branch = preg_replace('/^\*\s+/', '', $branch);
		$branch = trim($branch);
		return $branch;
	}

	/**
 	 * Get the configured Git-flow prefix
 	 */
	protected function _get_gitflow_prefix($category) {
		$prefix = $this->_exec_cmd("git config gitflow.prefix.$category");
		$prefix = trim($prefix);
		return $prefix;
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

}
