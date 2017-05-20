<?php

namespace Texter\extensions;

use Texter\Main;

/**
 * TexterExtension
 */
abstract class TexterExtension{
  public $name = 'unknown'.'Extension',
         $version = '1',
         $extDir = __DIR__;

  /* Variable inheritance */
  public function __construct(Main $main){
    $this->main = $main;
    $this->api = $main->getAPI();
    $main->getLogger()->info("§a┝ ".$this->name." v".$this->version." をロードしました");
  }

  abstract public function initialize();// initialize

  /* @return filestream or null */
  public function getResource(string $filename){
    $filename = rtrim(str_replace("\\", "/", $filename), "/");
    if(file_exists($this->getDir()."resources\\".$filename)){
      return fopen($this->getDir()."resources\\".$filename, "rb");
    }else {
      return null;
    }
  }

  /* @return string dir path */
  public function getDir(): string{
    return $this->extDir."\\";
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
