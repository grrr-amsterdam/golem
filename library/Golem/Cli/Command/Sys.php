<?php
/**
 * Golem_Cli_Command_Sys
 * The Sys Golem CLI command. For system configuration.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Sys extends Garp_Cli_Command {
	/**
 	 * Relative location of .golemrc
 	 * @var String
 	 */
	const GOLEMRC = '/data/.golemrc';

	/**
 	 * Configure .golemrc
 	 * @param Array $args 
 	 * @return Void
 	 */
	public function configure(array $args = array()) {
		// Devs can passing wether they wish to configure all values, or only missing ones.
		$all = array_key_exists('all', $args) ? $args['all'] : true;

		$golemRc = new Golem_Rc(APPLICATION_PATH.self::GOLEMRC);

		$this->_welcome();
		Garp_Cli::lineOut('Some required data is missing.');
		Garp_Cli::lineOut('In order to setup your environment, I\'m going to ask you a few simple questions.');
		Garp_Cli::lineOut('Defaults are shown in parentheses. Just answer blank if you wish to use the default.');
		Garp_Cli::lineOut('');
		if ($all) {
			$golemRc->askForData();
		} else {
			$golemRc->askForMissingData();
		}
		Garp_Cli::lineOut('');
		if ($golemRc->write()) {
			Garp_Cli::lineOut('Your settings have been saved. If you ever want to reconfigure, simply run ', null, false);
			Garp_Cli::lineOut('golem Sys configure', Garp_Cli::BLUE);
		} else {
			Garp_Cli::errorOut('There was trouble saving your .golemrc file. Make sure '.APPLICATION_PATH.'/data/ is writable.');
			Garp_Cli::lineOut('For now we\'ll continue with uncached data. Next time you will have to configure golem again.');
		}
	}

	/**
 	 * Welcome our guests.
 	 * @return Void
 	 */
	protected function _welcome() {
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('WELCOME TO GOLEM!', Garp_Cli::GREEN);
	}		
}
