<?php
/**
 * Golem_Cli_Command_Hotfix
 * Start and finish a new hotfix.
 * Basically a wrapper around git flow and semver.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.0.1
 * @package      Golem_Cli_Command_Hotfix
 */
class Golem_Cli_Command_Hotfix extends Golem_Cli_Command_Flow {

	/**
 	 * Start a git flow hotfix
 	 */
	public function start($args) {
		return $this->startHotfix($args);
	}

	/**
 	 * Finish a git flow hotfix
 	 */
	public function finish($args) {
		return $this->finishHotfix($args);
	}

	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' g hotfix start', Garp_Cli::BLUE);
		Garp_Cli::lineOut(' g hotfix finish', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Note: this requires the git flow and semver commandline utilities.');
	}

}
