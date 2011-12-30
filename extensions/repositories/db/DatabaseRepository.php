<?php
require_once(ABSPATH_CORE.'AbstractRepository.php') ;
require_once(ABSPATH_LIB.'Database.php') ;

// Coding Scheme:
//   perspective = 
//      in the current implementation there is only one "raw" perspective named  _
//   classFragment = the name of table
//   attribute = the name of a column
// Exemple
//   conference::researcher::university
//

define('RAW_PERSPECTIVE_SOID','_') ;
 
 
 
 
//--------------------------------------------------------------------------------
//--- Model Repository implementations -------------------------------------------
//--------------------------------------------------------------------------------

class DatabaseReadOnlyModelRepository 
           extends AbstractCachedReadOnlyModelRepository 
           implements IReadOnlyModelRepository, IModelLoader {
  protected /*URL!*/ $url ;                                        
  protected /*Database*/ $db ;
  
  // XXX Should be replaced by a SchemaReader
  protected /*DatabaseIntrospector!*/ $databaseIntrospector ;
  

  
  public function /*Database*/ getDB() {
    return $this->db ;
  }

  // XXX Should be replaced by a SchemaReader  
  public function /*DatabaseIntrospector!*/ getIntrospector() {
    return $this->databaseIntrospector ;
  }
  
  public function /*URL!*/ getURL() {
    return $this->url ;
  }

  // XXX here, depending on the perspective selected (could be _ or a stored one)
  // the implementation may change
  public function /*IPerspective?*/ loadPerspective(/*String!*/ $perspective_soid) {
    $this->log("loadPerspective($perspective_soid)") ;
    $perspective_soids = $this->getAllPerspectiveSoids() ;
    // TODO add multiple perspective management
    if (in_array($perspective_soid,$perspective_soids)) {
      return new DatabasePerspective($perspective_soid, $this) ;
    } else {
      return NULL ;
    }
  }  
  
  public function /*List*<String!>!*/ loadAllPerspectiveSoids() {
    // TODO add multiple perspective management
    // XXX, lookup into the see the Schema repository for database perspectives
    return array($this->getRawPerspectiveSoid()) ;
  }

  public function /*String!*/ getRawPerspectiveSoid() {
    return RAW_PERSPECTIVE_SOID ;
  }
  
  public function /*IClassFragment?*/ loadClassFragment(/*String!*/ $class_fragment_soid) {
    $this->log("loadClassFragment($class_fragment_soid)") ;
    $perspectivesoid=HierarchicalSoidMapper::perspectiveSoidSegment($class_fragment_soid) ;
    $dbSinglePerspective = $this->getPerspective($perspectivesoid) ;
    $tablename = HierarchicalSoidMapper::classFragmentSoidSegment($class_fragment_soid) ;
    $tablekeys = $this->databaseIntrospector->getTablePrimaryKey($tablename) ;
    return new DatabaseClassFragment($dbSinglePerspective, $tablename, $tablekeys ) ;
  }
  
  public function /*IAttribute?*/ getAttribute(/*�String!*/ $attribute_soid) {
    $error="getAttribute($attribute_soid) : This method is not implemtend yet in DatabaseRepository.php";
    $this->log($error) ;
    die ($error) ;
    
    
    // The implementation should be continued...
    $this->log("getAttribute($attribute_soid)") ;
    HierarchicalSoidMapper::perspectiveSoidSegment($class_fragment_soid) ;
  }
  
  public function __construct(/*URL!*/ $url, 
                              /*Database*/ $db, 
                              /*DatabaseIntrospector!*/ $databaseIntrospector,
                              /*String?*/ $logfile="" ) {
    parent::__construct($logfile) ;
    $this->url = $url ;
    $this->db = $db ;
    $this->databaseIntrospector = $databaseIntrospector ;
  }  
}




//--------------------------------------------------------------------------------
//--- InstanceRepository implementations --------------------------------------------
//--------------------------------------------------------------------------------


