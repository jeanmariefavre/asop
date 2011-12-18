<?php
require_once("common/RepositoryServer.php") ;

/*"GET"|"POST"|"PUT"|"OPTION"*/      $method=$_SERVER['REQUEST_METHOD'] ;
/*Map<String!,String!>!*/            $methodParameters=$_GET;
$entityType=$_GET["type"] ;          unset($methodParameters["type"]) ;
$protocol=$_GET["protocol"] ;        unset($methodParameters["protocol"]) ;
$repositoryName=$_GET["repname"] ;   unset($methodParameters["repname"]) ;
$server = new MultiProtocolRepositoryServer($protocol,$repositoryName) ;
$server->processQuery($entityType,$method,$methodParameters) ;    
?>