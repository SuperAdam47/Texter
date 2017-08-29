<?php

/**
 * ## To English-speaking countries
 *
 * Texter, the display FloatingTextPerticle plugin for PocketMine-MP
 * Copyright (c) 2017 yuko fuyutsuki < https://twitter.com/y_fyi >
 *
 * Released under the "MIT license".
 * You should have received a copy of the MIT license
 * along with this program.  If not,
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
use Texter\EventListener;
use Texter\TexterApi;
use Texter\commands\{
  TxtCommand,
  TxtAdmCommand};
use Texter\language\Lang;
use Texter\text\{
  CantRemoveFloatingText as CRFT,
  FloatingText as FT};
use Texter\task\{
  CheckUpdateTask,
  WorldGetTask};
use Texter\utils\TunedConfig as Config;

define("DS", DIRECTORY_SEPARATOR);

class Main extends PluginBase {

  const NAME = "Texter";
  const VERSION = "v2.2.0-b5";
  const CODENAME = "Papilio dehaanii(カラスアゲハ)";

  const FILE_CONFIG = "config.yml";
  const FILE_CRFTP = "crftps.json";// for old format
  const FILE_CRFT = "crfts.json";
  const FILE_FTP = "ftps.json";// for old format
  const FILE_FT = "fts.json";

  const CONFIG_VERSION = 20;

  /** @var bool $devmode */
  public $devmode = false;
  /** @var string $dir */
  public $dir = "";
  /** @var Config $config */
  private $config = null;
  /** @var Config $crft_config */
  private $crft_config = null;
  /** @var Config $ft_config */
  private $ft_config = null;
  /** @var TexterApi $api */
  private $api = null;
  /** @var Lang $language */
  private $language = null;
  /** @var array $crfts */
  private $crfts = [];
  /** @var array $fts */
  private $fts = [];
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
  public function getAddPacket(){
    return clone $this->apk;
  }

  /**
   * 削除用パケットを取得します
   * @return RemoveEntityPacket $this->rpk
   */
  public function getRemovePacket(){
    return clone $this->rpk;
  }

  public function getCharaLimit(): int{
    return (int)$this->config->get("limit");
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
    $this->prepareTexts();
    $listener = new EventListener($this);
    $this->getServer()->getPluginManager()->registerEvents($listener, $this);
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
    // config.yml
    $this->config = new Config($this->dir.self::FILE_CONFIG, Config::YAML);
    // Lang
    $lang = $this->config->get("language");
    if ($lang !== false) {
      $this->language = new Lang($this, $lang);
      $this->getLogger()->info(TF::GREEN.$this->language->transrateString("lang.registered", ["{lang}"], [$lang]));
    }else {
      $this->getLogger()->error("Invalid language settings. If you have any questions, please contact the issue.");
    }
    if(!file_exists($this->dir.self::FILE_CRFT)){
      if (!file_exists($this->dir.self::FILE_CRFTP)) {
        file_put_contents($this->dir.self::FILE_CRFT, $this->getResource(self::FILE_CRFT));
      }else {
        $tmpOld = new Config($this->dir.self::FILE_CRFTP, Config::JSON);
        $tmpOldData = $tmpOld->getAll();
        file_put_contents($this->dir.self::FILE_CRFT, []);
        $tmpNew = new Config($this->dir.self::FILE_CRFT, Config::JSON);
        $tmpNew->setAll($tmpOldData);
        $tmpNew->save();
        unlink($this->dir.self::FILE_CRFTP);
        $this->getLogger()->info(TF::GREEN.$this->language->transrateString("transfer.crftp"));
      }
    }
    if(!file_exists($this->dir.self::FILE_FT)){
      if (!file_exists($this->dir.self::FILE_FTP)) {
        file_put_contents($this->dir.self::FILE_FT, $this->getResource(self::FILE_FT));
      }else {
        $tmpOld = new Config($this->dir.self::FILE_FTP, Config::JSON);
        $tmpOldData = $tmpOld->getAll();
        file_put_contents($this->dir.self::FILE_FT, []);
        $tmpNew = new Config($this->dir.self::FILE_FT, Config::JSON);
        $tmpNew->setAll($tmpOldData);
        $tmpNew->save();
        unlink($this->dir.self::FILE_FTP);
        $this->getLogger()->info(TF::GREEN.$this->language->transrateString("transfer.ftp"));
      }
    }
    // crfts.json
    $this->crft_config = new Config($this->dir.self::FILE_CRFT, Config::JSON);
    $this->crfts = $this->crft_config->getAll();
    // fts.json
    $this->ft_config = new Config($this->dir.self::FILE_FT, Config::JSON);
    $this->fts = $this->ft_config->getAll();
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
        $this->apk = new \pocketmine\network\mcpe\protocol\AddPlayerPacket();
        $this->rpk = new \pocketmine\network\mcpe\protocol\RemoveEntityPacket();
      break;

      default:
        $this->apk = new \pocketmine\network\protocol\AddPlayerPacket();
        $this->rpk = new \pocketmine\network\protocol\RemoveEntityPacket();
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

  private function prepareTexts(){
    if (!empty($this->crfts)) {
      $this->crft_config->setAll([]);
      $this->crft_config->save();
      foreach ($this->crfts as $k => $value) {
        $title = isset($value["TITLE"]) ? str_replace("#", "\n", $value["TITLE"]) : "";
        $text = isset($value["TEXT"]) ? str_replace("#", "\n", $value["TEXT"]) : "";
        if (is_null($value["WORLD"]) || $value["WORLD"] === "default"){
          $value["WORLD"] = $this->getServer()->getDefaultLevel()->getName();
        }
        //
        if ($this->getServer()->loadLevel($value["WORLD"])) {
          $level = $this->getServer()->getLevelByName($value["WORLD"]);
          $crft = new CRFT($level, $value["Xvec"], $value["Yvec"], $value["Zvec"], $title, $text);
          $key = $value["WORLD"].$value["Zvec"].$value["Xvec"].$value["Yvec"];
          $this->crft_config->set($key, $this->crfts[$k]);
          $this->crft_config->save();
        }else {
          $this->getLogger()->notice($this->language->transrateString("world.not.exists", ["{world}"], [$value["WORLD"]]));
        }
      }
    }
    if (!empty($this->fts)) {
      $this->ft_config->setAll([]);
      $this->ft_config->save();
      foreach ($this->fts as $k => $value) {
        $title = isset($value["TITLE"]) ? str_replace("#", "\n", $value["TITLE"]) : "";
        $text = isset($value["TEXT"]) ? str_replace("#", "\n", $value["TEXT"]) : "";
        if (is_null($value["WORLD"]) || $value["WORLD"] === "default"){
          $value["WORLD"] = $this->getServer()->getDefaultLevel()->getName();
        }
        //
        if ($this->getServer()->loadLevel($value["WORLD"])) {
          $level = $this->getServer()->getLevelByName($value["WORLD"]);
          $ft = new FT($level, $value["Xvec"], $value["Yvec"], $value["Zvec"], $title, $text, $value["OWNER"]);
          $key = $value["WORLD"].$value["Zvec"].$value["Xvec"].$value["Yvec"];
          $this->ft_config->set($key, $this->fts[$k]);
          $this->ft_config->save();
        }else {
          $this->getLogger()->notice($this->language->transrateString("world.not.exists", ["{world}"], [$value["WORLD"]]));
        }
      }
    }
  }
}
