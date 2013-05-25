<?php

namespace sylma\storage\sql\template\component;
use sylma\core, sylma\storage\sql, sylma\parser\languages\common;

abstract class Field extends sql\schema\component\Field implements sql\template\pathable {

  protected $parent;
  protected $query;
  protected $var;

  protected function getQuery() {

    return $this->getParent()->getQuery();
  }

  protected function getSource() {

    return $this->getParent()->getSource();
  }

  public function reflectRead() {

    $this->launchException('Should not be used');
  }

  public function reflectApply($sMode = '') {

    return $this->reflectApplySelf($sMode);
  }

  protected function lookupTemplate($sMode) {

    return $this->getParser()->lookupTemplate($this, 'element', $sMode);
  }

  protected function reflectApplySelf($sMode = '') {

    if ($result = $this->lookupTemplate($sMode)) {

      $result->setTree($this);
    }

    return $result;
  }

  public function reflectApplyFunction($sName, array $aPath, $sMode) {

    switch ($sName) {

      case 'value' : $result = $this->reflectRead(); break;
      case 'alias' : $result = $this->getAlias(); break;
      case 'apply' : $result = $this->reflectApply($sMode); break;

      default :

        $this->launchException(sprintf('Uknown function "%s()"', $sName), get_defined_vars());
    }

    return $result;
  }

  protected function reflectSelf() {

    return $this->getWindow()->createCall($this->getSource(), 'read', 'php-string', array($this->getAlias()));
  }

  public function reflectApplyDefault($sPath, array $aPath, $sMode, $bRead) {

    $result = $this->getParser()->reflectApplyDefault($this, $sPath, $aPath, $sMode, $bRead);

    return $result;
  }
}

