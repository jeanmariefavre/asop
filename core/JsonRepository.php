<?php  defined('_SOS') or die("No direct access") ;

require_once('JsonRepositoryInterfaces.php') ;

class ReadJsonRepository implements IReadJsonRepository {
  protected /*IReadRepository*/ $repository ;
  
  public function /*Json!*/ getJsonRepository() {
    return json_encode(
      array(
        "url" => $this->repository->getURL(),
        "dialect" => $this->repository->getDialect(),
        "readonly" => $this->repository->isReadonly()
      )
    ) ;
  }
  
  public function /*Json!*/ getJsonAllPerspectiveSoids() {
    $soids = $this->repository->getAllPerspectiveSoids() ;
    return json_encode(
      array(
        "items" => $soids
      )) ;
  }
  
  public function /*Json!*/ getJsonPerspective(/*String!*/ $perspective_soid) {
    /*IPerspective?*/ $perspective = $this->repository->getPerspective($perspective_soid) ;
    if ($perspective) {
      return json_encode(_arrayFromPerspective($perspective)) ;
    } else {
      return json_encode( 
        array("error"=>"perspective ".$perspective_soid." not found" ) );
    }     
  }
  
  public function /*Json!*/ getJsonClassFragment   (/*String!*/ $class_fragment_soid) {
    $class_fragment = 
          $this->repository->getClassFragment($class_fragment_soid) ;
    if ($class_fragment) {
      return json_encode(_arrayFromClassFragment($class_fragment)) ;
    } else {
      return json_encode( 
        array("error"=>"ClassFragment ".$class_fragment_soid." not found" ) );
    }    
  }
  
  # this method is called when a direct access to an attribute is needed
  public function /*Json!*/ getJsonAttribute(/*String!*/ $attribute_soid) {
    $attribute = $this->repository->getAttribute($attribute_soid) ;
    if ($attribute) {
      return json_encode(_arrayFromAttribute($attribute)) ;
    } else {
      return json_encode( 
        array("error"=>"Attribute ".$attribute_soid." not found" ) );
    }      }  

  public function /*Json!*/ getJsonAllInstanceFragmentSoids( 
                                 /*String!*/ $class_fragment_soid) {
    /*List*<String+!>?*/ $soids =
          $this->repository->getAllInstanceFragmentSoids($class_fragment_soid) ;
    if (is_array($soids)) {
      return json_encode(
        array(
          "items" => $soids
        )) ;
        
    } else {
      return json_encode( 
        array("error"=>"ClassFragment ".$class_fragment_soid." not found" ) );
    }     
  }
  public function /*Json!*/ getJsonInstanceFragment(/*String!*/ $class_fragment_soid, 
                                                    /*String!*/ $instance_soid) {
    /*IInstanceFragment?*/ $instance_fragment = 
         $this->repository->getInstanceFragment($class_fragment_soid, $instance_soid) ;
    if ($instance_fragment) {
      return json_encode(_arrayFromInstanceFragment($instance_fragment)) ;
    } else {
      return json_encode( 
        array("error"=>"InstanceFragment ".$instance_soid
                         ." for ClassFragment ".$class_fragment_soid." not found" ) );
    } 
  }
  

  
  public function __construct(IReadRepository $repository) {
    $this->repository = $repository ;
  }
}

class QueryJsonRepository extends ReadJsonRepository implements IQueryJsonRepository {
  public function /*Json!*/queryJsonInstanceFragmentSoids(
                                 /*Map<String!,String!>!*/ $query ) {
    /*List*<String+!>?*/ $soids = $this->repository->queryInstanceFragments($query) ;
    if (is_array($soids)) {
      return json_encode(
        array(
          "items" => $soids
        )) ;
        
    } else {
      return json_encode( 
        array("error"=>__FILENAME__."::QueryJsonRepository::queryJsonInstanceFragmentSoids: the implementation did not returned a list of soids" ) );
    }
    
  }
  

  public function __construct(IQueryRepository $repository) {
    parent::__construct($repository) ;
  }
}


class SchemaFixedJsonRepository extends    QueryJsonRepository
                                implements ISchemaFixedJsonRepository  { 
                                
  public /*true|null*/ function putJsonInstanceFragment(/*Json!*/ $json_instance_fragment ) {
    $a = json_decode($json_instance_fragment,TRUE) ;
    assert('is_array($a)') ;
    $instance_soid = $a["_soid"] ;
    $class_fragment_soid = $a["classFragment"]["_soid"];
    $attribute_map = array() ;
    foreach ($a["values"] as $valuepair) {
      $attribute_map[$valuepair["attribute"]] =  $valuepair["value"] ; 
    }
    $instance_fragment = $this->repository->putInstanceFragment($class_fragment_soid,
                                                                $instance_soid,
                                                                $attribute_map) ;
    if ($instance_fragment==NULL) {
      return NULL ;
    } else {
      return TRUE ;
    }      
  }
  
  public function __construct(ISchemaFixedRepository $repository) {
    parent::__construct($repository) ;
  }
}



