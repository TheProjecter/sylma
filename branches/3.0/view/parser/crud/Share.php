<?php

namespace sylma\view\parser\crud;
use sylma\core, sylma\dom, sylma\parser\reflector;

class Share extends reflector\component\Foreigner implements dom\domable {

  protected $sName;

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
    $this->loadName();
  }

  protected function loadName() {

    $this->setName($this->readx('@name'));
  }

  protected function setName($sName) {

    $this->sName = $sName;
  }

  public function getName() {

    return $this->sName;
  }

  public function asDOM() {

    $dom = $this->getManager('dom');
    $collection = $dom->create('collection', array($this->getNode()->getChildren()));
    $collection->add($this->queryx('@*'));

    return $collection;
  }
}
