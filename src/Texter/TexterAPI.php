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

use Texter\Main;

# Player
use pocketmine\Player;

# Entity
use pocketmine\entity\Entity;
use pocketmine\entity\Item as ItemEntity;

# Math
use pocketmine\math\Vector3;

# Texter
use Texter\task\extensionTask;

/**
 * TexterAPI
 */
class TexterAPI{

  /* @var Crftp[$levelName][] = $pk */
  private $crftp = [],
  /* @var Ftp[$levelName][] = $pk */
          $ftp = [];
  /* @var TexterAPI */
  private static $instance = null;

  public function __construct(Main $main){
    $this->main = $main;
    self::$instance = $this;
  }

/******************************************************************************/
### get/set(情報取得/変更) 関連 ###########
  /**
   * @return TexterAPI
   */
  public static function getInstance(){
    return self::$instance;
  }

  /**
   * get using language
   * @return string jpn | eng
   */
  public function getLanguage(): string{
    $lang = $this->main->config->get("language");
    switch (strtolower($lang)) {
      case 'jpn': return "jpn"; break;
      case 'eng': return "eng"; break;
      default:    return "eng"; break;
    }
  }

  /**
   * get from language file
   * @param string $key
   * ---------------------------
   * @return string $message
   */
  public function getMessage(string $key){
    return $this->main->messages->get($key);
  }

  /**
   * @return array $this->crftp[$levelName][$euid] | false
   */
  public function getCrftps(){
    $crftp = isset($this->crftp) ? $this->crftp : false;
    return $crftp;
  }

  /**
   * @param string $levelName
   * @param int $euid
   * -------------------------------
   * @return array $this->crftp[$levelName][$euid] | false
   */
  public function getCrftp(string $levelName, int $euid){
    $crftps = $this->getCrftps();
    if ($crftps === false) {
      return false;
    }else {
      $pk = (isset($crftps[$levelName][$euid])) ? $crftps[$levelName][$euid] : false;
      if ($pk === false) {
        return false;
      }else {
        return $pk;
      }
    }
  }

  /**
   * @return array $this->ftp[$levelName][$euid] | false
   */
  public function getFtps(){
    $ftp = isset($this->ftp) ? $this->ftp : false;
    return $ftp;
  }

  /**
   * @param string $levelName
   * @param int $euid
   * -------------------------------
   * @return array $this->ftp[$levelName][$euid] | false
   */
  public function getFtp(string $levelName, int $euid){
    $ftps = $this->getFtps();
    if ($ftps === false) {
      return false;
    }else {
      $pk = (isset($ftps[$levelName][$euid])) ? $ftps[$levelName][$euid] : false;
      if ($pk === false) {
        return false;
      }else {
        return $pk;
      }
    }
  }

/******************************************************************************/
### FloatingTextPerticle 関連 ############
  /**
   * 固定の浮き文字を生成します
   *
   * @param Vector3 $pos
   * @param string $title
   * @param string $text = ""
   * @param string $levelname = "world"
   * ------------------------
   * @return AddEntityPacket $pk
   */
  public function makeCrftp(Vector3 $pos, string $title, string $text = "", string $levelname = "world"){
    $pk = $this->makeAddEntityPacket($pos, $title, $text);
    $this->crftp[$levelname][$pk->entityUniqueId] = $pk;
    return $pk;
  }

  /**
   * 可変動の浮き文字を生成します
   *
   * @param Vector3 $pos
   * @param string $title
   * @param string $text = ""
   * @param string $levelname = "world"
   * @param string $ownername = ""
   * ------------------------
   * @return AddEntityPacket $pk
   */
  public function makeFtp(Vector3 $pos, string $title, string $text = "", string $levelname = "world", string $ownername = ""){
    $pk = $this->makeAddEntityPacket($pos, $title, $text);
    $pk->world = $levelname;
    $pk->owner = strtolower($ownername);
    $this->ftp[$levelname][$pk->entityUniqueId] = $pk;
    return $pk;
  }

