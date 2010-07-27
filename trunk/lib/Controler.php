<?php

/**
 * Contrôleur général du framework
 */
class Controler {
  
  private static $iStartTime = 0;
  private static $iBacktraceLimit = SYLMA_BACKTRACE_LIMIT;
  
  private static $oMessages = null;
  private static $oUser = null;
  private static $oDirectory = null;
  
  private static $oWindow;
  private static $oWindowSettings;
  private static $oSettings;
  private static $oRedirect;
  private static $oDatabase;
  
  private static $oPath = null;      // Chemin complet du fichier. Ex: /utilisateur/edit/1.html
  private static $aPaths = array(); // Liste des précédents chemins redirigés, ajoutés dans oRedirect
  private static $sAction = '';     // Chemin de l'action. Ex: /utilisateur/edit
  public static $aResults = array();     // Pile of results of the same action in different mime type (typically html + json)
  public static $hasResult = false;
  private static $aQueries = array();
  private static $bUseMessages = false;
  
  private static $aActions = null;      // Array of running actions
  
  public static function trickMe() {
    
    global $aDefaultInitMessages;
    global $aExecutableExtensions;
    global $aDB;
    
    self::$iStartTime = microtime(true);
    self::$aActions[] = new XML_Action();
    
    // Authentification : récupération du cookie User
    self::setUser(self::loadUser());
    
    // Define error_report
    self::setReportLevel();
    
    // Main Messages object
    self::$oMessages = new Messages();
    
    // Root directory
    self::$oDirectory = new XML_Directory('', '', array('owner' => 'root', 'group' => '0', 'mode' => '700', 'user-mode' => null));
    
    // Loading general parameters
    self::loadSettings();
    
    // Set Controler ready
    self::useMessages(true);
    
    if (SYLMA_USE_DB) self::setDatabase(new XML_Database($aDB));
    
    // Récupération du cookie Redirect qui indique qu'une redirection a été effectuée
    $oRedirect = self::loadRedirect();
    
    // Parse of the request_uri, creation of the window
    self::loadContext();
    
    // Reload last alternatives mime-type results
    self::loadResults();
    
    if (($sExtension = Controler::getPath()->getExtension()) &&
     (!in_array($sExtension, array('eml', 'iml')) || strtobool(self::getPath()->getAssoc('no-action'))) && 
     ($oFile = self::getFile(Controler::getPath().'.'.$sExtension)) &&
     $oFile->checkRights(MODE_READ)) {
      
      /* A file */
      
      self::getWindow()->loadAction($oFile);
      
    } else if (in_array(self::getPath()->getExtension(), $aExecutableExtensions)) {
      
      /* An action */
      
      self::getPath()->parsePath();
      
      if ($mResult = self::getResult()) {
        
        // Pre-recorded result
        
        $oResult = self::getWindow()->loadAction($mResult); // TODO : make XML_Action
        
      } else {
        
        /*
        if (!isset($_SESSION['temp-paths'])) $_SESSION['temp-paths'] = array();
        //$_SESSION['temp-paths'] = array();
        $_SESSION['temp-paths'][] = (string) self::getPath();
        dspf($_SESSION['temp-paths']);
        */
        
        // Load file of the interface's paths
        
        Action_Controler::loadInterfaces();
        
        // Get then send the action
        
        $oAction = new XML_Action(self::getPath(), $oRedirect);
        
        if ($oAction->isEmpty() && SYLMA_EMPTY_REDIRECT) self::errorRedirect(); // no rights / empty main action
        else {
          
          if (self::getWindowSettings()->hasAttribute('action')) {
            
            $oResult = null;
            self::getWindow()->getPath()->setAssoc('window-action', $oAction);
            
          } else $oResult = self::getWindow()->loadAction($oAction); // TODO or not todo : make XML_Action
        }
      }
      
      /* Action redirected */
      
      if (is_object($oResult) && $oResult instanceof Redirect) {
        
        self::doHTTPRedirect($oResult);
        //if (self::isWindowType('html') || self::isWindowType('redirection')) self::doHTTPRedirect($oResult);
        //else self::doAJAXRedirect($oResult);
      }
    }
    
    return self::getWindow();
  }
  
  private static function setReportLevel() {
    
    if (self::isAdmin()) {
      
      // debug or not debug..
      if (DEBUG) error_reporting(E_ALL);
      else error_reporting(ERROR_LEVEL);
      
    } else error_reporting(0);
  }
  
  private static function loadSettings() {
    
    self::$oSettings = new XML_Document(SYLMA_PATH_SETTINGS, MODE_EXECUTION);
    
    $oAllowed = new XML_Document(self::getSettings('messages/allowed/@path'));
    
    $aMessages = self::getMessages()->getMessages();
    self::$oMessages = new Messages($oAllowed, $aMessages);
  }
  
