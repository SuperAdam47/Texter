<?php

namespace Texter\commands;

# pocketmine
use pocketmine\Player;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

# Math
use pocketmine\math\Vector3;

# texter
use Texter\Main;
use Texter\language\Lang;

/**
 * @var TxtCommand
 */
class TxtCommand extends Command{

  public function __construct(Main $main){
    $this->main = $main;
    $this->api = $main->getAPI();
    $this->lang = Lang::getInstance();
    parent::__construct("txt", $this->lang->transrateString("command.description.txt"), "/txt <add | remove | update | help>");//登録
    //
    $this->setPermission("texter.command.txt");
  }

  public function execute(CommandSender $s, string $cmd, array $args){
    if (!$this->main->isEnabled()) return false;
    if (!$this->testPermission($s)) return false;
    if ($s instanceof Player) {
      if (isset($args[0])) {
        strtolower($args[0]);
        switch ($args[0]) {
          case 'add':
          case 'a':
          if (isset($args[1])) {
            $lev = $s->getLevel();
            $levn = $lev->getName();
            $n = $s->getName();
            $title = str_replace("#", "\n", $args[1]);
            if (isset($args[2])) {
              $texts = array_slice($args, 2);
              $text = str_replace("#", "\n", implode(" ", $texts));
            }else {
              $text = "";
            }// TODO: 要調整(Y座標)
            $pos = new Vector3(sprintf('%0.1f', $s->x), sprintf('%0.1f', $s->y+1), $z = sprintf('%0.1f', $s->z));
            $result = $this->api->addFtp($s, $pos, $title, $text);
            if ($result !== false){
              $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.set"));
            }else{
              $s->sendMessage("§b[Texter] §e".$this->api->getMessage("command.txt.exists"));
            }
          }else {
            $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.add"));
          }
          break;

          case 'remove':
          case 'r':
          if (isset($args[1])) {
            $levn = $s->getLevel()->getName();
            $ftp = ($this->api->getFtp($levn, $args[1])) ? $this->api->getFtp($levn, $args[1]) : false;
            if (!$ftp) {
              $s->sendMessage("§b[Texter] §c".$this->api->getMessage("txt.doesn`t.exists"));
            }else {
              if ($s->isOp() || $ftp->owner === $s->getName()) {
                $result = $this->api->removeFtp($s, $ftp->entityUniqueId);
                if (!$result) {
                  $s->sendMessage("§b[Texter] ".$this->api->getMessage("txt.doesn`t.exists"));
                }else {
                  $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.remove"));
                }
              }else {
                $s->sendMessage("§b[Texter] §c".$this->api->getMessage("command.txt.permission.remove"));
              }
            }
          }else {
            $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.remove"));
          }
          break;

          case 'update':
          case 'u':
          if (isset($args[1]) &&//title or text
              isset($args[2]) &&//id
              isset($args[3])) {//文字列
            switch (strtolower($args[1])) {
              case 'title':
                if (!is_numeric($args[2])) {
                  $s->sendMessage("§b[Texter] §7".$this->api->getMessage("command.txt.usage.update"));
                }else {
                  $return = $this->api->updateTitle($s, $args[2], $args[3]);
                  if (!$return) {
                    $s->sendMessage("§b[Texter] ".$this->api->getMessage("txt.doesn`t.exists"));
                  }else {
                    $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.updated"));
                  }
                }
              break;

              case 'text':
              if (!is_numeric($args[2])) {
                $s->sendMessage("§b[Texter] §7".$this->api->getMessage("command.txt.usage.update"));
              }else {
                $return = $this->api->updateText($s, $args[2], array_slice($args, 3));
                if (!$return) {
                  $s->sendMessage("§b[Texter] ".$this->api->getMessage("txt.doesn`t.exists"));
                }else {
                  $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.updated"));
                }
              }
              break;

              default:
                $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.update"));
              break;
            }
          }else {
            $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.update"));
          }
          break;

          case 'help':
          case 'h':
          case '?':
            $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.line1")."\n§b".$this->api->getMessage("command.txt.usage.add")."\n§b".$this->api->getMessage("command.txt.usage.remove")."\n§b".$this->api->getMessage("command.txt.usage.update")."\n§b".$this->api->getMessage("command.txt.usage.indent"));
          break;

          default:
            $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.line1")."\n§b".$this->api->getMessage("command.txt.usage.add")."\n§b".$this->api->getMessage("command.txt.usage.remove")."\n§b".$this->api->getMessage("command.txt.usage.update")."\n§b".$this->api->getMessage("command.txt.usage.indent"));
          break;
        }
        return true;
      }else {
        $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.line1")."\n§b".$this->api->getMessage("command.txt.usage.add")."\n§b".$this->api->getMessage("command.txt.usage.remove")."\n§b".$this->api->getMessage("command.txt.usage.update")."\n§b".$this->api->getMessage("command.txt.usage.indent"));
      }
    }else {
      $this->main->getLogger()->info("§c".$this->api->getMessage("command.console"));
    }
  }
}
