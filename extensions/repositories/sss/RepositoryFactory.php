<?php  defined('_SOS') or die("No direct access") ;

require_once("StringBasedRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$logger) {
  $directory=ABSPATH_MODELS ;
  assert('strlen($directory)>=1') ;
  $jsonmodelfile = $directory.$repname.'.model.json' ;
  $logger->log("sss::createRepository:: opening $jsonmodelfile") ;
  $repository = new SimpleStringBasedInstanceEmptyRepository( 
                         URL_REPOSITORY.$protocol.'$'.$repname."/",
                         $jsonmodelfile,
                         "repository-".$protocol.'$'.$repname.".txt"  ) ;
  $logger->log("sss::createRepository:: repository successfully opened") ;    
  return $repository ;  
}
