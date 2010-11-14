<?php

class XML_Document extends DOMDocument {
  
  private $iMode = null;
  private $oFile = null;
  private $bInclude = true;
  private $bTemp = false; // WARNING, must be set to false, temp files are deleted on __destruct
  
  public function __construct($mChildren = '', $iMode = MODE_READ, $bInclude = true) {
    
    parent::__construct('1.0', 'utf-8');
    
    $this->preserveWhiteSpace = false;
    // $this->substituteEntities = false;
    
    $this->registerNodeClass('DOMDocument', 'XML_Document');
    $this->registerNodeClass('DOMElement', 'XML_Element');
    $this->registerNodeClass('DOMText', 'XML_Text');
    $this->registerNodeClass('DOMAttr', 'XML_Attribute');
    $this->registerNodeClass('DOMCdataSection', 'XML_CData');
    $this->registerNodeClass('DOMDocumentFragment', 'XML_Fragment');
    $this->registerNodeClass('DOMComment', 'XML_Comment');
    
    $this->setMode($iMode);
    $this->useInclude($bInclude);
    
    if ($mChildren) {
      
      // if Object else String
      if (is_object($mChildren)) $this->set($mChildren);
      else if (is_string($mChildren)) $this->startString($mChildren);
    }
  }
  
  public function getMode() {
    
    return $this->iMode;
  }
  
  public function useInclude($bInclude = null) {
    
    if ($bInclude !== null) $this->bInclude = $bInclude;
    
    return $this->bInclude;
  }
  
  private function setMode($iMode) {
    
    $this->iMode = in_array($iMode, array(MODE_EXECUTION, MODE_WRITE, MODE_READ)) ? $iMode : MODE_READ;
  }
  
  public function setPrefix($sPrefix, $sNamespace, $bDebug = true) {
    
    if ($this->getRoot()) {
      
      $this->getRoot()->setAttribute($sPrefix.':ns', 'null', $sNamespace);
      
    } else if($bDebug) dspm(xt('Impossible de spécifier un préfix dans un document vide'), 'xml/error');
  }
  
  private function extractMode($oNode) {
    
    $iMode = 7;
    
    if (!($oNode->hasAttributeNS(SYLMA_NS_SECURITY, 'owner') &&
      $oNode->hasAttributeNS(SYLMA_NS_SECURITY, 'mode') &&
      $oNode->hasAttributeNS(SYLMA_NS_SECURITY, 'group'))) {
      
      Controler::addMessage(xt('Sécurité : Élément sécurisé incomplet : %s', new HTML_Tag('em', $oNode->viewResume())), 'xml/warning');
      
    } else {
      
      $sOwner = $oNode->getAttribute('owner', SYLMA_NS_SECURITY);
      $sMode = $oNode->getAttribute('mode', SYLMA_NS_SECURITY);
      $sGroup = $oNode->getAttribute('group', SYLMA_NS_SECURITY);
      
      $iResult = Controler::getUser()->getMode($sOwner, $sGroup, $sMode, $oNode);
      if ($iResult !== null) $iMode = $iResult;
    }
    
    return $iMode;
  }
  
  private function appendLoadRights() {
    
    $bSecured = false;
    
    if (!$this->isEmpty()) {
      
      if (Controler::getUser() && !SYLMA_DISABLE_RIGHTS) {
        
        $oNodes = $this->query('//*[@ls:owner]', 'ls', SYLMA_NS_SECURITY); // or @ls:mode or @ls:group
        
        if ($oNodes->length) {
          
          $bSecured = true;
          
          if (Controler::useStatut('xml/report')) {
            
            if ($this->getFile()) $sPath = $this->getFile()->parse();
            else $sPath = 'le document';
            
            if ($oNodes->length == 1) $oMessage = xt('%s élément  sécurisé trouvé dans %s', new HTML_Strong('1'), $sPath);
            else $oMessage = xt('%s éléments  sécurisés trouvés dans %s', new HTML_Strong($oNodes->length), $sPath);
            
            Controler::addMessage($oMessage, 'xml/report');
          }
        }
        
        $this->removeLoadRights($oNodes);
      }
    }
    
    return $bSecured;
  }
  
