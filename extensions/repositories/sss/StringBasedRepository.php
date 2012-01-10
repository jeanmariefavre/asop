<?php
require_once(ABSPATH_CORE."AbstractRepository.php") ;


interface IStringBasedModel {
  public function /*Set*<String+!>*/ perspectiveSoids() ;
  
  public function /*Boolean*/ isValidPerspectiveSoid($perspective_soid) ;
  public function /*Set*<String+!>?*/ classFragmentSoids(/*¨String+!*/ $perspective_soid) ;
  
  public function /*Boolean*/  isValidClassFragmentSoid($class_fragment_soid) ;
  public function /*Set*<String+!>?*/ attributeSoids(/*¨String+!*/ $class_fragment_soid) ;
  
  public function /*Boolean*/ isValidAttributeSoid($attribute_soid) ;
  public function /*String!?*/ attributeType(/*¨String+!*/ $attribute_soid) ;
}



class SimpleStringBasedModel implements IStringBasedModel {
  protected /*Nested*/ $model = NULL ;
  
  public function isValid() {
    return $this->model !== NULL ;
  }
  
  public function /*Boolean!*/ fromJsonString(/*Json!*/ $json) {
    $this->model = json_decode($json,TRUE) ;
    return ($this->model !== NULL) ;
  }
  
  public function /*Boolean!*/ fromJsonModelFile(/*Path!*/ $jsonmodelfile) {
    $json = file_get_contents($jsonmodelfile) ;
    if ($json === FALSE) {
      $this->model = NULL ;
       die('cannot open '.$jsonmodelfile."."
            . "( Current directory is ".getcwd().')') ;

      return FALSE ;
    } else {
      return $this->fromJsonString($json) ;
    }
  }
    
  public function /*Set*<String+!>*/ perspectiveSoids() {
    assert('$this->model!==NULL') ;
    return array_keys($this->model) ;
  }
  public function /*Boolean*/ isValidPerspectiveSoid($perspective_soid) {
    assert('$this->model!==NULL') ;
    return array_key_exists($perspective_soid,$this->model) ;
  }
  
  public function /*Set*<String+!>?*/ classFragmentSoids(/*¨String+!*/ $perspective_soid)  {
    assert('$this->model!==NULL') ;
    if ($this->isValidPerspectiveSoid($perspective_soid)) {
      $names = array_keys($this->model[$perspective_soid]) ;
      return HierarchicalSoidMapper::buildClassFragmentSoids($perspective_soid,$names) ;
    } else{
      return NULL ;
    }
  }
  
  public function /*Boolean*/ isValidClassFragmentSoid($class_fragment_soid) {
    assert('$this->model!==NULL') ;
    $perspective_soid = HierarchicalSoidMapper::perspectiveSoidSegment($class_fragment_soid) ; 
    if (! $this->isValidPerspectiveSoid($perspective_soid)) {
      return FALSE ;
    }
    $class_fragment_segment = 
        HierarchicalSoidMapper::classFragmentSoidSegment($class_fragment_soid) ;
    return array_key_exists($class_fragment_segment,$this->model[$perspective_soid]) ;
  }
  
  public function /*Set*<String+!>?*/ attributeSoids(/*¨String+!*/ $class_fragment_soid)  {
    assert('$this->model!==NULL') ;
    if (! $this->isValidClassFragmentSoid($class_fragment_soid)) {
      return NULL ;
    }
    $perspective_soid = HierarchicalSoidMapper::perspectiveSoidSegment($class_fragment_soid) ;
    $class_fragment_segment = 
      HierarchicalSoidMapper::classFragmentSoidSegment($class_fragment_soid) ;      
    $names = array_keys($this->model[$perspective_soid][$class_fragment_segment]) ;
    return HierarchicalSoidMapper::buildAttributeSoids($class_fragment_soid,$names) ;
  }
  
  public function /*Boolean*/ isValidAttributeSoid($attribute_soid) {
    assert('$this->model!==NULL') ;
    $class_fragment_soid = HierarchicalSoidMapper::classFragmentSoid($attribute_soid) ; 
    if (! $this->isValidClassFragmentSoid($class_fragment_soid)) {
      return FALSE ;
    }
    $perspective_soid =
        HierarchicalSoidMapper::perspectiveSoidSegment($attribute_soid) ;
    $class_fragment_segment = 
        HierarchicalSoidMapper::classFragmentSoidSegment($attribute_soid)  ;
    $attribute_segment = 
        HierarchicalSoidMapper::attributeSoidSegment($attribute_soid) ;
    return array_key_exists($attribute_segment,
      $this->model[$perspective_soid][$class_fragment_segment]) ;
  }
  
