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
 	 * Overwrite to perform sanity checks
 	 */
	public function main(array $args = array()) {
		// Sanity check: do you have the right tools for the job?
		if (!$this->_required_tools_available()) {
			return false;
		}

		// Validate wether the git index is clean
		if (!$this->_validate_status()) {
			return false;
		}		

		return parent::main($args);
	}

	/**
 	 * Start a new release branch
 	 */
	public function startRelease(array $args = array()) {
		// Can be minor, major, or 'special'
		$type = isset($args[0]) ? $args[0] : 'minor';
		$this->_bump_version($type);
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
		if (!$this->_validate_branch('release', $version)) {
			return false;
		}
		$git_flow_finish_release_cmd = 'git flow release finish -m "Release_' . $version . '" ' . $version;
		passthru($git_flow_finish_release_cmd);
		return true;
	}

	/**
 	 * Start hotfix branch
 	 */
	public function startHotfix(array $args = array()) {
		$this->_bump_version('patch');
		$version = $this->_get_current_version();
		// Reset version cause we want to submit it only when finishing the hotfix
		$git_co_cmd = 'git checkout -- .semver';
		$this->_exec_cmd($git_co_cmd);

		$git_flow_start_release_cmd = 'git flow hotfix start ' . $version;
		$this->_exec_cmd($git_flow_start_release_cmd);
		return true;
	}

	/**
 	 * Finish hotfix branch
 	 */
	public function finishHotfix(array $args = array()) {
		$this->_bump_version('patch');
		$version = $this->_get_current_version();
		if (!$this->_validate_branch('hotfix', $version)) {
			// When shit hits the fan: revert semver
			$git_co_cmd = 'git checkout -- .semver';
			$this->_exec_cmd($git_co_cmd);
			return false;
		}

		$git_add_cmd = 'git add .semver';
		$this->_exec_cmd($git_add_cmd);

		// Commit semver
		$git_ci_cmd  = 'git commit -m "Incremented version to ' . $version . '."';
		$this->_exec_cmd($git_ci_cmd);

		$finish_hotfix_cmd = 'git flow hotfix finish -m "Hotfix_' . $version . '" ' . $version;
		passthru($finish_hotfix_cmd);
		return true;
	}

	/**
 	 * Execute the given command
 	 */
	protected function _exec_cmd($cmd) {
		return shell_exec($cmd);
	}

	/**
 	 * Bump semver
 	 * @param String $type Type of semver increment
 	 * @return Void
 	 */
	protected function _bump_version($type) {
		// Init semver (if semver is already initialized it's no problem, just ignore the output)
		$this->_exec_cmd('semver init');

		$semver_cmd = "semver inc ";
		if (!in_array($type, array('patch', 'minor', 'major'))) {
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
 	 *
 	 */
	protected function _validate_branch($type, $suffix) {
		$branch = $this->_get_current_branch();
		$prefix = $this->_get_gitflow_prefix($type);
		if ($branch == $prefix . $suffix) {
			return true;
		}
		Garp_Cli::errorOut("I'm sorry, you're not on the (right) $type branch.");
		Garp_Cli::lineOut("Expected branch: $prefix$suffix");
		Garp_Cli::lineOut("Got: $branch");
		return false;
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
 	 * Check if git status is clean. Return boolean accordingly.
 	 * @return Boolean
 	 */
	protected function _validate_status() {
		$st = $this->_exec_cmd('git status --porcelain');
		$st = trim($st);

		if (!$st) {
			return true;
		}

		Garp_Cli::errorOut("I can't proceed. Please clean your index first.");
		Garp_Cli::lineOut($st);
		return false;
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
		/*
			@todo REFACTOR LATER! Check fails on Ubuntu?
		$gitflow_checker = shell_exec('which git-flow');
		if (empty($gitflow_checker)) {
			Garp_Cli::errorOut('git-flow is not installed');
			Garp_Cli::lineOut('Get it from brew');
			Garp_Cli::lineOut(' brew install git-flow', Garp_Cli::BLUE);
			return false;
		}
		 */
		return true;
	}

}