  private function removeLoadRights($oNodes) {
    
    foreach ($oNodes as $oNode) {
      
      if ($oNode) {
        
        $bKeep = false;
        $iMode = $this->extractMode($oNode);
        
        if ($iMode) $bKeep = ($this->iMode & $iMode);
        if (!$bKeep) $oNode->remove();
      }
    }
  }
  
  private function appendSaveRights($oTargetDocument) {
    
  }
  
  public function startString($sString) {
    
    // if Path else XML String else new XML_Element
    if ($sString{0} == '/') return $this->loadFile($sString);
    else if ($sString{0} == '*') return $this->loadDB($sString);
    else if ($sString{0} == '<') return $this->loadText($sString);
    else return $this->set(new XML_Element($sString, '', null, '', $this));
  }
  
  public function isEmpty() {
    
    return !$this->getRoot();
  }
  
  protected function buildExternal($oElement, &$aPaths = array()) {
    
    $oResult = null;
      
    if ($sHref = $oElement->getAttribute('href')) {
      
      if ($this->getFile()) $sImportPath = Controler::getAbsolutePath($sHref, $this->getFile()->getParent());
      else $sImportPath = Controler::getAbsolutePath($sHref, '/');
      
      if (!in_array($sImportPath, $aPaths)) $oResult = Controler::getFile($sImportPath);
    }
    
    return $oResult;
  }
  
  protected function includeExternals(&$aPaths = array(), $iLevel = 0) {
    
    $iMaxLevel = SYLMA_MAX_INCLUDE_DEPTH;
    
    if ($iLevel > $iMaxLevel) {
      
      dspm(xt('Trop de redondance lors de l\'importation dans %s', $this->getFile()->parse()), 'xml/warning');
      
    } else {
      
      $oExternals = $this->query('//xi:include', array('xi' => SYLMA_NS_XINCLUDE));
      
      if ($oExternals->length) {
        
        $aPaths[] = $sPath;
        
        foreach ($oExternals as $oExternal) {
          
          if ($oFile = $this->buildExternal($oExternal, $aPaths)) {
            
            $oDocument = new XML_Document((string) $oFile, $this->getMode());
            
            if (!$oDocument->isEmpty()) {
              
              $oDocument->includeExternals($aPaths, $iLevel + 1);
              
              switch ($oExternal->getName(true)) {
                
                case 'include' : $oExternal->replace($oDocument->getChildren()); break;
                case 'import' : $this->shift($oDocument->getChildren()); break;
              }
              
            }
          }
          
          $oExternal->remove();
        }
        
      }// else return false;
    }
    
    return true;
  }
  
  /**
   * Method loadFile() alias
   */
  public function load($sPath) {
    
    $this->loadFile($sPath);
  }
  
  public function loadFile($sPath) {
    
    $bResult = false;
    
    if (($oFile = Controler::getFile($sPath, true)) && $oFile->checkRights($this->iMode)) {
      
      $this->setFile($oFile);
      
      $bLog = Controler::getUser()->isMember(SYLMA_AUTHENTICATED);
      
      if (!$oFile->isLoaded()) {
        
        // not yet loaded
        
        if (SYLMA_ACTION_STATS && ($oFile->getExtension() != 'eml') && $bLog)
          Controler::infosSetFile($oFile, true); // if action, add it to controler infos array
        
        $bResult = parent::load(MAIN_DIRECTORY.$sPath);
        
        if ($bResult) {
          
          if (Controler::useStatut('xml/report')) Controler::addMessage(xt('Chargement du fichier %s', new HTML_Strong($oFile)), 'xml/report');
          
          $oFile->setDocument($this); // PUT a copy in XML_File
          
          if ($this->isEmpty()) Controler::addMessage(xt('Aucun contenu dans %s', $oFile->parse()), 'xml/warning');
          else {
            
            XML_Controler::addStat('file');
            $oFile->isFileSecured($this->appendLoadRights());
            
            // if ($this->useInclude()) $this->includeExternals(); // include
          }
          
        } else dspm (xt('Problème lors du chargement du fichier %s', new HTML_Strong($oFile)), 'file/error');
        
      } else if (!$oFile->getFreeDocument()->isEmpty()) {
        
        $bResult = true;
        
        // already loaded
        
        if (SYLMA_ACTION_STATS && ($oFile->getExtension() != 'eml') && $bLog)
          Controler::infosSetFile($oFile, false);
        
        // GET a copy from XML_File's XML_document instance
        $this->set($oFile->getFreeDocument());
        if ($oFile->isFileSecured()) $this->appendLoadRights();
      }
      
    } else dspm(xt('Fichier %s introuvable', new HTML_Strong($sPath)), 'xml/error');
    
    return false;
  }
  