class DatabaseQueryOnlyRepository extends DatabaseReadOnlyModelRepository
                                          implements IReadOnlyRepository {
  
  
  // because of non multiple inheritance, we copy the code from AbstractCachedReadOnlyRepository
  // which is anyway not interesting because 
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
  
  public function /*IInstanceFragment?*/ loadInstanceFragment(
                                                      /*String!*/ $class_fragment_soid, 
                                                      /*String!*/ $instance_soid ) {
    $this->log("loadInstanceFragment($class_fragment_soid,$instance_soid)") ;

    $classfragment = $this->getClassFragment($class_fragment_soid) ;
    if ($classfragment) {
    
      // build a query that will search for the row with the good key values
      $keymap = $classfragment->getKeyMapFromInstanceSoid($instance_soid) ;
      $keynames= $classfragment->getKeyColumns() ;
      $condition = "" ;
      foreach($keymap as $keyname => $keyvalue) {
        if ($condition!="") {
          $condition .= " AND " ;
        }
        $condition .='`'.$keyname.'` = ' . "'" . $keyvalue . "'" ;
      }
      $query = 'SELECT * FROM `'.$classfragment->getTableName().'`'
               . ' WHERE '.$condition ;
      $this->log("  query: $query") ;
      $rows = $this->db->queryAll($query) ;
      if  (is_array($rows) && count($rows) == 1) {
        $this->log("  result: 1 result, OK") ;
        $attmap=array() ;
        $classfragmentsoid = $classfragment->get_soid() ;
        foreach($rows[0] as $attname=>$attvalue) {
          $attsoid = HierarchicalSoidMapper::buildAttributeSoid($classfragmentsoid,$attname) ;
          $attmap[$attsoid] = $attvalue ;
        }
        return new DatabaseInstanceFragment($instance_soid,$classfragment,$attmap) ;
      } else {
        $this->log("loadInstanceFragment: InstanceFragment ".$instance_soid." not found."
             . "searched with query ".$query .". keymap is ".$keymap) ;
       
        return NULL ;
      }
    } else {
      return NULL ;
    }
  }
  
  public function /*List*<String+!>?*/ loadAllInstanceFragmentSoids(
                                         /*String!*/ $class_fragment_soid) {
    assert(isset($class_fragment_soid) && $class_fragment_soid!='') ;
    return $this->instancesFragmentsSoids($class_fragment_soid, NULL) ;
  }
  
  
  public function /*List*<String!>?*/ queryInstanceFragments(
                                         /*Map+<String!,String!>!*/ $query ) {
    assert('is_array($query) && count($query)>=1') ;
    $this->log("queryInstanceFragments(".json_encode($query).")") ;
    // all attributes comes from the same table, so we can get the first
    $attributes=array_keys($query);
    $class_fragment_soid = HierarchicalSoidMapper::classFragmentSoid($attributes[0]);  
    return $this->instancesFragmentsSoids($class_fragment_soid, $query) ;
  }
  
  // called either by loadAllInstanceFragmentSoids or queryInstanceFragments
  // return a list of instance ids, either all ids, or those selected by the query
  protected function /*List*<String!>?*/ instancesFragmentsSoids(
                                           /*String!*/ $class_fragment_soid,
                                           /*Map+<String!,String!>?*/ $query = NULL) {
    $this->log("instancesFragmentsSoids($class_fragment_soid,".json_encode($query).")") ;
    $classfragment = $this->getClassFragment($class_fragment_soid) ;
    if ($classfragment) {
      if ($query) {      
        $conditions = array() ;
        foreach( $query as $attribute_soid => $pattern ) {
          // the table is assumed to be always the same
          $column = HierarchicalSoidMapper::attributeSoidSegment($attribute_soid) ;
          if (strpos($pattern,'*') !== FALSE) {
            $sqlpattern = str_replace('*','%',$pattern) ; 
            $conditions[] = '`'.$column.'` LIKE \''.$sqlpattern."'" ; 
          } else {
            $conditions[] = '`'.$column.'` = \''.$pattern."'" ; 
          }
        }      
        $where = ' WHERE '.implode(' AND ',$conditions) ;
      } else {
        $where = '' ;
      }
      $keys=$classfragment->getKeyColumns() ;
      $query= 'SELECT `'.implode('`,`',$keys).'`'
              . ' FROM `'.$classfragment->getTableName().'`' 
              . $where ;
      $this->log("  query: $query") ;
      $rows = $this->db->queryAll($query) ;
      assert(is_array($rows)) ;
      $this->log("  result: ".count($rows). " rows") ;
      $soids=array() ;
      foreach($rows as $row) {
        $soids[]= $classfragment->getInstanceSoidFromKeyMap($row) ;
      }
      return $soids ;      
    } else {
      $this->error("  ERROR: ClassFragment $class_fragment_soid not available") ;
      return NULL ;
    }
  }
  
  public function __construct(/*URL!*/ $url, 
                              /*Database*/ $db, 
                              /*DatabaseIntrospector!*/ $databaseIntrospector,
                              /*String?*/ $logfile="" ) {
    parent::__construct($url,$db,$databaseIntrospector,$logfile) ;
  }  
}



