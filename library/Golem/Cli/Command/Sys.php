<?php
/**
 * Golem_Cli_Command_Sys
 * The Sys Golem CLI command. For system configuration.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Sys extends Golem_Cli_Command {
	/**
 	 * Configure .golemrc
 	 * @param Array $args 
 	 * @return Boolean
 	 */
	public function configure(array $args = array()) {
		// Devs can pass wether they wish to configure all values, or only missing ones.
		$all = array_key_exists(0, $args) ? strtolower($args[0]) == 'all' : true;
		$golemRc = $this->_toolkit->getRc();

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
		Garp_Cli::lineOut('For help, run ', null, false);
		Garp_Cli::lineOut('golem help ', Garp_Cli::BLUE, false);
		Garp_Cli::lineOut('');
		return true;
	}

	/**
 	 * Add a project to your workspace
 	 * @return Boolean
 	 */
	public function checkout(array $args = array()) {
		$project = $args[0];
		return true;
	}

	/**
 	 * Help those poor devs
 	 * @return Boolean
 	 */
	public function help() {
		$this->_welcome();

		/**
 		 * @todo Wanneer je al in een project folder zit is onderstaande overbodig natuurlijk.
 		 */
		$projects = $this->_toolkit->getProjects();		
		Garp_Cli::lineOut('Here are your Garp projects:');
		foreach ($projects as $project) {
			Garp_Cli::lineOut(' - '.$project);
		}
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('You can prepend your commands with the project name in order to execute them in the context of that project. For example:');
		Garp_Cli::lineOut('golem '.$projects[0].' admin add', Garp_Cli::BLUE);
		Garp_Cli::lineOut('Alternatively, execute a system-wide command:');
		Garp_Cli::lineOut('golem checkout grrr.nl', Garp_Cli::BLUE);
		return true;
	}

	/**
 	 * Just to see if command routing works.
 	 * @return Boolean
 	 */
	public function test() {
		Garp_Cli::lineOut('Golem reached method '.__METHOD__);
		return true;
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
