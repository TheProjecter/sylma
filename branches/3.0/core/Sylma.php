<?php

use sylma\core, sylma\modules, sylma\dom, sylma\storage;

class Sylma {

  const NS = 'http://www.sylma.org';

  const ROOT = sylma\ROOT; // ex: protected
  const PATH = sylma\SYLMA_PATH; // ex: /sylma
  const PATH_SYSTEM = sylma\SYSTEM_PATH;  // ex : /var/www/mysite or C:/xampp/htdocs/mysite
  const PATH_OPTIONS = '/core/sylma.yml';

  const PATH_CACHE = 'cache';
  const PATH_TRASH = 'trash';

  const MODE_EXECUTE = 1;
  const MODE_WRITE = 2;
  const MODE_READ = 4;

  const LOG_STATUT_DEFAULT = 'notice';

  /**
   * @var core\argument
   */
  private static $settings = null;

  protected static $aControlers;
  protected static $aFiles = array();

  public static $sExceptionFile = 'core/exception/Basic.php';
  public static $sExceptionClass = '\sylma\core\exception\Basic';

  public static $sInitializerFile = 'core/Initializer.php';
  public static $sInitializerClass = '\sylma\core\Initializer';

  /**
   * Handle final result for @method render()
   * @var mixed
   */
  private static $result = null;

  public static function init($sServer = '') {

    require_once(self::$sExceptionFile);
    //xdebug_disable();
    set_error_handler(self::$sExceptionClass . "::loadError");

    spl_autoload_register('\Sylma::autoload');

    require_once(self::$sInitializerFile);

    //xdebug_start_code_coverage();

    $init = self::$aControlers['init'] = new self::$sInitializerClass;
    self::setControler('init', $init);

    try {

      self::$settings = $init->loadSettings($sServer, self::ROOT . self::PATH . self::PATH_OPTIONS);
      self::$result = $init->run(self::get('initializer'));
    }
    catch (core\exception $e) {

      $e->save();

      if (!self::read('debug/enable')) {

        header('HTTP/1.0 404 Not Found');
        self::$result = $init->getError();
      }
      else {

        self::get('render')->set('gzip', false);
      }
    }

    //var_dump(xdebug_get_code_coverage());
    //session_write_close();
  }

  public static function autoload($sClass) {

    include_once(self::classToFile($sClass));
  }

  public static function classToFile($sClass) {

    return str_replace('\\', '/', $sClass . '.php');
  }

  public static function setControler($sName, $controler) {

    self::$aControlers[$sName] = $controler;
    return $controler;
  }

  public static function getControler($sName, $bLoad = true, $bDebug = true) {

    $controler = array_key_exists($sName, self::$aControlers) ? self::$aControlers[$sName] : null;

    if (!$controler && $bLoad) {

      $controler = self::loadControler($sName);
    }

    if (!$controler && $bLoad && $bDebug) {

      self::throwException(sprintf('Controler %s is not defined', $sName));
    }

    return $controler;
  }

  public static function setManager($sName, $controler) {

    return self::setControler($sName, $controler);
  }

  public static function getManager($sName, $bLoad = true, $bDebug = true) {

    return self::getControler($sName, $bLoad, $bDebug);
  }

  protected static function loadControler($sName) {

    $result = null;

    switch ($sName) {

      /** Parsers **/

      case 'parser' :

        $result = new \sylma\parser\Manager;

      break;

      case 'action' :

        $result = new \sylma\parser\action\Manager();

      break;

      /** Others **/

      /*
      case 'fs' :

        require_once('storage/fs/Controler.php');
        $result = new storage\fs\Controler('', false, false);
        $result->loadDirectory();

      break;
      */

      case 'fs/editable' :

        $result = new storage\fs\Controler(self::ROOT, true);
        $result->loadDirectory();

      break;

      case 'fs/cache' :

        $result = new storage\fs\Controler(self::PATH_CACHE, true, true, false);
        $result->loadDirectory('');

      break;

      case 'fs/trash' :

        $result = new storage\fs\Controler(self::PATH_TRASH, true, true, false);
        $result->loadDirectory('');

      break;

      case 'dom' :

        $result = new dom\Controler;

      break;

      case 'user' :

        $result = new core\user\Controler;
        $result = $result->getUser();

      break;

      case 'formater' :

        $result = new modules\formater\Controler;

      break;

      case 'redirect' :

        $init = self::getControler('init');
        $result = $init->loadRedirect();

      break;
/*
      case 'argument/parser' :

        $result = new core\argument\parser\Manager;

      break;
*/
      case 'timer' :

        $timer = new modules\timer\Controler;

        $result = $timer->create('timer');

      break;

      case 'mysql' :

        $result = new storage\sql\Manager(self::get('database'));
    }

    if ($result) self::setControler($sName, $result);

    return $result;
  }

