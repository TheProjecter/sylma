<?php

namespace sylma\template\parser\handler;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\storage\fs, sylma\parser\languages\common, sylma\template;

class Domed extends Templated implements reflector\elemented, template\parser\handler {

  const NS = 'http://2013.sylma.org/template';
  const PREFIX = 'tpl';

  protected $aConstants = array();
  protected $bInternal = false;

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

    if (!$tpl = $this->getCurrentTemplate(false)) {

      $aTemplates = $this->getTemplates();
      $tpl = end($aTemplates);
    }

    return $tpl->parseComponent($el);
  }

  protected function checkInternal() {

    $window = $this->getWindow();
    $external = $this->getRoot()->getExternal();

    if ($this->readx('@internal')) {

      $window->add($window->createCondition($external, $window->getSylma()->call('throwException', array('Public request for internal view'))));
      $this->isInternal(true);
    }
  }

  public function isInternal($bVal = null) {

    if (is_bool($bVal)) $this->bInternal = $bVal;

    return $this->bInternal;
  }

  public function importFile(fs\file $file) {

    $this->log("Import : " . $file->asToken());

    $doc = $this->getRoot()->importDocument($file->getDocument(), $file);
    $this->parseChildren($doc->getChildren());
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

  public function applyArrayTo($target, array $aPath, $sMode, array $aArguments = array()) {

    $pather = $this->getPather();
    $pather->setSource($target);

    return $aPath ? $pather->parsePathTokens($aPath, $sMode, $aArguments) : $target->reflectApply($sMode, $aArguments);
  }

  public function applyPathTo($target, $sPath, $sMode, array $aArguments = array()) {

    $pather = $this->getPather();
    $pather->setSource($target);

    return $pather->applyPath($sPath, $sMode, $aArguments);
  }

  public function getPather() {

    return $this->getCurrentTemplate()->getPather();
  }

  public function importTree(fs\file $file, $sType = '') {

    switch ($sType) {

      case 'argument' :

        $content = $this->createArgumentFromString((string) $file);
        break;

      default :
      case 'document' :

        $this->loadDefaultArguments();
        $content = $this->create('options', array($file->getDocument(), $this->getNS()));

        break;
    }

    $result = $this->loadSimpleComponent('component/tree');
    $result->setOptions($content);

    return $result;
  }


  public function createTree($sReflector) {

    $result = $this->checkTree(new $sReflector($this));
    $result->parseRoot();

    return $result;
  }

  protected function checkTree(template\parser\tree $tree) {

    return $tree;
  }

  public function trimString($sValue) {

    return parent::trimString($sValue);
  }

  /**
   *
   * @return common\_var
   */
  public function getResult() {

    return $this->result;
  }

  public function addToResult($mContent, $bAdd = true, $bFirst = false) {

    return $this->getWindow()->addToResult($mContent, $this->getResult(), $bAdd, $bFirst);
  }

  public function register($obj) {

    $this->launchException('No usage defined');
  }

  public function xmlize($mValue) {

    if (is_string($mValue)) {

      $mResult = htmlspecialchars($mValue);
    }
    else if (is_array($mValue)) {

      foreach ($mValue as &$mSub) {

        $mSub = $this->xmlize($mSub);
      }

      $mResult = $mValue;
    }
    else {

      $mResult = $mValue;
    }

    return $mResult;
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
