<?php
/**
 * Golem_Content_Upload_FileList
 * You can use an instance of this class as a numeric array, containing Golem_Content_Upload_FileNode elements.
 *
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Golem_Content_Upload_FileList extends ArrayObject {
	/**
	 * @param String $filename 	The filename, f.i. 'pussy.gif'
	 * @param String $type		The upload type, i.e. 'document' or 'image'.
	 */
	public function addEntry(Golem_Content_Upload_FileNode $file) {
		if ($file->isValid()) {
			$this[] = $file;
		}
	}
	
	/**
	 * @param Golem_Content_Upload_FileList $files List of Golem_Content_Upload_FileNode elements.
	 */
	public function addEntries(Golem_Content_Upload_FileList $files) {
		foreach ($files as $file) {
			$this->addEntry($file);
		}
	}
	
	/**
	 * @return Golem_Content_Upload_FileList Intersecting files, matching filenames and types
	 */
	public function findIntersecting(Golem_Content_Upload_FileList $thoseFiles) {
		$intersecting = new Golem_Content_Upload_FileList();

		foreach ($this as $thisFile) {
			foreach ($thoseFiles as $thatFile) {
				if ($thisFile == $thatFile) {
					$intersecting->addEntry($thisFile);
				}
			}
		}
		
		return $intersecting;
	}

	/**
	 * @param	Golem_Content_Upload_FileList $thoseFiles	The file list to check for corresponding files
	 * @return 	Golem_Content_Upload_FileList 				Files that are unique and do not exist in $thoseFiles
	 */
	public function findUnique(Golem_Content_Upload_FileList $thoseFiles) {
		$unique = new Golem_Content_Upload_FileList();

		foreach ($this as $thisFile) {
			if (!$thoseFiles->nodeExists($thisFile)) {
				$unique->addEntry($thisFile);
			}
		}

		return $unique;
	}
	
	/**
	 * @return 	Bool	Whether the file node exists in this list
	 */
	public function nodeExists(Golem_Content_Upload_FileNode $thatFile) {
		foreach ($this as $thisFile) {
			if ($thisFile == $thatFile) {
				return true;
			}
		}
		
		return false;
	}
}