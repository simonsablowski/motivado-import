<?php

class ImporterController extends Controller {
	protected $Importer;
	
	public function setup($void = NULL) {
		if (is_null($this->getConfiguration('pathModeling'))) {
			throw new FatalError('Modeling path not set');
		}
		
		$this->setImporter(new Importer($this->getConfiguration()));
	}
	
	protected function getImportDirectories($baseDirectory = NULL) {
		if (is_null($baseDirectory)) $baseDirectory = $this->getConfiguration('pathModeling');
		
		$directories = array();
		$base = dir($baseDirectory);
		while (($directory = $base->read()) !== FALSE) {
			if (in_array($directory, $this->getConfiguration('ignoreDirectoriesModeling'))) continue;
			if (!is_dir($pathDirectory = $baseDirectory . $directory)) continue;
			$dir = dir($pathDirectory);
			while (($file = $dir->read()) !== FALSE) {
				if ($file == $this->getConfiguration('startFileNameModeling')) {
					break $directories[$directory] = $pathDirectory;
				}
			}
			$dir->close();
			$directories = array_merge($directories, $this->getImportDirectories($pathDirectory));
		}
		$base->close();
		
		return $directories;
	}
	
	public function index() {
		$this->setup();
		// $keys = array_keys($this->getImportDirectories());
		$keys = array('psychotest2');
		
		$this->getImporter()->run($keys);
		
		return $this->displayView('Importer.index.php');
		/*$this->getOutputBuffer()->start();
		foreach ($this->getImporter()->getObjects() as $Object) {
			var_dump($Object->getData());
		}
		$this->displayView('Importer.index.php', array(
			'output' => $this->getOutputBuffer()->get()
		));*/
	}
}