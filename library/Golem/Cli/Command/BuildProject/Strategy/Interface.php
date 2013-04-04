<?php
/**
 * Golem_Cli_Command_BuildProject_Strategy_Interface
 * Interface for BuildProject strategies
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Golem_Cli_Command_BuildProject_Strategy
 */
interface Golem_Cli_Command_BuildProject_Strategy_Interface {
	/**
 	 * Class constructor
 	 * @param String $projectName
 	 * @param String $repository
 	 * @return Void
 	 */
	public function __construct($projectName, $repository = null);

	/**
 	 * Build all the things
 	 * @return Void
 	 */
	public function build();
}