  public function loadFreeFile($sPath) {
    
    $bResult = parent::load(MAIN_DIRECTORY.$sPath, LIBXML_COMPACT);
    
    if ($bResult) {
      
      if (Controler::useStatut('xml/report')) dspm(xt('Chargement [libre] du fichier %s', new HTML_Strong($sPath)), 'xml/report');
      if ($this->isEmpty()) dspm(xt('Aucun contenu [libre] dans %s', new HTML_Strong($sPath)), 'xml/warning');
      
      XML_Controler::addStat('file');
      
    } else dspm(xt('Impossible de charger le fichier [libre] %s', new HTML_Strong($sPath)), 'xml/warning');
    
    return $bResult;
  }
  
  public function loadDB($sPath) {
    
    $bResult = false;
    
    if (Controler::isAdmin()) {
      
      if ($sResult = Controler::getDatabase()->query(substr($sPath, 1)))
        $bResult = $this->loadText('<root>'.$sResult.'</root>');
    }
    
    return $bResult;
  }
  
  public function loadText($sContent) {
    
    if ($sContent) {
      
      // TODO : Bug with not UTF-8
      
      if (!@parent::loadXML($sContent)) {
        
        dspm(array(t('Chargement texte impossible, contenu invalide :'),
          new HTML_Hr, stringResume($sContent, 500)), 'xml/warning');
          
        dspm(new HTML_Tag('pre', $sContent));
        return false;
        //if (DEBUG) echo 'Chargement texte impossible : '.xmlize($sContent).new HTML_Br;
        
      } else if ($this->isEmpty()) Controler::addMessage(t('Chargement texte échoué, aucun résultat'), 'xml/warning');
      
      //dspl($sContent);
      
      XML_Controler::addStat('load');
      
    } else Controler::addMessage(t('Document : Chargement texte impossible, la chaîne est vide !'), 'xml/error');
    
    return true;
    // $this->appendLoadRights(); TODO or not
  }
  
  public function setFile($oFile) {
    
    $this->oFile = $oFile;
    return $oFile;
  }
  
  public function getFile() {
    
    return $this->oFile;
  }
  
  public function saveFree(XML_Directory $oDirectory = null, $sName = null) {
    
    $oResult = null;
    
    if ($oDirectory && $sName) {
      
      $this->formatOutput = true;
      $sPath = MAIN_DIRECTORY.$oDirectory.'/'.$sName;
      
      if (file_exists($sPath)) unlink($sPath);
      
      $bResult = parent::save($sPath);
      
      if (!$bResult) dspm(t('Le document n\'a pu être sauvegardé'), 'file/error');
      else $oDirectory->updateFile($sName);
      
      $oResult = $oDirectory->getFile($sName);
      
    } else dspm(t('Impossible de sauvegarder le document, arguments invalide'), 'file/error');
    
    return $oResult;
  }
  
