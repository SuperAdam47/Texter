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

  public function __construct(Main $plugin){
    parent::__construct("txt", $plugin->messages->get("command.description"), "/txt add | remove");//登録
    //
    $this->setPermission("texter.command.txt");
    $this->plugin = $plugin;
  }

  public function execute(CommandSender $s, $cmd, array $args){
    if (!$this->plugin->isEnabled()) return false;
    if (!$this->testPermission($s)) return false;
    if ($s instanceof Player) {
      if (isset($args[0])) {
        switch ($args[0]) {
          case 'add':
          if (isset($args[1])) {
            $lev = $s->getLevel();
            $levn = $lev->getName();
            $n = $s->getName();
            $args[1] = str_replace("#", "\n", $args[1]);
            if (isset($args[2])) {
              $args[2] = str_replace("#", "\n", $args[2]);
            }else {
              $args[2] = "";
            }
            $pos = [sprintf('%0.1f', $s->x), sprintf('%0.1f', $s->y+1), $z = sprintf('%0.1f', $s->z)];
            $eid = $this->plugin->addFtp($s, $pos, $args[1], $args[2], $n);
            //
            $key = $levn.$pos[2].$pos[0].$pos[1];
            if (!$this->plugin->ftps_file->exists($key)) {
              $this->plugin->ftps_file->set($key, [
                "WORLD" => $levn,
                "Xvec" => $pos[0],
                "Yvec" => $pos[1],
                "Zvec" => $pos[2],
                "TITLE" => $args[1],
                "TEXT" => $args[2],
                "OWNER" => $n
              ]);
              $this->plugin->ftps_file->save();
              $s->sendMessage("§b[Texter] ".$this->plugin->messages->get("command.txt.set"));
            }else{
              $this->plugin->removeFtp($s, $eid);
              $s->sendMessage("§e[Texter] ".$this->plugin->messages->get("command.txt.exists"));
            }
          }else {
            $s->sendMessage("§b[Texter] ".$this->plugin->messages->get("command.txt.usage"));
          }
          break;

          case 'remove':
          if (isset($args[1])) {
            $levn = $s->getLevel()->getName();
            foreach ($this->plugin->ftp as $k => $ftps) {
              if ($k == $levn) {
                foreach ($ftps as $fk => $ftp) {
                  if ($ftp->eid == $args[1]) {
                    if ($ftp->owner === $s->getName() or $s->isOp()) {
                      $ckey = $levn.$ftp->z.$ftp->x.$ftp->y;
                      if ($this->plugin->ftps_file->exists($ckey)) {
                        $this->plugin->ftps_file->remove($ckey);
                        $this->plugin->ftps_file->save();
                      }
                      unset($this->plugin->ftp[$k][$fk]);
                      @$this->plugin->ftp[$k] = array_values($this->plugin->ftp[$k]);
                      $this->plugin->removeFtp($s, $ftp->eid);
                      $s->sendMessage("§b[Texter] ".$this->plugin->messages->get("command.txt.remove"));
                    }else {
                      $s->sendMessage("§b[Texter] §c".$this->plugin->messages->get("command.txt.permission"));
                    }
                  }
                }
              }
            }
          }else {
            $s->sendMessage("§b[Texter] §7".$this->plugin->messages->get("command.txt.id?"));
          }
          break;

          default:
          $s->sendMessage("§b[Texter] ".$this->plugin->messages->get("command.txt.usage"));
          break;
        }
      }else {
        $s->sendMessage("§b[Texter] ".$this->plugin->messages->get("command.txt.usage"));
      }
    }else {
      $this->plugin->getLogger()->info("§c[Texter] ".$this->plugin->messages->get("command.txt.console"));
    }
  }
}
