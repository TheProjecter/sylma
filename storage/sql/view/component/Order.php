<?php

namespace sylma\storage\sql\view\component;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\parser\languages\common, sylma\storage\sql;

class Order extends reflector\component\Foreigner implements reflector\component, common\arrayable {

  protected $var;

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
    $this->allowForeign(true);
    $this->allowText(true);
  }

  public function asArray() {

    $tree = $this->getParser()->getCurrentTree();
    $query = $this->checkQuery($tree->getQuery());

    if ($this->getNode()->isComplex()) {

      $content = $this->parseComponentRoot($this->getNode(), true);
      $query->setOrderDynamic($content);
    }
    else {

      $sValue = $this->readx();

      if ($this->readx('@function')) {

        $query->setOrderFunction($sValue);
      }
      else {

        $query->setOrderPath($sValue);
      }
    }

    $this->log('SQL : order');

    return array();
  }

  protected function checkQuery(sql\query\parser\Select $query) {

    return $query;
  }
}

