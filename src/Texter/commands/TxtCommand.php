<?php
namespace Texter\commands;

#pocketmine
use pocketmine\Player;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

#texter
use Texter\Main;

/**
 * @var TxtCommand
 */
class TxtCommand extends Command{

  public function __construct(Main $main){
    $this->main = $main;
    $this->api = $main->getAPI();
    parent::__construct("txt", $this->api->getMessage("command.description"), "/txt add | remove | update | help");//登録
    //
    $this->setPermission("texter.command.txt");
  }

  public function execute(CommandSender $s, $cmd, array $args){
    if (!$this->main->isEnabled()) return false;
    if (!$this->testPermission($s)) return false;
    if ($s instanceof Player) {
      if (isset($args[0])) {
        switch ($args[0]) {
          case 'add':
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
            }
            $pos = [sprintf('%0.1f', $s->x), sprintf('%0.1f', $s->y+1), $z = sprintf('%0.1f', $s->z)];
            $eid = $this->main->api->addFtp($s, $pos, $title, $text, $n);
            //
            $key = $levn.$pos[2].$pos[0].$pos[1];
            if (!$this->main->ftps_file->exists($key)) {
              $this->main->ftps_file->set($key, [
                "WORLD" => $levn,
                "Xvec" => $pos[0],
                "Yvec" => $pos[1],
                "Zvec" => $pos[2],
                "TITLE" => $title,
                "TEXT" => $text,
                "OWNER" => $n
              ]);
              $this->main->ftps_file->save();
              $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.set"));
              return true;
            }else{
              $this->api->removeFtp($s, $eid);
              $s->sendMessage("§b[Texter] §e".$this->api->getMessage("command.txt.exists"));
              return true;
            }
          }else {
            $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.add"));
            return false;
          }
          break;

          case 'remove':
          if (isset($args[1])) {
            $levn = $s->getLevel()->getName();
            foreach ($this->main->ftp as $k => $ftps) {
              if ($k == $levn) {
                foreach ($ftps as $fk => $ftp) {
                  if ($ftp->eid == $args[1]) {
                    if ($ftp->owner === $s->getName() || $s->isOp()) {
                      $ckey = $levn.$ftp->z.$ftp->x.$ftp->y;
                      if ($this->main->ftps_file->exists($ckey)) {
                        $this->main->ftps_file->remove($ckey);
                        $this->main->ftps_file->save();
                      }
                      unset($this->main->ftp[$k][$fk]);
                      @$this->main->ftp[$k] = array_values($this->main->ftp[$k]);
                      $this->api->removeFtp($s, $ftp->eid);
                      $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.remove"));
                      return true;
                    }else {
                      $s->sendMessage("§b[Texter] §c".$this->api->getMessage("command.txt.permission.remove"));
                      return false;
                    }
                  }
                }
              }
            }
          }else {
            $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.remove"));
          }
          break;

          case 'update':
          if (isset($args[1]) &&//title or text
              isset($args[2]) &&//id
              isset($args[3])) {//文字列
            switch (strtolower($args[1])) {
              case 'title':
                if (!is_numeric($args[2])) {
                  $s->sendMessage("§b[Texter] §7".$this->api->getMessage("command.txt.usage.update"));
                }else {
                  $this->api->updateTitle($s, $args[2], $args[3]);
                }
              break;

              case 'text':
              if (!is_numeric($args[2])) {
                $s->sendMessage("§b[Texter] §7".$this->api->getMessage("command.txt.usage.update"));
              }else {
                $this->api->updateText($s, $args[2], array_slice($args, 3));
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
            $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.line1")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.add")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.remove")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.update")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.indent")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.line2")."\n");
          break;

          default:
            $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.line1")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.add")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.remove")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.update")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.indent")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.line2")."\n");
          break;
        }
      }else {
        $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.line1")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.add")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.remove")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.update")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.indent")."\n§b[Texter] ".$this->api->getMessage("command.txt.usage.line2")."\n");
      }
    }else {
      $this->plugin->getLogger()->info("§c[Texter] ".$this->api->getMessage("command.txt.console"));
    }
  }
}