  /**
   * 消すことのできない浮き文字を追加します
   *
   * @param Object $player
   * @param Vector3 $pos
   * @param string $title
   * @param string $text
   * -------------------
   * @return AddEntityPacket $pk or bool(false)
   */
  public function addCrftp($player, Vector3 $pos, string $title, string $text = ""){
    $level = $player->getLevel();
    $levelname = $level->getName();
    $pk = $this->makeAddEntityPacket($pos, $title, $text);
    $this->crftp[$levelname][$pk->entityUniqueId] = $pk;
    $key = count($this->main->crftps);
    if ($this->main->crftps_file->exists(--$key) === false) {
      $this->main->crftps_file->set(--$key, [
        "WORLD" => $levelName,
        "Xvec" => $pk->x,
        "Yvec" => $pk->y,
        "Zvec" => $pk->z,
        "TITLE" => $title,
        "TEXT" => $text,
      ]);
      $this->crftps_file->save();

      $players = $level->getPlayers();
      foreach ($players as $pl) {
        $pl->dataPacket($pk);
      }
      return $pk;
    }else {
      $this->removeFtp($player, $pk->entityUniqueId);
      return false;
    }
  }

  /**
   * 浮き文字を追加します
   *
   * @param Object $player
   * @param Vector3 $pos
   * @param string $title
   * @param string $text
   * ------------------------
   * @return AddEntityPacket or bool(false)
   */
  public function addFtp($player, Vector3 $pos, string $title, string $text = ""){
    $level = $player->getLevel();
    $levelName = $level->getName();
    $pk = $this->makeAddEntityPacket($pos, $title, $text);
    $pk->world = $levelName;
    $pk->owner = strtolower($player->getName());
    $this->ftp[$levelName][$pk->entityUniqueId] = $pk;//オリジナルを保存
    $key = "{$levelName}{$pk->z}{$pk->x}{$pk->y}";
    if ($this->main->ftps_file->exists($key) === false) {
      $this->main->ftps_file->set($key, [
        "WORLD" => $levelName,
        "Xvec" => $pk->x,
        "Yvec" => $pk->y,
        "Zvec" => $pk->z,
        "TITLE" => $title,
        "TEXT" => $text,
        "OWNER" => $pk->owner
      ]);
      $this->main->ftps_file->save();

      $players = $level->getPlayers();
      foreach ($players as $pl) {
        $n = $pl->getName();
        if ($n === $player->getName() or $pl->isOp()) {
          $pks = clone $pk;//送信用パケット複製
          $pks->metadata[4][1] = "[$pk->entityUniqueId] ".$pks->metadata[4][1];
          $pl->dataPacket($pks);
        }else {
          $pl->dataPacket($pk);
        }
      }
      return $pk;
    }else {
      $this->removeFtp($player, $pk->entityUniqueId);
      return false;
    }
  }

  /**
   * 指定IDの浮き文字を削除します (ftps.json)
   *
   * @param Object $player
   * @param int $euid
   * ------------------
   * @return bool
   */
  public function removeFtp($player, int $euid): bool{
    $level = $player->getLevel();
    $levelName = $level->getName();
    //
    $pk = $this->getPacketModel("remove");
    $pk->entityUniqueId = $euid;
    $epk = $this->getFtp($levelName, $euid);
    if ($epk === false) {
      return false;
    }else {
      $key = "{$levelName}{$epk->z}{$epk->x}{$epk->y}";
      if ($this->main->ftps_file->exists($key) !== false) {
        $this->main->ftps_file->remove($key);
        $this->main->ftps_file->save();
      }
      unset($this->ftp[$levelName][$euid]);
      $players = $level->getPlayers();//Levelにいる人を取得
      foreach ($players as $pl) {
        $pl->dataPacket($pk);
      }
      return true;
    }
  }

  /**
   * 全ての浮き文字を削除します (ftps.json)
   *
   * @param Player $player
   * ---------------------------
   * @return int or bool(false)
   */
  public function removeFtps($player){
    $level = $player->getLevel();
    $levelName = $level->getName();
    //
    $pk = $this->getPacketModel("remove");
    $ftps = $this->getFtps();
    if ($ftps === false) {
      return false;
    }else {
      $reuids = [];
      foreach ($ftps as $levn => $euids) {
        foreach ($euids as $euid => $pk) {
          $reuids[] = $euid;
          $key = "{$levn}{$pk->z}{$pk->x}{$pk->y}";
          $this->main->ftps_file->remove($key);
          $this->main->ftps_file->save();
        }
      }
      $pls = $this->main->getServer()->getOnlinePlayers();
      if (count($pls) !== 0) {
        foreach ($pls as $pl) {
          $this->rFeTmP($pl, $reuids);
        }
      }
      $this->ftp = [];
      return count($reuids);
    }
  }

