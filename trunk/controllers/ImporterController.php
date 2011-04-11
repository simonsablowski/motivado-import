<?php

class ImporterController extends Controller {
	protected $Importer;
	
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
				break $files[strstr($file, $extension, TRUE)] = $pathFile;
			} else if (is_dir($pathFile)) {
				$files = array_merge($files, $this->getImportFiles($pathFile . '/'));
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
			$keys = $this->getImportFiles();
		}
		
		$this->getImporter()->run($keys);
		
		return $this->displayView('Importer.import.php', array(
			'Coachings' => $this->getImporter()->getCoachings()
		));
	}
}