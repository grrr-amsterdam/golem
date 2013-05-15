<?php
/**
 * Golem_Cli_Command_Update
 * Updates the Golem revision.
 *
 * @author       David Spreekmeester | grrr.nl
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Update extends Golem_Cli_Command {
	const MSG_UP_TO_DATE = 'Golem is up to date.';


	/**
	 * Central start method
	 * @param Array $args Various options. Must contain;
	 * @return Boolean
	 */
	public function main(array $args = array()) {
		if ($this->_helpWasRequested($args)) {
			$this->_help();
			return;
		}

		$this->_update();
	}

	protected function _update() {
		$golemPath 	= GOLEM_APPLICATION_PATH . DIRECTORY_SEPARATOR . '..';
		$command 	= 'git pull --recurse-submodules';

		chdir($golemPath);
		$lastLine = exec($command);
		Garp_Cli::lineOut($lastLine);
	}

	/**
 	 * Help
 	 * @return Boolean
 	 */
	protected function _help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' golem update', Garp_Cli::BLUE);
	}
	
	protected function _helpWasRequested(array $args) {
		return
			array_key_exists(0, $args) &&
			strcasecmp($args[0], 'help') === 0
		;
	}
}
