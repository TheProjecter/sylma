<?php

namespace sylma\storage\sql\template\insert;
use sylma\core, sylma\storage\sql, sylma\schema\parser, sylma\parser\languages\common;

class Foreign extends sql\template\component\Foreign {

  protected function reflectFunctionAll(array $aPath, $sMode) {

    return null;
  }

  protected function reflectFunctionRef(array $aPath, $sMode) {

    return null;
  }

  public function reflectRegister() {

    $this->getParent()->addElementToHandler($this, $this->getDefault());
  }

  protected function _applyElement(sql\template\component\Table $element, array $aPath, $sMode) {

    return null;
  }
}