  public static function getSettings($sQuery = '') {
    
    if ($sQuery) {
      
      if (array_key_exists($sQuery, self::$aQueries)) return self::$aQueries[$sQuery];
      else $sResult = self::$aQueries[$sQuery] = self::$oSettings->read($sQuery);
      
      if (!$sResult) dspm(xt('Aucun paramètre recupéré dans %s avec la requête "%s"', self::$oSettings->getFile()->parse(), new HTML_Strong($sQuery)), 'action/error');
      
      return $sResult;
      
    } else return self::$oSettings;
  }
  
  public static function parseGet() {
    
    $sQuery = substr($_SERVER['QUERY_STRING'], 2);
  }
  /*
   * load GET, build action path, show-index, load window settings
   **/
  private static function loadContext() {
    
    //$aGET = self::parseGet();
    $aGET = $_GET;
    
    if (isset($aGET['q']) && $aGET['q']) {
      
      $sPath = $aGET['q'];
      unset($aGET['q']);
      
    } else $sPath = '/';
    
    // L'extension (si elle est correct) indique le type de fenêtre
    
    $oPath = new XML_Path('/'.$sPath, $aGET, false);
    if (!$sExtension = $oPath->parseExtension(true)) $sExtension = 'html';
    $sExtension = strtolower($sExtension);
    
    if (self::isAdmin() && $oPath->getIndex(0, true) == 'show-report') {
      
      $oPath->getIndex();
      
      if (!$oAction = self::getMessages()->get('action')) $oAction = self::getMessages()->addNode('action');
      $oAction->addNode('report');
    }
    
    self::$oPath = $oPath;
    
    $oWindowSettings = self::getSettings()->get("window/*[extensions[contains(text(), '$sExtension')]]");
    if (!$oWindowSettings) $oWindowSettings = new XML_Element('any');
    
    self::$oWindowSettings = $oWindowSettings;
    
    // Creation of the window
    
    $oWindow = null;
    
    if ($sInterface = $oWindowSettings->getAttribute('interface')) {
      
      Action_Controler::loadInterfaces();
      
      if ($oInterface = Action_Controler::buildInterface(new XML_Document($sInterface))) {
        
        if (!$sAction = $oWindowSettings->getAttribute('action')) {
          
          dspm(t('Impossible de charger la fenêtre, action introuvable'), 'action/error');
          
        } else {
          
          if ($sInterface = $oInterface->readByName('file')) $sInterface = self::getAbsolutePath($sInterface, $oInterface->getFile()->getParent());
          $oWindow = self::buildClass($oInterface->readByName('name'), $sInterface, array($sAction, self::getRedirect()));
        }
      }
      
    } else $oWindow = self::buildClass(ucfirst(self::getWindowType()));
    
    if (!$oWindow) dspm(t('Aucune fenêtre valide, impossible de charger le site'), 'error');;
    
    self::$oWindow = $oWindow;
  }
  
  public static function loadResults() {
    
    if (!array_key_exists('results', $_SESSION)) $_SESSION['results'] = array();
    self::$aResults = $_SESSION['results'];
    
    foreach (self::$aResults as $sKey => $aAction) {
      
      if (!array_key_exists('result-time', $aAction)) $fTime = 0;
      else $fTime = $aAction['result-time'];
      
      if ((microtime(true) - $fTime) > SYLMA_RESULT_LIFETIME) unset(self::$aResults[$sKey]);
    }
    
    self::updateResults();
    // return self::$aResults;
  }
  
  public static function updateResults() {
    
    $_SESSION['results'] = self::$aResults;
  }
  
  public static function hasResult() {
    
    return self::$hasResult;
  }
  
  public static function addResult($mResult, $sWindow) {
    
    $sPath = self::getPath()->getSimplePath();
    
    if (!array_key_exists($sPath, self::$aResults)) self::$aResults[$sPath] = array();
    self::$aResults[$sPath]['result-time'] = microtime(true);
    
    if (!array_key_exists($sWindow, self::$aResults[$sPath])) self::$aResults[$sPath][$sWindow] = array();
    
    self::$aResults[$sPath][$sWindow][] = $mResult;
    self::updateResults();
    
    self::$hasResult = true;
    
    return $mResult;
  }
  
