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
use Texter\task\WorldGetTask;
use Texter\task\CheckUpdateTask;
use Texter\utils\TunedConfig as Config;

define("DS", DIRECTORY_SEPARATOR);

class Main extends PluginBase {

  const NAME = "Texter";
  const VERSION = "v2.2.0-b3";
  const CODENAME = "Papilio dehaanii(カラスアゲハ)";

  /** @var bool $devmode */
  public $devmode = false;
  /** @var TexterApi $api */
  private $api = null;
  /** @var array $extensions */
  private $extensions = [];

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
    $this->checkUpdate();
    $this->setTimezone();
  }

  public function onEnable(){

  }

  /****************************************************************************/
  /* Private functions */

  private function loadFiles(){
    $this->dir  = $this->getDataFolder();
    $this->conf = "config.yml";
    $this->data = "data.db";
    //
    if(!file_exists($this->dir)){
      mkdir($this->dir);
    }
    if(!file_exists($this->dir.$this->conf)){
      file_put_contents($this->dir.$this->conf, $this->getResource($this->conf));
    }
    if(!file_exists($this->dir.$this->data)){
      file_put_contents($this->dir.$this->data, []);
    }
    $this->config = new Config($this->dir.$this->conf, Config::YAML);
    $this->language = $this->config->get("language");
    $this->getLogger()->info();// TODO: language sut
    if (!$this->config->exists("configVersion") ||
        $this->config->get("configVersion") < $newer) {
      $this->getLogger()->notice();// TODO: Too newer or invalid
    }
  }

  private function initApi(){
    $this->api = new TexterApi($this);
  }
}
