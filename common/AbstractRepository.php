<?php
require_once('RepositoryInterfaces.php') ;
require_once('Logger.php') ;




//----------  ModelRepository ----------------------------------------------------------

interface IModelLoader {
  public function /*List*<String+*>!*/ loadAllPerspectiveSoids() ;
  public function /*IPerspective?*/    loadPerspective(  /*String!*/ $perspective_soid) ;
  public function /*IClassFragment?*/  loadClassFragment(/*String!*/ $class_fragment_soid) ;
}

/* This class is usefull to create wrapper to load big repository Model.
   The Model (Perspective,ClassFragment,Attribute) are build only on demand but are
   cached, so that successive getters will not load again the information.
   The class is based on the LoadingRepository interface which is used to load for
   the first time the given entity. See above. 
   It is guaranteed that the loadXXX functions will be called only once per entity, so
   the load function must always create new objects.
   With this implementation all the Model entities creation must come from the LoadingRepository 
   interface.
*/
  // To be implemented 
  // function /*URL!*/ getURL() ;
  // OPTIONAL: function /*String!*/ getDialect() ;
  // OPTIONAL: function /*"true"|"false"*/ isReadOnly() ;
  // function /*IPerspective?*/ loadPerspective(/*String!*/ $perspective_soid) ;
  // function /*List*<String+!>?*/ loadAllInstanceFragmentSoids(
  //                                                       /*String!*/ $class_fragment_soid) ;
  // function /*IInstanceFragment?*/ loadInstanceFragment(
  //                                                       /*String!*/ $class_fragment_soid, 
  //                                                       /*String!*/ $instance_soid ) ;  
  // function /*IClassFragment?*/ loadClassFragment(/*String!*/ $class_fragment_soid) ;