  public function /*String!?*/ attributeType(/*¨String+!*/ $attribute_soid) {
    assert('$this->model!==NULL') ;
    if (! $this->isValidAttributeSoid($attribute_soid)) {
      return NULL ;
    }
    $perspective_soid =
        HierarchicalSoidMapper::perspectiveSoidSegment($attribute_soid) ;
    $class_fragment_segment = 
        HierarchicalSoidMapper::classFragmentSoidSegment($attribute_soid)  ;
    $attribute_segment = 
        HierarchicalSoidMapper::attributeSoidSegment($attribute_soid) ;
    return $this->model[$perspective_soid][$class_fragment_segment][$attribute_segment] ;
  }
  
  public function /*HTML!*/ toHTML()  {
    assert('$this->model!==NULL') ;
    $html = "" ;
    $html .= '<ul class="perspectives">' ;
    foreach ($this->perspectiveSoids() as $perspectivesoid) {
      $html .= '<li class="perspective">' . $perspectivesoid ;
      $html .= '<ul class="classFragments">' ;
      foreach ($this->classFragmentSoids($perspectivesoid) as $classfragmentsoid) {
        $html .= '<li class="classFragment">' . $classfragmentsoid ;
        $html .= '<ul class="attributes">' ;
        foreach ($this->AttributeSoids($classfragmentsoid) as $attributesoid) {
          $html .= '<li class="attribute">' . $attributesoid ;
          $html .= '<span class="attributeType">' 
                      . $this->attributeType($attributesoid)
                      . '</span>' ;
          $html .= '</li>' ;
        }
        $html .= "</ul>" ;
        $html .= "</li>" ;
      }
      $html .= "</ul>" ;
      $html .= "</li>" ;
    }
    $html .= "</ul>" ;    
    return $html ;
  }
  
  public function __construct(/*Json!|Filename!*/ $jsonModelStringOrJsonModelFile="") {
    if ($jsonModelStringOrJsonModelFile==="") {
      $this->model = NULL ;
    } else if (preg_match('/\.model\.json$/',$jsonModelStringOrJsonModelFile)) {
      if (! $this->fromJsonModelFile($jsonModelStringOrJsonModelFile)) {
        die('cannot open or parse '.$jsonModelStringOrJsonModelFile."."
            . "( Current directory is ".getcwd().')') ;
      } 
    } else {
      if(! $this->fromJsonString()) {
        die('cannot parse '.$jsonModelStringOrJsonModelFile) ;
      }
    }
  }

}


//--------------------------------------------------------------------------------
//--- Repository implementations -------------------------------------------------
//--------------------------------------------------------------------------------


class SimpleStringBasedModelRepository 
                  extends AbstractCachedReadModelRepository 
                  implements IReadModelRepository {
  protected /*URL!*/ $url ;
  protected /*SimpleStringBasedModel!*/ $model ;
  
  public function /*URL!*/ getURL() {
    return $this->url ;
  }
  
  public function /*SimpleStringBasedModel!*/ getModel() {
    return $this->model ;
  }
  
  public function /*IPerspective?*/ loadPerspective(/*String!*/ $perspective_soid) {
    if ($this->model->isValidPerspectiveSoid($perspective_soid)) {
      return new SimpleStringBasedPerspective($perspective_soid, $this) ;
    } else {
      return NULL ;
    }
  }
  
  public function /*IClassFragment?*/ loadClassFragment(/*String!*/ $class_fragment_soid) {
    if ($this->model->isValidClassFragmentSoid($class_fragment_soid)) {
      $perspective = 
        $this->getPerspective(
           HierarchicalSoidMapper::perspectiveSoidSegment($class_fragment_soid)) ;
      return new SimpleStringBasedClassFragment($class_fragment_soid,$perspective) ;
    } else {
      return NULL ;
    }
  }
  
  // polymorphic constructor. The second parameter can either be a (short) model file name
  // or a valid model
  public function __construct(/*URL!*/ $url, 
                              /*IStringBasedModel!|String!*/ $modelOrModelFile, 
                              $logfile="") {
    parent::__construct($logfile) ;
    $this->url = $url ;
    if ($modelOrModelFile instanceof IStringBasedModel) {
      $this->model = $modelOrModelFile ;
    } else if (strlen($modelOrModelFile)>=1) {
      $this->model = new SimpleStringBasedModel($modelOrModelFile) ;
    } else {
      die('SimpleStringBasedModelRepository::__construct : wrong argument #2') ;
    }
    assert('$this->model->isValid()') ;
  }
}


