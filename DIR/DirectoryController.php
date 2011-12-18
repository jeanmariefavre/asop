<?php
require('DirectoryServer.php') ;

/*"GET"|"POST"|"PUT"|"OPTION"*/   $method=$_SERVER['REQUEST_METHOD'] ;
/*Map<String!,String!>!*/         $methodParameters=$_GET ;
$entityType=$_GET["type"] ;       unset($methodParameters["type"]) ;
$server = new ActorPerspectivesHardwiredDirectory($entityType,$method,$methodParameters) ;
$server->processQuery($entityType,$method,$methodParameters) ;   