  public function save($sPath = null) {
    
    $oResult = null;
    $oFile = null;
    
    if ($sPath || ($oFile = $this->getFile())) {
      
      $sName = substr(strrchr($sPath, '/'), 1);
      $sDirectory = substr($sPath, 0, strlen($sPath) - strlen($sName) - 1);
      
      if ($oDirectory = Controler::getDirectory($sDirectory)) {
        
        if ($sPath) $oFile = Controler::getFile($sPath);
        
        $bSecurityFile = ($sName == SYLMA_SECURITY_FILE);
        $bAccess = ($oFile) ? $oFile->checkRights(MODE_WRITE) : $oDirectory->checkRights(MODE_WRITE);
        
        // TEMPORARY System fo avoiding erasing of protected files from not admin users. TODO
        
        if (!$bSecurityFile && $bAccess) { //$bSecurityFile &&  || (Controler::isAdmin())
          
          if ($oFile) $oDocument = $oFile->getFreeDocument();
          
          if ($oFile && $oFile->isFileSecured()) {
            
            // Secured File
            $bSecured = false;
            
            $oNodes = $oDocument->query('//*[@ls:owner or @ls:mode or @ls:group]', 'ls', SYLMA_NS_SECURITY);
            
            if ($oNodes->length) {
              
              foreach ($oNodes as $oNode) {
                
                if ($oNode) {
                  
                  $iMode = $this->extractMode($oNode);
                  if (!$bSecured && $iMode) $bSecured = !(MODE_WRITE & $iMode);
                }
              }
            }
            
          } else $bSecured = false; // Not secured file
          
          if (!$bSecured) {
            
            $this->setFile($this->saveFree($oDirectory, $sName));
            $oResult = $this->getFile();
            //$oDirectory->updateFile($sName);
            
          } else dspm(xt('Le fichier %s contient des balises protégées, le système ne permet actuellement pas de modifier ce type de fichier, veuillez contacter l\'administrateur !', new HTML_Strong($sPath)), 'error');
          
        } else dspm(xt('Droits insuffisants pour sauvegarder le fichier dans %s !', new HTML_Strong($sPath)), 'error');
        
      } else dspm(xt('Le répertoire de destination %s n\'existe pas !', new HTML_Strong($sPath)), 'error');
      
    } else  dspm(t('Aucun chemin pour la sauvegarde !'), 'error');
    
    return $oResult;
  }
  
  public function saveTemp($sPath = null) {
    
    $sPath = Controler::getUser()->getDirectory('temp').'/dbx-'.uniqid().'.xml';
    
    $this->save($sPath);
    $this->bTemp = true;
    
    if (!$this->getFile()) dspm(xt('Impossible de créer le fichier temporaire dans %s', new HTML_Strong($sPath)), 'xml/warning');
    
    return $this->getFile();
  }
  /**
   * Method loadText() alias
   */
  public function loadXML() {
    
    return $this->loadText($sContent);
  }
  
  public function getChildren() {
    
    if ($this->getRoot()) return $this->getRoot()->getChildren();
    else return null;
  }
  
  public function getRoot() {
    
    if (isset($this->documentElement)) return $this->documentElement;
    else return null;
  }
  
  public function test($sPath) {
    
    return (bool) $this->get($sPath);
  }
  
