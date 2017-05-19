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

#Player
use pocketmine\Player;

#Entity
use pocketmine\entity\Entity;
use pocketmine\entity\Item as ItemEntity;

#Texter
use Texter\task\extensionTask;

/**
 * APIs
 */
class TexterAPI{

  /* @var Crftp[$levelName][] = $pk */
  public $crftp = [],
  /* @var Ftp[$levelName][] = $pk */
         $ftp = [];
  /* @var Extensions */
  private $extensions = [];
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
   * @return string $lang (jpn or eng)
   */
  public function getLangage(): string{
    return $this->main->lang;
  }

  /**
   * get from langage file
   * @param string $key
   * ---------------------------
   * @return string $message
   */
  public function getMessage(string $key){
    return $this->main->messages->get($key);
  }

  /**
   * @return array $this->crftp[$levelName][$eid] | false
   */
  public function getCrftps(){
    $crftp = isset($this->crftp) ? $this->crftp : false;
    return $crftp;
  }

  /**
   * @param string $levelName
   * @param int $eid
   * -------------------------------
   * @return array $this->crftp[$levelName][$eid] | false
   */
  public function getCrftp(string $levelName, int $eid){
    $crftps = $this->getCrftps();
    if ($crftps === false) {
      return false;
    }else {
      $pk = (isset($crftps[$levelName][$eid])) ? $crftps[$levelName][$eid] : false;
      if ($pk === false) {
        return false;
      }else {
        return $pk;
      }
    }
  }

  /**
   * @return array $this->ftp[$levelName][$eid] | false
   */
  public function getFtps(){
    $ftp = isset($this->ftp) ? $this->ftp : false;
    return $ftp;
  }

  /**
   * @param string $levelName
   * @param int $eid
   * -------------------------------
   * @return array $this->ftp[$levelName][$eid] | false
   */
  public function getFtp(string $levelName, int $eid){
    $ftps = $this->getFtps();
    if ($ftps === false) {
      return false;
    }else {
      $pk = (isset($ftps[$levelName][$eid])) ? $ftps[$levelName][$eid] : false;
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
   * @param array $pos
   * @param string $title
   * @param string $text = ""
   * @param string $levelname = "world"
   * ------------------------
   * @return AddEntityPacket $pk
   */
  public function makeCrftp(array $pos, string $title, string $text = "", string $levelname = "world"){
    $pk = $this->getPacketModel("add");
    $pk->eid = Entity::$entityCount++;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = sprintf('%0.1f', $pos[0]);
    $pk->y = sprintf('%0.1f', $pos[1]);
    $pk->z = sprintf('%0.1f', $pos[2]);
    $flags = 0;
    $flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
    $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title . ($text !== "" ? "\n" . $text : "")],
    ];
    $this->crftp[$levelname][$pk->eid] = $pk;

    return $pk;
  }