  /**
   * 指定ユーザーの浮き文字をすべて削除します
   *
   * @param string $user
   * ---------------------
   * @return int or bool
   */
  public function removeUserFtps(string $user){
    $name = strtolower($user);
    $ftps = $this->getFtps();
    if ($ftps === false) {
      return false;
    }else {
      $reuids = [];
      foreach ($ftps as $levn => $euids) {
        foreach ($euids as $euid => $pk) {
          if ($pk->owner === $name) {
            $reuids[] = $pk->entityUniqueId;
            $key = "{$levn}{$pk->z}{$pk->x}{$pk->y}";
            $this->main->ftps_file->remove($key);
            $this->main->ftps_file->save();
            unset($this->ftp[$levn][$euid]);
          }
        }
      }
      $pls = $this->main->getServer()->getOnlinePlayers();
      if (count($pls) !== 0) {
        foreach ($pls as $pl) {
          $this->rFeTmP($pl, $reuids);
        }
      }
      return count($reuids);
    }
  }

  /**
   * 指定IDの浮き文字のタイトルを更新します(ftps.jsonのみ)
   *
   * @param Object $player
   * @param int $euid
   * @param string $new_title
   * ------------------------
   * @return bool
   */
  public function updateTitle($player, int $euid, string $new_title): bool{
    if (isset($this->ftp)) {
      $name = $player->getName();
      $levelName = $player->getLevel()->getName();
      $pk = $this->getFtp($levelName, $euid);
      if ($pk === false) {
        return false;
      }else {
        if ($player->isOP() || $pk->owner === $name) {
          $texts = explode("\n", $pk->metadata[4][1]);
          $texts[0] = "{$new_title}";
          $pk->metadata[4][1] = implode("\n", $texts);
          //
          $players = $this->main->getServer()->getOnlinePlayers();
          if (count($players) !== 0) {
            foreach ($players as $pl) {
              if ($pl->getLevel()->getName() === $pk->world) {
                $n = $pl->getName();
                if ($pl->isOp() || $pk->owner === $n) {
                  $pks = clone $pk;//送信用パケット複製
                  $pks->metadata[4][1] = "[$pk->entityUniqueId] ".$pks->metadata[4][1];
                  $pl->dataPacket($pks);
                }else {
                  $pl->dataPacket($pk);
                }
              }
            }
          }
          array_shift($texts);
          $text = implode("\n", $texts);
          $key = "{$pk->world}{$pk->z}{$pk->x}{$pk->y}";
          if ($this->main->ftps_file->exists($key)) {
            $this->main->ftps_file->set($key, [
              "WORLD" => $pk->world,
              "Xvec" => $pk->x,
              "Yvec" => $pk->y,
              "Zvec" => $pk->z,
              "TITLE" => $new_title,
              "TEXT" => $text,
              "OWNER" => $pk->owner
            ]);
            $this->main->ftps_file->save();
            return true;
          }else {
            return false;
          }
        }
      }
    }
  }