  public function set() {
    
    $mResult = null;
    
    if (func_num_args() > 1) {
      
      $this->set(func_get_args());
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
          
      if (is_object($mValue)) {
        
        if ($mValue instanceof XML_Document && !method_exists($mValue, 'parse')) {
          
          /* XML_Document */
          
          if ($mValue->getRoot()) {
            
            $mResult = $this->setChild($mValue->getRoot());
            
          } else Controler::addMessage('Document->set() - Document vide', 'xml/notice');
          
        } else if ($mValue instanceof XML_Element) {
          
          /* XML_Element */
          
          $mResult = $this->setChild($mValue);
          
        } else if ($mValue instanceof XML_NodeList) {
          
          /* XML_NodeList */
          //dspm(array('+++ Création de document', view($mValue)), 'action/report');
          $mValue->rewind();
          $this->set($mValue->current());
          $mValue->next();
          
          while ($mValue->valid()) {
            
            $this->add($mValue->current());
            $mValue->next();
          }
          
        } else {
          
          /* Undefined object (Action, XML_Action, others) */
          
          if (method_exists($mValue, 'parse')) {
            
            $mResult = $mValue->parse();
            
            if ($mResult != $mValue) return $this->set($mResult);
            else Controler::addMessage(xt('L\'objet parsé de classe "%s" ne doit pas se retourner lui-même !', new HTML_Strong(get_class($mValue))), 'xml/error');
            
          } else if ($this->getRoot()) $mResult = $this->getRoot()->set($mValue);
        }
        
      } else if (is_array($mValue) && $mValue) {
        
        /* Array */
        
        if (count($mValue) > 1) {
          
          // > 1
          
          $aChildren = array();
          
          $this->set(array_shift($mValue));
          foreach ($mValue as $oChild) $aChildren = $this->add($oChild);
          
          $mResult = $aChildren;
          
          // = 1
          
        } else $mResult = $this->set(array_pop($mValue));
        
        // If String load as XML String
        
      } else if (is_string($mValue)) $mResult = $this->startString($mValue);
      
    } else if ($this->getRoot()) $this->getRoot()->remove();
    
    return $mResult;
  }
  
  public function createNode($sName, $oContent = '', $aAttributes = null, $sUri = null) {
    
    return new XML_Element($sName, $oContent, $aAttributes, $sUri, $this);
  }
  
  public function addNode($sName, $oContent = '', $aAttributes = null, $sUri = '') {
    
    if ($this->getRoot()) return $this->getRoot()->addNode($sName, $oContent, $aAttributes, $sUri);
    else return $this->setChild($this->createNode($sName, $oContent, $aAttributes, $sUri));
  }
  
  public function setChild($oChild) {
    
    if (!$this->isEmpty()) $this->getRoot()->remove();
    
    if ($oChild && is_object($oChild)) {
      
      if ((bool) $oChild->getDocument() && ($oChild->getDocument() !== $this)) {
        
        $oChild = $this->importNode($oChild);
      }
      
      parent::appendChild($oChild);
      
    } else Controler::addMessage('Element->setChild : No object', 'xml/error');
    
    // $this->appendRights();
    
    return $oChild;
  }
  
  /**
   * Method add() alias
   */
  public function appendChild() {
    
    $this->add(func_get_args());
  }
  
  public function add() {
    
    if (func_num_args() > 1) {
      
      foreach (func_get_args() as $mValue) $this->add($mValue);
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
      
      if ($this->getRoot()) return $this->getRoot()->add($mValue);
      else return $this->set($mValue);
    }
    
    return null;
  }
  
  public function importNode($oChild, $bDepth = true) {
    
    if ($oChild) {
      
      /*if ($oChild instanceof HTML_Tag) {
        
        $oChild = $oChild->cloneNode(true);
        $oChild->parse();
      }*/
      
      return parent::importNode($oChild, $bDepth);
      
    } else Controler::addMessage('Document->importNode : No object', 'xml/error');
  }
  
  /**
   * Extract the first result of a DOMNodeList if possible
   */
  public function queryArray($sQuery, $sPrefix = '', $sUri = '') {
    
    $aResult = array();
    $oResult = $this->query($sQuery, $sPrefix, $sUri);
    foreach ($oResult as $oStatut) $aResult[] = $oStatut->read();
    
    return $aResult;
  }
  
  /**
   * Return the first row of a result of an xpath query
   */
  public function queryOne($oCollection) {
    
    if ($oCollection && $oCollection->length) return $oCollection->item(0);
    else return null;
  }
  
  /**
   * Extract a string value from a mixed variable
   */
  public function queryString($mValue) {
    
    if (is_object($mValue)) {
      
      if (get_class($mValue) == 'DOMNodeList') $mValue = $mValue->item(0);
      if (get_class($mValue) == 'XML_Element' || get_class($mValue) == 'XML_Attribute') $mValue = $mValue->nodeValue;
    }
    
    return (string) $mValue;
  }
  