  private static function getResult() {
    
    $sPath = self::getPath()->getSimplePath();
    $sWindow = self::getPath()->getExtension();
    
    if (isset(self::$aResults[$sPath][$sWindow])) {
      
      $mResult = array_pop(self::$aResults[$sPath][$sWindow]);
      
      if (!count(self::$aResults[$sPath][$sWindow])) unset(self::$aResults[$sPath][$sWindow]);
      if (!count(self::$aResults[$sPath])) unset(self::$aResults[$sPath]);
      
      self::updateResults();
      
      return $mResult;
      
    } else return null;
  }
  
  private static function loadRedirect() {
    
    $oRedirect = new Redirect();
    
    // Une redirection a été effectuée
    
    if (array_key_exists('redirect', $_SESSION)) {
      
      $oRedirect = unserialize($_SESSION['redirect']);
      unset($_SESSION['redirect']);
      
      // Récupération des messages du Redirect et suppression
      
      if (get_class($oRedirect) == 'Redirect') {
        
        $oRedirect->setReal();
        
        $oMessages = $oRedirect->getDocument('messages');
        // $oMessages = new XML_Document;
        // $oMessages->loadText($oRedirect->getArgument('messages'));
        $aMessages = $oMessages->query('//lm:message', 'lm', SYLMA_NS_MESSAGES);
        
        $oRedirect->setMessages($aMessages);
        
        if ($aMessages->length) self::getMessages()->addMessages($aMessages);
        
      } else {
        
        $oRedirect = new Redirect();
        self::addMessage(t('Session Redirect perdu !'), 'warning');
      }
      
    } else {
      
      if ($_POST) {
        
        //$oValues = new XML_Document(new XML_Element('post', null, null, SYLMA_NS_XHTML));
        $oValues = new XML_Document('post');
        
        foreach ($_POST as $sKey => $mValue) {
          
          //if (is_string($mValue)) $oTest = $oValues->addNode($sKey, $mValue, null, SYLMA_NS_XHTML);
          if (is_string($mValue)) $oTest = $oValues->addNode($sKey, $mValue);
          
          //else if (is_array($mValue))
        }
        
        $oRedirect->setDocument('post', $oValues);
      }
    }
    
    self::$oRedirect = $oRedirect;
    
    return $oRedirect;
  }
  
  private static function getRedirect() {
    
    return self::$oRedirect;
  }
  
  private static function getMime($sExtension) {
    
    switch (strtolower($sExtension)) {
      
      case 'jpg' : $sExtension = 'jpeg';
      case 'jpeg' :
      case 'png' :
      case 'gif' : return 'image/'.$sExtension;
      
      case 'js' : return 'application/javascript';
      case 'css' : return 'text/css';
      case 'xml' :
      case 'xsl' : return 'text/xml';
      
      default : return 'plain/text';
    }
  }
  
  public static function setContentType($sExtension) {
    
    header('Content-type: '.self::getMime($sExtension));
  }

  private static function getActionRights($sPath = null) {
    
    if (!$sPath) $sPath = self::getAction();
    
    $aActionRights = isset(self::$aRights[$sPath]['rights']) ? self::$aRights[$sPath]['rights'] : array();
    
    if (!is_array($aActionRights)) $aActionRights = array($aActionRights);
    
    return $aActionRights;
  }
  
  /*****************/
  /* Infos & Stats */
  /*****************/
  
  public static function getSystemInfos() {
    
    $oView = new HTML_Ul(null, array('class' => 'msg-system'));
    
    $oMessage = new HTML_Strong(t('Authentification').' : ');
    
    if (self::getUser()->isReal()) $oView->addMultiItem($oMessage, self::getUser()->getArgument('full-name'));
    else $oView->addMultiItem($oMessage, new HTML_Tag('em', t('- aucun -')));
    
    if (self::getUser()->isReal()) {
      
      $oMessage = new HTML_Strong(t('Groupe(s)').' : ');
      
      if (self::getUser()->getGroups()) $oView->addMultiItem($oMessage, implode(', ', self::getUser()->getGroups()));
      else $oView->addMultiItem($oMessage, new HTML_Tag('em', t('- aucun -')));
    }
    
    $oView->addMultiItem(new HTML_Strong(t('Adresse').' : '), self::getPath());
    
    $oMessage = new HTML_Strong(t('Redirection').' : ');
    
    if (self::getRedirect()) {// && ) {
      
      if (self::getRedirect()->isReal()) $oPath = new HTML_Div(self::getRedirect()->getSource()->getOriginalPath());
      else $oPath = new HTML_Div(new HTML_Tag('em', t('- aucune -')));
      
      $nItem = $oView->addMultiItem($oMessage, $oPath);
      foreach (self::getRedirect()->getDocuments() as $sKey => $oDocument) $nItem->add($sKey, ': ', view($oDocument, false));
    }
    
    $oView->addMultiItem(new HTML_Strong(t('Fenêtre').' : '), self::getWindowType());
    $oView->addMultiItem(new HTML_Strong(t('Date & heure').' : '), date('j M Y').' - '.date('H:i'));
    $oView->addMultiItem(new HTML_Strong(t('Statistiques XML').' : '), XML_Controler::viewStats());
    $oView->addMultiItem(new HTML_Strong(t('Resources').' : '),
      number_format(microtime(true) - self::$iStartTime, 3).' s', new HTML_Br,
      formatMemory(memory_get_peak_usage()));
    
    return $oView;
  }
  