  protected static function getSettings($sPath = '') {

    if ($sPath) return self::getSettings()->get($sPath);
    else return self::$settings;
  }

  public static function read($sPath = '', $bDebug = true) {

    if (self::getSettings()) return self::getSettings()->read($sPath, $bDebug);

    return false;
  }

  public static function get($sPath = '', $bDebug = true) {

    if (self::getSettings()) return self::getSettings()->get($sPath, $bDebug);

    return false;
  }

  /**
   * Log system messages either in database or in a file defined in @settings /messages/log/file if db is not yet ready
   * Arguments can be see as questions : Who, What, Where
   */
  public static function log($mPath, $mMessage, $sStatut = self::LOG_STATUT_DEFAULT) {

    $aPath = (array) $mPath;
    $aPath[] = '@time ' . date('Y-m-d H:m:s');

    $sPath = implode(' ', array_reverse($aPath));

    $aMessage = array($sPath, ' @message ', $mMessage);
    $sMessage = implode('', $aMessage);
    //print_r(debug_backtrace());
    //if (class_exists('Controler') && Controler::isAdmin() && Controler::useMessages()) {

      //if (self::read('messages/print/visible')) echo $sMessage."<br/>\n";
      //Controler::addMessage($aMessage, $sStatut); // temp
    //}

    if (self::read('debug/enable')) {

      echo $sMessage . "<br/>\n";
    }

    /*
    if (class_exists('Logger')) {

      // database is open log into


    }
    else if (self::read('messages/log/enable', false)) {

      // no database instance, use a file

      if ($sFile = self::read('messages/log/file', false)) {

        $fp = fopen(MAIN_DIRECTORY.$sFile, 'a+');
        fwrite($fp, "----\n" . $sMessage . ' -- ' . $sStatut . "\n"); //.Controler::getBacktrace()
        fclose($fp);
      }
    }
    */
  }

  public static function display($mValue) {

    $parser = self::getManager('parser');
    $context = $parser ? $parser->getContext('messages', false) : null;
    //$action = $parser ? $parser->getContext('action/current', false) : null;
    //$context = $action ? $action->getContext('message', false) : null;

    if ($context) $context->add('<div xmlns="http://www.w3.org/1999/xhtml" class="sylma-message" tabindex="0">' . $mValue . '</div>');
    else echo $mValue . '<hr/>';
  }

  public static function dsp() {

    foreach (func_get_args() as $mVal) {

      self::display(self::show($mVal, false));
    }
  }

  public static function show($mVal, $bToken = true) {

    $formater = self::getControler('formater');

    $result = $bToken ? $formater->asToken($mVal) : $formater->asHTML($mVal);

    //echo '<pre>' . $result . '</pre>';
    return $result;
  }

  public static function loadException(Exception $e) {

    $newException = new Sylma::$exception;
    $newException->loadException($e);

    return $newException;
  }

  public static function throwException($sMessage, array $aPath = array(), $iOffset = 1, array $aVars = array()) {

    $e = new Sylma::$sExceptionClass($sMessage);

    $e->setPath($aPath);
    $e->setVariables($aVars);
    $e->load($iOffset);

    throw $e;
  }

  public static function isWindows() {

    return PHP_OS == 'WINNT';
  }

  public static function load($sFile, $sDirectory = '') {

    $result = null;
    $bRoot = $sFile{0} == '/';

    if (!$sDirectory || $bRoot) {

      if ($bRoot) $sFile = substr($sFile, 1);
      $sPath = $sFile;
    }
    else {

      $aFile = explode('/', $sFile);
      $aDirectory = explode('/', $sDirectory);

      foreach ($aFile as $sName) {

        if ($sName == '..') {

          array_pop($aDirectory);
          array_shift($aFile);
        }
        else {

          $aDirectory[] = $sName;
        }
      }

      $sPath = implode('/', $aDirectory);
    }

    if (!array_key_exists($sPath, self::$aFiles)) {

      $result = require_once($sPath);
    }

    return $result;
  }

  public static function render() {

    if (!self::read('debug/enable') && self::read('render/gzip')) ob_start('ob_gzhandler');

    return self::$result;
  }
}