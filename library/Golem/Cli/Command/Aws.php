<?php
/**
 * Golem_Cli_Command_S3
 * Wrapper around awscmd
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Aws extends Golem_Cli_Command {
	const AWS_CONFIG_LOCATION = '.aws/config';

	/**
 	 * The profile used by the current environment
 	 */
	protected $_profile;

	/**
 	 * Set and/or create profile in awscmd.
 	 * Also check wether this project actually uses Amazon.
 	 */
	public function main(array $args = array()) {
		if (!$this->_usesAmazon()) {
			Garp_Cli::errorOut('Clearly this environment does not ' .
				'use Amazon services. Get outta here!');
			return false;
		}

		$this->_setProfile();

		if (!$this->_profileExists()) {
			$this->_createProfile();
		}
		return parent::main($args);
	}

	/**
 	 * Execute s3 methods
 	 * @param String $cmd The command (for instance 'ls')
 	 * @param Array $args Various arguments
 	 * return Void
 	 */
	public function s3($cmd, $args) {
		$this->_exec('s3', $cmd, $args);
	}

	/** Display help */
	public function help() {
		Garp_Cli::lineOut('This is a wrapper around Amazon awscmd.', Garp_Cli::YELLOW);
		Garp_Cli::lineOut("Make sure it's installed on your machine (go to http://aws.amazon.com/cli/ for instructions)");
		Garp_Cli::lineOut('Note that this wrapper manages your awscmd profiles for you.');
	}

	/**
 	 * Execute awscmd function
 	 * @param String $group For instance s3, or ec2
 	 * @param String $subCmd For instance 'ls' or 'cp'
 	 * @param Array $args Further commandline arguments
 	 */
	protected function _exec($group, $subCmd, $args) {
		$cmd = "aws $group $subCmd ";
		$cmd .= implode(' ', $args);
		$cmd .= ' --profile=' . $this->_profile;
		passthru($cmd);
	}

	/** Set the current profile */
	protected function _setProfile() {
		$projectName = $this->_toolkit->getCurrentProject();
		$profileName = $projectName . '_' . APPLICATION_ENV;

		$this->_profile = $profileName;
	}

	/** Check if the current profile exists */
	protected function _profileExists() {
		$homeDir = trim(`echo \$HOME`);
		$config = file_get_contents($homeDir . DIRECTORY_SEPARATOR . self::AWS_CONFIG_LOCATION);
		return strpos($config, "[profile {$this->_profile}]") !== false;
	}

	/** Create the currently used profile */
	protected function _createProfile() {
		$config = Zend_Registry::get('config');

		$confStr = "[profile {$this->_profile}]\n";
		$confStr .= "aws_access_key_id = {$config->cdn->s3->apikey}\n";
		$confStr .= "aws_secret_access_key = {$config->cdn->s3->secret}\n";
		$confStr .= "output = json\n\n";

		$homeDir = trim(`echo \$HOME`);
		file_put_contents($homeDir . DIRECTORY_SEPARATOR . self::AWS_CONFIG_LOCATION, $confStr, FILE_APPEND);
	}

	/** Check wether environment actually uses Amazon */
	protected function _usesAmazon() {
		$config = Zend_Registry::get('config');
		return !empty($config->cdn->s3->apikey) && !empty($config->cdn->s3->secret);
	}
}
