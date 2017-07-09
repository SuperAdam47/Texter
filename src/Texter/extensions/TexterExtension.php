<?php
namespace Texter\extensions;

use Texter\Main;

/**
 * TexterExtension
 */
abstract class TexterExtension{

  public $name = 'unknown'.'Extension',
         $version = '1',
         $extDir = null;

  /* Variable inheritance */
  public function __construct(Main $main){
    $this->main = $main;
    $this->api = $main->getAPI();
    $search = ["{name}", "{ver}"];
    $replace = [$this->name, $this->version];
    $message = str_replace($search, $replace, $this->api->getMessage("extension.loaded"));
    $main->getLogger()->info($message);
  }

  /**
   * initialize function. It works when the file is loaded.
   */
  abstract public function initialize();

  /* @return filestream or null */
  public function getResource(string $filename){
    $filename = rtrim(str_replace(DS, "/", $filename), "/");
    if(file_exists($this->getDir()."resources".DS.$filename)){
      return fopen($this->getDir()."resources".DS.$filename, "rb");
    }else {
      return null;
    }
  }

  /* @return string dir path */
  public function getDir(): string{
    return $this->extDir.DS;
  }

  /* @return string version */
  public function getVersion(): string{
    return $this->version;
  }

  /* @return string name */
  public function getName(): string{
    return $this->name;
  }
}
