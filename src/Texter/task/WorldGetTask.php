<?php

namespace Texter\task;

use Texter\Main;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

/**
 * 1秒遅らせて移動後のワールドを取得するタスク
 */
class WorldGetTask extends PluginTask{

  public function __construct(Main $main, Player $p){
    parent::__construct($main);
    $this->api = $main->getAPI();
    $this->p = $p;
  }

  public function onRun($tick){
    $p = $this->p;
    $lev = $p->getLevel();
    $levn = $lev->getName();
    //
    $crftps = ($this->api->getCrftps()) ? $this->api->getCrftps() : false;
    if (isset($crftps[$levn])) {
      foreach ($crftps[$levn] as $pk) {
        $p->dataPacket($pk);
      }
    }
    $ftps = ($this->api->getFtps()) ? $this->api->getFtps() : false;
    if (isset($ftps[$levn])) {
      $n = strtolower($p->getName());
      foreach ($ftps[$levn] as $pk) {
        if ($n === $pk->owner or $p->isOp()) {
          $pks = clone $pk;
          $pks->metadata[4][1] = "[$pks->entityUniqueId] ".$pks->metadata[4][1];
          $p->dataPacket($pks);
        }else {
          $p->dataPacket($pk);
        }
      }
    }
  }
}
