<?php
/*
 * Classe de gestion des erreurs
 * Created on 23 oct. 2008
 */

function userErrorHandler($errno, $errstr, $errfile, $errline) {
  
  if (Controler::isAdmin()) {
    
    if (FORMAT_MESSAGES) {
      
      $oMessage = new HTML_Div;
      $oMessage->add(
        new HTML_Strong("ERREUR [$errno] "),
        xt($errstr),
        new HTML_Tag('i', " - [$errline] - $errfile"));
        
    } else $oMessage = "ERREUR [$errno] $errstr - [$errline] - $errfile : ".Controler::getBacktrace();
    
    if (Controler::useMessages()) Controler::addMessage($oMessage, 'error');
    else if (DEBUG) echo $oMessage;
    
    return true;
  }
  
  return true;
}
