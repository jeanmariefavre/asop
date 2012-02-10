<?php  defined('_SOS') or die("No direct access") ;

include_once "RDFStore.php" ;

// A SRDFStore is a RDFStore but it is social-object ontology aware

class SRDFStore extends RDFStore {
  protected /*URL!*/           $repositoryurl ; /* The url of the repository */ 
  
  const SOO_TYPE_PERSPECTIVE = 'soo:Perspective' ;
  const SOO_PROP_REPOSITORY = 'soo:perspectiveRepository!' ;
  const SOO_PROP_NAME = 'soo:name!' ;
  const SOO_PROP_OWNER = 'soo:perspectiveOwner!' ;
  const SOO_PROP_CLASS_FRAGMENTS = '~soo:classFragmentPerspective*' ;
  
  const SOO_PERSPECTIVE_PROPERTIES = array( 
          SOO_PROP_REPOSITORY, SOO_PROP_NAME, SOO_PROP_OWNER, 
          SOO_PROP_CLASS_FRAGMENTS ) ;
  
//   public function getPerspectivePropertySet( /*RDFId!*/ $perspectiverdfid ) {
//     if (isOfType($perspectiverdfid,'soo:Perspective')) {
//       $perspectiveprops = $this->evalPropertySetExpression(
//           $perspectiveuri,
//           'soo:perspectiveRepository! soo:name! soo:perspectiveOwner!'
//             . 'soo:classFragmentExcluded* soo:classFragmentIncluded* ~soo:classFragmentPerspective*'
//         ) ;
//       ... deals with the various conversion if necessary ...
//       return $perspectiveprops ;
//     } else {
//       return NULL ;
//     }
//   }
  
  
  public function __construct(/*URL*/     $repositoryurl, 
                              /*String!*/ $repositoryname,
                              /*Logger!*/ $logger) {
    assert('$logger!=NULL') ;                          
    parent::__construct(
        SRDF_DATABASE_SERVER,
        SRDF_DATABASE_NAME,
        SRDF_DATABASE_USER,
        SRDF_DATABASE_PASSWORD,
        SRDF_STORE_PREFIX.$repositoryname,
        array ("soo" => SRDF_SOO_URI),
        $logger
      ) ;
    $this->repositoryurl = $repositoryurl ;
  }
  
}


