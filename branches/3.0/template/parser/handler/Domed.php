<?php

namespace sylma\template\parser\handler;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\storage\fs, sylma\parser\languages\common;

class Domed extends Templated implements reflector\elemented {

  const NS = 'http://2013.sylma.org/template';
  //const PREFIX = 'tpl';

  protected $aRegistered = array();
  protected $aTemplates = array();
  protected $aConstants = array();

  protected $result;

  public function parseRoot(dom\element $el) {

    $this->launchException('Not ready');

    $this->setNode($el, false);

    if ($el->getName() !== 'stylesheet') {

      $this->throwException('Bad root');
    }

    $this->loadTemplates($el);
    $this->loadResult();
  }

  public function parseFromChild(dom\element $el) {

    return $this->getCurrentTemplate()->parseComponent($el);
  }

  public function importFile(fs\file $file) {

    $this->log("Import : " . $file->asToken());

    $this->parseChildren($file->getDocument()->getChildren());
  }

  protected function parseElementSelf(dom\element $el) {

    switch ($el->getName()) {

      case 'template' :

        $this->loadTemplate($el);
        $result = null;
        break;

      default :

        $result = parent::parseElementSelf($el);
    }

    return $result;
  }

  public function lookupNamespace($sPrefix = '') {

    return $this->getNode()->lookupNamespace($sPrefix);
  }

  protected function loadResult() {

    $window = $this->getWindow();

    $result = $window->addVar($window->argToInstance(''));
    $this->result = $result;
  }

  /**
   *
   * @return common\_var
   */
  public function getResult() {

    return $this->result;
  }

  public function addToResult($mContent, $bAdd = true) {

    return $this->getWindow()->addToResult($mContent, $this->getResult(), $bAdd);
  }

  public function register($obj) {

    $this->aRegistered[] = $obj;
  }

  public function getRegistered() {

    return $this->aRegistered;
  }

  public function setConstant($sName, $sValue) {

    $this->aConstants[$sName] = $sValue;
  }

  public function getConstant($sName) {

    return $this->aConstants[$sName];
  }
}