  public static function infosSetFile($oFile, $bFirstTime) {
    
    if ($oLast = array_last(self::$aActions)) $oLast->resumeFile($oFile, $bFirstTime);
  }
  
  public static function infosSetQuery($sQuery) {
    
    if ($oLast = array_last(self::$aActions)) $oLast->resumeQuery($sQuery);
  }
  
  public static function infosOpenAction($oCaller) {
    
    self::$aActions[] = $oCaller;
  }
  
  public static function infosCloseAction($oAction) {
    
    if (self::$aActions) {
      
      array_pop(self::$aActions);
      if (($oLast = array_last(self::$aActions)) && ($oLast !== $oAction)) $oLast->resumeAction($oAction);
    }
  }
  
  public static function viewResume() {
    
    $oAction = array_pop(self::$aActions);
    
    $oAction->parse(array('time' => self::$iStartTime), false);
    
    $oResume = new XML_Element('controler', $oAction->viewResume());
    
    $oResume->getFirst()->setAttribute('path', '<controler>');
    $oTemplate = new XSL_Document(Controler::getSettings('actions/template/@path'), MODE_EXECUTION);
    $oTemplate->setParameter('path-editor', SYLMA_PATH_EDITOR);
    
    return $oResume->getDocument()->parseXSL($oTemplate);
  }
  
  public static function getInfos() {
    
    return new HTML_Tag('div',
        array(self::getSystemInfos(), self::viewResume()),
        array('class' => 'msg-infos clear-block'));
  }
  
  /* Window methods : TODO clean */
  
  public static function getWindowSettings() {
    
    return self::$oWindowSettings;
  }
  
  public static function getWindow() {
    
    return self::$oWindow;
  }
  
  public static function getWindowType() {
    
    if ($sClass = self::getWindowSettings()->getAttribute('class')) return $sClass;
    else return self::getWindowSettings()->getName(true);
  }
  
  public static function isWindowType($sWindowType) {
    
    return (self::getWindowType() == $sWindowType);
  }
  
  
  /*************/
  /* Redirects */
  /*************/
  
  public static function errorRedirect($mMessages = null, $sStatut = 'error') {
    
    if ($mMessages) Controler::addMessage($mMessages, $sStatut);
    
    if ($sExtension = self::getPath()->getExtension()) $sExtension = '.'.$sExtension;
    
    $sPath = SYLMA_PATH_ERROR.$sExtension;
    
    self::doHTTPRedirect(new Redirect($sPath));
  }
  
  private static function doHTTPRedirect($oRedirect) {
    
    self::doRedirect($oRedirect);
    
    // Redirection
    
    $sPath = (string) $oRedirect;
    
    if ($sPath) {
      
      header("Location: $sPath");
      exit;
      
    } else self::errorRedirect('Redirection incorrect !');
  }
  
  private static function doRedirect($oRedirect) {
    
    // Récupération et ajout dans le Redirect des messages en attente
    
    $oRedirect->getMessages()->addMessages(self::getMessages()->getMessages());
    
    // Ajout des messages requêtes si admin
    
    // if (self::isAdmin()) $oRedirect->getMessages()->addMessages(db::getQueries('old')->getMessages());
    
    $oRedirect->setDocument('messages', $oRedirect->getMessages());
    $oRedirect->setSource(Controler::getPath());
    
    // Redirection
    
    $_SESSION['redirect'] = serialize($oRedirect);
  }
  
  public static function error404() {
    
    header('HTTP/1.0 404 Not Found');
    // echo 'Erreur 404 :\'(';
    exit;
  }
  
  /* Backtrace / Messages */
  
