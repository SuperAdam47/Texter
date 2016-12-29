<?php
namespace Texter\commands;

#pocketmine
use pocketmine\Player;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\math\Vector3;

#texter
use Texter\Main;

/**
 * @var TxtCommand
 */
class TxtCommand extends Command{

  public function __construct(Main $plugin){
		parent::__construct("txt", "浮く文字を追加/削除します。", "/txt add | remove");//登録
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
            $pos = new Vector3(sprintf('%0.1f', $s->x), sprintf('%0.1f', $s->y+1), $z = sprintf('%0.1f', $s->z));
            $eid = $this->plugin->addFtp($s, $pos, $args[1], $args[2], $lev, $n);
            //
            $key = $levn.$pos->z.$pos->x.$pos->y;
            if (!$this->plugin->ftps->exists($key)) {
              $this->plugin->ftps->set($key, [
                "WORLD" => $levn,
                "Xvec" => $pos->x,
                "Yvec" => $pos->y,
                "Zvec" => $pos->z,
                "TITLE" => $args[1],
                "TEXT" => $args[2],
                "OWNER" => $n
              ]);
              $this->plugin->ftps->save();
              $s->sendMessage("§b[Texter] 浮き文字を設置しました。");
            }else{
              $this->plugin->removeFtp($s, $eid);
              $s->sendMessage("§e[Texter] この位置には別の人の浮き文字が存在します。");
            }
          }else {
            $s->sendMessage("§b[Texter] 使用方法: /txt add [タイトル] [メッセージ(改行したい場合は§6#§b)]");
          }
          break;

          case 'remove':
          if (isset($args[1])) {
          $levn = $s->getLevel()->getName();
            foreach ($this->plugin->pks as $key => $ftps) {
              if ($key == $levn) {
                foreach ($ftps as $fkey => $ftp) {
                  if ($ftp->eid == $args[1]) {
                    if ($ftp->owner === $s->getName() or $s->isOp()) {
                      $ckey = $levn.$ftp->z.$ftp->x.$ftp->y;
                      if ($this->plugin->ftps->exists($ckey)) {
                        $this->plugin->ftps->remove($ckey);
                        $this->plugin->ftps->save();
                      }
                      unset($this->plugin->pks[$key][$fkey]);
                      @$this->plugin->pks[$key] = array_values($this->plugin->pks[$key]);
                      $this->plugin->removeFtp($s, $ftp->eid);
                      $s->sendMessage("§b[Texter] 浮き文字を削除しました。");
                    }else {
                      $s->sendMessage("§b[Texter] §cこの浮き文字を消す権限がありません。");
                    }
                  }
                }
              }
            }
          }else {
            $s->sendMessage("§b[Texter] §7浮き文字のID§bを指定してください。");
          }
          break;

          default:
          $s->sendMessage("§b[Texter] 使用方法: /txt add|remove (改行したい場合は§6#§b)");
          break;
        }
      }else {
        $s->sendMessage("§b[Texter] 使用方法: /txt add|remove (改行したい場合は§6#§b)");
      }
    }else {
      $this->plugin->getLogger()->info("§c[Texter] ゲーム内から使用してください");
    }
  }
}
