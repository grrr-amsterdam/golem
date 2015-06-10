<?php
/**
 * Garp_Content_Import_Excel
 * Import data from Excel
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Import_Excel extends Garp_Content_Import_Abstract {
	/**
	 * Return some sample data so an admin can provide
	 * mapping of columns by example.
	 * @return Array
	 */
	public function getSampleData() {
		$excelReader = $this->_getReader();
		$worksheet = $excelReader->getActiveSheet();
		$maxRows = 3;
		$out = array();

		foreach ($worksheet->getRowIterator() as $i => $row) {
			// workaround cause those PHPExcel assholes start their arrays at index 1
			$n = $i-1;
			if ($n >= $maxRows) {
				break;
			}

			$cellData = array();
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false);
			foreach ($cellIterator as $cell) {
				$cellData[] = $cell->getValue();
			}
			$out[] = $cellData;
		}
		return $out;
	}


	/**
	 * Insert data from importfile into database
	 * @param Garp_Model $model The imported data is for this model
	 * @param Array $mapping Mapping of import columns to table columns
	 * @param Array $options Various extra import options
	 * @return Boolean
	 */
	public function save(Garp_Model $model, array $mapping, array $options) {
		$excelReader = $this->_getReader();
		$worksheet = $excelReader->getActiveSheet();
		$pks = array();
		$iterator = $worksheet->getRowIterator();
		foreach ($iterator as $i => $row) {
			// workaround cause those PHPExcel assholes start their arrays at index 1
			$n = $i-1;
			if ($n < $options['firstRow']) {
				continue;
			}

			$cellData = array();
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false);
			foreach ($cellIterator as $j => $cell) {
				$cellData[] = $cell->getValue();
			}
			try {
 			 	// Sanity check: do not insert completely empty rows
				$check = implode('', $cellData);
				$check = trim($check);
				if (!$check) {
					continue;
				}
				$primaryKey = $this->_insert($model, $cellData, $mapping);
				$pks[] = $primaryKey;
			} catch (Exception $e) {
				if (!$options['ignoreErrors']) {
					$this->rollback($model, $pks);
				}
				throw $e;
			}
		}
		return true;
	}


	/**
	 * Insert a new row
	 * @param Garp_Model $model
	 * @param Array $cellData Collection of data
	 * @param Array $mapping Collection of column names
	 * @return Mixed primary key
	 */
	protected function _insert(Garp_Model $model, array $cellData, array $mapping) {
		if (count($cellData) !== count($mapping)) {
			throw new Exception(
				"Cannot create rowdata from these keys and values.\nKeys:".
				implode(', ', $mapping)."\n".
				"Values:".implode(', ', $cellData)
			);
		}
		$data = array_combine($mapping, $cellData);
		// ignored columns have an empty key
		unset($data['']);
		return $model->insert($data);
	}

	/**
	 * Return an Excel reader
	 * @return PHPExcel
	 */
	protected function _getReader() {
		require APPLICATION_PATH.'/../garp/library/Garp/3rdParty/PHPExcel/Classes/PHPExcel.php';

		$inputFileType = PHPExcel_IOFactory::identify($this->_importFile);
		// HTML is never correct. Just default to Excel2007
		// @todo Fix this. It should be able to determine the filetype correctly.
		if ($inputFileType === 'HTML') {
			$inputFileType = 'Excel2007';
		}
		$reader = PHPExcel_IOFactory::createReader($inputFileType);
		// we are only interested in cell values (not formatting etc.), so set readDataOnly to true
		// $reader->setReadDataOnly(true);
		$phpexcel = $reader->load($this->_importFile);

		return $phpexcel;
	}
}
