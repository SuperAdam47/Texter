<?php
namespace Texter\task;

use Texter\Main;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\math\Vector3;

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
    if (!isset($this->plugin->crftp[$levn])) {
      foreach ($this->plugin->crftps as $v) {
        str_replace("#", "\n", $v["TITLE"]);
        str_replace("#", "\n", $v["TEXT"]);
        if (is_null($v["WORLD"]) or $v["WORLD"] === "default"){
          $v["WORLD"] = $this->plugin->getServer()->getDefaultLevel()->getName();
        }
        //
        if ($this->plugin->getServer()->loadLevel($v["WORLD"])) {
          if ($levn === $v["WORLD"]) {
            $pos = new Vector3($v["Xvec"], $v["Yvec"], $v["Zvec"]);
            $this->plugin->addCrftp($p, $pos, $v["TITLE"], $v["TEXT"], $v["WORLD"]);
          }
        }else {
          $this->plugin->getLogger()->notice("記載されたワールド名 ".$v["WORLD"]." は存在しません。");
        }
      }
      if (isset($this->plugin->ftp)) {
        foreach ($this->plugin->ftp as $v) {
          str_replace("#", "\n", $v["TITLE"]);
          str_replace("#", "\n", $v["TEXT"]);
          if (is_null($v["WORLD"]) or $v["WORLD"] === "default"){
            $v["WORLD"] = $this->plugin->getServer()->getDefaultLevel()->getName();
          }
          //
          if ($this->plugin->getServer()->loadLevel($v["WORLD"])) {
            if ($levn === $v["WORLD"]) {
              $pos = new Vector3($v["Xvec"], $v["Yvec"], $v["Zvec"]);
              $this->plugin->addFtp($p, $pos, $v["TITLE"], $v["TEXT"], $lev, $v["OWNER"]);
            }
          }else {
            $this->plugin->getLogger()->notice("記載されたワールド名 ".$v["WORLD"]." は存在しません。");
          }
        }
      }
      if (isset($this->plugin->pks[$levn])) {
        $n = $p->getName();
        foreach ($this->plugin->pks[$levn] as $pk) {
          if ($n === $pk->owner or $p->isOp()) {
            $pks = clone $pk;
            $pks->metadata[4][1] = "[$pks->eid] ".$pks->metadata[4][1];
            $p->dataPacket($pks);
          }else {
            $p->dataPacket($pk);
          }
        }
      }
    }else {//isset($this->plugin->crftp[$levn])
      if (isset($this->plugin->crftp[$levn])) {
        foreach ($this->plugin->crftp[$levn] as $pk) {
          $p->dataPacket($pk);
        }
      }
      if (isset($this->plugin->pks[$levn])) {
        $n = $p->getName();
        foreach ($this->plugin->pks[$levn] as $pk) {
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
}