class DatabaseModelFixedRepository extends DatabaseQueryOnlyRepository implements IModelFixedRepository, IQueryOnlyRepository {
  public function /*IInstanceFragment?*/ putInstanceFragment( 
                                           /*String!*/ $class_fragment_soid,
                                           /*String!*/ $instance_fragment_soid,
                                           /*Map*<String!,String!>!*/ $attribute_map ) {
    $this->log("putInstanceFragment($class_fragment_soid,$instance_fragment_soid,"
                .json_encode($attribute_map).")") ;

    // TODO: this method is not necessarily working properly with multiple keys
    // and with autoincrement fields.
    // Currently all attributes are set (this includes in fact keys)
    // and the soid is assumed to be a composition of the key attribute    
    // In fact, we ignore the instance_fragment_soid, insert the tuple
    // and then try to get the entity with the instance_fragment_soid
    // this will return null if this is not working properly    
                                           
    $classfragment = $this->getClassFragment($class_fragment_soid) ;
    if ($classfragment) {
      $keycolumns = $classfragment->getKeyColumns() ;
      // the lines below would be usefull with a syntax INSERT table (columns) VALUES (values)
      //$columns=array() ;
      //$values=array() ;
      //foreach($attribute_map as $attributesoid -> $attributevalue) {
      //  $columns[]=HierarchicalSoidMapper::attributeSoidSegment($attributesoid) ;
      //  $values[]=$attributevalue ;
      //}
      $instance=$this->loadInstanceFragment($class_fragment_soid,$instance_fragment_soid);
      
      

      if ($instance===NULL) {
        // create a query of the form
        //   INSERT INTO <table> SET <att1> = <val1>, ... 
        $assignments = array() ;
        foreach($attribute_map as $attributesoid => $attributevalue) {
          $columnname = HierarchicalSoidMapper::attributeSoidSegment($attributesoid) ;
          $value = $attributevalue ;
          $assignments[] = '`'.$columnname.'` = \'' . $value . "'" ; 
        }
        $query = 'INSERT INTO `'.$classfragment->getTableName().'`'
                 . ' SET '.implode(', ',$assignments) ;
        $this->log("  query: $query") ;         
      } else {
        $assignments = array() ;
        $conditions = array() ;
        $keys = $classfragment->getKeyColumns() ;
        foreach($attribute_map as $attributesoid => $attributevalue) {
          $columnname = HierarchicalSoidMapper::attributeSoidSegment($attributesoid) ;
          $value = $attributevalue ;
          if (in_array($columnname,$keys)) {
            $conditions[] = '`'.$columnname.'` = \'' . $value . "'" ;
          } else {
            $assignments[] = '`'.$columnname.'` = \'' . $value . "'" ;
          }          
        }
        $query = 'UPDATE `'.$classfragment->getTableName().'`'
                 . ' SET '.implode(', ',$assignments)
                 . ' WHERE '.implode(' AND ',$conditions) ;
        $this->log("  query: $query") ;                  
      }

      $result = $this->db->execute($query) ;      
      // TODO we should log errors
      if ($result !== FALSE) {
        return $this->loadInstanceFragment($class_fragment_soid,$instance_fragment_soid) ;
      } else {
        $this->log("  ERROR in query result") ;         
        return NULL ;
      }
    } else {
      return NULL ;
    }
  }
  public function __construct(/*URL!*/ $url, 
                              /*Database*/ $db, 
                              /*DatabaseIntrospector!*/ $databaseIntrospector,
                              /*String?*/ $logfile="" ) {
    parent::__construct($url,$db,$databaseIntrospector,$logfile) ;
  }
}