  public static function formatResource($mArgument, $bDecode = false, $iMaxLength = 120) {
    
    if (FORMAT_MESSAGES) {
      
      if (is_string($mArgument)) {
        
        //if (!mb_check_encoding($mArgument, 'UTF-8')) $mArgument = 'ERREUR D\'ENCODAGE';
        
        $aValue = array("'".stringResume($mArgument, $iMaxLength, false)."'", '#999');
        
      } else if (is_bool($mArgument))
        $aValue = $mArgument ? array('TRUE', 'green') :  array('FALSE', 'red');
      else if (is_numeric($mArgument))
        $aValue = array($mArgument, 'green');
      else if (is_array($mArgument)) {
        
        // Arrays
        
        if (count($mArgument)) {
        $iCount = count($mArgument) - 1;
          
          $oContent = new HTML_Div(null, array('style' => 'display: inline;'));
          foreach ($mArgument as $mKey => $mValue) {
            
            $oContent->add(view($mKey), ' => ', self::formatResource($mValue, $bDecode));
            if ($iCount) $oContent->add(', ');
            
            $iCount--;
          }
          
        } else $oContent = '';
        
        $aValue = array(xt('array[%s](%s)', new HTML_Strong(count($mArgument)), $oContent), 'orange');
        
      } else if (is_object($mArgument)) {
        
        // Objects
        
        if ($mArgument instanceof XML_Document && !($mArgument instanceof XML_Action)) {
          
          /* XML_Document */
          
          if (MESSAGES_SHOW_XML) {
            
            if ($mArgument->isEmpty()) {
              
              $mContent = get_class($mArgument).' (vide)';
              
            } else {
              
              $oContainer = $mArgument->view(true, true, $bDecode);
              $oContainer->addClass('hidden');
              
              $mContent = array(get_class($mArgument), $oContainer);
            }
            
            $aValue = array(new HTML_Div($mContent, array('class' => 'element')), 'purple');
            
          } else $aValue = array(array(get_class($mArgument), ' => ', $mArgument->viewResume(160, false)), 'purple');
          
        } else if ($mArgument instanceof XML_Action) {
          
          $oContainer = $mArgument->getPath()->parse();
          $oContainer->addClass('hidden');
          
          $mContent = array(get_class($mArgument), $oContainer);
          
          $aValue = array(new HTML_Div($mContent, array('class' => 'element')), 'magenta');
          
        } else if ($mArgument instanceof XML_Element) {
          
          if (MESSAGES_SHOW_XML) {
            
            $oContainer = $mArgument->view(true, true, $bDecode);
            // $oContainer = new HTML_Span;
            $oContainer->addClass('hidden');
            
            $aValue = array(new HTML_Div(array(
              strtoupper($mArgument->getName()),
              $oContainer), array('class' => 'element')), 'blue');
            
          } else $aValue = array(new HTML_Span($mArgument->viewResume(160, false)), 'gray');
          
        } else if ($mArgument instanceof XML_NodeList) {
          
          if ($mArgument->length) {
          
            $mArgument->store();
            
            $iCount = $mArgument->length - 1;
            
            $oContent = new HTML_Div(null, array('style' => 'display: inline;'));
            foreach ($mArgument as $mKey => $mValue) {
              
              $oContent->add(view($mKey), ' => ', view($mValue, false));
              if ($iCount) $oContent->add(', ');
              
              $iCount--;
            }
            
            $mArgument->restore();
            
          } else $oContent = '';
          
          $aValue = array(xt('XML_NodeList[%s](%s)', new HTML_Strong($mArgument->length), $oContent), 'green');
          
        } else if ($mArgument instanceof XML_Comment) {
          
          $oContainer = new HTML_Tag('pre', xmlize($mArgument));
          //$oContainer = new HTML_Tag('pre', 'Comment');
          $oContainer->addClass('hidden');
          
          $aValue = array(new HTML_Div(array(
            'XML_Comment',
            $oContainer), array('class' => 'element')), 'blue');
          
        } else if ($mArgument instanceof XML_Text) {
          
          $aValue = array((string) $mArgument, 'green');
        } else {
          
          $sValue = get_class($mArgument);
          if (in_array($sValue, array('XML_Directory', 'XML_File'))) $sValue = stringResume($mArgument, 150);
          
          $aValue = array($sValue, 'red');
        }
        
      } else if ($mArgument === null) $aValue = array('NULL', 'magenta');
      else $aValue = array('undefined', 'orange');
      
      return new HTML_Div($aValue[0], array('style' => 'display: inline; color: '.$aValue[1].';'));
      
    } else {
      
      if (is_string($mArgument))
        $sValue = "'".stringResume($mArgument, $iMaxLength)."'";
      else if (is_array($mArgument)) {
        $sValue = 'array(';
        foreach ($mArgument as $sKey => $mValue) $sValue .= $sKey.'=>'.self::formatResource($mValue).', ';
        $sValue .= ')';
      } else if (is_object($mArgument)) {

        if ($mArgument instanceof XML_NodeList)
          $sValue = 'XML_NodeList('.$mArgument->length.')';
        else if ($mArgument instanceof XML_Element)
					$sValue = $mArgument;
				else
          $sValue = 'Classe : '.get_class($mArgument);
      } else if ($mArgument === null) $sValue = 'NULL';
      else $sValue = 'undefined';
      
      return $sValue;
    }
  }
  
