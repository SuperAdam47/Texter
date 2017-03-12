<?php
namespace Texter\task;

use Texter\Main;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

/**
 * 1秒遅らせて移動後のワールドを取得するタスク
 */
class worldGetTask extends PluginTask{

  public function __construct(Main $plugin, Player $p){
    parent::__construct($plugin);
    $this->plugin = $plugin;
    $this->p = $p;
  }

  public function onRun($tick){
    $p = $this->p;
    $lev = $p->getLevel();
    $levn = $lev->getName();
    //
    if (isset($this->plugin->crftp[$levn])) {
      foreach ($this->plugin->crftp[$levn] as $pk) {
        $p->dataPacket($pk);
      }
    }
    if (isset($this->plugin->ftp[$levn])) {
      $n = $p->getName();
      foreach ($this->plugin->ftp[$levn] as $pk) {
        if ($n === $pk->owner or $p->isOp()) {
          $pks = clone $pk;
          $pks->metadata[4][1] = "[$pks->eid] ".$pks->metadata[4][1];
          $p->dataPacket($pks);
        }else {
          $p->dataPacket($pk);
        }
      }
    }
  }
}