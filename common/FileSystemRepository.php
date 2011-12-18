<?php
require_once('NativeQueryOnlyRepository.php') ;
require_once('Files.php') ;
require_once('Logger.php') ;
require_once('Strings.php') ;


class FileSystemQueryOnlyRepository extends NativeQueryOnlyRepository
                                   implements IQueryOnlyRepository {
                                   
  const FS_MODEL_FILE = "./models/fs.model.json" ;
  protected /*Path!*/ $fileSystemRootDirectory ;  
  protected /*URL!*/ $fileSystemRootURL ;
  
  public function fullPath($logical_path) {
    return addToPath($this->fileSystemRootDirectory,$logical_path) ;
  }
  public function fullUrl($logical_path) {
    return addToPath($this->fileSystemRootURL,substr($logical_path,1)) ;
  }
  public function relativePath($path) {
    return substr($path,strlen($this->fileSystemRootDirectory)-1) ;
  }
  
  public function logicalPathFromInstanceSoid($instance_soid) {
    return str_replace('::','/',$this->relativeInstanceSoid($instance_soid)) ;
  } 
  public function fullPathFromInstanceSoid($instance_soid) {
    return $this->fullPath($this->logicalPathFromInstanceSoid($instance_soid)) ;
  }
  public function instanceSoidFromLogicalPath($path) {
    return str_replace('/','::',$path) ;
  }
  public function instanceSoidFromFullPath($fullpath) {
    return $this->instanceSoidFromLogicalPath($this->relativePath($fullpath)) ;
  }
  public function instanceSoidsFromFullPaths($fullpaths) {
    $soids = array() ;
    foreach($fullpaths as $fullpath) {
      $soids[] = $this->instanceSoidFromFullPath($fullpath) ;
    }
    return $soids ;
  }
  public function absoluteInstanceSoidFromLogicalPath($path) {
    return $this->absoluteInstanceSoid($this->instanceSoidFromLogicalPath($path)) ;
  }
  

  
  public function /*Map*<String!,String!>?*/  getDirectoryAttributes($soid) {
    $logical = $this->logicalPathFromInstanceSoid($soid) ;
    $fullname = $this->fullPath($logical) ;
    $parent = parentDirectory($logical) ;    // ??????
    if ($parent!==NULL) {
      $parent = $this->absoluteInstanceSoidFromLogicalPath($parent) ;
    }
    
    return
      array(
        "fullname" => $logical,
        "name" => basename($logical),
        "url" => $this->fullUrl($logical),
        "parent" => $parent 
        // "fs::Directory::directories" 
        // "fs::Directory::files"
      ) ;
  }
  public function /*Map*<String!,String!>?*/  getFileAttributes($soid) {
    $logical = $this->logicalPathFromInstanceSoid($soid) ;
    $fullname = $this->fullPath($logical) ;
    $parent = parentDirectory($logical) ;
    if ($parent!==NULL) {
      $parent = $this->absoluteInstanceSoidFromLogicalPath($parent) ;
    }
    $size = filesize($fullname) ;
    if ($size === FALSE) {
      $size = "NULL" ;
    }
    return
      array(
        "fullname" => $logical,
        "url" => $this->fullUrl($logical),
        "name" => basename($fullname),
        "corename" => fileCoreName($fullname),
        "extension" => fileExtension($fullname),
        "size" => $size,
        "parent" => $parent
      ) ;
  }  
  
  protected function /*Set*<String!>!*/ getAllDirectorySoids() {
    $soids=array() ;
    $dirs =listAllFileNames($this->fileSystemRootDirectory,"dir") ;
    return $this->instanceSoidsFromFullPaths($dirs) ;
  }
  
  protected function /*Set*<String!>!*/ getAllFileSoids() {
    $soids=array() ;
    $files = listAllFileNames($this->fileSystemRootDirectory,"dir|file") ;
    return $this->instanceSoidsFromFullPaths($files) ;
  }  
  
  // fs::Directory::directories
  // fs::Directory::parent=http://localhost/sos/fs$fs/InstanceFragment/::common
  protected function /*Set*<String!>!*/ getAllDirectorySoidsWith_parentIs($soid) {
    $fullname = $this->fullPathFromInstanceSoid($soid) ;
    //echo $fullname ;
    $dirs = listFileNames($fullname,"dir") ;
    //print_r($dirs) ;
    return $this->instanceSoidsFromFullPaths($dirs) ;
  }
  protected function /*Set*<String!>!*/ getAllFileSoidsWith_parentIs($soid) {
    $fullname = $this->fullPathFromInstanceSoid($soid) ;
    $dirs = listFileNames($fullname,"file") ;
    return $this->instanceSoidsFromFullPaths($dirs) ;
  }
  
  public function __construct(/*URL!*/ $repositoryUrl, 
                              /*Path!*/ $fileSystemRootDirectory, 
                              /*URL!*/ $fileSystemRootURL,
                              /*String?*/ $logfile="" ) {
    $this->fileSystemRootDirectory = addToPath($fileSystemRootDirectory,"") ;
    $this->fileSystemRootURL = addToPath($fileSystemRootURL,"") ;
    parent::__construct($repositoryUrl,self::FS_MODEL_FILE,$logfile) ;
  }  
}
