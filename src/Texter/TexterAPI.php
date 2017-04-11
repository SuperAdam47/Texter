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

  /* @var Extensions */
  private $extensions = [];

  public function __construct(Main $main){
    $this->main = $main;
  }

/******************************************************************************/
### get/set(情報取得/変更) 関連 ###########
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
   * @return array $this->main->crftp[$levelName][]
   */
  public function getCrftps(string $levelName): array{
    $crftp = isset($this->main->crftp[$levelName]) ? $this->main->crftp[$levelName] : [];
    return $crftp;
  }

  /**
   * @return $this->main->ftp[$levelName][]
   */
  public function getFtps(string $levelName): array{
    $ftp = isset($this->main->ftp[$levelName]) ? $this->main->ftp[$levelName] : [];
    return $ftp;
  }

/******************************************************************************/
### FloatingTextPerticle 関連 ############
  /**
   * 消すことのできない浮き文字を追加します
   *
   * @param Player $player
   * @param array $pos
   * @param string $title
   * @param string $text
   * -------------------
   * @return int $eid
   */
  public function addCrftp(Player $player, array $pos, string $title, string $text) :int{
    $levelname = $p->getLevel()->getName();
    $pk = clone $this->getPacketModel()[0];
    $pk->eid = Entity::$entityCount++;
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
    $this->main->crftp[$levelname][] = $pk;
    $player->dataPacket($pk);
    return $pk->eid;
  }

  /**
   * 浮き文字を追加します
   *
   * @param Player $player
   * @param array $pos
   * @param string $title
   * @param string $text
   * @param string $ownername
   * ------------------------
   * @return int $eid
   */
  public function addFtp(Player $player, array $pos, string $title, string $text, string $ownername) :int{
    $level = $player->getLevel();
    $levelname = $level->getName();
    $pk = clone $this->getPacketModel()[0];
    $pk->eid = Entity::$entityCount++;
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
    $this->main->ftp[$levelname][] = $pk;//オリジナルを保存

    $players = $level->getPlayers();
    foreach ($players as $pl) {
      $n = $pl->getName();
      if ($n === $ownername or $pl->isOp()) {
        $pks = clone $pk;//送信用パケット複製
        $pks->metadata[4][1] = "[$pk->eid] ".$pks->metadata[4][1];
        $pl->dataPacket($pks);
      }else {
        $pl->dataPacket($pk);
      }
    }
    return $pk->eid;
  }

  /**
   * 指定IDの浮き文字を削除します
   *
   * @param Player $player
   * @param int $eid
   */
  public function removeFtp(Player $player, int $eid){
    $pk = clone $this->getPacketModel()[1];
    $pk->eid = $eid;
    $level = $player->getLevel();
    $players = $level->getPlayers();//Levelにいる人を取得
    foreach ($players as $pl) {
      $pl->dataPacket($pk);
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
  public function updateTitle($player, int $eid, string $new_title) :bool{
    if (isset($this->main->ftp)) {
      foreach ($this->main->ftp as $levn => $k) {
        foreach ($k as $pk) {
          if ((int)$pk->eid === $eid) {
            if ($player->isOp() ||
                $pk->owner === $player->getName()) {
              //removePk
              $rpk = clone $this->getPacketModel()[1];
              $rpk->eid = $eid;
              //sendPk
              $texts = explode("\n", $pk->metadata[4][1]);
              $texts[0] = $new_title;
              $pk->metadata[4][1] = implode("\n", $texts);
              //
              $players = $this->main->getServer()->getOnlinePlayers();
              if (count($players) !== 0) {
                foreach ($players as $pl) {
                  if ($pl->getLevel()->getName() === $pk->world) {
                    $pl->dataPacket($rpk);
                    $n = $pl->getName();
                    if ($n === $pk->owner or $pl->isOp()) {
                      $pks = clone $pk;//送信用パケット複製
                      $pks->metadata[4][1] = "[$pk->eid] ".$pks->metadata[4][1];
                      $pl->dataPacket($pks);
                    }else {
                      $pl->dataPacket($pk);
                    }
                  }
                }
              }
              $text = array_shift($texts);
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
                $player->sendMessage("§b[Texter] ".$this->main->messages->get("command.txt.updated"));
                return true;
              }else {
                $player->sendMessage("§b[Texter] §c".$this->main->messages->get("command.txt.exists?.ftp"));
                return false;
              }
            }
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
  public function updateText($player, int $eid, array $new_text) :bool{
    if (isset($this->main->ftp)) {
      foreach ($this->main->ftp as $levn => $k) {
        foreach ($k as $pk) {
          if ((int)$pk->eid === $eid) {
            if ($player->isOp() ||
                $pk->owner === $player->getName()) {
              //removePk
              $rpk = clone $this->getPacketModel()[1];
              $rpk->eid = $eid;
              //sendPk
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
                    $pl->dataPacket($rpk);
                    $n = $pl->getName();
                    if ($n === $pk->owner or $pl->isOp()) {
                      $pks = clone $pk;//送信用パケット複製
                      $pks->metadata[4][1] = "[$pk->eid] ".$pks->metadata[4][1];
                      $pl->dataPacket($pks);
                    }else {
                      $pl->dataPacket($pk);
                    }
                  }
                }
              }
              $key = "{$pk->world}{$pk->z}{$pk->x}{$pk->y}";
              if ($this->main->ftps_file->exists($key)) {
                $this->main->ftps_file->set($key, [
                  "WORLD" => $pk->world,
                  "Xvec" => $pk->x,
                  "Yvec" => $pk->y,
                  "Zvec" => $pk->z,
                  "TITLE" => $title,
                  "TEXT" => $new_text,
                  "OWNER" => $pk->owner
                ]);
                $this->main->ftps_file->save();
                $player->sendMessage("§b[Texter] ".$this->main->messages->get("command.txt.updated"));
                return true;
              }else {
                $player->sendMessage("§b[Texter] §c".$this->main->messages->get("command.txt.exists?.ftp"));
                return false;
              }
            }
          }
        }
      }
    }
  }

/******************************************************************************/
### 拡張ファイル関連 #################
  /**
   * @return array
   */
  public function getPacketModel(): array{
    return [$this->main->AddEntityPacket, $this->main->RemoveEntityPacket];
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
   * @return Extensions[]
   */
  public function getExtensions(){
    $return = isset($this->main->extensions) ? $this->main->extensions : false;
    return $return;
  }

  /**
   * @param string $extensionName
   * -------------------------------------
   * @return Extension
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
