<?php

namespace sylma\storage\sql\template\component;
use sylma\core, sylma\dom, sylma\storage\sql, sylma\schema\parser, sylma\parser\languages\common;

class Rooted extends sql\schema\component\Table {

  protected $source;
  protected $query;

  protected $bRoot = false;

  public function isRoot($bValue = null) {

    if (is_bool($bValue)) $this->bRoot = $bValue;

    return $this->bRoot;
  }

  public function setSource($source) {

    $this->source = $source;
  }

  protected function getSource() {

    return $this->source;
  }

  public function setQuery(sql\query\parser\Basic $query) {

    $this->query = $query;
  }

  protected function getQuery() {

    return $this->query;
  }

  protected function parsePaths($sPath) {

    $aResult = $this->getParser()->parsePaths($sPath);

    return $aResult;
  }
  
  protected function lookupTemplate($sMode) {

    return $this->getParser()->lookupTemplate($this, 'element', $sMode, $this->isRoot());
  }

  protected function parsePathTokens($aPath, $sMode) {

    return $this->getParser()->parsePathTokens($this, $aPath, $sMode);
  }
}
