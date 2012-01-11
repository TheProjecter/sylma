<?php

namespace sylma\storage\fs\test;
use \sylma\modules\tester, \sylma\core, \sylma\dom, \sylma\storage\fs;

require_once('modules/tester/Basic.php');

class Basic extends tester\Basic {
  
  const NS = 'http://www.sylma.org/storage/fs/test';
  protected $sTitle = 'File system';
  
  public function __construct() {
    
    \Sylma::getControler('dom');
    
    $this->setDirectory(__file__);
    $this->setNamespace(self::NS, 'self');
    
    $this->setArguments('../settings.yml');
    
    $dir = $this->getDirectory();
    
    $controler = $this->create('controler');
    $controler->loadDirectory((string) $dir);
    
    $this->setFiles(array($this->getFile('basic.xml')));
    
    $this->setControler($controler);
  }
}


