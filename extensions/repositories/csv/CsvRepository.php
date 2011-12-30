<?php  defined('_SOS') or die("No direct access") ;

require_once(ABSPATH_CORE.'AbstractRepository.php') ;
require_once(ABSPATH_LIB.'Files.php') ;
require_once(ABSPATH_LIB.'Csv.php') ;

// Coding Scheme:
//   perspective = directory (short) name in the csvroot
//   classFragment = name of csv file excluding extension which is necessarily .csv
//   attribute = a column in a csv file
// Exemple
//   conference::researcher::university
//
// TODO: Add support for queries (using a generic support would be a good idea)
 
 
//--------------------------------------------------------------------------------
//--- Repository implementations -------------------------------------------------
//--------------------------------------------------------------------------------

class CsvReadOnlyRepository extends AbstractCachedReadOnlyRepository
                                          implements IReadOnlyRepository {
  protected /*URL!*/ $url ;
  protected /*(URL|DirectoryName)!*/ $repositoryDirectory ;
  
  public function /*URL!*/ getURL() {
    return $this->url ;
  }
  
  public function /*(URL|DirectoryName)!*/ getRepositoryDirectory() {
    return $this->repositoryDirectory ;
  }

  public function /*IPerspective?*/ loadPerspective(/*String!*/ $perspective_soid) {
    $this->log("loadPerspective($perspective_soid)") ;
    // check if this correspond to a directory
    $perspectivedir = $this->getRepositoryDirectory()."/".$perspective_soid ;
    if (isReadableDirectory($perspectivedir)) {
      return new CsvPerspective($this,$perspective_soid) ;
    } else {
      $this->log("  $perspectivedir is not a readable directory") ;
      return NULL ;
    }
  }
  
  public function /*IClassFragment?*/ loadClassFragment(/*String!*/ $class_fragment_soid) {
    $this->log("loadClassFragment($class_fragment_soid)") ;
    // get the perspective and check that it exist
    $perspective_soid=HierarchicalSoidMapper::perspectiveSoidSegment($class_fragment_soid) ;
    $perspective=$this->getPerspective($perspective_soid) ;
    if ($perspective===NULL) {
      return NULL ;
    }
    // get the class fragment name and check that the corresponding file exist
    $class_fragment_segment = HierarchicalSoidMapper::classFragmentSoidSegment($class_fragment_soid) ;
    $csvfilename = $perspective->getPerspectiveDirectory().'/'.$class_fragment_segment.'.csv' ;
    $csvfile =new CsvFile() ;
    $csvfile->load($csvfilename) ;
    if (! $csvfile->isValid()) {
      return NULL ;
    }
    return new CsvClassFragment( $perspective, $csvfile) ;
  }
  
  public function /*IInstanceFragment?*/ loadInstanceFragment(
                                                      /*String!*/ $class_fragment_soid, 
                                                      /*String!*/ $instance_soid ) {
    assert('strlen($class_fragment_soid)>=1') ;
    assert('strlen($instance_soid)>=1') ;
    $this->log("loadInstanceFragment($class_fragment_soid,$instance_soid)") ;
    $classfragment = $this->getClassFragment($class_fragment_soid) ;
    if ($classfragment instanceof IClassFragment) {
      return $classfragment->getInstanceFragment($instance_soid) ;
    } else {
      return NULL ;
    }
  }
  
  public function /*List*<String+!>?*/ loadAllInstanceFragmentSoids(
                                         /*String!*/ $class_fragment_soid) {
    assert('strlen($class_fragment_soid)>=1') ;
    return $this->instancesFragmentsSoids($class_fragment_soid, NULL) ;
  }
  
  
  // called either by loadAllInstanceFragmentSoids or queryInstanceFragments
  // return a list of instance ids, either all ids, or those selected by the query
  protected function /*List*<String!>?*/ instancesFragmentsSoids(
                                           /*String!*/ $class_fragment_soid,
                                           /*Map+<String!,String!>?*/ $query = NULL) {
    $this->log("instancesFragmentsSoids($class_fragment_soid,".json_encode($query).")") ;
    assert('$query===NULL') ;  // TODO Queries are are not implemented currently
    assert('strlen($class_fragment_soid)>=1') ;
    $this->log("loadAllInstanceFragmentSoids($class_fragment_soid)") ;
    /*CsvClassFragment?*/$classfragment = $this->getClassFragment($class_fragment_soid) ;
    if ($classfragment instanceof IClassFragment) {
      return $classfragment->getAllInstanceFragmentSoids();
    } else {
      return NULL ;
    }
  }

  
  public function __construct(/*URL!*/ $url, 
                              /*String!*/ $repositoryDirectory,
                              /*String?*/ $logfile="" ) {
    assert('strlen($url)>=1') ;
    assert('strlen($repositoryDirectory)>=1') ;
    $this->url = $url ;
    $this->repositoryDirectory = $repositoryDirectory ;
    parent::__construct($logfile) ;
  }  
}



//--------------------------------------------------------------------------------
//--- Perspective implementations ------------------------------------------------
//--------------------------------------------------------------------------------