  public function updateAllNamespaces() {
    
    $this->set($this->getRoot()->updateAllNamespaces());
  }
  
  public function updateNamespaces($mFrom = null, $mTo = null, $mPrefix = '', $oParent = null) {
    
    if ($this->getRoot()) return new XML_Document($this->getRoot()->updateNamespaces($mFrom, $mTo, $mPrefix, $oParent));
    else return new XML_Document;
  }
  
  /**
   * Return a new document, with only the elements selected by namespace
   */
   public function extractNS($sNamespace, $bKeep = false) {
    
    if (!$this->isEmpty()) return new XML_Document($this->getRoot()->extractNS($sNamespace, $bKeep));
    else return null;
  }
  
  /** 
   * Check validity against W3C XMLSchema
   */
  public function validate(XML_Document $oSchema, $bMessages = false) {
    
    $oParser = new XSD_Parser($oSchema, $this, false, $bMessages);
    
    return $oParser->isValid();
  }
  
  /** 
   * Build validity model with W3C XMLSchema
   */
  public function getModel(XML_Document $oSchema, $bMessages = true, $bLoadRefs = true) {
    
    $oParser = new XSD_Parser($oSchema, $this, true, $bMessages, true, $bLoadRefs);
    
    return $oParser->parse();
  }
  
  public function parseXSL($oDocument, $bXML = true) {
    
    if (is_object($oDocument)) {
      
      if (!$oDocument instanceof XSL_Document) {
        
        $oTemplate = new XSL_Document($oDocument);
        $oTemplate->setFile($oDocument->getFile());
        
      } else $oTemplate = $oDocument;
      
      return $oTemplate->parseDocument($this, $bXML);
      
    } else Controler::addMessage(xt('Template %s invalide !', Controler::formatResource($oDocument)), 'xml/error');
    
    return null;
  }
  
  /**
   * Method __toString() alias
   */
  public function saveXML() {
    
    return $this->display();
  }
  
  /**
   * Return path of the document as a string
   */
  public function parseFile() {
    
    if ($this->getFile()) return $this->getFile()->parse();
    else return new HTML_Em(t('- aucun chemin -'));
  }
  
  public function __call($sMethod, $aArguments) {
    
    $oResult = null;
    
    if ($oRoot = $this->getRoot()) {
      
      if (method_exists($oRoot, $sMethod)) {
        
        $aEvalArguments = array();
        for ($i = 0; $i < count($aArguments); $i++) $aEvalArguments[] = "\$aArguments[$i]";
        
        eval('$oResult = $oRoot->$sMethod('.implode(', ', $aEvalArguments).');');
        
      } else Controler::addMessage(xt('Document : Méthode %s introuvable', new HTML_Strong($sMethod)), 'xml/error');
      
    } else Controler::addMessage(xt('Document vide : Impossible d\'appliquer la méthode %s', new HTML_Strong($sMethod)), 'xml/error');
    
    return $oResult;
  }
  
  public function formatOutput() {
    
    if ($this->getRoot()) $this->getRoot()->formatOutput();
  }
  
  public function view($bContainer = true, $bIndent = true, $bFormat = false) {
    
    $oView = new XML_Document($this);
    
    if ($bIndent) $oView->formatOutput();
    
    if ($bFormat) $sResult = htmlspecialchars($oView->display(true, false));
    else $sResult = $oView->display(true, false);
    
    if ($bContainer) {
      
      $oResult = new HTML_Tag('pre');
      $oResult->add($sResult);
      
    } else $oResult = $sResult;
    
    return $oResult;
  }
  
  public function dspm() {
    
    dspf($this);
  }
  
  public function dsp($bHtml = false) {
    
    echo $this->view(true, true, $bHtml);
    /*
    $oView = new XML_Document($this);
    $oView->formatOutput();
    
    $oPre = new XML_Element('pre');
    $oPre->addText($oView);
    
    echo $oPre;*/
  }
  