abstract class AbstractCachedReadOnlyModelRepository 
                   implements IReadOnlyModelRepository, IModelLoader {
  protected /*Logger!*/ $logger ;               
  protected /*Map*<String!,IPerspective!>!*/ $perspectivesLoaded = array() ;
  protected /*Map*<String!,IClassFragment!>!*/ $classFragmentsLoaded = array() ;
  protected /*Set*<String!>?*/ $perspectiveSoidsLoaded = NULL ;
  
  // TODO currently this method is not implemented by all implementation.
  // So fail if it is used at execution time. We remove this implementation later 
  // to oblige implementation to implement it.
  public function /*IAttribute?*/ getAttribute(/*¨String!*/ $attribute_soid) {
    $error="getAttribute($attribute_soid) : This method is not implemtend yet";
    $this->log($error) ;
    die ($error) ;
  }
  
  // TODO currently this method is not implemented by all implementation.
  // So fail if it is used at execution time. We remove this implementation later 
  // to oblige implementation to implement it.
  public function /*List*<String!>!*/ loadAllPerspectiveSoids() {
    $error="loadAllPerspectiveSoids() : This method is not implemtend yet";
    $this->log($error) ;
    die ($error) ;
  }
  
  //------ SOID convienience methods ----------------------------------------
  private function /*String?*/ absoluteSoid(
             /*PERSPECTIVE_URL|CLASS_FRAGMENT_URL|INSTANCE_FRAGMENT_URL*/ $type,
             /*String?*/$soid) {
    if ($soid == NULL) {
      return NULL ;
    }
    $prefix = addToPath($this->getURL(),$type.'/') ;
    return prefixIfNeeded($soid,$prefix) ;
  }

  private function /*String?*/ relativeSoid(
             /*PERSPECTIVE_URL|CLASS_FRAGMENT_URL|INSTANCE_FRAGMENT_URL*/ $type,
             /*String?*/$soid) {
    if ($soid == NULL) {
      return NULL ;
    }
    $prefix = addToPath($this->getURL(),$type.'/') ;
    return withoutOptionalPrefix($soid,$prefix) ;
  }
  
  public function /*String!*/ absolutePerspectiveSoid(/*String?*/ $soid) {
    return $this->absoluteSoid(PERSPECTIVE_URL,$soid) ;
  }
  public function /*String!*/ relativePerspectiveSoid(/*String?*/ $soid) {
    return $this->relativeSoid(PERSPECTIVE_URL,$soid) ;
  }
        
  public function /*String!*/ absoluteClassFragmentSoid(/*String?*/ $soid) {
    return $this->absoluteSoid(CLASS_FRAGMENT_URL,$soid) ;
  }
  public function /*String!*/ relativeClassFragmentSoid(/*String?*/ $soid) {
    return $this->relativeSoid(CLASS_FRAGMENT_URL,$soid) ;
  }
  
  // TODO since these methods deals with InstanceFragment they should be lower in the hierarchy
  // at a level of instance repository. 
  
  public function /*String!*/ absoluteInstanceSoid(/*String!*/ $soid) {
    return $this->absoluteSoid(INSTANCE_FRAGMENT_URL,$soid) ;
  }
  public function /*String!*/ relativeInstanceSoid(/*String!*/ $soid) {
    return $this->relativeSoid(INSTANCE_FRAGMENT_URL,$soid) ;
  }
  
  // can be overridden 
  public function /*String!*/ getDialect() {
    return "default" ;  
  }
  // should be overrriden if necessary!
  public function /*"true"|"false"!*/ isReadOnly() {
    return "true" ;
  }
  
  public function /*Logger!*/ log($message) {
    return $this->logger->log($message) ;
  }

  public function /*IPerspective?*/ getPerspective(/*String!*/ $perspective_soid) {
    $this->log("getPerspective($perspective_soid)") ;
    if (isset($this->perspectivesLoaded[$perspective_soid])) {
      return $this->perspectivesLoaded[$perspective_soid] ;
    } else {
      $perspective = $this->loadPerspective($perspective_soid) ;
      if ($perspective) {
        $this->perspectivesLoaded[$perspective_soid] = $perspective ;
        return $perspective ;
      } else {
        return NULL ;
      }      
    }
  }
  
  public function /*IClassFragment?*/ getClassFragment(/*String!*/ $class_fragment_soid) {
    $this->log("getClassFragment($class_fragment_soid)") ;
    if (isset($this->classFragmentsLoaded[$class_fragment_soid])) {
      return $this->classFragmentsLoaded[$class_fragment_soid] ;
    } else {
      $classFragment = $this->loadClassFragment($class_fragment_soid) ;
      if ($classFragment) {
        $this->classFragmentsLoaded[$class_fragment_soid] = $classFragment ;
        return $classFragment ;
      } else {
        return NULL ;
      }      
    }  
  }
  
  public function /*List*<String!>!*/ getAllPerspectiveSoids () {
    if (! isset($this->$perspectivesLoaded)) {
      $this->perspectivesLoaded = $this->loadAllPerspectiveSoids() ;
    }
    return $this->perspectivesLoaded ;
  }


  public function __construct($logfile="") {
    $this->logger = new Logger($logfile) ;
  }  
}



//----------  InstanceRepository ----------------------------------------------------------



// Repository with no Instance. Just a Model. Convenient to test things.
abstract class AbstractCachedReadOnlyInstanceEmptyRepository
                    extends AbstractCachedReadOnlyModelRepository
                    implements IReadOnlyRepository {
  public function /*List*<String+!>?*/ getAllInstanceFragmentSoids(
                                         /*String!*/ $class_fragment_soid) {
    return array() ;
  }
  public function /*IClassFragment?*/ getInstanceFragment(
                                         /*String!*/ $class_fragment_soid, 
                                         /*String!*/ $instance_soid) {
    return array() ;
  }
  public function __construct($logfile="") {
    parent::__construct($logfile) ;
  }  
}


// currently there is no cache for instance so, the interface and implementation
// below are not really worth

interface IInstanceLoader {
  public function /*List*<String+!>?*/ loadAllInstanceFragmentSoids(
                                                         /*String!*/ $class_fragment_soid) ;
  public function /*IInstanceFragment?*/ loadInstanceFragment(
                                                         /*String!*/ $class_fragment_soid, 
                                                         /*String!*/ $instance_soid ) ;
}


interface IModelAndInstanceLoader extends IModelLoader, IInstanceLoader {
}



// this class cause a problem for multiple inheritance (see for instance the database
// repository.) Since it does not bring anything it might be usefull ro remove it as well
// as the InstanceLoader