  public static function getBacktrace() {
    
    $aResult = array(); $aLines = array(); $i = 0;
    
    $aBackTrace = debug_backtrace();
    array_shift($aBackTrace);
    
    // if (DEBUG) dsp($aBackTrace);
    // return null;
    
    foreach ($aBackTrace as $aLine) {
      
      if (isset($aLine['line'])) $aLines[] = $aLine['line'];
      else $aLines[] = 'k';
    }
    
    $aLines[] = 'x';
    
    foreach ($aBackTrace as $aTrace) {
      
      if (isset($aTrace['file'])) $sFile = new HTML_Tag('u', strrchr($aTrace['file'], DIRECTORY_SEPARATOR));
      else $sFile = 'xxx';
      
      if (isset($aTrace['class'])) $sClass = "::{$aTrace['class']}";
      else $sClass = '';
      
      // Arguments
      
      $oArguments = null;
      
      if (isset($aTrace['args']) && $aTrace['args']) {
        
        $aArguments = array();
        $iMaxLength = 120 / count($aTrace['args']);
        
        foreach ($aTrace['args'] as $mArgument) {
          
          $aArguments[] = self::formatResource($mArgument, false, $iMaxLength);
          $aArguments[] = new HTML_Strong(', ');
        }
        
        if ($aArguments) array_pop($aArguments);
        
        $oArguments = new HTML_Span($aArguments);
      }
      
      if (FORMAT_MESSAGES) {
        
        $aResult[] = new HTML_Div(array(
          '[',
          new HTML_Span($aLines[$i], array('style' => 'color: blue; font-weight: bold;')),
          '] ',
          $sFile,
          $sClass,
          '::',
          new HTML_Strong($aTrace['function']),
          '(',  $oArguments, ')'));
          
      } else {
        
        $aResult[] = '['.$aLines[$i].'] '.$sFile.$sClass.'::'.$aTrace['function'].'(no display)'.new HTML_Br;
      }
      
      $i++;
    }
    
    return new HTML_Div(array_reverse($aResult), array('style' => 'margin: 3px; padding: 3px; border: 1px solid white; border-width: 1px 0 1px 0; font-size: 0.9em'));
    // self::addMessage(new HTML_Strong(t('Backtrace').' : ').implode('<br/>', $aResult), $sStatut);
    // return new XML_NodeList($aResult);
  }
  
  /* *** */
  
  /**
   * Build an object from the class name with Reflection
   * @param string $sClassName the class name
   * @param string $sFile the file where the class is declared to include
   * @param array $aArgument the arguments to use at the __construct call
   * @return null|object the created object
   */
  public static function buildClass($sClassName, $sFile = '', $aArguments = array()) {
    
    if ($sFile) {
      
      // Include du fichier
      
      $sFile = MAIN_DIRECTORY.$sFile;
      
      if (file_exists($sFile)) require_once($sFile);
      else dspm(xt('Fichier "%s" introuvable !', new HTML_Strong($sFile)), 'action/error');
    }
    
    // Contrôle de l'existence de la classe
    
    if (!class_exists($sClassName)) {
      
      dspm(xt('Action impossible (la classe "%s" n\'existe pas) !', new HTML_Strong($sClassName)), 'action/error');
      
    } else {
      
      // Création de la classe
      
      $oReflected = new ReflectionClass($sClassName);
      
      if ($aArguments) $oAction = $oReflected->newInstanceArgs($aArguments);
      else $oAction = $oReflected->newInstance();
      
      return $oAction;
    }
    
    return null;
  }
  
  public static function setDatabase($oDatabase) {
    
    self::$oDatabase = $oDatabase;
  }
  
  public static function getDatabase() {
    
    return self::$oDatabase;
  }
  
  public static function setUser($oUser = null) {
    
    if (is_object($oUser) && get_class($oUser) == 'User') self::$oUser = $oUser;
  }
  
  private static function loadUser() {
    
    // Une redirection a été effectuée
    
    $oAnonymous = new User('anonymous', array('web', SYLMA_ANONYMOUS), array('full_name' => 'Anonymous'));
    
    if (array_key_exists('user', $_SESSION)) {
      
      // self::addMessage(t('Session existante'), 'report');
      
      $oUser = unserialize($_SESSION['user']);
      
      // Récupération des messages du Redirect et suppression
      
      if (!($oUser instanceof User)) {
        
        $oUser = $oAnonymous;
        
        unset($_SESSION['user']);
        self::addMessage(t('Session utilisateur perdue !'), 'warning');
      }
      
    } else $oUser = $oAnonymous;
    
    return $oUser;
  }
  
