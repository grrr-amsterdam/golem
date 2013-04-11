<?php
/**
 * Golem_Content_Upload_Mediator
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Golem_Content_Upload_Mediator {

	/**
	 * Golem_Content_Upload_Storage_Protocol $source
	 */
	protected $_source;

	/**
	 * Golem_Content_Upload_Storage_Protocol $target
	 */
	protected $_target;


	/**
	 * @param String $sourceEnv
	 * @param String $targetEnv
	 */
	public function __construct($sourceEnv, $targetEnv) {
		$this->setSource($sourceEnv);
		$this->setTarget($targetEnv);
	}


	/**
	 * @param String $environment
	 */
	public function setSource($environment) {
		$this->_source = Golem_Content_Upload_Storage_Factory::create($environment);
	}


	/**
	 * @param String $environment
	 */
	public function setTarget($environment) {
		$this->_target = Golem_Content_Upload_Storage_Factory::create($environment);
	}
	
	
	/**
	 * Finds out which files should be transferred.
	 * @return Golem_Content_Upload_FileList List of file paths that should be transferred from source to target.
	 */
	public function fetchDiff() {
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();		
		$diffList = new Golem_Content_Upload_FileList();

		$sourceList = $this->_source->fetchFileList();
		$targetList = $this->_target->fetchFileList();

		$progress->display("Looking for new files");
		$newFiles = $this->_findNewFiles($sourceList, $targetList);

		$progress->display("Looking for conflicting files");
		$conflictingFiles = $this->_findConflictingFiles($sourceList, $targetList);

		$diffList->addEntries($newFiles);
		$diffList->addEntries($conflictingFiles);

		return $diffList;
	}
	
	
	public function transfer(Golem_Content_Upload_FileList $fileList) {
		foreach ($fileList as $file) {
			$this->_transferSingle($file);
		}
	}
	
	protected function _transferSingle(Golem_Content_Upload_FileNode $file) {
		$progress 	= Garp_Cli_Ui_ProgressBar::getInstance();
		$filename 	= $file->getFilename();
		$type 		= $file->getType();

		$progress->display("Fetching {$filename}");
		$data = $this->_fetchSourceData($filename, $type);
		$progress->advance();
			
		if (!$data) {
			$progress->advance();
			continue;
		}

		$progress->display("Uploading {$filename}");			
		if (!$this->_target->store($filename, $type, $data)) {
			throw new Exception(
				"Could not store {$type} {$filename} on " 
				. $this->_target->getEnvironment()
			);
		}
		$progress->advance();
	}
	
	protected function _fetchSourceData($filename, $type) {
		try {
			$fileData = $this->_source->fetchData($filename, $type);
			return $fileData ?: false;

		} catch (Exception $e) {}
		
		return false;
	}
	
	
	/**
	 * @return Array Numeric array of files, with keys 'filename' and 'type'.
	 */
	protected function _findNewFiles(Golem_Content_Upload_FileList $sourceList, Golem_Content_Upload_FileList $targetList) {
		$unique = $sourceList->findUnique($targetList);
		return $unique;
	}


	/**
	 * @return Golem_Content_Upload_FileList Conflicting files
	 */
	protected function _findConflictingFiles(Golem_Content_Upload_FileList $sourceList, Golem_Content_Upload_FileList $targetList) {
		$existingFiles 		= $sourceList->findIntersecting($targetList);
		$conflictingFiles 	= $this->_findConflictingFilesByEtag($existingFiles);

		return $conflictingFiles;
	}

	protected function _findConflictingFilesByEtag(Golem_Content_Upload_FileList $conflictsByName) {
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();
		$progress->init(count($conflictsByName));

		$conflictsByEtag = new Golem_Content_Upload_FileList();
		
		foreach ($conflictsByName as $file) {
			$this->_addEtagConflictingFile($conflictsByEtag, $file);
		}

		return $conflictsByEtag;
	}

	/**
	 * @param	Golem_Content_Upload_FileList	&$conflictsByEtag	A reference to the list that a matching entry should be added to
	 * @param	Golem_Content_Upload_FileNode	$file	The current file node within the Golem_Content_Upload_FileList
	 * @return 	Void
	 */
	protected function _addEtagConflictingFile(Golem_Content_Upload_FileList &$conflictsByEtag, Golem_Content_Upload_FileNode $file) {
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();
		$filename	= $file->getFilename();
		$progress->display("Comparing {$filename}");

		if (!$this->_matchEtags($file)) {
			$conflictsByEtag->addEntry($file);
		}

		$progress->advance();
	}
	
	
	protected function _matchEtags(Golem_Content_Upload_FileNode $file) {
		$filename	= $file->getFilename();
		$type		= $file->getType();

		$sourceEtag = $this->_source->fetchEtag($filename, $type);
		$targetEtag = $this->_target->fetchEtag($filename, $type);

		return $sourceEtag == $targetEtag;
	}

}
