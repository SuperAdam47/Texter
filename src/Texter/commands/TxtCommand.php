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
use Texter\text\FloatingText as FT;

/**
 * TxtCommand
 */
class TxtCommand extends Command{

  const PREFIX = "[Texter] ";

  public function __construct(Main $main){
    $this->main = $main;
    $this->api = $main->getAPI();
    $this->lang = Lang::getInstance();
    parent::__construct("txt", $this->lang->transrateString("command.description.txt"), "/txt <add | remove | update | help>");//登録
    //
    $this->setPermission("texter.command.txt");
  }

  public function execute(CommandSender $sender, string $label, array $args){
    if (!$this->main->isEnabled()) return false;
    if (!$this->testPermission($sender)) return false;
    if ($sender instanceof Player) {
      if (isset($args[0])) {
        $name = $sender->getName();
        $lev = $sender->getLevel();
        $levn = $lev->getName();
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
              if (mb_strlen($title . $text, "UTF-8") > $this->main->getCharaLimit()) {
                $message = $this->lang->transrateString("command.txt.limit");// TODO:
                $sender->sendMessage(TF::AQUA . self::PREFIX . $message);
              }else {
                // TODO: 要調整(Y座標)
                $pos = new Vector3(sprintf('%0.1f', $sender->x), sprintf('%0.1f', $sender->y+1), sprintf('%0.1f', $sender->z));
                $ft = new FT($lev, $pos, $title, $text, $name);
                $this->api->registerTexts($ft);
                $players = $lev->getPlayers();
                foreach ($players as $player) {
                  $ft->send($player, FT::SEND_TYPE_ADD);
                }
                $message = $this->lang->transrateString("command.txt.set");
                $sender->sendMessage(TF::AQUA . self::PREFIX . $message);
              }
            }else {
              $message = $this->lang->transrateString("command.txt.usage.add");
              $sender->sendMessage(TF::AQUA . self::PREFIX . $message);
            }
          break;

          case 'remove':
          case 'r':
            # code...
          break;

          case 'add':
          case 'a':
            # code...
          break;

          case 'update':
          case 'u':
            # code...
          break;

          case 'help':
          case 'h':
          case '?':
          default:
            # code...
          break;
        }
      }else {
        // TODO: send /help
      }
    }else {
      $this->main->getLogger()->info("§c".$this->lang->transrateString("command.console"));
    }
  }
}
