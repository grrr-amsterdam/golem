<?php
/**
 * Golem_GitHelper
 * Util for doing all kinds of fun Git stuff.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Golem
 */
class Golem_GitHelper {
	/**
 	 * The Git commands
 	 */
	const GIT_LS_CMD_BITBUCKET    = 'git ls-remote git@bitbucket.org:grrr/%s';
	const GIT_LS_CMD_GITHUB       = 'git ls-remote git@github.com:grrr-amsterdam/%s.git';
	const GIT_CLONE_CMD_BITBUCKET = 'git clone git@bitbucket.org:grrr/%s %s --recursive';
	const GIT_CLONE_CMD_GITHUB    = 'git clone git@github.com:grrr-amsterdam/%s.git %s --recursive';

	public function createCloneCmd($project, $destination = '', $repo = null) {
		if (!$repo) {
			return sprintf('git clone %s %s --recursive', $repo, $destination);
		}
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
