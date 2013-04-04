<?php
/**
 * Golem_Cli_Command_Vhost
 * Manage vhosts
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Cli_Command
 */
class Golem_Cli_Command_Vhost extends Golem_Cli_Command {
	/**
 	 * Add new vhost. Note: this will also modify the hosts file.
 	 * @param Array $args
 	 * @return Boolean
 	 * @todo Maybe check if the vhost is already present?
 	 */
	public function add(array $args = array()) {
		$golemRc    = $this->_toolkit->getRc();
		$vhostsFile = $golemRc->getData(Golem_Rc::APACHE_VHOSTS_FILE);
		$hostsFile  = $golemRc->getData(Golem_Rc::HOSTS_FILE);
		$workspace  = $golemRc->getData(Golem_Rc::WORKSPACE);

		$project = isset($args[0]) ? $args[0] : Garp_Cli::prompt('Choose project');
		$localUrl = isset($args[1]) ? $args[1] : Garp_Cli::prompt('Choose local URL');

		$webroot = $workspace.DIRECTORY_SEPARATOR.$project.DIRECTORY_SEPARATOR.'public';

		// Modify vhosts file
		Garp_Cli::lineOut('Adding vhost tag for '.$project.'.');
		$vhostTag = $this->_getVhostTag($localUrl, $webroot);
		$vhostAppendCmd = $this->_getAppendCmd($vhostTag, $vhostsFile);
		$this->_sudoExec($vhostAppendCmd);

		// Modify hosts file
		Garp_Cli::lineOut('Adding '.$localUrl.' to hosts file.');
		$hostLine = $this->_getHostsDeclaration($localUrl);
		$hostLineAppendCmd = $this->_getAppendCmd($hostLine, $hostsFile);
		$this->_sudoExec($hostLineAppendCmd);

		// Restart Apache
		Garp_Cli::lineOut('Restarting Apache.');
		`sudo apachectl graceful`;
		Garp_Cli::lineOut('Done.');

		return true;
	}

	/**
 	 * Generate a shell append command
 	 * @param String $appendix
 	 * @param String $targetFile
 	 * @return String
 	 */
	protected function _getAppendCmd($appendix, $targetFile) {
		return 'echo '.escapeshellarg($appendix).' >> '.$targetFile;
	}


	/**
 	 * Get vhost code
 	 * @param String $localUrl
 	 * @param String $webroot
 	 * @return String
 	 */
	protected function _getVhostTag($localUrl, $webroot) {
		$template  = "\n<VirtualHost *:80>\n";
		$template .= "ServerName %s\n";
		$template .= "DocumentRoot %s\n";
		$template .= "</VirtualHost>";

		$vhostCode = sprintf($template, $localUrl, $webroot);
		return $vhostCode;
	}		


	/**
 	 * Get hosts file declaration
	 * @param String $url description
	 * @return String
	 */
	protected function _getHostsDeclaration($url) {
		$hostsEntry = "127.0.0.1    $url";
		return $hostsEntry;
	}

	/**
 	 * An eloborate way to write to files that need sudo.
 	 * First we write the write command into a temporary file. Then we sudo execute that file.
 	 * Presto!
 	 * @param String $code The code to execute as sudo
 	 * @return String The shell response
 	 */
	protected function _sudoExec($code) {
		$code = '#!/bin/bash'."\n".$code;
		$tmpFilePath = GOLEM_APPLICATION_PATH.'/../scripts/temp.sh';
		// create temp file that's going to execute the code
		file_put_contents($tmpFilePath, $code);
		// sudo execute the file
		$sudoCmd = 'sudo sh '.$tmpFilePath;
		$shellResponse = `$sudoCmd`;
		// clean up temp file
		unlink($tmpFilePath);
		return $shellResponse;
	}

	/**
 	 * Help
 	 * @return Boolean
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' golem vhost add <projectname> <local-url>', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		return true;
	}
}
