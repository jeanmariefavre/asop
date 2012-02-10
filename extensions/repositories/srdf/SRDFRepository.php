<?php  defined('_SOS') or die("No direct access") ;

require_once ABSPATH_LIB.'Logger.php' ;
require_once ABSPATH_CORE.'RepositoryInterfaces.php' ;
require_once ABSPATH_CORE.'AbstractRepository.php' ;
require_once 'SRDFStore.php' ;

// Coding Scheme:
//   repository x    => rdfstore repo-x    
//   perspective     => soo:Perspective    Perspective/<soid>
//   classFragment   => soo:ClassFragment  ClassFragment/<soid>
//   attribute       => soo:Attribute      Attribute/<soid>


class SRDFRepository extends AbstractCachedReadModelRepository
                                   implements IReadModelRepository, IModelLoader {
  protected /*URL!*/ $url ;
  protected /*String!*/ $repositoryname ;
  
  protected /*SRDFStore!*/ $srdfstore ;  /* Social RDF Store, A proxy to a rdf store based on the soo ontology */
  
  public function /*URL!*/ getURL() {
    return $this->url ;
  }
  
  public function /*IPerspective?*/ loadPerspective(/*String!*/ $perspective_soid) {
    // TODO
    return NULL ;
  }
  
  public function /*List*<String!>!*/ loadAllPerspectiveSoids() {
    // TODO
    return array() ;
  }
  
  public function /*IClassFragment?*/ loadClassFragment(/*String!*/ $class_fragment_soid) {
    // TODO
    return NULL ;
  }

  public function /*IAttribute?*/ getAttribute(/*String!*/ $attribute_soid) {
    // TODO
    return NULL ;
  }  
  
  public function __construct(/*URL!*/        $url, 
                              /*String!*/     $repositoryname, 
                              /*String?*/     $logfile="" ) {
    parent::__construct($logfile) ;
    assert('$this->logger!=NULL') ;                          
    $this->url = $url ;
    $this->repositoryname = $repositoryname ;
    $this->srdfstore = new SRDFStore($this->url, $this->repositoryname, $this->logger) ;
  }
}

class SRDFPerspective extends AbstractCachedClassFragmentsPerspective
implements IPerspective {
  public function /*Set*<String+!>!*/ loadClassFragmentSoids() {
    // TODO
    return NULL ; 
  } 
  public function __construct(/*String!*/ $perspective_soid /*TODO*/ ) {
    // TODO
  }
} 