abstract class AbstractCachedReadOnlyRepository 
                      extends AbstractCachedReadOnlyModelRepository
                      implements IReadOnlyRepository, IModelAndInstanceLoader {
                      
  // to be implemented
  // public abstract function /*IInstanceFragment?*/ loadInstanceFragment(
  //                                                    /*String!*/ $class_fragment_soid, 
  //                                                    /*String!*/ $instance_soid ) ;
                                                      
  public function /*List*<String+!>?*/ getAllInstanceFragmentSoids(
                                         /*String!*/ $class_fragment_soid) {
    /* currently no cache for instance */
    $this->log("getAllInstanceFragmentSoids($class_fragment_soid)") ;

    return $this->loadAllInstanceFragmentSoids($class_fragment_soid) ;
  }
  

  public function /*IClassFragment?*/ getInstanceFragment(
                                         /*String!*/ $class_fragment_soid, 
                                         /*String!*/ $instance_soid) {
    /* currently no cache for instance */
    $this->log("getInstanceFragment($class_fragment_soid,$instance_soid)") ;
    return $this->loadInstanceFragment($class_fragment_soid,$instance_soid) ;
  }
  
  

  
}






//--------------------------------------------------------------------------------
//--- Perspectives implementations -----------------------------------------------
//--------------------------------------------------------------------------------


  /* To implement in subclasses */
  // public abstract function /*Set[*]<IClassFragment!>!*/ getClassFragments() ; */
  
abstract class AbstractPerspective implements IPerspective {
  protected /*String!*/ $_soid ;
  protected /*AbstractCachedReadOnlyRepository!*/ $repository ;
  protected /*String!*/ $name  ;
  protected /*IActor?*/ $owner ;
  protected /*List*<IImportDeclaration!>!*/ $importDeclarations ;  
  public function /*Logger!*/ log($message) {
    return $this->getRepository()->log("Perspective::".$message) ;
  }
  public function /*URL!*/ get_server() {
    return $this->getRepository()->getURL() ;
  }
  public function /*String!*/ get_type() {
    return "Perspective" ;
  }
  public function /*String!*/ get_soid() {
    return $this->_soid ;
  }
  public function /*IRepository!*/ getRepository() {
    return $this->repository ;
  }
  public function /*String!*/ getName() {
    return $this->name ;
  }
  public function /*IActor?*/ getOwner() {
    return NULL ;
  }
  public function /*List[*]<IImportDeclaration!>!*/ getImportDeclarations() {
    return $this->importDeclarations ;
  }
  
  public function addImportDeclaration(/*IImportDeclaration*/ $import_declaration) {
    $this->importDeclarations[] = $import_declaration ;
  }
  
  public function __construct($soid,IReadOnlyRepository $repository,$name, $owner=NULL) {
    assert('strlen($soid)>=1') ;
    assert('strlen($name)>=1') ;
    $this->_soid = $soid ;
    $this->repository = $repository ;
    $this->name = $name ;
    $this->owner = $owner ;
    $this->importDeclarations = array() ;
  }
}

// AbstractCachedClassFragmentsPerspective is suitable for perspective where the set of 
// class fragment is computed once but on demand via one call on loadClassFragmentSoSoids.

abstract class AbstractCachedClassFragmentsPerspective 
                      extends AbstractPerspective 
                      implements IPerspective {
                      
  // to be implemented by subclasses
  public abstract function /*Set*<String+!>!*/ loadClassFragmentSoids() ;
  
  protected /*Set*<String+!>?*/ $classFragmentsLoaded=NULL ;  // load at once
  public function /*Set*<IClassFragment!>!*/ getClassFragments() {
    if (isset($this->classFragmentsLoaded)) {
      return $this->classFragmentsLoaded ;
    } else {
      $class_fragment_soids = $this->loadClassFragmentSoids() ;
      $this->classFragmentsLoaded=array() ;
      foreach ($class_fragment_soids as $class_fragment_soid) {
        $this->classFragmentsLoaded[] = 
          $this->getRepository()->getClassFragment($class_fragment_soid) ;
      }
      return $this->classFragmentsLoaded ;
    }      
  }
  public function __construct($soid,AbstractCachedReadOnlyModelRepository $repository,
                              $name,$owner=NULL) {
    assert('strlen($soid)>=1') ;
    assert('strlen($name)>=1') ;
    parent::__construct($soid,$repository,$name,$owner) ;
    $this->classFragmentSoidsLoaded=NULL ;
  }
}

