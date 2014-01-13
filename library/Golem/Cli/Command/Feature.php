<?php
/**
 * Golem_Cli_Command_Feature
 * Start and finish a new feature.
 * Basically a wrapper around git flow and semver.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.0.1
 * @package      Golem_Cli_Command_Feature
 */
class Golem_Cli_Command_Feature extends Golem_Cli_Command_Flow {

	/**
 	 * Start a git flow feature
 	 */
	public function start($args) {
		return $this->startFeature($args);
	}

	/**
 	 * Finish a git flow feature
 	 */
	public function finish($args) {
		return $this->finishFeature($args);
	}

	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' g feature start discombobulator', Garp_Cli::BLUE);
		Garp_Cli::lineOut(' g feature finish', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Note: this requires the git flow and semver commandline utilities.');
	}

}
