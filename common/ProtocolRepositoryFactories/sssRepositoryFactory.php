<?php
require_once("common/StringBasedRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$config,$logger) {
  $directory=$config["ROOT_DIR"]."/".$config["LOCAL_SSS_ROOT_DIRECTORY"] ;
  $logdir=$config["ROOT_DIR"]."/".$config["LOG_DIR"] ;
  assert('strlen($directory)>=1') ;
  $jsonmodelfile = $directory.'/'.$repname.'.model.json' ;
  $logger->log("sss::createRepository:: opening $jsonmodelfile") ;
  $repository = new SimpleStringBasedInstanceEmptyRepository( 
                         $config["ROOT_URL"].$protocol.'$'.$repname."/",
                         $jsonmodelfile,
                         $logdir."/repository-".$protocol.'$'.$repname.".txt"  ) ;
  $logger->log("sss::createRepository:: repository successfully opened") ;    
  return $repository ;  
}