  /**
   * 指定IDの浮き文字のテキストを更新します(ftps.jsonのみ)
   *
   * @param Object $player
   * @param int $euid
   * @param array $new_text
   * ------------------------
   * @return bool
   */
  public function updateText($player, int $euid, array $new_text): bool{
    if (isset($this->ftp)) {
      $name = $player->getName();
      $levelName = $player->getLevel()->getName();
      $pk = $this->getFtp($levelName, $euid);
      if ($pk === false) {
        return false;
      }else {
        if ($player->isOP() || $pk->owner === $name) {
          $texts = explode("\n", $pk->metadata[4][1]);
          $title = $texts[0];
          $new_text = implode(" ", $new_text);
          $text = str_replace("#", "\n", $new_text);
          $pk->metadata[4][1] = "{$title}\n{$text}";
          //
          $players = $this->main->getServer()->getOnlinePlayers();
          if (count($players) !== 0) {
            foreach ($players as $pl) {
              if ($pl->getLevel()->getName() === $pk->world) {
                $n = $pl->getName();
                if ($pl->isOp() || $pk->owner === $n) {
                  $pks = clone $pk;//送信用パケット複製
                  $pks->metadata[4][1] = "[$pk->entityUniqueId] ".$pks->metadata[4][1];
                  $pl->dataPacket($pks);
                }else {
                  $pl->dataPacket($pk);
                }
              }
            }
          }
          array_shift($texts);
          $key = "{$pk->world}{$pk->z}{$pk->x}{$pk->y}";
          if ($this->main->ftps_file->exists($key)) {
            $this->main->ftps_file->set($key, [
              "WORLD" => $pk->world,
              "Xvec" => $pk->x,
              "Yvec" => $pk->y,
              "Zvec" => $pk->z,
              "TITLE" => $title,
              "TEXT" => $text,
              "OWNER" => $pk->owner
            ]);
            $this->main->ftps_file->save();

            return true;
          }else {
            return false;
          }
        }
      }
    }
  }

  /**
   * @param string $type add: AddEntityPacket, remove: RemoveEntityPacket
   * --------------
   * @return packetObject | bool(false)
   */
  public function getPacketModel(string $type){
    switch ($type) {
      case 'add':
        return clone $this->main->AddEntityPacket;
      break;

      case 'remove':
        return clone $this->main->RemoveEntityPacket;
      break;

      default:
        return false;
      break;
    }
  }

  public function makeAddEntityPacket(Vector3 $pos, string $title, string $text, int $id = 0){
    $pk = $this->getPacketModel("add");
    $pk->entityUniqueId = ($id === 0) ? Entity::$entityCount++ : $id;
    $pk->entityRuntimeId = $pk->entityUniqueId;// ...huh?
    $pk->eid = $pk->entityRuntimeId;// for old packetObject
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = (float)sprintf('%0.1f', $pos->x);
    $pk->y = (float)sprintf('%0.1f', $pos->y);
    $pk->z = (float)sprintf('%0.1f', $pos->z);
    $flags = 0;
    $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title . ($text !== "" ? "\n" . $text : "")],
    ];
    return $pk;
  }

  private function rFeTmP($pl, array $reuids){
    foreach ($reuids as $euid) {
      $pk = $this->getPacketModel("remove");
      $pk->entityUniqueId = $euid;
      $pl->dataPacket($pk);
    }
  }
  /****************************************************************************/
  ### 拡張ファイル関連 #################
  /**
   * @param TexterExtension
   */
  public function registerEvents($extension){
    $this->main->getServer()->getPluginManager()->registerEvents($extension, $this->main);
  }

  /**
   * @param string $class (extension`s CommandPath)
   */
  public function registerCommand(string $class){
    $command = new $class($this->main);
    $this->main->getServer()->getCommandMap()->register("Texter", $command);
  }

  /**
   * @return Extensions[] or bool(false)
   */
  public function getExtensions(){
    $return = (count($this->main->extensions) !== 0) ? $this->main->extensions : false;
    return $return;
  }

  /**
   * @param string $extensionName
   * -------------------------------------
   * @return Extension or bool(false)
   */
  public function getExtension(string $extensionName){
    $return = isset($this->main->extensions[$extensionName]) ? $this->main->extensions[$extensionName] : false;
    return $return;
  }

  /**
   * @param string $extensionName
   * @param string $functionName
   * -------------------------------------
   * @return extensionTask $task
   */
  public function getExtensionTask(string $extensionName, string $functionName){
    $task = new extensionTask($this->main, $extensionName, $functionName);
    return $task;
  }

  /**
   * @param extensionTask $task
   * @param string $taskType(Delayed/Repeating)
   * @param int $period
   */
  public function execExtensionTask(extensionTask $task, string $taskType = "Delayed", int $period = 20){
    $taskType = "schedule{$taskType}Task";
    $this->main->getServer()->getScheduler()->{$taskType}($task, $period);
  }
}