  /**
   * 可変動の浮き文字を生成します
   *
   * @param array $pos
   * @param string $title
   * @param string $text = ""
   * @param string $levelname = "world"
   * @param string $ownername = ""
   * ------------------------
   * @return AddEntityPacket $pk
   */
  public function makeFtp(array $pos, string $title, string $text = "", string $levelname = "world", string $ownername = ""){
    $pk = $this->getPacketModel("add");
    $pk->eid = Entity::$entityCount++;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = sprintf('%0.1f', $pos[0]);
    $pk->y = sprintf('%0.1f', $pos[1]);
    $pk->z = sprintf('%0.1f', $pos[2]);
    $flags = 0;
    $flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
    $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
    $title = str_replace("#", "\n", $title);
    $text = str_replace("#", "\n", $text);
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title . ($text !== "" ? "\n" . $text : "")],
    ];
    $pk->world = $levelname;
    $pk->owner = strtolower($ownername);
    $this->ftp[$levelname][$pk->eid] = $pk;

    return $pk;
  }

  /**
   * 消すことのできない浮き文字を追加します
   *
   * @param Object $player
   * @param array $pos
   * @param string $title
   * @param string $text
   * -------------------
   * @return AddEntityPacket $pk or bool(false)
   */
  public function addCrftp($player, array $pos, string $title, string $text = ""){
    $level = $player->getLevel();
    $levelname = $level->getName();
    $pk = $this->getPacketModel("add");
    $pk->eid = Entity::$entityCount++;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = sprintf('%0.1f', $pos[0]);
    $pk->y = sprintf('%0.1f', $pos[1]);
    $pk->z = sprintf('%0.1f', $pos[2]);
    $flags = 0;
    $flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
    $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
    $title = str_replace("#", "\n", $title);
    $text = str_replace("#", "\n", $text);
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title . ($text !== "" ? "\n" . $text : "")],
    ];
    $this->crftp[$levelname][$pk->eid] = $pk;
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
      $this->removeFtp($player, $pk->eid);
      return false;
    }
  }

  /**
   * 浮き文字を追加します
   *
   * @param Object $player
   * @param array $pos
   * @param string $title
   * @param string $text
   * ------------------------
   * @return AddEntityPacket or bool(false)
   */
  public function addFtp($player, array $pos, string $title, string $text = ""){
    $level = $player->getLevel();
    $levelName = $level->getName();
    $pk = $this->getPacketModel("add");
    $pk->eid = Entity::$entityCount++;
    $pk->type = ItemEntity::NETWORK_ID;
    $pk->x = sprintf('%0.1f', $pos[0]);
    $pk->y = sprintf('%0.1f', $pos[1]);
    $pk->z = sprintf('%0.1f', $pos[2]);
    $flags = 0;
    $flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
    $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
    $title = str_replace("#", "\n", $title);
    $text = str_replace("#", "\n", $text);
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title . ($text !== "" ? "\n" . $text : "")],
    ];
    $pk->world = $levelName;
    $pk->owner = strtolower($player->getName());
    $this->ftp[$levelName][$pk->eid] = $pk;//オリジナルを保存
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
          $pks->metadata[4][1] = "[$pk->eid] ".$pks->metadata[4][1];
          $pl->dataPacket($pks);
        }else {
          $pl->dataPacket($pk);
        }
      }
      return $pk;
    }else {
      $this->removeFtp($player, $pk->eid);
      return false;
    }
  }

  /**
   * 指定IDの浮き文字を削除します (ftps.json)
   *
   * @param Object $player
   * @param int $eid
   * ------------------
   * @return bool
   */
  public function removeFtp($player, int $eid): bool{
    $level = $player->getLevel();
    $levelName = $level->getName();
    //
    $pk = $this->getPacketModel("remove");
    $pk->eid = $eid;
    $epk = $this->getFtp($levelName, $eid);
    if ($epk === false) {
      return false;
    }else {
      $key = "{$levelName}{$epk->z}{$epk->x}{$epk->y}";
      if ($this->main->ftps_file->exists($key) !== false) {
        $this->main->ftps_file->remove($key);
        $this->main->ftps_file->save();
        unset($this->ftp[$levelName][$eid]);
        $players = $level->getPlayers();//Levelにいる人を取得
        foreach ($players as $pl) {
          $pl->dataPacket($pk);
        }
      }else {
        unset($this->ftp[$levelName][$eid]);
        $players = $level->getPlayers();//Levelにいる人を取得
        foreach ($players as $pl) {
          $pl->dataPacket($pk);
        }
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
      $reids = [];
      foreach ($ftps as $levn => $eids) {
        foreach ($eids as $eid => $pk) {
          $reids[] = $eid;
          $key = "{$levn}{$pk->z}{$pk->x}{$pk->y}";
          $this->main->ftps_file->remove($key);
          $this->main->ftps_file->save();
        }
      }
      $pls = $this->main->getServer()->getOnlinePlayers();
      if (count($pls) !== 0) {
        foreach ($pls as $pl) {
          $this->rFeTmP($pl, $reids);
        }
      }
      $this->ftp = [];
      return count($reids);
    }
  }

  /**
   * 指定ユーザーの浮き文字をすべて削除します
   *
   * @param Object $player
   * @param string $user
   * ---------------------
   * @return int or bool
   */
  public function removeUserFtps($player, string $user){
    $name = strtolower($player->getName());
    $ftps = $this->getFtps();
    if ($ftps === false) {
      return false;
    }else {
      $reids = [];
      foreach ($ftps as $levn => $eids) {
        foreach ($eids as $eid => $pk) {
          if ($pk->owner === $name) {
            $reids[] = $pk->eid;
            $key = "{$levn}{$pk->z}{$pk->x}{$pk->y}";
            $this->main->ftps_file->remove($key);
            $this->main->ftps_file->save();
            unset($this->ftp[$levn][$eid]);
          }
        }
      }
      $pls = $this->main->getServer()->getOnlinePlayers();
      if (count($pls) !== 0) {
        foreach ($pls as $pl) {
          $this->rFeTmP($pl, $reids);
        }
      }
      return count($reids);
    }
  }

  /**
   * 指定IDの浮き文字のタイトルを更新します(ftps.jsonのみ)
   *
   * @param Object $player
   * @param int $eid
   * @param string $new_title
   * ------------------------
   * @return bool
   */
  public function updateTitle($player, int $eid, string $new_title): bool{
    if (isset($this->ftp)) {
      $name = $player->getName();
      $levelName = $player->getLevel()->getName();
      $pk = $this->getFtp($levelName, $eid);
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
                  $pks->metadata[4][1] = "[$pk->eid] ".$pks->metadata[4][1];
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
   * @param int $eid
   * @param array $new_text
   * ------------------------
   * @return bool
   */
  public function updateText($player, int $eid, array $new_text): bool{
    if (isset($this->ftp)) {
      $name = $player->getName();
      $levelName = $player->getLevel()->getName();
      $pk = $this->getFtp($levelName, $eid);
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
                  $pks->metadata[4][1] = "[$pk->eid] ".$pks->metadata[4][1];
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

/******************************************************************************/
### 拡張ファイル関連 #################
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
    $return = isset($this->main->extensions) ? $this->main->extensions : false;
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

  /****************************************************************************/
  private function rFeTmP($pl, array $reids){
    foreach ($reids as $eid) {
      $pk = $this->getPacketModel("remove");
      $pk->eid = $eid;
      $pl->dataPacket($pk);
    }
  }
  /****************************************************************************/
}
