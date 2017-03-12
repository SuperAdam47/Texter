<?php

/*
 * Texter, the display FloatingTextPerticle plugin for PocketMine-MP
 * Copyright (C) 2017 fuyutsuki <https://twitter.com/y_fyi>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Texter;

# Base
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

# Server
use pocketmine\Server;

# Level
use pocketmine\level\Level;
use pocketmine\level\Position;

#Entity
use pocketmine\entity\Entity;
use pocketmine\entity\Item as ItemEntity;

# Player
use pocketmine\Player;

#Item
use pocketmine\item\Item;

# Event
use pocketmine\event\Event;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;

#Command
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;

#Scheduler
use pocketmine\scheduler\PluginTask;

#Network
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;

# Utils
use pocketmine\utils\UUID;
use pocketmine\utils\TextFormat as Color;

#etc
use Texter\commands\TxtCommand;
use Texter\task\worldGetTask;
use Texter\utils\tunedConfig as Config;

class Main extends PluginBase implements Listener{
  const NAME = 'Texter',
        //NOTE: このバージョンを変えた場合、正常な動作をしない場合があります
        VERSION = 'v1.6.0';

  /****************************************************************************/
  /**
   * Private APIs
   */

  /**
   * 初期化処理
   */
  private function initialize(){
    date_default_timezone_set("Asia/Tokyo");//時刻合わせ
    try {
      $this->checkFiles();
      $this->registerCommands();
      $this->YamlToJson();
      $this->checkUpdate();
      $this->preparePacket();
    } catch (Exception $e) {
      $this->getLogger()->critical($e->getMessage());
    }
  }

  /**
   * ファイルチェック
   */
  private function checkFiles(){
    $this->dir   = $this->getDataFolder();
    $this->conf  = "config.yml";
    $this->file2 = "crftps.json";
    $this->file3 = "ftps.json";
    //
    if(!file_exists($this->dir)){
      mkdir($dir);
    }
    if(!file_exists($this->dir.$this->conf)){
      file_put_contents($this->dir.$this->conf, $this->getResource($this->conf));
    }
    if(!file_exists($this->dir.$this->file2)){
      file_put_contents($this->dir.$this->file2, $this->getResource($this->file2));
    }
    if(!file_exists($this->dir.$this->file3)){
      file_put_contents($this->dir.$this->file3, $this->getResource($this->file3));
    }
    // config.yml
    $this->config = new Config($this->dir.$this->conf, Config::YAML);
    $this->lang = $this->config->get("lang");
    // lang_{$this->lang}.json
    $this->file1 = "lang_{$this->lang}.json";
    $this->messages = new Config(__DIR__."/../../lang/{$this->file1}", Config::JSON);
    $this->getLogger()->info(str_replace("{lang}", $this->lang, $this->messages->get("lang.registered")));
    // crftps.json
    $this->crftps_file = new Config($this->dir.$this->file2, Config::JSON);
    $this->crftps = $this->crftps_file->getAll();
    // ftps.json
    $this->ftps_file = new Config($this->dir.$this->file3, Config::JSON);
    $this->ftps = $this->ftps_file->getAll();
  }

  /**
   * コマンド追加処理
   */
  private function registerCommands(){
    if ((bool)$this->config->get("canUseCommands")) {
      $map = $this->getServer()->getCommandMap();
      $commands = [
        "txt" => "\\Texter\\commands\\TxtCommand"
      ];
      foreach ($commands as $cmd => $class) {
        $map->register("Texter", new $class($this));
      }
      $this->getLogger()->info("§a".$this->messages->get("commands.registered"));
    }else {
      $this->getLogger()->info("§c".$this->messages->get("commands.unavailable"));
    }
  }

  /**
   * yaml->json処理
   */
  private function YamlToJson(){
    if (is_dir($this->dir) and $handle = opendir($this->dir)) {
      while (($file = readdir($handle)) !== false) {
        if (filetype($path = $this->dir.$file)) {
          switch ($file) {
            case "crftps.yml":
              $oldyml1 = new Config($path, Config::YAML);
              $oldData1 = $oldyml1->getAll();
              $this->crftps_file->setAll($oldData1);
              $this->crftps_file->save();
              $this->crftps = $oldData1;
              unlink($path);
              $this->getLogger()->info(Color::GREEN."[ crftps.yml -> crftps.json ] ".$this->messages->get("exchange.data"));
            break;

            case 'ftps.yml':
              $oldyml2 = new Config($path, Config::YAML);
              $oldData2 = $oldyml2->getAll();
              $this->ftps_file->setAll($oldData2);
              $this->ftps_file->save();
              $this->ftps = $oldData2;
              unlink($path);
              $this->getLogger()->info(Color::GREEN."[ crftps.yml -> crftps.json ] ".$this->messages->get("exchange.data"));
            break;
          }
        }
      }
    }
  }

  /**
   * アップデート確認
   */
  private function checkUpdate(){
    if ((bool)$this->config->get("checkUpdate")) {
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.github.com/repos/fuyutsuki/PMMP-Texter/releases",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => "getGitHubAPI",
        CURLOPT_SSL_VERIFYPEER => false
      ]);
      $json = curl_exec($curl);

      $errorno = curl_errno($curl);
      if ($errorno) {
        $error = curl_error($curl);
        throw new Exception($error);
      }
      curl_close($curl);
      $data = json_decode($json, true);

      $newver = str_replace("v", "", $data[0]["name"]);
      $curver = str_replace("v", "", self::VERSION);
      $flag = null;
      if ($this->getDescription()->getVersion() !== $curver) {
        $this->getLogger()->warning($this->messages->get("warning.version?"));
        $flag = 0;
      }
      if (version_compare($newver, $curver, "=")) {
        $this->getLogger()->info(str_replace("{curver}", $curver, $this->messages->get("update.unnecessary")));
      }elseif (version_compare($newver, $curver, "<") and
               is_null($flag)) {
        $this->getLogger()->notice("debug/test mode | v{$curver}");
        $this->devmode = 1;// TODO
      }elseif (is_null($flag)) {
        $this->getLogger()->notice(str_replace(["{newver}", "{curver}"], [$newver, $curver], $this->messages->get("update.available.1")));
        $this->getLogger()->notice($this->messages->get("update.available.2"));
        $this->getLogger()->notice(str_replace("{url}", $data[0]["html_url"], $this->messages->get("update.available.3")));
      }
    }
  }

  /**
   * パケット送信準備
   */
  private function preparePacket(){
    foreach ($this->crftps as $v) {
      $title = str_replace("#", "\n", $v["TITLE"]);
      $text  = str_replace("#", "\n", $v["TEXT"]);
      if (is_null($v["WORLD"]) or $v["WORLD"] === "default"){
        $v["WORLD"] = $this->getServer()->getDefaultLevel()->getName();
      }
      //
      if ($this->getServer()->loadLevel($v["WORLD"])) {
        $pos = [$v["Xvec"], $v["Yvec"], $v["Zvec"]];
        $this->makeCrftp($pos, $title, $text, $v["WORLD"]);
      }else {
        $this->getLogger()->notice(str_replace("{world}", $v["WORLD"], $this->messages->get("world.not.exists")));
      }
    }
    foreach ($this->ftps as $v) {
      $title = str_replace("#", "\n", $v["TITLE"]);
      $text  = str_replace("#", "\n", $v["TEXT"]);
      if (is_null($v["WORLD"]) or $v["WORLD"] === "default"){
        $v["WORLD"] = $this->getServer()->getDefaultLevel()->getName();
      }
      //
      if ($this->getServer()->loadLevel($v["WORLD"])) {
        $pos = [$v["Xvec"], $v["Yvec"], $v["Zvec"]];
        $this->makeFtp($pos, $title, $text, $v["WORLD"], $v["OWNER"]);
      }else {
        $this->getLogger()->notice(str_replace("{world}", $v["WORLD"], $this->messages->get("world.not.exists")));
      }
    }
  }

  /**
   * crftp送信準備
   */
  private function makeCrftp(array $pos, string $title, string $text, string $levelname){
    $this->entityId = Entity::$entityCount++;
    $pk = new AddEntityPacket();
    $pk->eid = $this->entityId;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = $pos[0];
    $pk->y = $pos[1];
    $pk->z = $pos[2];
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 0;
    $pk->pitch = 0;
    $pk->item = 0;
    $pk->meta = 0;
    $flags = 0;
    $flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
    $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title . ($text !== "" ? "\n" . $text : "")],
    ];
    $this->crftp[$levelname][] = $pk;
  }

  /**
   * ftp送信準備
   */
  private function makeFtp(array $pos, string $title, string $text, string $levelname, string $ownername){
    $this->entityId = Entity::$entityCount++;
    $pk = new AddEntityPacket();
    $pk->eid = $this->entityId;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = $pos[0];
    $pk->y = $pos[1];
    $pk->z = $pos[2];
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 0;
    $pk->pitch = 0;
    $pk->item = 0;
    $pk->meta = 0;
    $flags = 0;
    $flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
    $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title . ($text !== "" ? "\n" . $text : "")],
    ];
    $pk->owner = $ownername;
    $this->ftp[$levelname][] = $pk;
  }

  /**
   * ワールド変更時のdespawn処理
   *
   * @param Player $player
   * @param string $eid
   */
  private function removeWorldChangeFtp(Player $player, string $eid){
    $rpk = new RemoveEntityPacket();
    $rpk->eid = $eid;

    $player->dataPacket($rpk);
  }

  /****************************************************************************/
  /**
   * Public APIs
   */

  /**
   * 消すことのできない浮き文字を追加します
   *
   * @param Player $player
   * @param array $pos
   * @param string $title
   * @param string $text
   */
  public function addCrftp(Player $player, array $pos, string $title, string $text){
    $levelname = $p->getLevel()->getName();
    $this->entityId = Entity::$entityCount++;
    $pk = new AddEntityPacket();
    $pk->eid = $this->entityId;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = $pos[0];
    $pk->y = $pos[1];
    $pk->z = $pos[2];
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 0;
    $pk->pitch = 0;
    $pk->item = 0;
    $pk->meta = 0;
    $flags = 0;
    $flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
    $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title . ($text !== "" ? "\n" . $text : "")],
    ];
    $this->crftp[$levelname][] = $pk;
    $player->dataPacket($pk);
  }

  /**
   * 浮き文字を追加します
   *
   * @param Player $player
   * @param array $pos
   * @param string $title
   * @param string $text
   * @param string $ownername
   */
  public function addFtp(Player $player, array $pos, string $title, string $text, string $ownername){
    $level = $player->getLevel();
    $levelname = $level->getName();
    $this->entityId = Entity::$entityCount++;
    $pk = new AddEntityPacket();
    $pk->eid = $this->entityId;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = $pos[0];
    $pk->y = $pos[1];
    $pk->z = $pos[2];
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 0;
    $pk->pitch = 0;
    $pk->item = 0;
    $pk->meta = 0;
    $flags = 0;
    $flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
    $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title . ($text !== "" ? "\n" . $text : "")],
    ];
    $pk->owner = $ownername;
    $this->ftp[$levelname][] = $pk;//オリジナルを保存

    $players = $level->getPlayers();
    foreach ($players as $pl) {
      $n = $pl->getName();
      if ($n === $ownername or $pl->isOp()) {
        $pks = clone $pk;//送信用パケット複製
        $pks->metadata[4][1] = "[$this->entityId] ".$pks->metadata[4][1];
        $pl->dataPacket($pks);
      }else {
        $pl->dataPacket($pk);
      }
    }
    return $this->entityId;
  }

  /**
   * 指定IDの浮き文字を削除します
   *
   * @param Player $player
   * @param string $id
   */
  public function removeFtp(Player $player, string $id){
    $pk = new RemoveEntityPacket();
    $pk->eid = $id;
    $level = $player->getLevel();
    $levelname = $level->getName();
    $players = $level->getPlayers();//Levelにいる人を取得
    foreach ($players as $pl) {
      $pl->dataPacket($pk);
    }
  }

  /****************************************************************************/
  /**
   * PMMPPluginBase APIs
   */

  public function onEnable(){
    $this->initialize();
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    $this->getLogger()->info(Color::GREEN.self::NAME." ".self::VERSION." ".$this->messages->get("on.enable"));
  }

  public function onJoin(PlayerJoinEvent $e){
    $p = $e->getPlayer();
    $lev = $p->getLevel();
    $levn = $lev->getName();
    //
    if (isset($this->crftp[$levn])) {
      foreach ($this->crftp[$levn] as $pk) {
        $p->dataPacket($pk);
      }
    }
    if (isset($this->ftp[$levn])) {
      $n = $p->getName();
      foreach ($this->ftp[$levn] as $pk) {
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

  public function onLevelChange(EntityLevelChangeEvent $e){
    $p = $e->getEntity();
    if ($p instanceof Player){
      $levn = $p->getLevel()->getName();
      if (isset($this->ftp[$levn])) {
        foreach ($this->ftp[$levn] as $ftp) {
          $this->removeWorldChangeFtp($p, $ftp->eid);
        }
      }
      if (isset($this->crftp[$levn])) {
        foreach ($this->crftp[$levn] as $ftp) {
          $this->removeWorldChangeFtp($p, $ftp->eid);
        }
      }
      $task = new worldGetTask($this, $p);
      $this->getServer()->getScheduler()->scheduleDelayedTask($task, 20);
    }
  }

  public function onDisable(){
    $this->getLogger()->info(Color::RED.self::NAME." ".$this->messages->get("on.disable"));
  }
}
