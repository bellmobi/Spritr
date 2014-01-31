<?php
class FileAlterationMonitor {
	private $scanFolder, $initialFoundFiles;

	public function __construct($scanFolder) {
		$this -> scanFolder = $scanFolder;
		$this -> updateMonitor();
	}

	private function _arrayValuesRecursive($array) {
		$arrayValues = array();

		foreach ($array as $value) {
			if (is_scalar($value) OR is_resource($value)) {
				$arrayValues[] = $value;
			} elseif (is_array($value)) {
				$arrayValues = array_merge($arrayValues, $this -> _arrayValuesRecursive($value));
			}
		}

		return $arrayValues;
	}

	private function _scanDirRecursive($directory) {
		$folderContents = array();
		$directory = realpath($directory) . DIRECTORY_SEPARATOR;

		foreach (scandir($directory) as $folderItem) {
			if ($folderItem != "." AND $folderItem != "..") {
				if (is_dir($directory . $folderItem . DIRECTORY_SEPARATOR)) {
					$folderContents[$folderItem] = $this -> _scanDirRecursive($directory . $folderItem . "\\");
				} else {
					$folderContents[] = $folderItem;
				}
			}
		}

		return $folderContents;
	}

	public function getNewFiles() {
		$finalFoundFiles = $this -> _arrayValuesRecursive($this -> _scanDirRecursive($this -> scanFolder));

		if ($this -> initialFoundFiles != $finalFoundFiles) {
			$newFiles = array_diff($finalFoundFiles, $this -> initialFoundFiles);
			return empty($newFiles) ? FALSE : $newFiles;
		}
	}

	public function getRemovedFiles() {
		$finalFoundFiles = $this -> _arrayValuesRecursive($this -> _scanDirRecursive($this -> scanFolder));

		if ($this -> initialFoundFiles != $finalFoundFiles) {
			$removedFiles = array_diff($this -> initialFoundFiles, $finalFoundFiles);
			return empty($removedFiles) ? FALSE : $removedFiles;
		}
	}

	public function updateMonitor($initialFoundFiles = array()) {
		$this -> initialFoundFiles = array();
		if (!empty($initialFoundFiles)) {
			$this -> initialFoundFiles = $initialFoundFiles;
		} else {
			$this -> initialFoundFiles = $this -> _arrayValuesRecursive($this -> _scanDirRecursive($this -> scanFolder));
		}
		return $this -> initialFoundFiles;
	}

}
?>