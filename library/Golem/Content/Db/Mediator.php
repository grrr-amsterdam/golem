<?php
/**
 * Golem_Content_Db_Mediator
 * 
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Golem_Content_Db_Mediator {	
    /**
     * Singleton instance
     * @var Garp_Auth
     */
    private static $_instance = null;

	/**
	 * @var Golem_Content_Db_Server_*
	 */
	protected $_source;

	/**
	 * @var Golem_Content_Db_Server_*
	 */
	protected $_target;

	
	/**
	 * @param String $sourceEnv
	 * @param String $targetEnv
	 */
	public function __construct($sourceEnv, $targetEnv) {
		$this->setSource($sourceEnv, $targetEnv);
		$this->setTarget($targetEnv, $sourceEnv);
	}

	/**
	 * @param String $environment
	 */
	public function setSource($environment, $otherEnvironment) {
		$this->_source = Golem_Content_Db_Server_Factory::create($environment, $otherEnvironment);
	}

	/**
	 * @param String $environment
	 */
	public function setTarget($environment, $otherEnvironment) {
		$this->_target = Golem_Content_Db_Server_Factory::create($environment, $otherEnvironment);
	}

	public function getSource() {
		return $this->_source;
	}

	public function getTarget() {
		return $this->_target;
	}	
	

}