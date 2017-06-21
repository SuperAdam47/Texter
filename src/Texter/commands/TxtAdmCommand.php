<?php

namespace Texter\commands;

# pocketmine
use pocketmine\Player;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

# texter
use Texter\Main;

/**
 * @var TxtAdmCommand
 */
class TxtAdmCommand extends Command{

  public function __construct(Main $main){
    $this->main = $main;
    $this->api = $main->getAPI();
    parent::__construct("txtadm", $this->api->getMessage("command.description.txtadm"), "/txtadm <ar | ur | info>", ["tadm"]);//登録
    //
    $this->setPermission("texter.command.txtadm");
  }

  public function execute(CommandSender $s, $cmd, array $args){
    if (!$this->main->isEnabled()) return false;
    if (!$this->testPermission($s)) return false;
    if ($s instanceof Player) {
      if (isset($args[0])) {
        strtolower($args[0]);
        switch ($args[0]) {
          case 'allremove':
          case 'ar':
            $result = $this->api->removeFtps($s);
            if ($result === false) {
              $s->sendMessage("§b[Texter] §c".$this->api->getMessage("command.txtadm.notexists"));
            }else {
              $s->sendMessage("§b[Texter] §a".str_replace("{count}", $result, $this->api->getMessage("command.txtadm.allremove")));
            }
          break;

          case 'userremove':
          case 'ur':
            if (isset($args[1])) {
              $return = $this->api->removeUserFtps($args[1]);
              if (!$return || $return === 0) {
                $s->sendMessage("§b[Texter] §c".$this->api->getMessage("txt.user.doesn`t.exists"));
              }else {
                $s->sendMessage("§b[Texter] §a".str_replace("{user}", $args[1], $this->api->getMessage("command.txtadm.userremove")));
              }
            }else {
              $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txtadm.usage.ur"));
            }
          break;

          case 'info':
            $result = $this->count();
            $s->sendMessage("§b[Texter] \n§bcrftps: §6".$result[0]."\n§bftps: §6".$result[1]."\n§7".Main::NAME." ".Main::VERSION." - ".Main::CODENAME);
          break;

          case 'help':
          case 'h':
            $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.line1")."\n§b".$this->api->getMessage("command.txtadm.usage.allremove")."\n§b".$this->api->getMessage("command.txtadm.usage.userremove")."\n§b".$this->api->getMessage("command.txtadm.usage.info"));
          break;

          case 'test':
            if ($this->main->devmode === true) {
              /*for ($i=1; $i<51; $i++) {
                $this->api->addFtp($s, [$s->x+$i, $s->y, $s->z], "test", "§$i");
              }*/
              var_dump($this->api->getCrftps());
            }
          break;

          default:
            $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.line1")."\n§b".$this->api->getMessage("command.txtadm.usage.allremove")."\n§b".$this->api->getMessage("command.txtadm.usage.userremove")."\n§b".$this->api->getMessage("command.txtadm.usage.info"));
          break;
        }
      }else {
        $s->sendMessage("§b[Texter] ".$this->api->getMessage("command.txt.usage.line1")."\n§b".$this->api->getMessage("command.txtadm.usage.allremove")."\n§b".$this->api->getMessage("command.txtadm.usage.userremove")."\n§b".$this->api->getMessage("command.txtadm.usage.info"));
      }
      return true;
    }else {
      if (isset($args[0])) {
        switch (strtolower($args[0])) {
          case 'info':
            $result = $this->count();
            $this->main->getLogger()->info("§bcrftps: §6".$result[0]." §b| ftps: §6".$result[1]." §b| version: §7".Main::NAME." ".Main::VERSION." - ".Main::CODENAME);
          break;

          case 'help':
          case 'h':
            $this->main->getLogger()->info("§b".$this->api->getMessage("command.txt.usage.line1")."\n§b".$this->api->getMessage("command.txtadm.usage.info"));
          break;

          default:
            $this->main->getLogger()->info("§c".$this->api->getMessage("command.console"));
          break;
        }
      }else {
        $this->main->getLogger()->info("§b".$this->api->getMessage("command.txt.usage.line1")."\n§b".$this->api->getMessage("command.txtadm.usage.info"));
      }
    }
  }

  private function count(){
    ####crftp####
    $cc = 0;
    $crftps = $this->api->getCrftps();
    if ($crftps !== false) {
      foreach ($crftps as $euids) {
        foreach ($euids as $pk) {
          ++$cc;
        }
      }
    }
    #############
    ####ftps#####
    $fc = 0;
    $ftps = $this->api->getFtps();
    if ($ftps !== false) {
      foreach ($ftps as $euids) {
        foreach ($euids as $pk) {
          ++$fc;
        }
      }
    }
    return [$cc, $fc];
  }
}
