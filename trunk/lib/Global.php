<?php

function t($s) {
  
  //$s = '[['.$s.']]';
  
  return $s;
}

/*
 * Si il existe, renvoie la valeur de l'index du tableau , sinon renvoie la valeur de $sDefault
 **/
function array_val($sKey, $aArray, $sDefault = '') {
  
  if (is_array($aArray) && (is_string($sKey) || is_numeric($sKey)) && array_key_exists($sKey, $aArray)) return $aArray[$sKey];
  else return $sDefault;
}

function array_clear($aArray, $sDefault = '') {
  
  $aCopyArray = $aArray;
  
  foreach ($aArray as $sKey => $sValue)if (!$sValue) unset($aCopyArray[$sKey]);
  
  return $aCopyArray;
}

function strtobool($sValue, $bDefault = null) {
  
  if (strtolower($sValue) == 'true') return true;
  else if (strtolower($sValue) == 'false') return false;
  else return $bDefault;
}

function booltostr($bValue) {
  
  return $bValue ? 'true' : 'false';
}

/*
 * Renvoie la premi�re valeur non nulle envoy�e en argument, si aucune, renvoie la derni�re valeur
 **/
function nonull_val() {
  
  foreach (func_get_args() as $mArg) {
    
    $mResult = $mArg;
    if ($mArg) return $mArg;
  }
  
  return $mResult;
}

/*
 * 'Quote' une cha�ne, ou plusieurs dans un tableau
 **/
function addQuote($mValue) {
  
  if (is_string($mValue)) return "'$mValue'";
  else if (is_array($mValue)) {
    
    foreach ($mValue as &$mSubValue) $mSubValue = addQuote($mSubValue);
    return $mValue;
    
  } else return false;
}

/*
 * Formate le nombre donn�e en argument au format prix (p.ex : 1'999.95)
 **/
function formatPrice($fNumber) {
  
  if (is_numeric($fNumber)) return 'CHF '.number_format($fNumber, 2, '.', "'");
  else return '';
}

function stringResume($mValue, $iLength = 50) {
  
  $sValue = (string) $mValue;
  
  if (strlen($sValue) > $iLength) return substr($sValue, 0, $iLength).'...';
  else return $sValue;
}

/*
 * Fusionne les cl�s et les valeurs en ins�rant une cha�ne de s�paration
 **/
function fusion($sSep, $aArray) {
  
  $aResult = array();
  
  foreach ($aArray as $sKey => $sVal) $aResult[] = $sKey.$sSep.$sVal;
  
  return $aResult;
}

/*
 * Implosion = fusion + implode
 **/
function implosion($sSepFusion, $sepImplode, $aArray) {
  
  return implode($sepImplode, fusion($sSepFusion, $aArray));
}

/*
 * Pour le d�buggage, affiche une variable dans un tag <pre> qui affiche les retours � la ligne
 **/
function dsp($mVar) {
  
  echo '<pre>';
  print_r($mVar);
  echo '</pre>';
}