// AbstractCachedHierarchicalClassFragmentsPerspective is suitable for perspective 
// where the set of  class fragment is computed once but on demand via one call 
// on loadClassFragmentSoidSegments.

abstract class AbstractCachedHierarchicalClassFragmentsPerspective 
                      extends AbstractCachedClassFragmentsPerspective 
                      implements IPerspective {
                      
  // to be implemented by subclasses
  public abstract function /*Set*<String+!>!*/ loadClassFragmentSoidSegments() ;
  
  public function /*Set*<String+!>!*/ loadClassFragmentSoids() {
    return 
      HierarchicalSoidMapper::buildClassFragmentSoids(
        $this->get_soid(),
        $this->loadClassFragmentSoidSegments() ) ;             
  }
  
  public function __construct($soid,/*AbstractCached???*/ IReadOnlyRepository $repository,
                              $name,$owner=NULL) {
    assert('strlen($soid)>=1') ;
    assert('strlen($name)>=1') ;
    parent::__construct($soid,$repository,$name,$owner) ;
  }
}

// ClassFragmentStoredPerspective is suitable for perspective where the set of class fragment
// is computed and stored during the construction of the object by means of addClassFragment
// after the constructor and before calls of getClassFragments
class StoredClassFragmentPerspective extends AbstractPerspective implements IPerspective {
  protected /*Set*<IClassFragment!>!*/ $classFragmentSet ;
  public function /*void*/ addClassFragment(IClassFragment $classfragment) {
    $this->classFragmentSet[]=$classFragment ;
  }
  public function /*Set*<IClassFragment!>!*/ getClassFragments() {
    return $this->classFragmentSet ; 
  }
  public function __construct($soid,IReadOnlyRepository $repository,$name,$owner=NULL) {
    assert('strlen($soid)>=1') ;
    assert('strlen($name)>=1') ;
    parent::__construct($soid,$repository,$name,$owner) ;
    $this->classFragmentSet=array() ;
  }
}






//--------------------------------------------------------------------------------
//--- ClassFragment implementations ----------------------------------------------
//--------------------------------------------------------------------------------


abstract class AbstractClassModelFragment implements IClassModelFragment {
  protected /*String!*/ $_soid ;
  protected /*String!*/ $name ;
  protected /*IPerspective!*/ $perspective ;
  protected /*ÏClassFragment?*/ $target ;
  
  public function /*Logger!*/ log($message) {
    return $this->getPerspective()->getRepository()->log("ClassFragment::".$message) ;
  }

  public function /*URL!*/ get_server() {
    return $this->getPerspective()->get_server() ;
  }
  public function /*String!*/ get_type() {
    return "ClassFragment" ;
  }
  public function /*String!*/ get_soid() {
    return $this->_soid ;
  }
  public function /*String!*/ getName() {
    return $this->name ;
  }
  public function /*IPerspective!*/ getPerspective() {
    return $this->perspective;
  }
  public function /*ÏClassFragment?*/ getTarget() {
    return $this->target ;
  }
  
  // To implement in subclasses
  // public abstract function /*Set[*]<IInstanceFragment>!*/ getAttributes() ;
  
  
  public function __construct($soid,IPerspective $perspective,$name,$target=NULL) {
    assert('strlen($soid)>=1') ;
    assert('strlen($name)>=1') ;
    $this->_soid = $soid ;
    $this->name = $name ;
    $this->perspective = $perspective ;
    $this->target = $target ;
  }
}


// AbstractCachedAttributesClassFragment is suitable for ClassFragment where the set of 
// attributes is computed once but on demand via one call on loadAttributeSoids.

abstract class AbstractCachedAttributesClassModelFragment 
                      extends AbstractClassModelFragment 
                      implements IClassModelFragment {
                      
  // to be implemented by subclasses
  public abstract function /*Set*<String+!>!*/ loadAttributeSoids() ;
  // the attribute_soid must be valid and correspond to an attribute of this ClassFragment
  public abstract function /*IAttribute!*/ loadAttribute(/*String!*/ $attribute_soid) ;
  
  protected /*Set*<String+!>?*/ $attributesLoaded=NULL ;  // load at once
  public function /*Set*<IAttributes!>!*/ getAttributes() {
    if (isset($this->attributesLoaded)) {
      return $this->attributesLoaded ;
    } else {
      $attribute_soids = $this->loadAttributeSoids() ;
      $this->attributesLoaded=array() ;
      foreach ($attribute_soids as $attribute_soid) {
        $this->attributesLoaded[] = $this->loadAttribute($attribute_soid) ;
      }
      return $this->attributesLoaded ;
    }      
  }
  public function __construct($soid,IPerspective $perspective,
                              $name,$target=NULL) {
    assert('strlen($soid)>=1') ;
    assert('strlen($name)>=1') ;
    parent::__construct($soid,$perspective,$name,$target) ;
    $this->attributesLoaded=NULL ;
  }
}