//--------------------------------------------------------------------------------
//--- Perspective implementations ------------------------------------------------
//--------------------------------------------------------------------------------

class DatabasePerspective extends AbstractCachedHierarchicalClassFragmentsPerspective 
                          implements IPerspective {
  // Link with database entity
  protected /*String!*/ $databaseName ;
  
  public function /*Set*<String+!>!*/ loadClassFragmentSoidSegments() {
    return $this->getRepository()->getIntrospector()->getTableNames() ;
  }
    
  public function __construct(/*String!*/ $perspective_soid, 
                              DatabaseQueryOnlyRepository $database_repository) {
    $this->databaseName = $perspective_soid ;
    parent::__construct(
      $this->databaseName,
      $database_repository,
      $this->databaseName,
      NULL  ); // TODO owner
  }
}



// --------------------------------------------------------------------------------
// --- ClassFragment implementations ----------------------------------------------
// --------------------------------------------------------------------------------

// ClassModel

class DatabaseClassModelFragment extends AbstractCachedHierarchicalClassModelFragment 
                                  implements IClassModelFragment {
  // Link with database entities
  protected /*String!*/ $tableName ;
  protected /*List1..*<String!>!*/ $keyColumns ;
  
  public function /*Set*<String+!>!*/ loadAttributeSoidSegments() {
    return $this->getPerspective()->getRepository()
                   ->getIntrospector()->getColumnNames($this->getTableName()) ;
  }
  
  public function /*IAttribute!*/ loadAttributeBySoidSegment(/*String!*/ $attribute_segment) {
    return new DatabaseAttribute($attribute_segment,$this) ;
  }
  
  public function /*String!*/ getTableName() {
    return $this->tableName ;
  }
  public function /*List+<String!>!*/ getKeyColumns() {
    return $this->keyColumns ;
  }
  public function __construct (IPerspective $perspective,
                               /*String!*/ $tableName,
                               /*List1..*<String!>!*/ $keyColumns ) {
    parent::__construct(
      HierarchicalSoidMapper::buildClassFragmentSoid($perspective->get_soid(),$tableName),
      $perspective,
      $tableName
    ) ;
    $this->tableName = $tableName ;
    $this->keyColumns = $keyColumns ;
  }  
}
 
// Coding Scheme:
//   instance = a table row
//   instance soid = 
//   attribute value = a column value
//   instance soid = the concatenation of the keys 
//   attribute = the name of a column
// Exemple
//   conference::researcher::university
// 


interface IDatabaseInstanceSoidKeyValuesMapper {
  public function /*Map+<String!,String!>!*/ getKeyMapFromInstanceSoid(/*String!*/$soid) ;
  public function /*String!*/ getInstanceSoidFromKeyMap(/*Map+<String!,String!>!*/ $keymap) ;
}

define('SOID_SLASH_REPLACEMENT','_%_') ;

// DatabaseInstanceSoidConcatenateKeyValuesMapper
//     concatenation of key values prefixed by the name of the database and table
//     Note that / in key values are replaced by .�. is soids to avoid problems with url (/ not allowed) 
//     for instance 
//       if keyvalues are name="ahmed" and surname="brandon", 
//       if the  db is "company" and table is "employee" 
//       then the instance soid is "company::employee::ahmed::brandon"
//     Note that the database name and table name are used, not the ClassFragment soid
class DatabaseInstanceSoidConcatenateKeyValuesMapper implements IDatabaseInstanceSoidKeyValuesMapper {

  protected /*String!*/ $db_name ;
  protected /*String!*/ $table_name ;
  protected /*List+<String+>+*/ $key_names ;

  
  //---- instance soid <-> key value mapper  -----
  // see the coding scheme above
  public function /*Map+<String!,String!>!*/ getKeyMapFromInstanceSoid(/*String!*/$soid) {
    $s = str_replace(SOID_SLASH_REPLACEMENT,'/',$soid) ;
    $soidparts=explode(HierarchicalSoidMapper::SOID_SEPARATOR,$s) ;
    assert('count($soidparts)>=3') ;
    $keymap=array() ;
    $i=2 ;  // skip the soidparts[0] and soidparts[1] since they contains the dbname and tablename
    foreach($this->key_names as $keyname) {
      $keymap[$keyname] = $soidparts[$i] ;
      $i++ ;
    }
    return $keymap ;    
  }
  
