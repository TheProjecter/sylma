<?php

namespace sylma\core\argument\parser\test;
use \sylma\modules\tester, \sylma\core, \sylma\dom, \sylma\storage\fs, \sylma\parser;

class Standalone extends tester\Prepare {

  const NS = 'http://www.sylma.org/core/argument/parser/test';

  protected $sTitle = 'Standalone';

  public function __construct() {

    $this->setDirectory(__file__);
    $this->setNamespace(self::NS, 'self');
    $this->setNamespace(parser\action::NS, 'le', false);

    //if (!$controler) $controler = $this;
    //if (!$controler) $controler = \Sylma::getControler('action');

    $this->setControler($this->getControler('argument'));
    $this->setFiles(array($this->getFile('standalone.xml')));
  }

  public function getDirectory($sPath = '', $bDebug = true) {

    return parent::getDirectory($sPath, $bDebug);
  }

  public function getArgument($sPath, $mDefault = null, $bDebug = false) {

    return parent::getArgument($sPath, $mDefault, $bDebug);
  }

  public function setArgument($sPath, $mValue) {

    return parent::setArgument($sPath, $mValue);
  }

  protected function test(dom\element $test, $sContent, $controler, dom\document $doc, fs\file $file) {

    return parent::test($test, $sContent, $this, $doc, $file);
  }

  public function createHandler($sPath) {

    $file = $this->getControler('fs')->getFile($sPath, $this->getDirectory('samples'));

    return $this->getControler()->create('handler', array($file));
  }
}