abstract class AbstractCachedHierarchicalClassModelFragment 
                    extends AbstractCachedAttributesClassModelFragment
                    implements IClassModelFragment {
                    
  // to be implemented by subclasses
  public abstract function /*Set*<String+!>!*/ loadAttributeSoidSegments() ;
  public abstract function /*IAttribute!*/ loadAttributeBySoidSegment(
                                                /*String!*/ $attribute_segment) ;
  
  public function /*Set*<String+!>!*/ loadAttributeSoids() {
    return 
      HierarchicalSoidMapper::buildAttributeSoids(
        $this->get_soid(),
        $this->loadAttributeSoidSegments() ) ;             
  }
  public function /*IAttribute!*/ loadAttribute(/*String!*/ $attribute_soid) {
    return 
      $this->loadAttributeBySoidSegment(
        HierarchicalSoidMapper::attributeSoidSegment($attribute_soid)) ;
  }
                 
  public function __construct($soid,IPerspective $perspective,
                              $name,$target=NULL) {
    assert('strlen($soid)>=1') ;
    assert('strlen($name)>=1') ;
    parent::__construct($soid,$perspective,$name,$target) ;
  }                  
}


// StoredAttributesClassFragment is suitable for classFragment with all attributes known
// during the construction. Use addAttribute function.

abstract class AbstractStoredAttributesClassModelFragment 
                extends AbstractClassModelFragment implements IClassModelFragment {
  protected /*List*<IAttribute!>!*/ $attributeList ;  // added via the add function
  public function /*List[*]<IAttribute>!*/ getAttributes() {
    return $this->attributeList ;
  }
  public function /*void*/ addAttribute(IAttribute $attribute) {
    $this->attributeList[] = $attribute;
  }
  public function __construct($soid,IPerspective $perspective,$name,$target=NULL) {
    assert('strlen($soid)>=1') ;
    assert('strlen($name)>=1') ;
    parent::__construct($soid,$perspective,$name,$target) ;
    $this->attributeList = array() ;
  }  
}





//--------------------------------------------------------------------------------
//--- Attribute implementations --------------------------------------------------
//--------------------------------------------------------------------------------

class StandardAttribute implements IAttribute {
  protected /*String!*/ $_soid ;
  protected /*String!*/ $name ;
  protected /*IClassFragment!*/ $classFragment ;
  protected /*String!*/ $type ;
  protected /*Integer>=-1*/ $positionInLabel ;
  
  public function /*Logger!*/ log($message) {
    return $this->getClassFragment()->getPerspective()->getRepository()->log("Attribute::".$message) ;
  }
  public function /*URL!*/ get_server() {
    return $this->getClassFragment()->get_server() ;
  }
  public function /*String!*/ get_type() {
    return "IAttribute" ;
  }
  public function /*String!*/ get_soid() {
    return $this->_soid ;
  }
  public function /*String!*/ getName() {
    return $this->name ;
  }
  public function /*IClassFragment*/ getClassFragment() {
    return $this->classFragment ;
  }
  public function /*String!*/ getType() {
    return $this->type ;
  }
  public function /*Integer>=-1*/ getPositionInLabel() {
    return $this->positionInLabel ;
  }
  
  public function __construct( /*String!*/ $soid,
                               IClassFragment $classfragment,
                               /*String!*/ $name,
                               /*String!*/ $type="A(S)",
                               /*Integer>=-1*/ $position=-1) {
    assert('strlen($soid)>=1') ;
    assert('strlen($name)>=1') ;
    assert('strlen($type)>=1') ;
    assert('$position>=-1') ;
    $this->_soid =$soid ;
    $this->name =$name ;
    $this->classFragment=$classfragment ;
    $this->type=$type ;
    $this->positionInLabel=$position ;
  }
}



