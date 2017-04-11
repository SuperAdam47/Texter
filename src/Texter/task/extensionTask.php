<?php
namespace Texter\task;

use Texter\Main;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

/**
 * extensionTask
 */
class extensionTask extends PluginTask{

  public function __construct(Main $main, string $extensionName, string $functionName){
    parent::__construct($main);
    $this->main = $main;
    $this->api = $main->getAPI();
    $this->extName = $extensionName;
    $this->funcName = $functionName;
  }

  public function onRun($tick){
    $ext = $this->api->getExtension($this->extName);
    if ($ext !== false) {
      if ($this->funcName === "tick") {
        $ext->tick($tick);
      }else {
        $ext->{$this->funcName}();
      }
    }
  }
}
