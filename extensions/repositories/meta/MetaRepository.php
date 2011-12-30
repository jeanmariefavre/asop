<?php  defined('_SOS') or die("No direct access") ;

require_once(ABSPATH_LIB.'Files.php') ;
require_once(ABSPATH_LIB.'Logger.php') ;
require_once(ABSPATH_LIB.'Strings.php') ;
require_once(ABSPATH_EXTENSIONS_REPOSITORIES.'php/PhpQueryOnlyRepository.php') ;


define ('META_MODEL_FILE',ABSPATH_MODELS."meta.model.json" ) ;

class MetaQueryOnlyRepository extends PhpQueryOnlyRepository
                                   implements IQueryOnlyRepository {
                                   
  protected /*IModelRepository!*/ $modelRepository ;  
  protected /*Set*<String!>!*/ $modelPerspectiveSoids ;
  
  public function /*String!*/ urlToSoid($url) {
    return str_replace('/','^',$url) ;
  }
  public function soidToUrl($soid) {
    return str_replace('^','/',$soid) ;
  }

  public function  getAllModelRepositorySoids() {
    // ad-hoc
    return array($this->urlToSoid($this->modelRepository->getURL())) ;
  }
  
  public function /*Map*<String!,String!>?*/  getModelRepositoryAttributes($soid) {
    // ad-hoc - we should check the soid and extract a repository from that
    $repository = $this->modelRepository ;
    if ($repository) {
      return 
        array(
          "url"       => $repository->getUrl(),
          "dialect"   => $repository->getDialect(),
          "readonly"  => $repository->isReadOnly()
          // "perspectives"
        ) ;
    } else {
      return NULL ;
    }
  }
  
  public function  getAllPerspectiveSoids() {
    return $this->modelPerspectiveSoids ;
  }
    
  public function /*Map*<String!,String!>?*/  getPerspectiveAttributes($soid) {
    // ad-hoc - the repository should be extracted from the perspective
    $perspective = $this->modelRepository->getPerspective($soid) ;
    
    if ($perspective) {
      return
        array(
          "repository" => $this->absoluteInstanceSoid($this->urlToSoid(
                             $perspective->getRepository()->getURL()  )),
          "_soid"      => $perspective->get_soid(),
          "url"        => $perspective->getRepository()
                             ->absolutePerspectiveSoid($perspective->get_soid()),
          "name"       => $perspective->getName()
          //"owner"      => "NULL"
          // "importDeclarations" 
          // "classFragments"
        ) ;
    } else {
      return NULL ;
    }      
  }
  
  public function getAllClassFragmentSoidsWith_perspectiveIs($absoluteSoid) {
    $relativeSoid=$this->relativeInstanceSoid($absoluteSoid) ;
    //echo "absoluteSoid $absoluteSoid<br/>relative $relativeSoid<br/>";

    // ad-hoc - the repository should be extracted from the perspective
    $perspective = $this->modelRepository->getPerspective($relativeSoid) ;
     if ($perspective) {
      $soids = array() ;
      foreach ($perspective->getClassFragments() as $classFragment) {
        $soids[] = $classFragment->get_soid() ;
        // echo $classFragment->get_soid(). "<br>" ;
      }
      return $soids ; 
    } else {
      return NULL ;
    }
  }
  
  public function /*Map*<String!,String!>?*/  getClassFragmentAttributes($soid) {
    // ad-hoc - the repository should be extracted from the perspective
    $classFragment = $this->modelRepository->getClassFragment($soid) ;
    if ($classFragment) {    
      return
        array(
          "perspective" => $this->absoluteInstanceSoid(
                             $classFragment->getPerspective()->get_soid() ),
          "_soid"      => $classFragment->get_soid(),
          "url"        => $classFragment->getPerspective()->getRepository()
                             ->absoluteClassFragmentSoid($soid),
          "name"       => $classFragment->getName() 
          // NULL "owner"      => $classFragment->getOwner(
          // "importDeclarations" 
          // NULL "target"  =>
          // "extensions"
          // "attributes"
          // "referenceTypes"
        ) ;
    } else {
      return NULL ;
    }
  }   
  
  public function /*Map*<String!,String!>?*/  getAttributeAttributes($soid) {
    // ad-hoc - the repository should be extracted from the perspective
    
    $attribute = $this->modelRepository->getClassFragment($soid) ;
  }
    
  public function __construct(/*URL!*/ $metaRepositoryUrl, 
                              /*IRepository!*/ $modelRepository,
                              /*Set*<String!>!*/ $modelPerspectiveSoids,           
                              /*String?*/ $logfile="" ) {
    $this->modelRepository = $modelRepository ;
    $this->modelPerspectiveSoids = $modelPerspectiveSoids ;
    parent::__construct($metaRepositoryUrl,META_MODEL_FILE,$logfile) ;
  }  
}
