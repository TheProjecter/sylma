<?php
namespace sylma\core;
use sylma\core;

interface request extends core\arrayable {

  /**
   * @return \sylma\storage\fs\file
   */
  function getFile();
}