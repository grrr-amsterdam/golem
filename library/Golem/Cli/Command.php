<?php
/**
 * Golem_Cli_Command
 * Blueprint for all Golem CLI commands. Accepts Golem_Toolkit
 * as argument to the constructor, giving it access to Golem
 * environment information.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Golem_Cli
 */
class Golem_Cli_Command extends Garp_Cli_Command {

	/**
 	 * Golem Toolkit. It's full of wisdom.
 	 * @var Golem_Toolkit
 	 */
	protected $_toolkit;

	/**
 	 * Class constructor
 	 * @param Golem_Toolkit $toolkit
 	 * @return Void
 	 */
	public function __construct(Golem_Toolkit $toolkit) {
		$this->_toolkit = $toolkit;
	}

}
