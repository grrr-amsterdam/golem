<?php
/**
 * Golem_Cli_Command_Release
 * Start and finish a new release.
 * Basically a wrapper around git flow and semver.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.0.1
 * @package      Golem_Cli_Command_Release
 */
class Golem_Cli_Command_Release extends Golem_Cli_Command_Flow {

	/**
 	 * Start a git flow release
 	 */
	public function start($args) {
		return $this->startRelease($args);
	}

	/**
 	 * Finish a git flow release
 	 */
	public function finish($args) {
		return $this->finishRelease($args);
	}

	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' g release start', Garp_Cli::BLUE);
		Garp_Cli::lineOut(' g release finish', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Determine the type of version bump: (default is minor)');
		Garp_Cli::lineOut(' g release start major|minor', Garp_Cli::BLUE);
		Garp_Cli::lineOut('Or do a "special" release:');
		Garp_Cli::lineOut(' g release start "beta"');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Note: this requires the git flow and semver commandline utilities.');
	}

}