class CsvPerspective extends AbstractCachedHierarchicalClassFragmentsPerspective
                     implements IPerspective {
  protected /*URL!*/ $perspectiveDirectory ;
  public function getPerspectiveDirectory() {
    return $this->perspectiveDirectory ;
  }
  
  public function /*Set*<String+!>!*/ loadClassFragmentSoidSegments() {
    $files = listFileNames($this->getPerspectiveDirectory(),"file",'/\.csv$/') ;
    $this->log("filenames :".json_encode($files)) ;
    if (is_array($files)) {
      $classfragmentsoidsegments = array() ;
      foreach ($files as $file) {
        $classfragmentsoidsegments[] = basename($file, ".csv") ;
      }
      return $classfragmentsoidsegments ;
    } else {
      return array() ;
    }
  }
  
  public function __construct(CsvReadOnlyRepository $csvrepository, $perspectivename) {
    $this->perspectiveDirectory = $csvrepository->getRepositoryDirectory()."/".$perspectivename ;
    parent::__construct(
      /* soid = */        $perspectivename,      
      /* repository = */  $csvrepository,
      /* name = */        $perspectivename );
  }
}


//--------------------------------------------------------------------------------
//--- ClassFragment implementations ----------------------------------------------
//--------------------------------------------------------------------------------

class CsvClassModelFragment extends AbstractStoredAttributesClassModelFragment 
                             implements IClassModelFragment {
  // Link with csvfile
  protected /*CsvFile!*/ $csvFile ;    

  public function __construct (IPerspective $perspective,
                               CSVFile $csvfile ) {
    assert('$csvfile->isValid()') ;
    $this->csvFile = $csvfile ;
    $class_fragment_name = $csvfile->getCsvBaseName() ;
    $class_fragment_soid = HierarchicalSoidMapper::buildClassFragmentSoid(
                                   $perspective->get_soid(),
                                   $class_fragment_name ) ;
    parent::__construct(
      $class_fragment_soid,
      $perspective,
      $class_fragment_name,
      NULL
    ) ;
    
    // add the attributes
    $header = $csvfile->getHeader() ;
    foreach ($header as $column) {
      $attribute = new CsvAttribute($column,$this) ;
      $this->addAttribute($attribute) ;
    }
  }
}


class CsvClassFragment extends CsvClassModelFragment implements IClassFragment {
  public function /*Set[*]<IInstanceFragment> !*/ getAllInstanceFragments() {
    return array() ; /*TODO*/
  }
  
  public function /*List*<String!>!*/ getAllInstanceFragmentSoids() {
    $this->log("getAllInstanceFragmentSoids") ;
    $rowkeys = $this->csvFile->getAllRowKeys() ;
    $stringkeys = array() ;
    foreach($rowkeys as $rowkey) {
     $stringkeys[]=$rowkey."" ;
    }
    $this->log("result:".json_encode($stringkeys)) ;
    return $stringkeys ;
  }

  public function /*CsvInstanceFragment?*/ getInstanceFragment($instance_soid) {
    $this->log("getInstanceFragment($instance_soid)") ;
    $row = $this->csvFile->getRow($instance_soid) ;
    if (!$row instanceof CsvInstanceFragment) {
      // in the csv file, only the shortname of attribute are used. We need to build attribute soid.
      $attributemap = array() ;
      $classfragmentsoid = $this->get_soid() ;
      foreach ($row as $columnname => $value) {
        $attributemap[HierarchicalSoidMapper::buildAttributeSoid($classfragmentsoid,$columnname)]=$value ;
      }
      return new CsvInstanceFragment($instance_soid,$this,$attributemap) ;
    } else {
      $this->log("no instance $instance_soid for ClassFragment ".$this->getName()) ;
      return NULL ;
    }
  }
  public function __construct (IPerspective $perspective,
                               CSVFile $csvfile ) {
    parent::__construct($perspective, $csvfile) ;
  }

}

//--------------------------------------------------------------------------------
//--- Attribute implementations --------------------------------------------------
//--------------------------------------------------------------------------------


class CsvAttribute extends StandardAttribute implements IAttribute {
  // Link with column 
  protected /*String!*/ $columnName ;
   
  public function __construct( /*String!*/ $columnName,
                               IClassFragment $classFragment) {
    assert('strlen($columnName)>=1') ;
    $this->columnName =  $columnName ;

    $soid = HierarchicalSoidMapper::buildAttributeSoid($classFragment->get_soid(),$columnName) ;
    $name = $columnName ;
    $type = "A(S)" ;
    $position = -1 ;
    parent::__construct($soid,$classFragment,$name,$type,$position) ;
  }
}


//--------------------------------------------------------------------------------
//--- InstanceFragment implementations -------------------------------------------
//--------------------------------------------------------------------------------


class CsvInstanceFragment extends StandardInstanceFragment {
  public function __construct(/*String!*/$soid,
                     CsvClassFragment $classfragment,
                     /*Map*<String!,String!>!*/$attributemap) {
    parent::__construct($soid,$classfragment,$attributemap) ;
  }
}

