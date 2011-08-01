<?php

class ImporterController extends AuthenticationController {
	protected $Importer;
	
	public function getFields() {
		return array();
	}
	
	public function setup($void = NULL) {
		if (is_null($this->getConfiguration('pathModeling'))) {
			throw new FatalError('Modeling path not set');
		}
		
		$this->setImporter(new Importer($this->getConfiguration()));
	}
	
	protected function getImportFiles($path = NULL) {
		if (is_null($path)) {
			$path = str_replace('\\', '/', $this->getConfiguration('pathModeling'));
		}
		
		$extension = $this->getConfiguration('fileExtensionModeling');
		
		$files = array();
		$directory = dir($path);
		while (($file = $directory->read()) !== FALSE) {
			if (in_array($file, $this->getConfiguration('ignoreFilesModeling'))) continue;
			$pathFile = $path . $file;
			if (is_file($pathFile) && substr($file, -(strlen($extension))) == $extension) {
				$files[$pathFile] = strstr($file, $extension, TRUE);
			} else if (is_dir($pathFile)) {
				$files = array_merge($files, $this->getImportFiles($pathFile . '/'));
			}
		}
		$directory->close();
		
		if ($this->getConfiguration('sortByFileName')) {
			ksort($files);
		} else {
			asort($files);
		}
		
		return $files;
	}
	
	protected function updateImportFiles($sourcePath = NULL, $path = NULL) {
		if (is_null($sourcePath)) {
			$sourcePath = str_replace('\\', '/', $this->getConfiguration('sourcePathModeling'));
		}
		if (is_null($path)) {
			$path = str_replace('\\', '/', $this->getConfiguration('pathModeling'));
		}
		
		$extension = $this->getConfiguration('fileExtensionModeling');
		
		$files = array();
		$directory = dir($sourcePath);
		while (($file = $directory->read()) !== FALSE) {
			if (in_array($file, $this->getConfiguration('ignoreFilesModeling'))) continue;
			$sourcePathFile = $sourcePath . $file;
			$pathFile = $path . $file;
			if (is_file($sourcePathFile) && substr($file, -(strlen($extension))) == $extension) {
				if (copy($sourcePathFile, $pathFile)) {
					$files[$pathFile] = strstr($file, $extension, TRUE);
				}
			} else if (is_dir($sourcePathFile)) {
				$files = array_merge($files, $this->updateImportFiles($sourcePathFile . '/'));
			}
		}
		$directory->close();
		
		return $files;
	}
	
	public function index() {
		if ($this->getRequest()->getData('submit')) {
			return $this->import();
		}
		
		return $this->displayView('Importer.index.php', array(
			'Coachings' => $this->getImportFiles()
		));
	}
	
	public function import() {
		$this->setup();
		
		if (!$keys = $this->getRequest()->getData('keys')) {
			$keys = $this->getImportFiles($this->getConfiguration('approvedPathModeling'));
		}
		
		$this->getImporter()->run($keys);
		
		return $this->displayView('Importer.import.php', array(
			'Coachings' => $this->getImporter()->getCoachings()
		));
	}
	
	public function update() {
		$this->updateImportFiles();
		$this->getMessageHandler()->setMessage('Import files successfully updated.');
		
		return $this->redirect('index');
	}
}