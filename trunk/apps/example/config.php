<?php

define('PATH_SYLMA', '../../lib');
define('PATH_PHP', 'protected');
define('SITE_TITLE', 'Lemon.web');
define('MAIN_DIRECTORY', PATH_PHP);

// A mettre pour le d�buggage, renvoie Controler::isAdmin() � true et enl�ve le cache des templates
define('DEBUG', false);
// define('DEBUG', true);

$aDB = array(
  
  'host'      => 'localhost',
  'database'  => 'example',
  'user'      => 'root',
  'password'  => '',
);