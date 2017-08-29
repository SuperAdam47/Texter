<?php

namespace Texter\commands;

# Pocketmine
use pocketmine\Player;
use pocketmine\command\{
  Command,
  CommandSender};
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat as TF;

# Texter
use Texter\Main;
use Texter\language\Lang;
use Texter\text\{
  FloatingText as FT,
  Text};

/**
 * TxtCommand
 */
class TxtCommand extends Command{

  /** @var string $help */
  private $help = "";

  public function __construct(Main $main){
    $this->main = $main;
    $this->api = $main->getAPI();
    $this->lang = Lang::getInstance();
    parent::__construct("txt", $this->lang->transrateString("command.description.txt"), "/txt <add | remove | update | help>");//登録
    //
    $this->setPermission("texter.command.txt");
    //
    $this->help  = $this->lang->transrateString("command.txt.usage")."\n";
    $this->help .= $this->lang->transrateString("command.txt.usage.add")."\n";
    $this->help .= $this->lang->transrateString("command.txt.usage.remove")."\n";
    $this->help .= $this->lang->transrateString("command.txt.usage.update")."\n";
    $this->help .= $this->lang->transrateString("command.txt.usage.indent");
  }

  public function execute(CommandSender $sender, string $label, array $args){
    if (!$this->main->isEnabled()) return false;
    if (!$this->testPermission($sender)) return false;
    if ($sender instanceof Player) {
      if (isset($args[0])) {
        $name = $sender->getName();
        $lev = $sender->getLevel();
        $levn = $lev->getName();
        $lim = $this->main->getCharaLimit();
        switch (strtolower($args[0])) { // subCommand
          case 'add':
          case 'a':
            if (isset($args[1])) { // Title
              $title = str_replace("#", "\n", $args[1]);
              if (isset($args[2])) {
                $texts = array_slice($args, 2);
                $text = str_replace("#", "\n", implode(" ", $texts));
              }else {
                $text = "";
              }
              if (mb_strlen($title . $text, "UTF-8") > $lim) {
                $message = $this->lang->transrateString("command.txt.limit", ["{limit}"], [$lim]);
                $sender->sendMessage(TF::RED . Lang::PREFIX . $message);
              }else {
                $x = sprintf('%0.1f', $sender->x);
                $y = sprintf('%0.1f', $sender->y + 1);
                $z = sprintf('%0.1f', $sender->z);
                $ft = new FT($lev, $x, $y, $z, $title, $text, $name);
                $message = $this->lang->transrateString("command.txt.set");
                $sender->sendMessage(TF::AQUA . Lang::PREFIX . $message);
              }
            }else {
              $message = $this->lang->transrateString("command.txt.usage.add");
              $sender->sendMessage(TF::AQUA . Lang::PREFIX . $message);
            }
          break;

          case 'remove':
          case 'r':
            if (isset($args[1])) { // entityId
              $eid = (int)$args[1];
              $ft = $this->api->getFt($levn, $eid);
              if ($ft !== false) {
                if ($ft->canEditFt($sender)) {
                  $ft->remove();
                  $message = $this->lang->transrateString("command.txt.remove");
                  $sender->sendMessage(TF::AQUA . Lang::PREFIX . $message);
                }else {
                  $message = $this->lang->transrateString("command.txt.permission");
                  $sender->sendMessage(TF::RED . Lang::PREFIX . $message);
                }
              }else {
                $message = $this->lang->transrateString("txt.doesn`t.exists");
                $sender->sendMessage(TF::RED . Lang::PREFIX . $message);
              }
            }else {
              $message = $this->lang->transrateString("command.txt.usage.remove");
              $sender->sendMessage(TF::AQUA . Lang::PREFIX . $message);
            }
          break;

          case 'update':
          case 'u':
            if (isset($args[1]) && isset($args[2]) && isset($args[3])) {
              // eid && title" or "text" && contents
              $eid = (int)$args[1];
              $ft = $this->api->getFt($levn, $eid);
              if ($ft !== false) {
                if ($ft->canEditFt($sender)) {
                  switch (strtolower($args[2])) {
                    case 'title':
                      if (mb_strlen($args[3] . $ft->getText(), "UTF-8") > $lim) {
                        $message = $this->lang->transrateString("command.txt.limit", ["{limit}"], [$lim]);
                        $sender->sendMessage(TF::RED . Lang::PREFIX . $message);
                      }else {
                        $ft->setTitle($args[3]);
                        $message = $this->lang->transrateString("command.txt.updated");
                        $sender->sendMessage(TF::AQUA . Lang::PREFIX . $message);
                      }
                    break;

                    case 'text':
                      $ft->setText($args[3]);
                      $message = $this->lang->transrateString("command.txt.updated");
                      $sender->sendMessage(TF::AQUA . Lang::PREFIX . $message);
                    break;

                    default:
                      $message = $this->lang->transrateString("command.txt.usage.update");
                      $sender->sendMessage(TF::AQUA . Lang::PREFIX . $message);
                    break;
                  }
                }else {
                  $message = $this->lang->transrateString("command.txt.permission");
                  $sender->sendMessage(TF::RED . Lang::PREFIX . $message);
                }
              }else {
                $message = $this->lang->transrateString("txt.doesn`t.exists");
                $sender->sendMessage(TF::RED . Lang::PREFIX . $message);
              }
            }else {
              $message = $this->lang->transrateString("command.txt.usage.update");
              $sender->sendMessage(TF::AQUA . Lang::PREFIX . $message);
            }
          break;

          case 'help':
          case 'h':
          case '?':
          default:
            $sender->sendMessage(TF::AQUA . Lang::PREFIX . $this->help);
          break;
        }
      }else {
        $sender->sendMessage(TF::AQUA . Lang::PREFIX . $this->help);
      }
    }else {
      $this->main->getLogger()->info("§c".$this->lang->transrateString("command.console"));
    }
    return true;
  }
}
