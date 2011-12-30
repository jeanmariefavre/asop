<?php // This is a top level file for the repository. So it includes constants and can be called directly

/*------------------------------------------------------------------------------------------
 Controller of the directory server.
--------------------------------------------------------------------------------------------
The actual syntax of URLs is decoded by the .htaccess which call this controller.
*/

require_once('../core/Constants.php') ;
require_once('DirectoryServer.php') ;

/*"GET"|"POST"|"PUT"|"OPTION"*/   $method=$_SERVER['REQUEST_METHOD'] ;
/*Map<String!,String!>!*/         $methodParameters=$_GET ;
$entityType=$_GET["type"] ;       unset($methodParameters["type"]) ;
$server = new ActorPerspectivesHardwiredDirectory($entityType,$method,$methodParameters) ;
$server->processQuery($entityType,$method,$methodParameters) ;   