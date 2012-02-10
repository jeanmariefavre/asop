<?php  defined('_SOS') or die("No direct access") ;


// IF NECESSARY COPY THIS FILE TO THE config DIRECTORY. DO NOT CHANGE THIS FILE.
// If the parameters below suit your needs then there is nothing to do. If not
// copy the file to the "config" directory and adjusts the settings.
// The framework will first search the configuration file in "config", and if not
// found, will use this one.


// Path to the arc2/ARC2.php file.
// Here we assume that the arc2 library is installed at the same level as this
// social object platform. 
define('SRDF_ARC2_LIBRARY',ABSPATH_BASE.'../arc2/ARC2.php') ;

// Database for the rdf stores
define('SRDF_DATABASE_SERVER','locahost') ;
define('SRDF_DATABASE_NAME','arc2_srdf');
define('SRDF_DATABASE_USER','rdfdbuser') ;
define('SRDF_DATABASE_PASSWORD','6456yiu78464') ;

// Prefix for repository stores
define('SRDF_STORE_PREFIX','repo_') ;

// URI of the Social Object Ontology
define('SRDF_SOO_URI','http://localhost/socialobjects/schema.ttl#') ;
