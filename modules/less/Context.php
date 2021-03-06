<?php

namespace sylma\modules\less;
use sylma\core, sylma\storage\fs, sylma\modules\html;

class Context extends html\context\CSS {

  public function __construct(array $aArray = array(), core\argument $fusion = null, core\window\context $js = null) {

    parent::__construct($aArray, $fusion);
    $this->js = $js; // TODO : change to context getter
  }

  protected function addFile(fs\file $file, $bReal = false) {

    $aResult = parent::addFile($file, $bReal);

    if ($file->getExtension() === 'less') {

      $fs = \Sylma::getManager('fs');
      $this->js->add($fs->getFile('less.js', $fs->extractDirectory(__FILE__)));
      $aResult['link']['@rel'] = 'stylesheet/less';
    }

    return $aResult;
  }

  protected function readFile(fs\file $file) {

    $sResult = '';

    switch ($file->getExtension()) {

      case 'css' : $sResult = parent::readFile($file); break;
      case 'less' : $sResult = $this->parse($this->parseLess($file), $file->getParent()); break;
      default :

        $this->launchException('Uknown extension type');
    }

    return $sResult;
  }

  protected function parseLess(fs\file $file) {

    $sResult = '';

    $less = new Prefixer;
    //$less->setImportDir($file->getParent()->getRealPath());
//echo (string) $file->getControler()->getDirectory()->getRealPath();
    $less->setImportDir($file->getControler()->getDirectory()->getRealPath());

    try {

      $sResult = $less->compileFile($file->getRealPath());
    }
    catch (\Exception $e) {

      throw \Sylma::loadException($e);
    }

    return $sResult;
  }
}