 // see the coding scheme above
  public function /*String!*/ getInstanceSoidFromKeyMap(/*Map+<String!,String!>!*/ $keymap) {
    assert('is_array($keymap) && (count($keymap) >=1)') ;
    $soidparts = array() ;
    $soidparts[] = $this->db_name ;
    $soidparts[] = $this->table_name ;
    foreach ($this->key_names as $keyname) {
      $soidparts[]=$keymap[$keyname] ;
    }
    $s=implode(HierarchicalSoidMapper::SOID_SEPARATOR,$soidparts) ;
    $soid = str_replace('/',SOID_SLASH_REPLACEMENT,$s) ;
    return $soid ;
  }
  
  public function __construct(DatabaseClassFragment $classfragment) {
    $this->db_name = $classfragment->getPerspective()->getRepository()->getDB()->dbname ;
    $this->table_name = $classfragment->getTableName() ;
    $this->key_names = $classfragment->getKeyColumns() ;
  }
}


// class DatabaseInstanceSoidKeyValuesStoredMapper

// ClassExtension part 
class DatabaseClassFragment extends DatabaseClassModelFragment
                            implements IClassFragment, IDatabaseInstanceSoidKeyValuesMapper {
                   
  protected /*DatabaseInstanceSoidKeyValuesMapper!*/ $soidKeyValuesMapper ;
  
  public function /*Map+<String!,String!>!*/ getKeyMapFromInstanceSoid(/*String!*/$soid) {
    return $this->soidKeyValuesMapper->getKeyMapFromInstanceSoid($soid) ;
  }
  public function /*String!*/ getInstanceSoidFromKeyMap(/*Map+<String!,String!>!*/ $keymap) {
    return $this->soidKeyValuesMapper->getInstanceSoidFromKeyMap($keymap) ;
  }
  
  public function /*Set[*]<IInstanceFragment> !*/ getAllInstanceFragments() {
    return array() ; /*TODO*/
  }
  
  public function __construct (IPerspective $perspective,
                               /*String!*/ $tableName,
                               /*List1..*<String!>!*/ $keyColumns ) {
    parent::__construct($perspective,$tableName,$keyColumns) ;
    $this->soidKeyValuesMapper = new DatabaseInstanceSoidConcatenateKeyValuesMapper($this) ;
  }
}





//--------------------------------------------------------------------------------
//--- Attribute implementations --------------------------------------------------
//--------------------------------------------------------------------------------


interface IDatabaseAttributeSoidKeyValuesMapper {
  public function /*Map+<String!,String!>!*/ getKeyMapFromInstanceSoid(/*String!*/$soid) ;
  public function /*String!*/ getInstanceSoidFromKeyMap(/*Map+<String!,String!>!*/ $keymap) ;
}


class DatabaseAttribute extends StandardAttribute implements IAttribute {
  // Link with database entity
  protected /*String!*/ $columnName ;
   
  public function __construct( /*String!*/ $columnName,
                               IClassFragment $classFragment) {
    $soid = HierarchicalSoidMapper::buildAttributeSoid($classFragment->get_soid(),$columnName) ;
    $name = $columnName ;
    $type = "A(S)" ;
    $position = -1 ;
    parent::__construct($soid,$classFragment,$name,$type,$position) ;
    $this->columnName =  $columnName ;
  }
}




//--------------------------------------------------------------------------------
//--- InstanceFragment implementations -------------------------------------------
//--------------------------------------------------------------------------------


class DatabaseInstanceFragment extends StandardInstanceFragment {
  public function __construct(/*String!*/$soid,
                     /*DatabaseClassFragment!*/$classfragment,
                     /*Map*<String!,String!>!*/$attributemap) {
    parent::__construct($soid,$classfragment,$attributemap) ;
  }
}





