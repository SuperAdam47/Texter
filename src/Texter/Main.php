<?php

/**
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

# Pocketmine
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\{
  Command,
  CommandSender};
use pocketmine\entity\Entity;
use pocketmine\event\{
  Listener,
  entity\EntityLevelChangeEvent,
  player\PlayerJoinEvent};
use pocketmine\item\Item;
use pocketmine\level\{
  Level,
  Position};
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

# Texter
use Texter\TexterApi;
use Texter\commands\{
  TxtCommand,
  TxtAdmCommand};
use Texter\language\Lang;
use Texter\text\{
  CantRemoveFloatingText,
  FloatingText};
use Texter\task\{
  CheckUpdateTask,
  WorldGetTask};
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

  /**
   * TexterApiを取得します
   * @return TexterApi $this->api
   */
  public function getApi(): TexterApi{
    return $this->api;
  }

  /**
   * 追加用パケットを取得します
   * @return AddPlayerPacket $this->apk
   */
  public function getAddPacket(): AddPlayerPacket{
    return clone $this->apk;
  }

  /**
   * 削除用パケットを取得します
   * @return RemoveEntityPacket $this->rpk
   */
  public function getRemovePacket(): RemoveEntityPacket{
    return clone $this->rpk;
  }

  /****************************************************************************/
  /* PMMP Api */

  public function onLoad(){
    $this->loadFiles();
    $this->initApi();
    $this->checkPath();
    $this->registerCommands();
    $this->checkUpdate();
    $this->setTimezone();
  }

  public function onEnable(){
    $this->preparePacket();
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
    var_dump($this->crftps);
    // ftps.json
    $this->ftps_file = new Config($this->dir.self::FILE_FTP, Config::JSON);
    $this->ftps = $this->ftps_file->getAll();
    var_dump($this->ftps);
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
    if ($this->getDescription()->getVersion() !== $curver) {
      $this->getLogger()->warning($this->messages->get("version.warning"));
    }
    if (version_compare($newver, $curver, "=")) {
      $this->getLogger()->notice($this->language->transrateString("update.unnecessary", ["{curver}"], [$curver]));
    }elseif (version_compare($newver, $curver, ">")){
      $this->getLogger()->notice($this->language->transrateString("update.available.1", ["{newver}", "{curver}"], [$newver, $curver]));
      $this->getLogger()->notice($this->language->transrateString("update.available.2"));
      $this->getLogger()->notice($this->language->transrateString("update.available.3", ["{url}"], [$data[0]["html_url"]]));
    }
  }

  private function setTimezone(){
    $timezone = $this->config->get("timezone");
    if ($timezone !== false) {
      date_default_timezone_set($timezone);
      $this->getLogger()->info(TF::GREEN.$this->language->transrateString("timezone", ["{zone}"], [$timezone]));
    }
  }

  private function preparePacket(){
    if (!empty($this->crftps)) {
      foreach ($this->crftps as $value) {
        $title = str_replace("#", "\n", $value["TITLE"]);
        $text = isset($value["TEXT"]) ? str_replace("#", "\n", $value["TEXT"]) : "";
        if (is_null($value["WORLD"]) || $value["WORLD"] === "default"){
          $value["WORLD"] = $this->getServer()->getDefaultLevel()->getName();
        }
        //
        if ($this->getServer()->loadLevel($value["WORLD"])) {
          $level = $this->getServer()->getLevelByName($value["WORLD"]);
          $pos = new Vector3($value["Xvec"], $value["Yvec"], $value["Zvec"]);
          $crftp = new CantRemoveFloatingTextPerticle($level, $pos, $title, $text);
        }else {
          $this->getLogger()->notice($this->language->transrateString("world.not.exists", ["{world}"], [$value["WORLD"]]));
        }
      }
    }
    if (!empty($this->ftps)) {
      foreach ($this->ftps as $value) {
        $title = str_replace("#", "\n", $value["TITLE"]);
        $text = isset($value["TEXT"]) ? str_replace("#", "\n", $value["TEXT"]) : "";
        if (is_null($value["WORLD"]) || $value["WORLD"] === "default"){
          $value["WORLD"] = $this->getServer()->getDefaultLevel()->getName();
        }
        //
        if ($this->getServer()->loadLevel($value["WORLD"])) {
          $level = $this->getServer()->getLevelByName($value["WORLD"]);
          $pos = new Vector3($value["Xvec"], $value["Yvec"], $value["Zvec"]);
          $ftp = new FloatingTextPerticle($level, $pos, $title, $text, $value["OWNER"]);
          $this->api->registerParticle($ftp);
        }else {
          $this->getLogger()->notice($this->language->transrateString("world.not.exists", ["{world}"], [$value["WORLD"]]));
        }
      }
    }
  }
}
