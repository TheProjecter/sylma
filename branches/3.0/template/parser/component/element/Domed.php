<?php

namespace sylma\template\parser\component\element;
use sylma\core, sylma\dom, sylma\parser\languages\common, sylma\template;

abstract class Domed extends template\parser\component\Unknowned implements template\parser\component {

  const TARGET_PREFIX = 'target';

  //protected $aAttributes = array();
  protected $aContent = array();
  protected $bBuilded = false;
  protected $aBefore = array();

  protected $aDefaultAttributes = array();
  protected $aAttributes = array();

  public function parseRoot(dom\element $el) {

    $this->allowUnknown(true);
    $this->allowText(true);

    $this->build($el);
    //$this->setNamespace(\Sylma::read('namespaces/html'));
  }

  public function build(dom\element $el = null) {

    if (!$this->bBuilded) {

      $this->setNode($el, true, false);
      $this->start();

      $this->buildContent();

      $this->stop();

      $this->bBuilded = true;
    }

    return $this->aContent;
  }

  protected function buildContent() {

    $el = $this->getNode();

    if ($el->countChildren()) {

      if ($el->countChildren() > 1) {

        $aContent = $this->parseComponentRoot($el);
      }
      else {

        $aContent = array($this->parseComponentRoot($el));
      }

      $this->aContent = $aContent;
    }
  }

  protected function start() {

    return $this->getRoot()->startElement($this);
  }

  protected function stop() {

    return $this->getRoot()->stopElement();
  }

  protected function loadName(dom\element $el) {

    //$sName = ($el->getPrefix() ? $el->getPrefix() . ':' : '') . $el->getName();
    //$sName = $this->getPrefix($el->getNamespace()) . $el->getName();
    $sName = $el->getName();

    return $sName;
  }

  public function asToken() {

    return $this->getNode(false) ? $this->getNode()->asToken() : '[No node defined]';
  }
}