  public static function getUser() {
    
    return self::$oUser;
  }
  
  public static function getAbsolutePath($sTarget, $mSource) {
    
    if ($sTarget{0} == '/' || $sTarget{0} == '*') return $sTarget;
    else {
      
      if ($mSource == '/') $mSource = '';
      return $mSource.'/'.$sTarget;
    }
  }
  
  public static function getMessages() {
    
    return self::$oMessages;
  }
  
  public static function useMessages($bValue = null) {
    
    if ($bValue !== null) self::$bUseMessages = $bValue;
    
    return self::$bUseMessages;
  }
  
  public static function addMessage($mMessage = '- message vide -', $sPath = SYLMA_MESSAGES_DEFAULT_STAT, $aArgs = array()) {
    
    if (Controler::isAdmin() && SYLMA_MESSAGES_BACKTRACE && strstr($sPath, 'error') && self::$iBacktraceLimit !== 0) {
      
      if (self::$iBacktraceLimit) self::$iBacktraceLimit--;
      $mMessage = array($mMessage, Controler::getBacktrace());
    }
    
    if (DEBUG && (SYLMA_PRINT_MESSAGES)) { // || !self::useMessages()
      
      if (is_array($mMessage)) foreach ($mMessage as $mContent) echo $mContent.new HTML_Br;
      else echo $mMessage.new HTML_Br;
    }
    
    if (DEBUG && (SYLMA_LOG_MESSAGES)) {
      
      if (is_array($mMessage)) foreach ($mMessage as $mContent) dspl($mContent."\n");
      else dspl($mMessage."\n");
    }
    
    if (self::getMessages()) self::getMessages()->addMessage(new Message($mMessage, $sPath, $aArgs));
    //else if (DEBUG) echo 'Impossible d\'ajouter le message : '.$mMessage;
  }
  
  public static function useStatut($sStatut) {
    
    if (SYLMA_DISABLE_STATUTS) return true;
    else return self::getMessages()->useStatut($sStatut);
  }
  
  public static function buildSpecials() {
    
    if (!$oDirectory = self::getDirectory(SYLMA_PATH_INTERFACES)) {
      
      dspm(xt('Le répértoire des interfaces "%s" n\'existe pas !', new HTML_Strong(SYLMA_PATH_INTERFACES)), 'action/warning');
      
    } else {
      
      $oInterfaces = $oDirectory->browse(array('iml'));
      
      if (!$aInterfaces = $oInterfaces->query('//file')) {
        
        dspm(xt('Aucun fichier d\'interface à l\'emplacement "%s" indiqué !', new HTML_Strong(SYLMA_PATH_INTERFACES)), 'action/warning');
        
      } else {
        
        $oIndex = new XML_Document('interfaces');
        
        foreach ($aInterfaces as $oFile) {
          
          $sPath = $oFile->getAttribute('full-path');
          $oInterface = new XML_Document($sPath, MODE_EXECUTION);
          
          if ($oInterface->isEmpty()) {
            
            dspm(xt('Fichier d\'interface "%s" vide', new HTML_Strong($sPath)), 'action/warning');
            
          } else {
            
            if (!$sName = $oInterface->readByName('name')) {
              
              dspm(xt('Fichier d\'interface "%s" invalide, aucune classe n\'est indiquée !', new HTML_Strong($sPath)), 'action/warning');
              
            } else {
              
              $oIndex->addNode('interface', $sPath, array('class' => $sName));
            }
          }
        }
        
        $oPath = new XML_Path(SYLMA_PATH_INTERFACES_INDEX, null, false);
        
        $oIndex->save($oPath);
        dspm(xt('Interface d\'actions %s regénéré !', $oPath->parse()), 'success');
      }
    }
  }
  
  public static function getAction() {
    
    return self::$sAction;
  }
  
  public static function getPath() {
    
    return self::$oPath;
  }
  
  public static function browseDirectory($aAllowedExt = array(), $aExcludedPath = array(), $iMaxLevel = null, $sOriginPath = '') {
    
    $oDocument = new XML_Document(self::getDirectory()->browse($aAllowedExt, $aExcludedPath, $iMaxLevel));
    
    if ($oDocument && !$oDocument->isEmpty()) $oDocument->getRoot()->setAttribute('path_to', $sOriginPath);
    
    return $oDocument;
  }
  