  public function display($bHtml = false, $bDeclaration = true) {
    
    $sResult = '';
    
    if (!$this->isEmpty()) {
      
      if ($bHtml) $sResult = parent::saveXML(null); //LIBXML_NOEMPTYTAG
      else {
        
        if ($bDeclaration) $sResult = parent::saveXML(); // TODO (?) empty tag ar not closed with ../> but with closing tag
        else $sResult = parent::saveHTML(); // entity encoding
      }
    }
    
    if (!$bDeclaration) $sResult = substr($sResult, 39);
    
    return $sResult;
  }
  
  public function __toString() {
    
    return $this->display();
  }
  
  /*public function __sleep() {
    
    return (string) $this;
  }
  
  public function __wakeup() {
    
    
  }*/
  
  public function __destruct() {
    
    if ($this->bTemp && $this->getFile()) $this->getFile()->delete(false, false);
  }
}

class XSL_Document extends XML_Document {
  
  private $oProcessor = null;
  
  public function __construct($mChildren = '', $iMode = MODE_READ, $bInclude = true) {
    
    $this->oProcessor = new XSLTProcessor;
    
    if ($mChildren) parent::__construct($mChildren, $iMode);
    else {
      
      parent::__construct(new XML_Element('xsl:stylesheet', null, array('xmlns' => SYLMA_NS_XHTML, 'version' => '1.0'), SYLMA_NS_XSLT), $iMode);
      
      //new XML_Element('output', array('method' => 'xml', 'encoding' => 'utf-8'), true, 'xsl'));
      // 'xmlns:fo'    => 'http://www.w3.org/1999/XSL/Format',
      // 'xmlns:axsl'  => 'http://www.w3.org/1999/XSL/TransformAlias',
    }//$this->includeExternals();
  }
  
  public function removeParameter($sLocalName, $sUri = '') {
    
    $bResult = $this->getProcessor()->removeParameter($sUri, $sLocalName);
    
    if (!$bResult) Controler::addMessage(xt('Suppression impossible du paramètre %s - [%s]', new HTML_Strong($sName), new HTML_Strong($sValue), new HTML_Strong($sUri)), 'xml/warning');
    return $bResult;
  }
  
  public function setParameters($aParams, $sUri = '') {
    
    foreach ($aParams as $sName => $sValue) $this->setParameter($sName, $sValue, $sUri);
  }
  
  public function setParameter($sName, $sValue, $sUri = '') {
    
    $bResult = $this->getProcessor()->setParameter($sUri, $sName, (string) $sValue);
    
    if (!$bResult) Controler::addMessage(xt('Création du paramètre %s impossible avec la valeur %s - [%s]', new HTML_Strong($sName), new HTML_Strong($sValue), new HTML_Strong($sUri)), 'xml/warning');
    return $bResult;
  }
  
  public function getParameter($sLocalName, $sUri = '') {
    
    $mResult = $this->getProcessor()->getParameter($sUri, $sLocalName);
    
    if (!$mResult) Controler::addMessage(xt('Aucun résultat pour le paramètre %s - [%s]', new HTML_Strong($sName), new HTML_Strong($sUri)), 'xml/warning');
    return $mResult;
  }
  
  private function getProcessor() {
    
    return $this->oProcessor;
  }
  
  /*public function includeExternals($sQuery = , $aNS = , $aAttributes = array('extension-element-prefixes'), &$aPaths = array(), $iLevel = 0) {
    
    return parent::includeExternals($sQuery, $aNS, $aAttributes, $aPaths, $iLevel);
    //dspf($this);
  }*/
  
