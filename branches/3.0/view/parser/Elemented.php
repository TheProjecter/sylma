<?php

namespace sylma\view\parser;
use sylma\core, sylma\dom, sylma\template, sylma\parser\reflector, sylma\schema, sylma\parser\languages\common;

class Elemented extends template\parser\handler\Elemented {

  const NS = 'http://2013.sylma.org/view';
  const NS_SCHEMA = 'http://2013.sylma.org/schema/template';

  const TMP_ARGUMENTS = 'view.xml';
  const LOG_RESULT = false;

  protected $allowForeign = true;

  protected $schema;
  protected $resource;
  protected $aEntries = array();
  protected $sMode;

  public function __construct(reflector\documented $root, reflector\elemented $parent = null, core\argument $arg = null) {

    //$this->setDirectory(__FILE__);
    //$this->setArguments(self::TMP_ARGUMENTS);
    //$arg = $this->setArguments($this->getFactory()->findClass('classes/elemented'));

    //$this->setNamespace(self::NS, self::PREFIX);
    $this->setNamespace(self::NS_SCHEMA);
    $this->setNamespace(parent::NS, parent::PREFIX);

    parent::__construct($root, $parent, $arg);
  }

  public function parseRoot(dom\element $el, $sMode = '') {

    $this->allowUnknown(true);
    $this->allowForeign(true);

    $this->setMode($sMode);
    $this->loadLogger();

    try {

      $tree = $this->loadTree($el, $sMode); // parseRoot(), onAdd()
      $result = $this->build($tree, $sMode);
      //$this->addToResult(array($this->getTemplate())); // asArray()
    }
    catch (core\exception $e) {

      $this->getLogger()->addException($e->getMessage());
      $this->loadLog();

      throw $e;
      //return null;
    }

    if (self::LOG_RESULT || $el->readx('@debug', array(), false)) $this->loadLog();

    return $result;
  }

  protected function setMode($sMode) {

    $this->sMode = $sMode;
  }

  protected function getMode() {

    return $this->sMode;
  }

  public function loadElementForeignKnown(dom\element $el, reflector\elemented $parser) {

    switch ($this->getMode()) {

      case 'update' :
      case 'insert' :

        $result = null;
        break;

      default :

        $result = parent::loadElementForeignKnown($el, $parser);
    }

    return $result;
  }

  /**
   * Namespace load from view except for empty prefix where schema target (default) namespace is loaded
   * @see \sylma\storage\sql\template\Handler::lookupNamespace()
   * @param type $sPrefix
   * @return type
   */
  public function lookupNamespace($sPrefix = '') {

    if (!$sPrefix) $sResult = $this->getSchema()->lookupNamespace($sPrefix);
    else $sResult = parent::lookupNamespace($sPrefix);

    return $sResult;
  }

  protected function parseElementSelf(dom\element $el) {

    switch ($el->getName()) {

      case 'template' : $result = $this->loadTemplate($el); break;
      default : $result = parent::parseElementSelf($el);
    }
  }

  protected function build(template\parser\tree $tree, $sMode) {

    switch ($sMode) {

      case 'update' :
      case 'insert' :

        $tpl = $tree->reflectApply();

        if ($tpl instanceof common\arrayable) {

          $this->addToResult($tpl->asArray());
        }
        else {

          $this->getWindow()->add($tpl);
        }
        //$tpl->asArray();
        //dsp($tpl);

        $var = $tree->getSource();
        $var->insert();

        $result = $var;

        break;

      // hollow, view, ...
      default :

        $content = $tree->reflectApply();

        if ($content instanceof common\arrayable) {

          $this->addToResult($content);
        }
        else {

          $this->getWindow()->add($content);
        }

        $result = $this->getResult();
    }

    return $result;
  }

  protected function loadTree(dom\element $el, $sMode) {

    $el = $this->setNode($el);

    if (!$el->getName() == 'view') {

      $this->throwException('Bad root');
    }

    $this->loadResult();

    $resource = $this->parseElement($this->getx('*[local-name() = "resource"]', true)->remove());
    $resource->setMode($sMode);

    $schema = $resource->setSchema($this->loadSchema());
    $this->setSchema($schema);

    $this->parseChildren($el->getChildren());

    //$this->loadTemplates();
    $schema->loadTemplates($this->getTemplates());

    return $resource->getTree();

    //$tpl = $this->getTemplate();
    //$tpl->setTree($tree);
  }

  protected function loadSchema() {

    $component = $this->loadSimpleComponent('component/schema', $this);
    $result = $component->parseRoot($this->getx('self:schema', true)->remove());

    return $result;
  }

  protected function setSchema(schema\parser\schema $schema) {

    $this->schema = $schema;
  }

  protected function getSchema() {

    return $this->schema;
  }
}