// THE CODE BELOW IS DUPLICATED WITH THE CODE ABOVE BECAUSE OF SIMPLE INHERITANCE

class SimpleStringBasedInstanceEmptyRepository 
                  extends AbstractCachedReadInstanceEmptyRepository 
                  implements IReadRepository {
  protected /*URL!*/ $url ;
  protected /*SimpleStringBasedModel!*/ $model ;
  
  public function /*URL!*/ getURL() {
    return $this->url ;
  }
  
  public function /*SimpleStringBasedModel!*/ getModel() {
    return $this->model ;
  }
  
  public function /*IPerspective?*/ loadPerspective(/*String!*/ $perspective_soid) {
    if ($this->model->isValidPerspectiveSoid($perspective_soid)) {
      return new SimpleStringBasedPerspective($perspective_soid, $this) ;
    } else {
      return NULL ;
    }
  }
  
  public function /*IClassFragment?*/ loadClassFragment(/*String!*/ $class_fragment_soid) {
    if ($this->model->isValidClassFragmentSoid($class_fragment_soid)) {
      $perspective = 
        $this->getPerspective(
           HierarchicalSoidMapper::perspectiveSoidSegment($class_fragment_soid)) ;
      return new SimpleStringBasedClassFragment($class_fragment_soid,$perspective) ;
    } else {
      return NULL ;
    }
  }
  
  // polymorphic constructor. The second parameter can either be a (short) model file name
  // or a valid model

  public function __construct(/*URL!*/ $url, 
                              /*IStringBasedModel!|String!*/ $modelOrModelFile, 
                              $logfile="") {
    parent::__construct($logfile) ;
    $this->url = $url ;
    if ($modelOrModelFile instanceof IStringBasedModel) {
      $this->model = $modelOrModelFile ;
    } else if (strlen($modelOrModelFile)>=1) {
      $this->model = new SimpleStringBasedModel($modelOrModelFile) ;
    } else {
      die('SimpleStringBasedInstanceEmptyRepository::__construct : wrong argument #2') ;
    }
    assert('$this->model->isValid()') ;
  }
  
}


//--------------------------------------------------------------------------------
//--- Perspective implementation -------------------------------------------------
//--------------------------------------------------------------------------------


class SimpleStringBasedPerspective 
            extends AbstractCachedClassFragmentsPerspective 
            implements IPerspective {
  public function /*SimpleStringBasedModel!*/ getModel() {
    return $this->getRepository()->getModel() ;
  }
  public function /*Set*<String+!>!*/ loadClassFragmentSoids() {
    return $this->getModel()->classFragmentSoids($this->get_soid()) ;
  }
  public function __construct($soid,$repository) {
    parent::__construct($soid,$repository,$soid, NULL) ;
  }
}

//--------------------------------------------------------------------------------
//--- ClassFragment implementation -----------------------------------------------
//--------------------------------------------------------------------------------

class SimpleStringBasedClassFragment 
            extends AbstractCachedAttributesClassModelFragment 
            implements IClassFragment {
  public function /*SimpleStringBasedModel!*/ getModel() {
    return $this->getPerspective()->getModel() ;
  }
  public function /*Set*<String+!>!*/ loadAttributeSoids () {
    return $this->getModel()->attributeSoids($this->get_soid()) ;
  }
  public function /*IAttribute!*/ loadAttribute(/*String!*/ $attribute_soid) {
    assert('$this->getModel()->isValidAttributeSoid($attribute_soid)') ;
    $name = HierarchicalSoidMapper::attributeSoidSegment($attribute_soid) ;
    $type = $this->getModel()->attributeType($attribute_soid) ;
    return new StandardAttribute($attribute_soid,$this,$name,$type,-1) ;
  }
  public function __construct($soid, $perspective) {
    $name = HierarchicalSoidMapper::classFragmentSoidSegment($soid) ;
    parent::__construct($soid,$perspective,$name, NULL) ;
  }

}



?>