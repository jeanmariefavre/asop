<?php
$_SERVER['REQUEST_METHOD'] = 'GET' ;

$_GET = array( 
    "type"=>"Repository",
    "protocol"=>"csv",
      "repname"=>"test") ;

$_GET = array(
    "type"=>"Repository",
    "protocol"=>"sss",
    "repname"=>"meta") ;

$_GET = array(
    "type"=>"Repository",
    "protocol"=>"db",
    "repname"=>"sodb1") ;

include('../core/RepositoryServerController.php') ;


$_GET = array(
    "type"=>"Repository",
    "protocol"=>"fs",
    "repname"=>"fs") ;



