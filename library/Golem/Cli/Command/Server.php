<?php
/**
 * Golem_Cli_Command_Server
 * Create integration server
 *
 * @author       David Spreekmeester | grrr.nl
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Server extends Golem_Cli_Command {

	const HOST 						= 'ci.grrr.nl';
	const SSH_USER 					= 'garp-ci';
	const OWNER_GROUP				= 'www-data';
	const BASE_DOMAIN 				= 'integration.grrr.nl';
	const BASE_PATH					= '/var/www/';
	const VHOST_DEFINITIONS_PATH 	= '/etc/apache2/sites-available/';
	// const DATA_PATH		= '/current/application/data';
	// const DATA_PATH_PERMISSIONS	= '775';


	/**
	 * @var Resource $_sshSession
	 */
	protected $_sshSession;
	
	/**
	 * @return Resource
	 */
	public function getSshSession() {
		return $this->_sshSession;
	}
	
	/**
	 * @param Resource $sshSession
	 */
	public function setSshSession($sshSession) {
		$this->_sshSession = $sshSession;
	}	

	/**
 	 * Class constructor
 	 * @param Golem_Toolkit $toolkit
 	 * @return Void
 	 */
	public function __construct(Golem_Toolkit $toolkit) {
		parent::__construct($toolkit);
		
		$this->setSshSession($this->_OpenSshSession());
	}

	public function add() {
		$this->_createWwwDir();
		$this->_chgrpWwwDir();
		// $this->_chmodWwwDir();
		
		$this->_addVhostEntry();

		Garp_Cli::lineOut('Done.');
	}
	
	protected function _openSshSession() {
		$remoteSession 	= new Garp_Shell_RemoteSession(self::HOST, self::SSH_USER);
		
		return $remoteSession;
	}
	
	protected function _createWwwDir() {
		$wwwDir 	= $this->_getWwwDir();
		$sshSession = $this->getSshSession();

		$command = new Garp_Shell_Command_CreateDir($wwwDir);
		$command->executeRemotely($sshSession);
	}
	
	protected function _chgrpWwwDir() {
		$wwwDir 	= $this->_getWwwDir();
		$sshSession = $this->getSshSession();
		
		$command = new Garp_Shell_Command_Chgrp(self::OWNER_GROUP, $wwwDir);
		$command->executeRemotely($sshSession);
	}

	// protected function _chmodDataDir() {
	// 	$dataPath 	= $this->_getDataPath();
	// 	$sshSession = $this->getSshSession();
	// 	
	// 	$command = new Garp_Shell_Command_Chmod(self::DATA_PATH_PERMISSIONS, $dataPath);
	// 	$command->executeRemotely($sshSession);
	// }
	
	protected function _getWwwDir() {
		$projectId 	= $this->_getProjectId();
		$projectUrl = $this->_getProjectUrl();
		
		return self::BASE_PATH . $projectUrl;
	}
	
	// protected function _getDataPath() {
// 		$wwwDir = $this->_getWwwDir();
// 		
// 		return $wwwDir . self::DATA_PATH;
// 	}
	
	protected function _getProjectId() {
		$projectId 	= $this->_toolkit->getCurrentProject();
		
		return $projectId;
	}
	
	protected function _getProjectUrl() {
		$projectId 	= $this->_getProjectId();
		$projectUrl = $projectId . '.' . self::BASE_DOMAIN;

		return $projectUrl;
	}
	
	protected function _getVhostDefinitionPath() {
		$projectUrl = $this->_getProjectUrl();
		$path		= self::VHOST_DEFINITIONS_PATH . $projectUrl;
		
		return $path;
	}
	
	protected function _addVhostEntry() {
		$definition = $this->_renderVhostDefinition();
		$path		= $this->_getVhostDefinitionPath();
		$sshSession = $this->getSshSession();
		
		$command = new Garp_Shell_Command_WriteStringToFile($definition, $path);
		$command = new Garp_Shell_Command_Decorator_Sudo($command);
		$command->executeRemotely($sshSession);
	}
	
	protected function _renderVhostDefinition() {
		$projectUrl = $this->_getProjectUrl();
		$wwwDir		= $this->_getWwwDir();
		
		$vhostLines = array(
			"<VirtualHost *:80>"
		    . "\tServerName  {$projectUrl}"
			. "\tDocumentRoot {$wwwDir}"
			. "</VirtualHost>"
		);
		
		return implode("\n", $vhostLines);
	}

	/**
 	 * Help
 	 * @return Boolean
 	 */
	public function help() {
		Garp_Cli::lineOut('This command sets up an integration server.');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' golem server add', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		return true;
	}	

}