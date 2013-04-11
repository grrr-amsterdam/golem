<?php
/**
 * Golem_Content_Db_Server_Factory
 * Produces a Golem_Content_Db_Server_Local or Golem_Content_Db_Server_Remote instance.
 */
class Golem_Content_Db_Server_Factory {

	/**
	 * @param String $environment 		The environment id, f.i. 'development' or 'production'.
	 * @param String $otherEnvironment 	The environment of the counterpart server
	 * 									(i.e. target if this is source, and vice versa).
	 */
	public static function create($environment, $otherEnvironment) {
		if ($environment === 'development') {
			return new Golem_Content_Db_Server_Local($environment, $otherEnvironment);
		} else {
			return new Golem_Content_Db_Server_Remote($environment, $otherEnvironment);
		}
	}
}