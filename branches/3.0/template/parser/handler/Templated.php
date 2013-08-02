<?php

namespace sylma\template\parser\handler;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\template\parser;

abstract class Templated extends reflector\handler\Elemented {

  protected $aCurrentTemplates = array();
  protected $aCheckTemplates = array();

  protected $aTemplates = array();

  public function lookupTemplate($sName, $sNamespace, $sMode, $bRoot = false) {

    $iLast = 0;
    $result = null;

    foreach ($this->getTemplates() as $template) {

      $sToken = $sNamespace . ':' . $sName;
      if ($this->checkTemplate($template, $sToken, false)) continue;

      $iWeight = $template->getWeight($sNamespace, $sName, $sMode, $bRoot);
      if ($iWeight && $iWeight >= $iLast) {

        $result = $template;
        $iLast = $iWeight;
      }
    }

    if ($result) {

      $result = clone $result;
    }

    return $result;
  }

  public function getCurrentTemplate($bDebug = true) {

    if (!$this->aCurrentTemplates) {

      if ($bDebug) $this->launchException('No template defined');
      $result = null;
    }
    else {

      $result = end($this->aCurrentTemplates);
    }

    return $result;
  }

  public function startTemplate(parser\template $tpl) {

    $this->aCurrentTemplates[] = $tpl;
    $this->aCheckTemplates[$this->getTreeID($tpl, $this->getTreeToken($tpl))] = true;
//echo 'start ' . $tpl->getID().'<br/>';
  }

  public function stopTemplate() {

    $tpl = array_pop($this->aCurrentTemplates);
    unset($this->aCheckTemplates[$this->getTreeID($tpl, $this->getTreeToken($tpl))]);
//echo 'stop ' . $tpl->getID().'<br/>';
  }

  protected function getTreeToken(parser\template $tpl) {

    if ($tree = $tpl->getTree(false)) {

      $sResult = $tree->asToken();
    }
    else {

      $sResult = '';
    }

    return  $sResult;
  }

  protected function getTreeID(parser\template $tpl, $sToken) {

    return $tpl->getID() . $sToken;
  }

  public function checkTemplate(parser\template $tpl, $sToken, $bDebug = true) {

    $bResult = isset($this->aCheckTemplates[$this->getTreeID($tpl, $sToken)]);

    if ($bResult) {

      if ($bDebug) $this->launchException('Recursive template call');
    }

    return $bResult;
  }

  protected function loadTemplate(dom\element $el) {

    $template = $this->createComponent('component/template', $this);
    $template->parseRoot($el);

    $this->addTemplate($template);
  }
/*
  protected function resetTemplates() {

    $this->aTemplates = array();
  }
*/
  protected function getTemplates() {

    return $this->aTemplates;
  }

  protected function addTemplate(parser\component\Template $template) {

    $this->aTemplates[] = $template;
  }

  protected function getTemplate($sPath = '') {

    if ($sPath) {

      $this->throwException('Feature not available');
    }

    //if (!$sMatch) $sMatch = parser_ns\component\Template::MATCH_DEFAULT;

    $result = $this->getDefaultTemplate();

    if (!$result) {

      $this->launchException('No root template found', get_defined_vars());
    }

    return $result;
  }

  protected function getDefaultTemplate() {

    $result = null;

    foreach ($this->aTemplates as $template) {

      if (!$template->getMatch() && !$template->getMode()) {

        $result = $template;
        break;
      }
    }

    return $result;
  }
}
