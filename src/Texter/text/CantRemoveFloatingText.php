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
use pocketmine\utils\UUID;

# Texter
use Texter\TexterApi;

/**
 * CantRemoveFloatingText
 */
class CantRemoveFloatingText{

  /** @link $this->send() */
  const SEND_TYPE_ADD = 0;
  const SEND_TYPE_REMOVE = 1;

  /** @var Level $level */
  public $level = null;
  /** @var Vector3 $pos */
  public $pos = null;
  /** @var string $title */
  public $title = "";
  /** @var string $text */
  public $text = "";
  /** @var bool $invisible */
  public $invisible = false;
  /** @var int $eid */
  private $eid = 0;

  /**
   * コンストラクタ
   * @param Level   $level
   * @param Vector3 $pos
   * @param string  $title = ""
   * @param string  $text = ""
   */
  public function __construct(Level $level, Vector3 $pos = null, string $title = "", string $text = ""){
    $this->level = $level;
    $this->pos = $pos === null ? new Vector3() : $pos;
    $this->title = $title;
    $this->text = $text;
    $this->eid = Entity::$entityCount++;
  }

  /**
   * Levelを取得します
   * @return Level $this->level
   */
  public function getLevel(): Level{
    return $this->level;
  }

  /**
   * Levelを変更します
   * @param  string $levelName
   * @return bool
   */
  public function setLevel(string $levelName): bool{
    $level = Server::getInstance()->getLevelByName($levelName);
    if ($level !== null) {
      $this->level = $level;
      return true;
    }else {
      return false;
    }
  }

  /**
   * 座標をVector3オブジェクトとして取得します
   * @return Vector3 $this->pos
   */
  public function getAsVector3(){
    return $this->pos;
  }

  /**
   * 座標をPositionオブジェクトとして取得します
   * @return Position
   */
  public function getAsPosition(){
    $x = $this->pos->x;
    $y = $this->pos->y;
    $z = $this->pos->z;
    return new Position($x, $y, $z, $this->level);
  }

  /**
  * 座標を変更します
  * @param  Vector3 $pos
  * @return bool    true
  */
  public function setCoord(Vector3 $pos): bool{
    $this->pos = $pos;
    return true;
  }

  /**
   * タイトルを取得します
   * @return string $this->title
   */
  public function getTitle(): string{
    return $this->title;
  }

  /**
   * タイトルを変更します, # で改行です.
   * @param  string $title
   * @return bool   true
   */
  public function setTitle(string $title): bool{
    $this->title = str_replace("#", "\n", $title);
    return true;
  }

  /**
   * テキストを取得します
   * @return string $this->text
   */
  public function getText(): string{
    return $this->text;
  }

  /**
   * テキストを変更します, # で改行です.
   * @param  string $text
   * @return bool   true
   */
  public function setText(string $text): bool{
    $this->text = str_replace("#", "\n", $text);
    return true;
  }

  /**
  * 不可視かどうか取得します
  * @return bool
  */
  public function isInvisible(): bool{
    return $this->invisible;
  }

  /**
   * 不可視かどうか変更します
   * @param  bool $bool
   * @return bool true
   */
  public function setInvisible(bool $bool): bool{
    $this->invisible = $bool;
    return true;
  }

  /**
   * エンティティIDを取得します
   * @return int $this->eid
   */
  public function getEntityId(): int{
    return $this->eid;
  }

  /**
   * エンティティIDを変更します
   * @param  int  $eid
   * @return bool true
   */
  public function setEntityId(int $eid): bool{
    $this->eid = $eid;
    return true;
  }

  /**
   * AddPlayerPacketとして取得します
   * @return AddPlayerPacket $pk
   */
  public function getAsAddPacket(){
    $pk = TexterApi::getInstance()->getAddPacket();
    $pk->uuid = UUID::fromRandom();
    $pk->username = "crftp";
    $pk->eid = $this->eid;// for old packetObject
    $pk->entityUniqueId = $this->eid;
    $pk->entityRuntimeId = $this->eid;// ...huh?
    $pk->item = Item::get(Item::AIR);
    $pk->x = (float)sprintf('%0.1f', $this->pos->x);
    $pk->y = (float)sprintf('%0.1f', $this->pos->y);
    $pk->z = (float)sprintf('%0.1f', $this->pos->z);
    $flags = 0;
    $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
    $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->title . ($this->text !== "" ? "\n" . $this->text : "")],
      Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0]
    ];
    return $pk;
  }

  /**
   * RemoveEntityPacketとして取得します
   * @return RemoveEntityPacket $pk
   */
  public function getAsRemovePacket(){
    $pk = TexterApi::getInstance()->getRemovePacket();
    $pk->eid = $this->eid;// for old packetObject
    $pk->entityUniqueId = $this->eid;
    return $pk;
  }

  /**
   * プレイヤーに送信します
   * @param  Player $player
   * @param  int    $type
   * @return bool
   */
  public function send(Player $player, int $type): bool{
    switch ($type) {
      case self::SEND_TYPE_ADD:
        $pk = $this->getAsAddPacket();
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
}
