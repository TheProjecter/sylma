<?php

namespace sylma\storage\sql\template\component;
use sylma\core, sylma\dom, sylma\storage\sql, sylma\schema;

class Table extends Rooted implements sql\template\pathable, schema\parser\element {

  protected $sMode = 'select';

  protected $bBuilded = false;
  protected $aColumns = array();
  protected $bQueryInserted = true;

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

  public function getQuery() {

    if (!$this->query) {

      $this->setQuery($this->createQuery($this->getMode()));
    }

    return $this->query;
  }

  public function getSource() {

    return $this->source ? $this->source : $this->getQuery()->getVar();
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

  public function reflectApplyDefault($sPath, array $aPath, $sMode, $bRead = false) {

    return $this->getParser()->reflectApplyDefault($this, $sPath, $aPath, $sMode, $bRead);
  }

  public function insertQuery($bVal = null) {

    if (is_bool($bVal)) $this->bQueryInserted = $bVal;

    return $this->bQueryInserted;
  }

  public function reflectApply($sMode = '', $bStatic = false) {

    if ($tpl = $this->lookupTemplate($sMode)) {

      $tpl->setTree($this);

      if ($this->insertQuery()) {

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
      case 'parent' :

        $parent = $this->getParent();

        if ($aPath) {

          $result = $this->getParser()->parsePathToken($parent, $aPath, $sMode, $bRead);
        }
        else {


          $result = $bRead ? $parent->reflectRead($sMode) : $parent->reflectApply($sMode);
        }

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

      list($sNamespace, $sName) = $this->getParser()->parseName($sName);
      $aRemoved[] = $this->getElement($sName, $sNamespace, false);
    }

    foreach ($this->getElements() as $element) {

      foreach ($aRemoved as $excluded) {

        if ($excluded === $element) {

          continue 2;
        }
      }

      $aResult[] = $element->reflectApply($sMode);
    }

    return $aResult;
  }
}

