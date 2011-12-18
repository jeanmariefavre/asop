<?php
class Logger {
  protected /*?*/ $filename ;
  protected $file ;
  protected $activated ;
  
  public function log($message) {
    if ($this->activated) {
      fwrite($this->file,$message."\n") ;
    }
  }
  
  public function on() {
    if ($this->filename) {
      $this->activated = TRUE ;
    }
  }
  
  public function off() {
    $this->activated = FALSE ;
  }
  
  private function openforappend() {
    if ($this->filename) {
      $this->file = fopen($this->filename,"a") ;
      if ($this->file === FALSE) {
        die('Logger: '.$this->filename.' cannot be opened for writing') ; 
      }
    }
  }
  public function clear() {
    if ($this->filename) {
      fclose($this->file) ;
      unlink($this->filename) ;
      $this->openforappend() ;
    }
  }
  
  public function __destruct() {
    if ($this->filename) {
      fclose($this->file) ;
    }
  }  
  
  public function __construct($filename=NULL) {
    if ($filename) {
      $this->filename = $filename ;
      $this->openforappend() ;
      $this->activated = TRUE ;
    } else {
      $this->activated = FALSE ;
      $this->filename = NULL ;
    }
  }
}