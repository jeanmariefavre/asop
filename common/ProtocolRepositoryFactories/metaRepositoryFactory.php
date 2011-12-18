<?php
require_once("common/MetaRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$config,$logger) {
  $directory=$config["ROOT_DIR"]."/".$config["LOCAL_SSS_ROOT_DIRECTORY"] ;
  $logdir=$config["ROOT_DIR"]."/".$config["LOG_DIR"] ;
  assert('strlen($directory)>=1') ;
  
  // repname is in the form of model_perspectivename1_perspectivename2...
  // this is necessary since currently repository do not provides the list of perspective
  $segments=split("_",$repname) ;
  $modelname =array_shift($segments) ;
  $perspectivesoids = $segments ;
  
  // open the repository with the model
  $jsonmodelfile = $directory.'/'.$modelname.'.model.json' ;
  $logger->log("meta::createRepository:: creating the model repository") ;
  $repositoryWithModel = new SimpleStringBasedInstanceEmptyRepository( 
                         $config["ROOT_URL"].'sss$'.$modelname."/",
                         $jsonmodelfile,
                         $logdir."/repository-sss$".$modelname.".txt"  ) ;  
  if (! $repositoryWithModel) {
    $logger->log("meta::createRepository:: creation of the model repository failed!") ;
  } else {
    $logger->log("meta::createRepository:: success with the creation of the model repository") ;

  }  
  $logger->log("meta::createRepository:: creation of the metamodel repository") ;

  $metaRepository = new MetaQueryOnlyRepository(
                         $config["ROOT_URL"].$protocol.'$'.$repname."/",
                         $repositoryWithModel,
                         $perspectivesoids,
                         $logdir."/repository-meta$".$repname.".txt" ) ;
  $logger->log("meta::createRepository:: metamodel repository successfully opened") ;
  
  return $metaRepository ;  
}