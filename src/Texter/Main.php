<?php

/*
 * ## To English-speaking countries
 *
 * Texter, the display FloatingTextPerticle plugin for PocketMine-MP
 * Copyright (c) 2017 yuko fuyutsuki < https://twitter.com/y_fyi >
 *
 * Released under the "MIT license".
 * You should have received a copy of the MIT license
 * along with this program.  If not, see
 * < http://opensource.org/licenses/mit-license.php >.
 *
 * ---------------------------------------------------------------------
 * ## 日本の方へ
 *
 * TexterはPocketMine-MP向けのFloatingTextPerticleを表示するプラグインです。
 * Copyright (c) 2017 yuko fuyutsuki < https://twitter.com/y_fyi >
 *
 * このソフトウェアは"MITライセンス"下で配布されています。
 * あなたはこのプログラムと共にMITライセンスのコピーを受け取ったはずです。
 * 受け取っていない場合、下記のURLからご覧ください。
 * < http://opensource.org/licenses/mit-license.php >
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

# Entity
use pocketmine\entity\Entity;
use pocketmine\entity\Item as ItemEntity;

# Player
use pocketmine\Player;

# Item
use pocketmine\item\Item;

# Event
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;

# Command
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;

# Math
use pocketmine\math\Vector3;

# Network
use pocketmine\network;

# Utils
use pocketmine\utils\TextFormat as Color;

# etc
use Texter\TexterAPI;
use Texter\commands\TxtCommand;
use Texter\commands\TxtAdmCommand;
use Texter\task\worldGetTask;
use Texter\utils\tunedConfig as Config;

define("DS", DIRECTORY_SEPARATOR);

class Main extends PluginBase implements Listener{
  const NAME = 'Texter',
        VERSION = 'v2.1.1',
        CODENAME = 'Convallaria majalis(鈴蘭)';

  /* @var developper`s option */
  public $devmode = false;
  /*

  /****************************************************************************/

  /**
   * @return Texter API
   */
  public function getAPI(){
    return TexterAPI::getInstance();
  }

  /****************************************************************************/
  /**
   * Private APIs
   */
  /**
   * 初期化処理
   */
  private function initialize(){
    $this->checkFiles();
    $this->checkPath();
    $this->registerCommands();
    $this->checkUpdate();
    date_default_timezone_set($this->config->get("timezone"));//時刻合わせ
    $this->getLogger()->info("§a".str_replace("{zone}", $this->config->get("timezone"), $this->messages->get("timezone")));
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
    $this->lang = $this->api->getLanguage();
    // lang_{$this->lang}.json
    $this->file1 = "lang_{$this->lang}.json";
    $this->messages = new Config(__DIR__.DS."..".DS."..".DS."lang".DS.$this->file1, Config::JSON);
    $this->getLogger()->info("§a".str_replace("{lang}", $this->lang, $this->messages->get("lang.registered")));
    // checkConfigVersion
    $newer = 10;
    if (!$this->config->exists("configVersion") ||
        $this->config->get("configVersion") < $newer) {
      $this->getLogger()->notice(str_replace("{newer}", "[{$newer}]", $this->messages->get("config.update")));
    }
    // crftps.json
    $this->crftps_file = new Config($this->dir.$this->file2, Config::JSON);
    $this->crftps = $this->crftps_file->getAll();
    // ftps.json
    $this->ftps_file = new Config($this->dir.$this->file3, Config::JSON);
    $this->ftps = $this->ftps_file->getAll();
  }

  /**
   * サーバー確認(パス変更の為)
   */
  private function checkPath(){
    $path = strtolower($this->config->get("path"));
    switch ($path) {
      case 'new':
        $this->AddEntityPacket = new network\mcpe\protocol\AddEntityPacket();
        $this->RemoveEntityPacket = new network\mcpe\protocol\RemoveEntityPacket();
      break;

      default:
        $this->AddEntityPacket = new network\protocol\AddEntityPacket();
        $this->RemoveEntityPacket = new network\protocol\RemoveEntityPacket();
      break;
    }
  }

  /**
   * コマンド追加処理
   */
  private function registerCommands(){
    if ((bool)$this->config->get("canUseCommands")) {
      $map = $this->getServer()->getCommandMap();
      $commands = [
        new TxtCommand($this),
        new TxtAdmCommand($this)
      ];
      $map->registerAll("Texter", $commands);
      $this->getLogger()->info("§a".$this->messages->get("commands.registered"));
    }else {
      $this->getLogger()->info("§c".$this->messages->get("commands.unavailable"));
    }
  }

  /**
   * アップデート確認
   */
  private function checkUpdate(){
    $this->devmode = false;
    if ((bool)$this->config->get("checkUpdate")) {
      try {
        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_URL => "https://api.github.com/repos/fuyutsuki/Texter/releases",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_USERAGENT => "getGitHubAPI",
          CURLOPT_SSL_VERIFYPEER => false
        ]);
        $json = curl_exec($curl);

        $errorno = curl_errno($curl);
        if ($errorno) {
          $error = curl_error($curl);
          throw new \Exception($error);
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
          $this->getLogger()->notice(str_replace("{curver}", $curver, $this->messages->get("update.unnecessary")));
        }elseif (version_compare($newver, $curver, "<") and
                 is_null($flag)) {
          $this->devmode = true;// NOTE:for developper option
        }elseif (is_null($flag)) {
          $this->getLogger()->notice(str_replace(["{newver}", "{curver}"], [$newver, $curver], $this->messages->get("update.available.1")));
          $this->getLogger()->notice($this->messages->get("update.available.2"));
          $this->getLogger()->notice(str_replace("{url}", $data[0]["html_url"], $this->messages->get("update.available.3")));
        }
      } catch (\Exception $e) {
        $this->getLogger()->warning($e->getMessage());
      }
    }else {
      $curver = str_replace("v", "", self::VERSION);
      if ($this->getDescription()->getVersion() !== $curver) {
        $this->getLogger()->warning($this->messages->get("warning.version?"));
      }
    }
  }

  /**
   * パケット送信準備
   */
  private function preparePacket(){
    foreach ($this->crftps as $v) {
      $title = str_replace("#", "\n", $v["TITLE"]);
      $text = isset($v["TEXT"]) ? str_replace("#", "\n", $v["TEXT"]) : "";
      if (is_null($v["WORLD"]) || $v["WORLD"] === "default"){
        $v["WORLD"] = $this->getServer()->getDefaultLevel()->getName();
      }
      //
      if ($this->getServer()->loadLevel($v["WORLD"])) {
        $pos = new Vector3($v["Xvec"], $v["Yvec"], $v["Zvec"]);
        $this->api->makeCrftp($pos, $title, $text, $v["WORLD"]);
      }else {
        $this->getLogger()->notice(str_replace("{world}", $v["WORLD"], $this->messages->get("world.not.exists")));
      }
    }
    foreach ($this->ftps as $v) {
      $title = str_replace("#", "\n", $v["TITLE"]);
      $text = isset($v["TEXT"]) ? str_replace("#", "\n", $v["TEXT"]) : "";
      if (is_null($v["WORLD"]) || $v["WORLD"] === "default"){
        $v["WORLD"] = $this->getServer()->getDefaultLevel()->getName();
      }
      //
      if ($this->getServer()->loadLevel($v["WORLD"])) {
        $pos = new Vector3($v["Xvec"], $v["Yvec"], $v["Zvec"]);
        $this->api->makeFtp($pos, $title, $text, $v["WORLD"], $v["OWNER"]);
      }else {
        $this->getLogger()->notice(str_replace("{world}", $v["WORLD"], $this->messages->get("world.not.exists")));
      }
    }
  }

  /**
   * ワールド変更時のdespawn処理
   *
   * @param Player $player
   * @param int $euid
   */
  private function removeWorldChangeFtp(Player $player, string $euid){
    $rpk = clone $this->RemoveEntityPacket;
    $rpk->entityUniqueId = $euid;

    $player->dataPacket($rpk);
  }

  /****************************************************************************/
  /**
   * PMMPPluginBase APIs
   */
  public function onLoad(){
    $this->initAPI();
    $this->initialize();
  }

  public function onEnable(){
    $this->preparePacket();
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    $this->getLogger()->info(Color::GREEN.self::NAME." ".self::VERSION." - ".Color::BLUE."\"".self::CODENAME."\" ".Color::GREEN.$this->messages->get("on.enable"));
  }

  public function onJoin(PlayerJoinEvent $e){
    $p = $e->getPlayer();
    $lev = $p->getLevel();
    $levn = $lev->getName();
    //
    $crftps = ($this->api->getCrftps()) ? $this->api->getCrftps() : false;
    if (isset($crftps[$levn])) {
      foreach ($crftps[$levn] as $pk) {;
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

  public function onLevelChange(EntityLevelChangeEvent $e){
    $p = $e->getEntity();
    if ($p instanceof Player){
      $levn = $p->getLevel()->getName();
      if ($crftps = $this->api->getCrftps() !== false) {
        if (isset($crftps[$levn])) {
          foreach ($crftps[$levn] as $crftp) {
            $this->removeWorldChangeFtp($p, $crftp->entityUniqueId);
          }
        }
      }
      if ($ftps = $this->api->getFtps() !== false) {
        if (isset($ftps[$levn])) {
          foreach ($ftps[$levn] as $ftp) {
            $this->removeWorldChangeFtp($p, $ftp->entityUniqueId);
          }
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
