<?php  defined('_SOS') or die("No direct access") ;

// IF NECESSARY COPY THIS FILE TO THE config DIRECTORY. DO NOT CHANGE THIS FILE.
// If the parameters below suit your needs then there is nothing to do. If not
// copy the file to the "config" directory and adjusts the settings.
// The framework will first search the configuration file in "config", and if not
// found, will use this one.

// The absolute path to the directory containing the file system part to explore
// Must be absolute. ABSPATH_BASE can be used.

// Here we use the extensions directory for demonstration purposes. This means
// that users can browse the structure of this directory.
// Since the code is open source, and available on the web, this should not be
// a major security risk

define('ABSPATH_LOCAL_FS_ROOT',ABSPATH_EXTENSIONS) ;

// The corresponding URL
define('URL_LOCAL_FS_ROOT',URL_BASE."extensions/") ;
