<?php

namespace sylma\storage\sql\template\component;
use sylma\core, sylma\dom, sylma\storage\sql, sylma\schema;

class Table extends Rooted implements sql\template\pathable, schema\parser\element {

  protected $sMode = 'select';

  protected $bBuilded = false;
  protected $aColumns = array();
  protected $bSub = false;

  protected $loop;

  public function parseRoot(dom\element $el) {

    parent::parseRoot($el);
  }

  public function setParent(schema\parser\element $parent) {

    $this->parent = $parent;
  }

  public function getParent($bDebug = true) {

    if (!$this->parent && $bDebug) {

      $this->throwException('No parent');
    }

    return $this->parent;
  }

  protected function getMode() {

    return $this->sMode;
  }

  public function isSub($bVal = null) {

    if (is_bool($bVal)) $this->bSub = $bVal;

    return $this->bSub;
  }

  public function getQuery() {

    if (!$this->query) {

      $this->setQuery($this->buildQuery());
    }

    return $this->query;
  }

  protected function buildQuery() {

    return $this->createQuery($this->getMode());
  }

  public function getSource() {

    return $this->source ? $this->source : $this->getQuery()->getVar();
  }

  public function getKey() {

    return parent::getKey();
  }

  protected function createQuery($sName) {

    $query = $this->loadSimpleComponent("template/$sName", $this);
    $query->setTable($this);

    return $query;
  }

  protected function getColumns() {

    return $this->aColumns;
  }

  protected function addColumn(schema\parser\element $el) {

    $this->aColumns[$el->getName()] = $el;
  }

  public function addElementToQuery(schema\parser\element $el) {

    $this->addColumn($el);

    $query = $this->getQuery();
    $query->setElement($el);
  }

  public function reflectApplyDefault($sPath, array $aPath, $sMode, $bRead = false, array $aArguments = array()) {

    return $this->getParser()->reflectApplyDefault($this, $sPath, $aPath, $sMode, $bRead, $aArguments);
  }

  public function reflectApply($sMode = '', array $aArguments = array(), $bStatic = false) {

    if ($tpl = $this->lookupTemplate($sMode)) {

      $tpl->setTree($this);
      $tpl->sendArguments($aArguments);

      if (!$bStatic && $this->insertQuery()) {

        $aResult[] = $this->getQuery();
        $this->insertQuery(false);
      }

      $aResult[] = $tpl;
    }
    else {

      if (!$sMode) {

        $this->launchException('Cannot apply table without template and without mode');
      }

      $aResult = array();
    }

    return $aResult;
  }

  public function reflectRead() {

    $this->launchException('Cannot read table');
  }

  public function reflectApplyFunction($sName, array $aPath, $sMode, $bRead) {

    switch ($sName) {

      //case 'apply' : $result = $this->reflectApply(''); break;
      case 'name' : $result = $this->getName(); break;
      case 'title' : $result = $this->getTitle(); break;
      case 'parent' :

        $result = $this->getParser()->parsePathToken($this->getParent(), $aPath, $sMode, $bRead);

        break;

      default :

        $this->launchException(sprintf('Uknown function "%s()"', $sName), get_defined_vars());
    }

    return $result;
  }

  public function reflectApplyAll($sMode) {

    $aResult = array();

    foreach ($this->getElements() as $element) {

      $aResult[] = $element->reflectApply($sMode);
    }

    return $aResult;
  }

  public function reflectApplyAllExcluding(array $aExcluded, $sMode) {

    $aResult = array();
    $aRemoved = array();

    foreach ($aExcluded as $sName) {

      list($sNamespace, $sName) = $this->parseName($sName);

      if (!$removed = $this->getElement($sName, $sNamespace, false)) {

        $removed = $this->getParser()->getType($sName, $sNamespace, false);
      }

      $aRemoved[] = $removed;
    }

    foreach ($this->getElements() as $element) {

      foreach ($aRemoved as $excluded) {

        if ($excluded === $element || $element->getType() === $excluded) {

          continue 2;
        }
      }

      $aResult[] = $element->reflectApply($sMode);
    }

    return $aResult;
  }

  public function reflectRegister() {

    $this->launchException('Table cannot be registered');
  }
}
