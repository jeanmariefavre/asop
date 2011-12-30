<?php // This is a top level file for the repository. So it includes constants and can be called directly

/*------------------------------------------------------------------------------------------
   Controller of the repository server.
--------------------------------------------------------------------------------------------
The actual syntax of URLs is decoded by the .htaccess which call this controller.
Some further request analysis is done here. Then a social server is instanciated.
Finally the quest us processed.
*/

// To ensures that no direct access to php files, all files will test if SOS is defined?
// _SOS stands for Social Object Server

require_once('Constants.php') ;
require_once("RepositoryServer.php") ;


// Prepare the various parameters
/*"GET"|"POST"|"PUT"|"OPTION"*/      $method=$_SERVER['REQUEST_METHOD'] ;
/*Map<String!,String!>!*/            $methodParameters=$_GET;
$entityType=$_GET["type"] ;          unset($methodParameters["type"]) ;
$protocol=$_GET["protocol"] ;        unset($methodParameters["protocol"]) ;
$repositoryName=$_GET["repname"] ;   unset($methodParameters["repname"]) ;
// other optional parameters are in $methodParameters

// Create a server to handle the requested protocol and specified repository
$server = new MultiProtocolRepositoryServer($protocol,$repositoryName) ;

// Process the query
$server->processQuery($entityType,$method,$methodParameters) ;    
?>