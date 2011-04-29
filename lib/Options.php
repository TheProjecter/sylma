<?php

require('module/Base.php');

class Options extends ModuleBase {
  
  private $dDocument = null;
  private $aOptions = array(); // cache array
  
  public function __construct(XML_Document $dDocument, XML_Document $dSchema = null, array $aNS = array()) {
    
    $this->dDocument = $dDocument;
    $this->setPrefix($dDocument && $dDocument->getRoot() ? $dDocument->getRoot()->getPrefix() : '');
    
    $this->setNamespaces($aNS);
    if ($dSchema) $this->setSchema($dSchema);
  }
  
  private function getDocument() {
    
    return $this->dDocument;
  }
  
  private function parsePath($sPath) {
    
    if ($sPrefix = $this->getPrefix()) return preg_replace('/([-\w]+)/', $sPrefix.':\1', $sPath);
    else return $sPath;
  }
  
  public function validate() {
    
    $bResult = false;
    
    if (!$this->getSchema()) {
      
      $this->dspm(xt('Cannot validate, no schema defined'), 'warning');
    }
    else if (!$this->getDocument() || $this->getDocument()->isEmpty()) {
      
      $this->dspm(xt('Cannot validate, document empty or not defined'), 'warning');
    }
    else {
      
      $bResult = $this->getDocument()->validate($this->getSchema);
    }
    
    return $bResult;
  }
  
  public function get($sPath, $bDebug = true) {
    
    $eResult = null;
    
    if (!$this->getDocument()) {
      
      $this->dspm(xt('Cannot load value %s, no document defined',
        new HTML_Strong($sPath)), 'error');
    }
    else {
      
      if (!array_key_exists($sPath, $this->aOptions) || !$this->aOptions[$sPath]) {
        
        $bPrefix = strpos($sPath, ':');
        
        if (!$bPrefix && !strpos($sPath, '/')) { // only first level, can optimize
          
          $eResult = $this->getDocument()->getByName($sPath);
        }
        else { // more than one level, use xpath
          
          if (!$bPrefix) $sRealPath = $this->parsePath($sPath);
          else $sRealPath = $sPath;
          
          $eResult = $this->getDocument()->get($sRealPath, $this->getNS());
        }
        
        $this->aOptions[$sPath] = $eResult;
        
        if (!$this->aOptions[$sPath] && $bDebug) {
          
          dspm(xt('Option %s not found in %s',
            new HTML_Strong($sPath),
            view($this->getDocument())), 'action/warning');
        }
      }
    }
    
    return $eResult;
  }
  
  public function read($sPath, $bDebug = true) {
    
    if ($oOption = $this->get($sPath, $bDebug)) return $oOption->read();
    else return '';
  }
  
  public function set($sPath, $mValue = null) {
    
    $mResult = '';
    
    if ($eOption = $this->get($sPath)) {
      
      if ($mValue) $mResult = $eOption->set($mValue);
      else $mResult = $eOption->remove();
    }
    
    return $mResult;
  }
}
