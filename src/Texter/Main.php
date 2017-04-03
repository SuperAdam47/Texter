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

# Utils
use pocketmine\utils\UUID;
use pocketmine\utils\TextFormat as Color;

#etc
use Texter\TexterAPI;
use Texter\commands\TxtCommand;
use Texter\task\worldGetTask;
use Texter\utils\tunedConfig as Config;

#Extensions
// TODO: ver1.6.5

class Main extends PluginBase implements Listener{
  const NAME = 'Texter',
        //NOTE: このバージョンを変えた場合、正常な動作をしない場合があります
        VERSION = 'v1.6.2';

  /* @var Extensions */
  private $extensions = [];

  /****************************************************************************/
  /**
   * Public APIs
   */
  /**
   * @return Texter API
   */
  public function getAPI(){
    return $this->api;
  }

  /**
   * @return array [
   *                "1" => AddEntityPacket
   *                "2" => RemoveEntityPacket
   *               ]
   */
  public function getPacketModel(){
    return [$this->AddEntityPacket, $this->RemoveEntityPacket];
  }

  /**
   * @return Extension
   * TODO
   */
  /*
  public function getExtension(string $key){
    if (isset($this->extensions[$key])) {
      return $this->extensions[$key];
    }else {
      return false;
    }
  }
  */

  /****************************************************************************/
  /**
   * Private APIs
   */
  /**
   * 初期化処理
   */
  private function initialize(){
    try {
      $this->checkServer();
      $this->initAPI();
      $this->checkFiles();
      $this->registerCommands();
      $this->YamlToJson();
      $this->loadExtensions();
      $this->checkUpdate();
      $this->preparePacket();
      date_default_timezone_set($this->config->get("timezone"));//時刻合わせ
    } catch (Exception $e) {
      $this->getLogger()->critical($e->getMessage());
    }
  }

  /**
   * サーバー確認(パス変更の為)
   */
  private function checkServer(){
    $serverName = strtolower($this->getServer()->getName());
    switch ($serverName) {
      case 'pocketmine-mp':
        $this->AddEntityPacket = new \pocketmine\network\mcpe\protocol\AddEntityPacket();
        $this->RemoveEntityPacket = new \pocketmine\network\mcpe\protocol\RemoveEntityPacket();
      break;

      default:
        $this->AddEntityPacket = new \pocketmine\network\protocol\AddEntityPacket();
        $this->RemoveEntityPacket = new \pocketmine\network\protocol\RemoveEntityPacket();
      break;
    }
  }

  /**
   * API初期化
   */
  private function initAPI(){
    $this->api = new TexterAPI($this);
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
      mkdir($this->dir);
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
    $this->getLogger()->info("§a".str_replace("{lang}", $this->lang, $this->messages->get("lang.registered")));
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
   * 拡張ファイル読み込み
   */
  private function loadExtensions(){
    // TODO: ver1.6.5
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
    }else {
      $curver = str_replace("v", "", self::VERSION);
      if ($this->getDescription()->getVersion() !== $curver) {
        $this->getLogger()->warning($this->messages->get("warning.version?"));
        $flag = 0;
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
    $pk = clone $this->AddEntityPacket;
    $pk->eid = ++Entity::$entityCount;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = $pos[0];
    $pk->y = $pos[1];
    $pk->z = $pos[2];
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
    $pk = clone $this->AddEntityPacket;
    $pk->eid = ++Entity::$entityCount;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = $pos[0];
    $pk->y = $pos[1];
    $pk->z = $pos[2];
    $flags = 0;
    $flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
    $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title . ($text !== "" ? "\n" . $text : "")],
    ];
    $pk->world = $levelname;
    $pk->owner = $ownername;
    $this->ftp[$levelname][] = $pk;
  }

  /**
   * ワールド変更時のdespawn処理
   *
   * @param Player $player
   * @param int $eid
   */
  private function removeWorldChangeFtp(Player $player, string $eid){
    $rpk = clone $this->RemoveEntityPacket;
    $rpk->eid = $eid;

    $player->dataPacket($rpk);
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
    //
    $task = new worldGetTask($this, $p);
    $this->getServer()->getScheduler()->scheduleDelayedTask($task, 20);
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
