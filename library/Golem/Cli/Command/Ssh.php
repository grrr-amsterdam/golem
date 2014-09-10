<?php
/**
 * Golem_Cli_Command_Ssh
 * Connects to server
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Ssh extends Golem_Cli_Command {
		
	public function staging() {
		$this->_openConnection('staging');
	}

	public function production() {
		$this->_openConnection('production');
	}

	public function _openConnection($environment) {
		$config = new Garp_Deploy_Config();

		$params = $config->getParams($environment);

		if (!$params) {
			Garp_Cli::errorOut(
				'No settings found for environment ' . $environment
			);
		}

		if (empty($params['server']) || empty($params['user'])) {
			Garp_Cli::errorOut(
				"'server' and 'user' are required settings."
			);
			return false;
		}

		passthru('ssh ' . escapeshellarg($params['user']) . 
			'@' . escapeshellarg($params['server']));
	}


	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' g ssh staging', Garp_Cli::BLUE);
		return true;
	}		
}