/*---------------------------------------------------------------------------------
** These private helpers allow to get an nested array for all objects.
** This is required because object references are not allowed by json_encode in the
** version of Php used. In next verions it seems that JsonSerializable class allow
** support to solve this aspect.
** The array build can be used directly by json_encode. 
**-------------------------------------------------------------------------------*/
function /*NestedArray*/ _arrayFromISocialObject( ISocialObject $so ) {
  return
    array(
      "_server" => $so->get_server(),
      "_type"   => $so->get_type(),
      "_soid"   => $so->get_soid()
    ) ;
}

function /*NestedArray*/ _arrayFromISocialObjectList( /*List[*]<ISocialObject!>!*/ $so_list ) {
  assert(is_array($so_list)) ;
  $a = array() ;
  foreach ($so_list as $so) {
    $a[]=_arrayFromISocialObject($so) ;
  }
  return $a ;
}

function /*NestedArray*/ _arrayFromRepository(IRepository/*!*/ $repository) {
  return
    array(
      "url" => $repository->getURL(),
      "dialect" => $repository->getDialect()
    ) ;
}

function /*NestedArray*/ _arrayFromPerspective(IPerspective/*!*/ $perspective) {
  $a = _arrayFromISocialObject($perspective) ;
  $a["name"]=$perspective->getName() ;
  $a["owner"]="not-implemented" ; /*TODO*/ 
  /*TODO $a["importDeclarations"]=array(...) */
  $a["classFragments"]= _arrayFromISocialObjectList($perspective->getClassFragments()) ;
  return $a ;
}

function /*NestedArray*/ _arrayFromClassFragment( IClassFragment/*!*/ $class_fragment) {
  $a = _arrayFromISocialObject($class_fragment) ;
  $a["name"]       =$class_fragment->getName() ;
  $a["perspective"]=_arrayFromISocialObject($class_fragment->getPerspective()) ; 
  $target = $class_fragment->getTarget() ;
  if (isset($target)) {
    $a["target"]     =_arrayFromISocialObject($target) ; 
  }
  $a["attributes"] =_arrayFromAttributeList($class_fragment->getAttributes()) ;
  return $a ;
}

function /*NestedArray*/ _arrayFromAttributeList( /*List<IAttribute!>!*/ $attributeList) {
  $a = array() ;
  foreach( $attributeList as $attribute ) {
    $a[]=_arrayFromAttribute($attribute) ;
  }
  return $a ;
}

function /*NestedArray*/ _arrayFromAttribute( /*IAttribute!*/ $attribute ) {
  $attarray = _arrayFromISocialObject($attribute) ;
  $attarray["name"]=$attribute->getName() ;
  $attarray["positionInLabel"]=$attribute->getPositionInLabel() ;
  $attarray["type"]=$attribute->getTypeExpression() ;
  return $attarray ;
}
      
function /*NestedArray*/ _arrayFromInstanceFragment( /*IInstanceFragment!*/ $instance_fragment ) {
  $a = array() ;
  $a["_soid"] = $instance_fragment->get_soid() ;
  
// this code assume that getAttributeValues() is available but in this first version we use a map
//  $a["values"]    = _arrayFromAttributeValueList( 
//                      $instance_fragment->getAttributeValues() ) ;
  $a["values"] = _arrayFromAttributeMap(
                   $instance_fragment->getAttributeMap() ) ;
  $a["classFragment"] = _arrayFromClassFragment($instance_fragment->getClassFragment()) ;
  return $a ;
}

function /*NestedArray*/ _arrayFromAttributeMap( /*List*<String!,String>!*/ $attmap ) {
  $a = array() ;
  foreach ($attmap as $attsoid => $attvalue) {
    $a[] = array(
             "attribute" => $attsoid,
             "value" => $attvalue
           ) ;
  }
  return $a ;
}

// this function should be called if a list of IAttributeValue is available but currently
// it has been prefered to use directly attribute map
function /*NestedArray*/ _arrayFromAttributeValueList( /*List[*]<IAttributeValue!>!*/ $values ) {
  $a = array() ;
  foreach ($values as $value) {
    $a[] = array(
             "attribute" => $value->getAttribute()->get_soid(),
             "value" => $value->getValue()
           ) ;
  }
  return $a ;
}