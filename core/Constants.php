<?php


//--- SECURITY -------------------------------------------------------------
// To ensures that no direct access to php files, all files ensures that SOS is defined.
// This means that files can only be executed if this file have been included, and this
// is the case only in the top-level php file. See .htaccess. 
// _SOS stands for Social Object Server
define ('_SOS',1) ;

//--- PATHS ----------------------------------------------------------------
// All constants starting with ABSPATH are absolute paths. 
// RELPATH_ constants are relative path.
// ALL paths end with a /
define('ABSPATH_BASE', dirname(dirname(__FILE__)).'/');
define('ABSPATH_CORE', dirname(__FILE__).'/');
define('ABSPATH_CONFIG', ABSPATH_BASE.'config/') ;
define('ABSPATH_LIB',ABSPATH_BASE.'libraries/') ;
define('ABSPATH_LOGS',ABSPATH_BASE.'logs/') ;
define('ABSPATH_EXTENSIONS',ABSPATH_BASE.'extensions/') ;
define('ABSPATH_EXTENSIONS_REPOSITORIES',ABSPATH_EXTENSIONS.'repositories/');
define('ABSPATH_REPOSITORY',ABSPATH_BASE) ;
define('RELPATH_DIRECTORY',"DIR/") ;
define('ABSPATH_DIRECTORY',ABSPATH_BASE.RELPATH_DIRECTORY) ;
define('ABSPATH_MODELS',ABSPATH_BASE."models/") ;

//--- URL FRAGMENTS ---------------------------------------------------------
// These constant are used to generated url, but the server is doing some url rewriting
// That means that CHANGING THESE CONSTANTS HERE IS NOT ENOUGH. 
// If you change the value below, then change also the .htaccess file
define('PERSPECTIVE_URL',"/Perspective") ;
define('CLASS_FRAGMENT_URL',"/ClassFragment") ;
define('ATTRIBUTE_URL',"/Attribute") ;
define('INSTANCE_FRAGMENT_URL',"/InstanceFragment") ;
