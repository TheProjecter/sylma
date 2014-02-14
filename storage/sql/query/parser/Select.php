<?php

namespace sylma\storage\sql\query\parser;
use sylma\core, sylma\parser\languages\common, sylma\schema, sylma\storage\sql;

class Select extends Ordered implements common\argumentable {

  protected $sMethod = '';
  protected $aElements = array();
  protected $aKeyElements = array();

  protected $offset = '0';
  protected $count;

  protected $aClones = array();
  protected $main;

  public function setElement(schema\parser\element $el, $bDistinct = false) {

    $sName = $el->getName();
    $bAdd = true;

    if (array_key_exists($sName, $this->aKeyElements)) {

      if ($el === $this->aKeyElements[$sName]) {

        $bAdd = false;
      }
      else {

        $el->useAlias(true);
      }
    }
    else {

      $this->aKeyElements[$sName] = $el;
    }

    if ($bAdd) {

      $sName = $el->asAlias();

      if ($bDistinct) {

        $mContent = array('DISTINCT ', $sName);
        $this->setColumn($mContent, true);
      }
      else {

        $mContent = $sName;
        $this->setColumn($mContent);
      }
    }

    $this->aElements[] = $el;
  }

  protected function getElements() {

    return $this->aElements;
  }

  protected function getColumns() {

    $aResult = array();

    if (!$this->aColumns) {

      $aResult[] = '*';
    }
    else {

      $aResult = parent::getColumns();
    }

    return $aResult;
  }

  public function isEmpty() {

    return !$this->aColumns;
  }

  public function clearColumns() {

    $this->aColumns = array();
  }

  public function setWhere($val1, $sOp, $val2, $sLog = 'AND') {

    foreach($this->getClones() as $clone) {

      $clone->setWhere($val1, $sOp, $val2, $sLog);
    }

    return parent::setWhere($val1, $sOp, $val2, $sLog);
  }

  protected function insertDynamicWhere($bVal = null) {

    if (is_bool($bVal)) $this->bDynamicWhere = $bVal;

    return $this->bDynamicWhere;
  }

  protected function buildDynamicWhere() {

    if ($this->getMain()) {

      $aResult = $this->getMain()->buildDynamicWhere();
    }
    else if ($this->getDynamicWhere() && $this->insertDynamicWhere()) {

      $aResult = parent::buildDynamicWhere();
    }
    else {

      $aResult = null;
    }

    $this->insertDynamicWhere(false);

    return $aResult;
  }

  protected function getWheres() {

    if ($this->getMain() && $this->getMain()->getDynamicWhere()) {

      $result = $this->getMain()->getDynamicWhere();
    }
    else {

      $result = parent::getWheres();
    }

    return $result;
  }

  public function setMethod($sMethod) {

    $this->sMethod = $sMethod;
  }

  public function setOffset($offset) {

    $this->offset = $offset;
  }

  protected function getOffset() {

    return $this->offset;
  }

  public function setCount($count) {

    $this->count = $count;
  }

  protected function getCount() {

    return $this->count;
  }

  protected function getLimit() {

    if ($this->getCount()) {

      $aResult = array(' LIMIT ', $this->getOffset(), ', ', $this->getCount());
    }
    else {

      $aResult = array();
    }

    return $aResult;
  }

  public function clearLimit() {

    $this->offset = $this->count = null;
  }

  public function getCall($bDebug = false) {

    $bDebug = $this->isMultiple() || $this->isOptional() ? false: true;

    return parent::getCall($bDebug);
  }

  protected function build() {

    if (!$sMethod = $this->getMethod()) {

      $this->setMethod($this->isMultiple() ? 'query' : 'get');
    }

    parent::build();
  }

  public function getString() {

    $aQuery = array('SELECT ', $this->getColumns(), ' FROM ', $this->getTables(), $this->getJoins(), $this->getWheres(), $this->getOrder(), $this->getLimit());

    return $this->getWindow()->createString($aQuery);
  }

  public function addClone(self $clone) {

    $this->aClones[] = $clone;
    $clone->setMain($this);
  }

  public function setMain(self $main) {

    $this->main = $main;
  }

  protected function getMain() {

    return $this->main;
  }

  protected function getClones() {

    return $this->aClones;
  }

  public function asArgument() {

    return $this->getWindow()->createGroup(array(
      $this->buildDynamicWhere(),
      $this->prepareOrder(),
      $this->getVar()->getInsert(),
    ))->asArgument();
    //$content = $this->getString();

    //return $content->asArgument();
  }
}

