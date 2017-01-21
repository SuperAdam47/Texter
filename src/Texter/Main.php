<?php
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

# Math
use pocketmine\math\Vector3;

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
        VERSION = 'v1.5.5';

  /****************************************************************************/
  /**
   * Private APIs
   */

  /**
   * 初期化処理
   */
  private function initialize(){
    date_default_timezone_set("Asia/Tokyo");//時刻合わせ
    //
    $this->dir = $this->getDataFolder();
    $this->file1 = "crftps.json";
    $this->file2 = "ftps.json";
    //
    if(!file_exists($this->dir)){
      mkdir($dir);
    }
    if(!file_exists($this->dir.$this->file1)){
      file_put_contents($this->dir.$this->file1, $this->getResource($this->file1));
    }
    if(!file_exists($this->dir.$this->file2)){
      file_put_contents($this->dir.$this->file2, $this->getResource($this->file2));
    }
    //
    $this->crftps_file = new Config($this->dir.$this->file1, Config::JSON);
    $this->crftps = $this->crftps_file->getAll();
    //
    $this->ftps = new Config($this->dir.$this->file2, Config::JSON);
    $this->ftp = $this->ftps->getAll();
    //
    $this->registerCommands();
    //
    $this->YamlToJson();
  }

  /**
   * コマンド追加処理
   */
  private function registerCommands(){
    $map = $this->getServer()->getCommandMap();
    $commands = [
      "txt" => "\\Texter\\commands\\TxtCommand"
    ];
    foreach ($commands as $cmd => $class) {
      $map->register("Texter", new $class($this));
    }
    return true;
  }

  /**
   * yaml->json処理
   */
  private function YamlToJson(){
    #crftps.json
    if (is_dir($this->dir) and $handle = opendir($this->dir)) {
      while (($file = readdir($handle)) !== false) {
        if (filetype($path = $this->dir.$file)) {
          switch ($file) {
            case "crftps.yml":
            case "config.yml":
              $oldyml1 = new Config($path, Config::YAML);
              $oldData1 = $oldyml1->getAll();
              $this->crftps_file->setAll($oldData1);
              $this->crftps_file->save();
              unlink($path);
              $this->getLogger()->info(Color::GREEN."[ crftps.yml -> crftps.json ] データ移動が完了しました。");
            break;

            case 'ftps.yml':
              $oldyml2 = new Config($path, Config::YAML);
              $oldData2 = $oldyml2->getAll();
              $this->ftps->setAll($oldData2);
              $this->ftps->save();
              unlink($path);
              $this->getLogger()->info(Color::GREEN."[ ftps.yml -> ftps.json ] データ移動が完了しました。");
            break;
          }
        }
      }
    }
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
   * @param Vector3 $pos
   * @param string $title
   * @param string $text
   * @param string $levelname
   */
  public function addCrftp(Player $player, Vector3 $pos, string $title, string $text, string $levelname){
    $this->entityId = Entity::$entityCount++;
    $pk = new AddEntityPacket();
    $pk->eid = $this->entityId;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = $pos->x;
    $pk->y = $pos->y;
    $pk->z = $pos->z;
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
   * @param Vector3 $pos
   * @param string $title
   * @param string $text
   * @param Level $level
   */
  public function addFtp(Player $player, Vector3 $pos, string $title, string $text, Level $level, string $ownername){
    $levelname = $level->getName();
    $this->entityId = Entity::$entityCount++;
    $pk = new AddEntityPacket();
    $pk->eid = $this->entityId;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = $pos->x;
    $pk->y = $pos->y;
    $pk->z = $pos->z;
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
    $this->pks[$levelname][] = $pk;//オリジナルを保存

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
    $this->getLogger()->info(Color::GREEN.self::NAME." ".self::VERSION." が読み込まれました。");
  }

  public function onJoin(PlayerJoinEvent $e){
    $p = $e->getPlayer();
    $lev = $p->getLevel();
    $levn = $lev->getName();
    //
    if (!isset($this->crftp[$levn])) {
      foreach ($this->crftps as $v) {
        str_replace("#", "\n", $v["TITLE"]);
        str_replace("#", "\n", $v["TEXT"]);
        if (is_null($v["WORLD"]) or $v["WORLD"] === "default"){
          $v["WORLD"] = $this->getServer()->getDefaultLevel()->getName();
        }
        //
        if ($this->getServer()->loadLevel($v["WORLD"])) {
          if ($levn === $v["WORLD"]) {
            $pos = new Vector3($v["Xvec"], $v["Yvec"], $v["Zvec"]);
            $this->addCrftp($p, $pos, $v["TITLE"], $v["TEXT"], $v["WORLD"]);
          }
        }else {
          $this->getLogger()->notice("記載されたワールド名 ".$v["WORLD"]." は存在しません。");
        }
      }
      if (isset($this->ftp)) {
        foreach ($this->ftp as $v) {
          str_replace("#", "\n", $v["TITLE"]);
          str_replace("#", "\n", $v["TEXT"]);
          if (is_null($v["WORLD"]) or $v["WORLD"] === "default"){
            $v["WORLD"] = $this->getServer()->getDefaultLevel()->getName();
          }
          //
          if ($this->getServer()->loadLevel($v["WORLD"])) {
            if ($levn === $v["WORLD"]) {
              $pos = new Vector3($v["Xvec"], $v["Yvec"], $v["Zvec"]);
              $this->addFtp($p, $pos, $v["TITLE"], $v["TEXT"], $lev, $v["OWNER"]);
            }
          }else {
            $this->getLogger()->notice("記載されたワールド名 ".$v["WORLD"]." は存在しません。");
          }
        }
      }
      if (isset($this->pks[$levn])) {
        $n = $p->getName();
        foreach ($this->pks[$levn] as $pk) {
          if ($n === $pk->owner or $p->isOp()) {
            $pks = clone $pk;
            $pks->metadata[4][1] = "[$pks->eid] ".$pks->metadata[4][1];
            $p->dataPacket($pks);
          }else {
            $p->dataPacket($pk);
          }
        }
      }
    }else {//isset($this->crftp[$levn])
      if (isset($this->crftp[$levn])) {
        foreach ($this->crftp[$levn] as $pk) {
          $p->dataPacket($pk);
        }
      }
      if (isset($this->pks[$levn])) {
        $n = $p->getName();
        foreach ($this->pks[$levn] as $pk) {
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

  public function onLevelChange(EntityLevelChangeEvent $e){
    $p = $e->getEntity();
    if ($p instanceof Player){
      $levn = $p->getLevel()->getName();
      if (isset($this->pks[$levn])) {
        foreach ($this->pks[$levn] as $ftp) {
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
    $this->getLogger()->info(Color::RED.self::NAME." が無効化されました。");
  }
}
