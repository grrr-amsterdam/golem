<?php
/**
 * Golem_Cli_Command_S3
 * Wrapper around awscmd, specific to s3 commands
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_S3 extends Golem_Cli_Command_Aws {

	public function ls() {
		$config = Zend_Registry::get('config');
		if (empty($config->cdn->s3->bucket)) {
			Garp_Cli::errorOut('No bucket configured');
			return false;
		}
		$args = array(
			's3://' . $config->cdn->s3->bucket
		);

		return $this->s3('ls', $args);
	}

}
