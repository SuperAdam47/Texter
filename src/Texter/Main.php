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
use pocketmine\utils\TextFormat as TF;

# etc
use Texter\TexterApi;
use Texter\commands\TxtCommand;
use Texter\commands\TxtAdmCommand;
use Texter\task\WorldGetTask;
use Texter\task\CheckUpdateTask;
use Texter\language\Lang;
use Texter\utils\TunedConfig as Config;

define("DS", DIRECTORY_SEPARATOR);

class Main extends PluginBase {

  const NAME = "Texter";
  const VERSION = "v2.2.0-b3";
  const CODENAME = "Papilio dehaanii(カラスアゲハ)";

  const FILE_CONFIG = "config.yml";
  const FILE_CRFTP = "crftps.json";
  const FILE_FTP = "ftps.json";

  const CONFIG_VERSION = 10;

  /** @var bool $devmode */
  public $devmode = false;
  /** @var string $dir */
  public $dir = "";
  /** @var TexterApi $api */
  private $api = null;
  /** @var Lang $language */
  private $language = null;
  /** @var AddPlayerPacket $apk */
  private $apk = null;
  /** @var RemoveEntityPacket $rpk */
  private $rpk = null;

  /****************************************************************************/
  /* Public functions */

  public function getApi(): TexterApi{
    return $this->api;
  }

  /****************************************************************************/
  /* PMMP Api */

  public function onLoad(){
    $this->loadFiles();
    $this->initApi();
    $this->checkPath();
    $this->registerCommands();
    //$this->checkUpdate();
    //$this->setTimezone();
  }

  public function onEnable(){
    //$this->preparePacket();
    //$this->getServer()->getPluginManager()->registerEvents($this,$this);
    $this->getLogger()->info(TF::GREEN.self::NAME." ".self::VERSION." - ".TF::BLUE."\"".self::CODENAME."\" ".TF::GREEN.$this->language->transrateString("on.enable"));
  }

  /****************************************************************************/
  /* Private functions */

  private function loadFiles(){
    $this->dir = $this->getDataFolder();
    //
    if(!file_exists($this->dir)){
      mkdir($this->dir);
    }
    if(!file_exists($this->dir.self::FILE_CONFIG)){
      file_put_contents($this->dir.self::FILE_CONFIG, $this->getResource(self::FILE_CONFIG));
    }
    if(!file_exists($this->dir.self::FILE_CRFTP)){
      file_put_contents($this->dir.self::FILE_CRFTP, $this->getResource(self::FILE_CRFTP));
    }
    if(!file_exists($this->dir.self::FILE_FTP)){
      file_put_contents($this->dir.self::FILE_FTP, $this->getResource(self::FILE_FTP));
    }
    // config.yml
    $this->config = new Config($this->dir.self::FILE_CONFIG, Config::YAML);
    // crftps.json
    $this->crftps_file = new Config($this->dir.self::FILE_CRFTP, Config::JSON);
    $this->crftps = $this->crftps_file->getAll();
    // ftps.json
    $this->ftps_file = new Config($this->dir.self::FILE_FTP, Config::JSON);
    $this->ftps = $this->ftps_file->getAll();
    // Lang
    $lang = $this->config->get("language");
    if ($lang !== false) {
      $this->language = new Lang($this, $lang);
      $this->getLogger()->info(TF::GREEN.$this->language->transrateString("lang.registered", ["{lang}"], [$lang]));
    }else {
      $this->getLogger()->error("Invalid language settings. If you have any questions, please contact the issue.");
    }
    // CheckConfigVersion
    if (!$this->config->exists("configVersion") ||
        $this->config->get("configVersion") < self::CONFIG_VERSION) {
      $this->getLogger()->notice($this->language->transrateString("config.update", ["{newer}"], [self::CONFIG_VERSION]));
    }
  }

  private function initApi(){
    $this->api = new TexterApi($this);
  }

  private function checkPath(){
    $path = strtolower($this->config->get("path"));
    switch ($path) {
      case 'new':
        $this->apk = new network\mcpe\protocol\AddPlayerPacket();
        $this->rpk = new network\mcpe\protocol\RemoveEntityPacket();
      break;

      default:
        $this->apk = new network\protocol\AddPlayerPacket();
        $this->rpk = new network\protocol\RemoveEntityPacket();
      break;
    }
  }

  private function registerCommands(){
    if ((bool)$this->config->get("canUseCommands")) {
      $map = $this->getServer()->getCommandMap();
      $commands = [
        new TxtCommand($this),
        new TxtAdmCommand($this)
      ];
      $map->registerAll(self::NAME, $commands);
      $this->getLogger()->info(TF::GREEN.$this->language->transrateString("commands.registered"));
    }else {
      $this->getLogger()->info(TF::RED.$this->language->transrateString("commands.unavailable"));
    }
  }

  private function checkUpdate(){
    if ((bool)$this->config->get("checkUpdate")) {
      try {
        $async = new CheckUpdateTask();
        $this->getServer()->getScheduler()->scheduleAsyncTask($async);
      } catch (\Exception $e) {
        $this->getLogger()->warning($e->getMessage());
      }
    }
    if (strpos(self::VERSION, "-") !== false) {
      $this->getLogger()->notice($this->language->transrateString("version.pre"));
      $this->devmode = true;
    }
  }

  public function versionCompare(array $data){
    $curver = str_replace("v", "", self::VERSION);
    $newver = str_replace("v", "", $data[0]["name"]);
    if ($this->getDescription()->getVersion() !== $this->curver) {
      $this->getLogger()->warning($this->messages->get("version.warning"));
    }
    if (version_compare($newver, $curver, "=")) {
      $this->getLogger()->notice($this->language->transrateString("update.unnecessary", ["{curver}"], [$curver]));
    }elseif (version_compare($newver, $this->curver, ">")){
      $this->getLogger()->notice($this->language->transrateString("update.available.1", ["{newver}", "{curver}"], [$newver, $curver]));
      $this->getLogger()->notice($this->language->transrateString("update.available.2"));
      $this->getLogger()->notice($this->language->transrateString("update.available.3", ["{url}"], [$data[0]["html_url"]]));
    }
  }
}