//--------------------------------------------------------------------------------
//--- IntanceFragment implementations --------------------------------------------
//--------------------------------------------------------------------------------

class StandardInstanceFragment implements IInstanceFragment {
  protected /*String!*/ $soid ;
  protected /*IClassFragment!*/ $classFragment ;
  protected /*Map*<String!,String!>!*/ $attributeMap ;   // attribute's soid -> attribute value
  
  public function /*String!*/ get_soid() {
    return $this->soid ;
  }
  public function /*String*/ getClassFragment() {
    return $this->classFragment ;
  }
  public function /*Map*<String!,String!>!*/ getAttributeMap() {
    return $this->attributeMap ;
  }
  public function __construct(/*String!*/$soid,
                     IClassFragment $classfragment,
                     /*Map*<String!,String!>!*/$attributemap) {
    $this->soid = $soid ;
    $this->classFragment = $classfragment ;
    $this->attributeMap = $attributemap ;
  }
  
}



class HierarchicalSoidMapper {
  const SOID_SEPARATOR = '::' ;
  static /*List+<String!>*/ function explodeHierarchicalSoid(/*String+*/ $soid) {
    $segments = explode(self::SOID_SEPARATOR, $soid) ;
    return $segments ;
  }
  
  //--- composition -----
  static /*String!*/ function buildClassFragmentSoid(/*String*/$perspectivesoid,
                                                     /*String*/$classfragmentsegment) {
    assert('strlen($perspectivesoid)>=1') ;
    assert('strlen($classfragmentsegment)>=1') ;
    return $perspectivesoid.self::SOID_SEPARATOR.$classfragmentsegment ;
  }
  static /*List!<String!>!*/ function buildClassFragmentSoids(/*String*/$perspectivesoid,
                                                     /*List*<String!>!*/$classfragmentsegments) {
    assert('strlen($perspectivesoid)>=1') ;
    assert('is_array($classfragmentsegments)') ;
    $soids=array();
    foreach($classfragmentsegments as $classfragmentsegment) {
      assert('strlen($classfragmentsegment)>=1') ;
      $soids[]=self::buildClassFragmentSoid($perspectivesoid,$classfragmentsegment) ; 
    }    
    return $soids ;
  }
  static /*String!*/ function buildAttributeSoid(/*String*/$classfragmentsoid,
                                                 /*String*/$attributesegment) {
    assert('strlen($classfragmentsoid)>=1') ;
    assert('strlen($attributesegment)>=1') ;
    return $classfragmentsoid.self::SOID_SEPARATOR.$attributesegment ;
  }
  static /*List!<String!>!*/ function buildAttributeSoids(/*String*/$classfragmentsoid,
                                                     /*List*<String!>!*/$attributesegments) {
    assert('strlen($classfragmentsoid)>=1') ;
    assert('is_array($attributesegments)') ;
    $soids=array();
    foreach($attributesegments as $attributesegment) {
      assert('strlen($attributesegment)>=1') ;
      $soids[]=self::buildAttributeSoid($classfragmentsoid,$attributesegment) ; 
    }    
    return $soids ;
  }  
  //--- decomposition ------
  static /*String!*/ function perspectiveSoidSegment(/*String*/$soid) {
    $segments=self::explodeHierarchicalSoid($soid) ;
    assert('count($segments)>=1') ;
    return $segments[0];
  }
  static /*String!*/ function classFragmentSoidSegment(/*String*/$soid) {
    $segments=self::explodeHierarchicalSoid($soid) ;
    assert('count($segments)>=2') ;
    return $segments[1];
  }
  static /*String!*/ function classFragmentSoid(/*String*/$soid) {
    $segments=self::explodeHierarchicalSoid($soid) ;
    assert('count($segments)>=2') ;
    return $segments[0].self::SOID_SEPARATOR.$segments[1] ;
  }
  static /*String!*/ function attributeSoidSegment(/*String*/$soid) {
    $segments=self::explodeHierarchicalSoid($soid) ;
    assert('count($segments)>=3') ;
    return $segments[2];
  }
  static /*Boolean*/ function isAttributeSoid(/*String*/$soid) {
    $segments=self::explodeHierarchicalSoid($soid) ;
    return count($segments) == 3 ;
  }
}
