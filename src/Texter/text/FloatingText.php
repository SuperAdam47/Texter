<?php
namespace Texter\text;

# Pocketmine
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\level\{
  Level,
  Position};
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\utils\{
  TextFormat as TF,
  UUID};

# Texter
use Texter\TexterApi;
use Texter\text\Text;
use Texter\language\Lang;

/**
 * FloatingText
 */
class FloatingText extends Text{

  /** @var string $owner */
  public $owner = "";

  /**
   * コンストラクタ
   * @Override
   * @param Level     $level
   * @param int|float $x = 0
   * @param int|float $y = 0
   * @param int|float $z = 0
   * @param Vector3   $pos
   * @param string    $title = ""
   * @param string    $text = ""
   * @param string    $owner = ""
   */
  public function __construct(Level $level, $x = 0, $y = 0, $z = 0, string $title = "", string $text = "", string $owner = ""){
    $this->level = $level;
    $this->x = $x;
    $this->y = $y;
    $this->z = $z;
    $this->title = $title;
    $this->text = $text;
    $this->owner = $owner;
    $this->eid = Entity::$entityCount++;
    $this->api = TexterApi::getInstance();
    $this->api->registerText($this);
    $this->update(self::SEND_TYPE_ADD);
  }

  /**
   * プレイヤーに送信します
   * @Override
   * @param  Player $player
   * @param  int    $type
   * @return bool
   */
  public function send(Player $player, int $type): bool{
    switch ($type) {
      case self::SEND_TYPE_ADD:
        $pk = $this->getAsAddPacket();
        if ($this->canEditFt($player)) {
          $pk->metadata[4][1] = TF::GRAY . "[" . $this->eid . "] " . TF::WHITE . $pk->metadata[4][1];
        }
        $player->dataPacket($pk);
      break;

      case self::SEND_TYPE_REMOVE:
        $pk = $this->getAsRemovePacket();
        $player->dataPacket($pk);
      break;

      default:
        return false;
      break;
    }
    return true;
  }

  /**
   * テキストを更新します
   * @Override
   * @param  int  $type
   * @return bool true
   */
  public function update(int $type): bool{
    switch ($type) {
      case self::SEND_TYPE_ADD:
        $pk = $this->getAsAddPacket();
        $players = $this->level->getPlayers();
        foreach ($players as $player) {
          if ($this->canEditFt($player)) {
            $pk->metadata[4][1] = TF::GRAY . "[" . $this->eid . "] " . TF::WHITE . $pk->metadata[4][1];
          }
          $player->dataPacket($pk);
        }
      break;

      case self::SEND_TYPE_REMOVE:
        $this->api->removeText($this);
        $pk = $this->getAsRemovePacket();
        $players = $this->level->getPlayers();
        foreach ($players as $player) {
          $player->dataPacket($pk);
        }
      break;

      default:
        return false;
      break;
    }
    return true;
  }

  /**
   * 所有者を取得します
   * @return string $this->owner
   */
  public function getOwner(): string{
    return $this->owner;
  }

  /**
   * 所有者を変更します
   * @param  string $owner
   * @return bool
   */
  public function setOwner(string $owner): bool{
    $this->owner = $owner;
    if ($this->api->saveText($this)) {
      $this->update();
      return true;
    }
    return true;
  }
}