  public static function getDirectory($sPath = '') {
    
    if ($sPath && $sPath != '/') {
      
      $aPath = explode('/', $sPath);
      array_shift($aPath);
      
      return self::$oDirectory->getDistantDirectory($aPath);
      
    } else return self::$oDirectory;
  }
  
  public static function getFile($sPath, $bDebug = false) {
    
    $aPath = explode('/', $sPath);
    array_shift($aPath);
    
    return self::getDirectory()->getDistantFile($aPath, $bDebug);
  }
  
  public static function isAdmin() {
    
    if (DEBUG) return true;
    else if (self::getUser()) return self::getUser()->isMember('0');
    else return false;
  }
  
  public static function isReady() {
    
    return self::$bReady;
  }
  
  public static function getYAML($sFilePath) {
    
    return Spyc::YAMLLoad(MAIN_DIRECTORY.'/'.$sFilePath);
  }
}

class Redirect {
  
  private $sPath = '/'; // URL cible
  private $oPath = null; // URL cible
  private $oSource = null; // URL de provenance
  private $sWindowType = 'html';
  private $bIsReal = false; // Défini si le cookie a été redirigé ou non
  
  private $aArguments = array();
  private $aDocuments = array();
  private $oMessages;
  
  public function __construct($sPath = '', $mMessages = array(), $aArguments = array()) {
    
    $this->setMessages($mMessages);
    
    if ($sPath) $this->setPath($sPath);
    
    $this->aArguments = $aArguments;
    //$this->setArgument('post', $_POST);
    //$this->setWindowType(Controler::getWindowType());
  }
  
  public function getArgument($sKey) {
    
    return (array_key_exists($sKey, $this->aArguments)) ? $this->aArguments[$sKey] : null;
  }
  
  public function setArgumentKey($sArgument, $sKey, $mValue = '') {
    
    $mArgument = $this->getArgument($sArgument);
    
    if (is_array($mArgument)) {
      
      if ($mValue) {
        
        $mArgument[$sKey] = $mValue;
        $this->setArgument($sArgument, $mArgument);
        
      } else unset($this->aArguments[$sArgument][$sKey]);
    }
  }
  
  public function setArgument($sKey, $mValue) {
    
    $this->aArguments[$sKey] = $mValue;
  }
  
  public function getArguments() {
    
    return $this->aArguments;
  }
  
  public function getDocuments() {
    
    return $this->aDocuments;
  }
  
  public function getDocument($sKey) {
    
    return (array_key_exists($sKey, $this->aDocuments)) ? $this->aDocuments[$sKey] : null;
  }
  
  public function setDocument($sKey, $oDocument) {
    
    $this->aDocuments[$sKey] = $oDocument;
  }
  
  public function setMessages($mMessages = array()) {
    
    $this->oMessages = new Messages(new XML_Document(Controler::getSettings('messages/allowed/@path')), $mMessages);
  }
  
  public function getMessages($sStatut = null) {
    
    if ($sStatut) return $this->oMessages->getMessages($sStatut);
    else return $this->oMessages;
  }
  
  public function addMessage($sMessage = '- message vide -', $sStatut = 'notice', $aArguments = array()) {
    
    $this->oMessages->addStringMessage($sMessage, $sStatut, $aArguments);
  }
  
  public function getPath() {
    
    return $this->oPath;
  }
  
  public function setPath($oPath) {
    
    $this->oPath = $oPath;
    return $oPath;
    // if ($sPath == '/' || $sPath != Controler::getPath()) $this->sPath = $sPath;
    // else Controler::errorRedirect(t('Un problème de redirection à été détecté !'));
  }
  
  public function getSource() {
    
    return $this->oSource;
  }
  
  public function setSource($oSource) {
    
    $this->oSource = $oSource;
    return $oSource;
  }
  
  public function isSource($sSource) {
    
    return ((string) $this->oSource == $sSource);
  }
  
  public function getWindowType() {
    
    return $this->sWindowType;
  }
  
  public function setWindowType($sWindowType) {
    
    $this->sWindowType = $sWindowType;
  }
  
  public function setReal($bValue = 'true') {
    
    $this->bIsReal = (bool) $bValue;
  }
  
  public function isReal() {
    
    return $this->bIsReal;
  }
  
  public function __sleep() {
    
    foreach ($this->aDocuments as $sKey => $oDocument) $this->aDocuments[$sKey] = (string) $oDocument;
    return array_keys(get_object_vars($this)); // TODO Ref or not ?
  }
  
  public function __wakeup() {
    
    foreach ($this->aDocuments as $sKey => $sDocument) $this->aDocuments[$sKey] = new XML_Document($sDocument);
  }
  
  public function __toString() {
    
    return (string) $this->oPath;
  }
}
