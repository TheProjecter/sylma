<?php

namespace sylma\storage\sql\template\component;
use sylma\core, sylma\storage\sql, sylma\template, sylma\schema\parser;

class Field extends sql\schema\component\Field implements template\parser\tree {

  protected $parent;
  protected $query;
  protected $var;

  public function setParent(parser\element $parent) {

    $this->parent = $parent;
  }

  public function getParent($bDebug = true) {

    if (!$this->parent && $bDebug) {

      $this->throwException('No parent');
    }

    return $this->parent;
  }

  protected function getQuery() {

    return $this->getParent()->getQuery();
  }

  protected function getVar() {

    return $this->getParent()->getVar();
  }

  public function reflectApplyPath(array $aPath, $sMode = '') {

    if (!$aPath) {

      $result = $this->reflectApplySelf($sMode);
    }
    else {

      $result = $this->parsePathToken($aPath, $sMode);
    }

    return $result;
  }

  public function reflectApply($sPath, $sMode = '') {

    if ($sPath) {

      $result = $this->reflectApplyPath($this->getParser()->parsePath($sPath), $sMode);
    }
    else {

      $result = $this->reflectRead();
    }

    return $result;
  }

  protected function parsePathToken($aPath, $sMode) {

    return $this->getParser()->parsePathToken($this, $aPath, $sMode);
  }

  protected function lookupTemplate($sMode) {

    if ($template = $this->getParser()->lookupTemplate($this, 'element', $sMode)) {

      $result = clone $template;
    }
    else {

      $result = null;
    }

    return $result;
  }

  protected function reflectApplySelf($sMode = '') {

    if ($result = $this->lookupTemplate($sMode)) {

      $result->setTree($this);
    }
    else {

      $result = $this->reflectRead();
    }

    return $result;
  }

  public function reflectRead() {

    $window = $this->getWindow();
    $query = $this->getQuery();

    $sName = $this->getName();

    $query->setColumn($this);

    $var = $this->getVar();

    return $window->createCall($var, 'read', 'php-string', array($sName));
  }
}