  public function includeElement(XML_Element $oElement, XML_Element $oExternal = null) {
    
    $sPrefixes = 'extension-element-prefixes';
    
    if ($sResult = $oElement->getAttribute($sPrefixes)) {
      
      if ($sTarget = $this->getAttribute($sPrefixes)) {
        
        $aTarget = explode(' ', $sTarget);
        $aResult = $aPrefixes = array_diff(explode(' ', $sResult), $aTarget);
        
      } else {
        
        $aTarget = array();
        $aResult = $aPrefixes = explode(' ', $sResult);
      }
      
      foreach ($aPrefixes as $iPrefix => $sPrefix) {
        
        if (!$this->getNamespace($sPrefix)) {
          
          if ($sNamespace = $oElement->getNamespace($sPrefix)) {
            
            // TODO to add a namespace
            $this->setAttribute($sPrefix.':ns', 'null', $sNamespace); 
            // $this->setAttribute('xmlns:'.$sPrefix, $sNamespace);
            
          } else unset($aResult[$iPrefix]);
        }
      }
      
      $this->setAttribute($sPrefixes, implode(' ', array_merge($aResult, $aTarget)));
    }
    
    if ($oExternal) {
      
      switch ($oExternal->getName(true)) {
        
        case 'include' : $oExternal->replace($oElement->getChildren()); break;
        case 'import' : $this->add($oElement->getChildren()); break;
      }
      
    } else $this->shift($oElement->getChildren());
    
  }
  
  public function includeExternal(XSL_Document $oTemplate, XML_Element $oExternal = null, $aMarks = array(), &$aPaths = array(), $iLevel = 0) {
    
    if (!$oTemplate->isEmpty()) {
      
      $oTemplate->includeExternals($aPaths, $iLevel + 1);
      
      foreach ($aMarks as $eMark) { // mark elements with filename
        
        foreach ($oTemplate->query('//la:*', array('la' => $eMark->read())) as $eElement)
          $eElement->setAttribute('file-source', (string) $oTemplate->getFile());
      }
      
      $this->includeElement($oTemplate->getRoot(), $oExternal);
    }
  }
  
  public function includeExternals(&$aPaths = array(), $iLevel = 0) {
    
    $iMaxLevel = SYLMA_MAX_INCLUDE_DEPTH;
    
    if ($iLevel > $iMaxLevel) {
      
      dspm(xt('Trop de redondance lors de l\'importation dans %s', $this->getFile()->parse()), 'xml/warning');
      
    } else {
      
      $oExternals = $this->query('/*/xsl:include | /*/xsl:import', array('xsl' => SYLMA_NS_XSLT));
      
      if ($oExternals->length) {
        
        if ($this->getFile()) $aPaths[] = (string) $this->getFile();
        $aMarks = $this->query('le:mark', array('le' => SYLMA_NS_EXECUTION)); // look for mark elements source
        
        foreach ($oExternals as $oExternal) {
          
          if ($oFile = $this->buildExternal($oExternal, $aPaths)) {
            
            $oTemplate = new XSL_Document((string) $oFile, MODE_EXECUTION);
            $this->includeExternal($oTemplate, $oExternal, $aMarks, $aPath, $iLevel);
          }
          
          $oExternal->remove();
        }
      }
    }
  }
  
  public function parseDocument(XML_Document $oDocument, $bXML = true) { // WARNING, XML_Document typed can cause crashes
    
    $mResult = null;
    
    if ($oDocument && !$oDocument->isEmpty() && !$this->isEmpty()) {
      
      $this->includeExternals();
      
      $this->getProcessor()->importStylesheet($this);
      
      $sResult = $this->getProcessor()->transformToXML($oDocument);
      
      if (Controler::isAdmin() && libxml_get_errors()) { // TODO, nice view
        foreach (libxml_get_errors() as $oError) {
          dspm(xt('%s : %s', new HTML_Strong('Libxml'), xmlize($oError->message)), 'warning');
        }
      }
      
      if ($bXML) {
        
        $mResult = new XML_Document($sResult);
        
        //if ($mResult->isEmpty()) Controler::addMessage(array(t('Un problème est survenu lors de la transformation XSL !'),new HTML_Hr,$sResult), 'xml/warning');
        
      } else $mResult = $sResult;
      //} else $mResult = substr($sResult, 21);
      
      XML_Controler::addStat('parse');
    }
    
    return $mResult;
  }
}


