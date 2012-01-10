<?php  defined('_SOS') or die("No direct access") ;

require_once(ABSPATH_LIB.'Logger.php') ;
require_once(ABSPATH_CORE.'AbstractRepository.php') ;
require_once(ABSPATH_EXTENSIONS_REPOSITORIES.'sss/StringBasedRepository.php') ;


class PhpQueryRepository extends SimpleStringBasedModelRepository
                                   implements IQueryRepository {
                            
  // getInstanceFragment("x::Concept",instancesoid) is converted to a call to
  //    /*Map(String!,String!)*/ get"Concept"Attributes(instancesoid)
  public function /*IInstanceFragment?*/ getInstanceFragment(
                                                      /*String!*/ $class_fragment_soid, 
                                                      /*String!*/ $instance_soid ) {

    // check if the method is available
    $className = HierarchicalSoidMapper::classFragmentSoidSegment($class_fragment_soid) ;
    $method = 'get'.$className.'Attributes' ;
    if (! method_exists($this,$method)) {
      $this->log("NativeQueryRepository::$method does not exist. Null returned instead") ;
      return NULL ;
    }
    
    // call the method
    $nativeattmap = $this->$method($instance_soid) ;
    
    // wrapp the result if any
    if ($nativeattmap === NULL) {
      $this->log("NativeQueryRepository::$method($instance_soid) returns NULL") ;
      return NULL ;
    } else {
    
      $classFragment = $this->getClassFragment($class_fragment_soid) ;

      // Convert the native attribute map to a social attribute map
      $attmap = array() ;
      foreach ($nativeattmap as $nativeattname => $nativevalue) {
        // The result of the method is a map using shortname for attribute.
        // It is therefore necessary to qualify the names to produce valid soids.
        $attribute =
          HierarchicalSoidMapper::buildAttributeSoid($class_fragment_soid,$nativeattname);
        $value=$nativevalue ;
        // TODO: depending of the attribute type we may want to make some conversion, for instance
        // to fully qualify references (adding http:// ...) (dealing with null)

        // TODO we should check here that attribute/value returned are conform with the model
        $attmap[$attribute]=$nativevalue ;

      }
      
      // Create an instance fragment with the proper attribute
      return new StandardInstanceFragment(
                   $instance_soid, 
                   $classFragment,
                   $attmap ) ;
    }
  }

  // getAllInstanceFragmentSoids("x::Concept") is converted to a call to
  //    /*List*<String!>?*/ getAll"Concept"Soids(instancesoid)
  
  public function /*List*<String+!>?*/ getAllInstanceFragmentSoids(
                                         /*String!*/ $class_fragment_soid) {
    assert('strlen($class_fragment_soid)>=1') ;
    $className = HierarchicalSoidMapper::classFragmentSoidSegment($class_fragment_soid) ;
    
    // check if the method is available
    $method = 'getAll'.$className."Soids" ;
    if (! method_exists($this,$method)) {
      $this->log("FileSystemQueryRepository::$method does not exist. Null returned instead") ;
      return NULL ;
    }
    
    // call the method
    $soids = $this->$method() ;
    
    return $soids ;  // could  be NULL
  }
  
   
  public function /*List*<String!>?*/ queryInstanceFragments(
                                         /*Map+<String!,String!>!*/ $query ) {
    assert('is_array($query) && count($query)>=1') ;
    // all attributes comes from the same table, so we can get the first
    $attributes=array_keys($query);
    $class_fragment_soid = HierarchicalSoidMapper::classFragmentSoid($attributes[0]);  

    $this->log("queryInstanceFragments(".json_encode($query).")") ;
    return $this->instancesFragmentsSoids($class_fragment_soid, $query) ;
  }
  
  
  // called by queryInstanceFragments
  // return a list of instance ids, either all ids, or those selected by the query
  protected function /*List*<String!>?*/ instancesFragmentsSoids(
                                           /*String!*/ $class_fragment_soid,
                                           /*Map+<String!,String!>?*/ $query = NULL) {
    $this->log("instancesFragmentsSoids($class_fragment_soid,".json_encode($query).")") ;
    $soidsSelectedSoFar = NULL ;
    $remaining_query = $query ;
    
    // try to filter soids by using criteria getAllClass_attIs method
    foreach($query as $att => $value) {
      $className = HierarchicalSoidMapper::classFragmentSoidSegment($att) ;
      $attName = HierarchicalSoidMapper::attributeSoidSegment($att) ;
      $method = "getAll".$className."SoidsWith_".$attName."Is" ;
      if (method_exists($this,$method)) {
        unset($remaining_query[$att]) ;
        $soids = $this->$method($value) ;
        $this->log("PhpQueryRepository::$method($value) returns ".count($soids));
        $soidsSelectedSoFar = 
          ($soidsSelectedSoFar===NULL) ? $soids : array_intersect($soids,$soidsSelectedSoFar) ;
      }
    }
    
    if ($soidsSelectedSoFar === NULL) {
      $soidsSelectedSoFar = $this->getAllInstanceFragmentSoids($class_fragment_soid) ;
    }
    
    return $soidsSelectedSoFar ;
      // fs::Directory::parent=http://localhost/sos/fs$fs/InstanceFragment/::common
      // getAllDirectorySoidsWith_parentIs($soid) {
    // :::
  }
                                     
  public function __construct(/*URL!*/ $repositoryUrl, 
                              /*IStringBasedModel!|String!*/ $modelOrModelFile,
                              /*String?*/ $logfile="" ) {
    parent::__construct($repositoryUrl,$modelOrModelFile,$logfile) ;
  }  
}
