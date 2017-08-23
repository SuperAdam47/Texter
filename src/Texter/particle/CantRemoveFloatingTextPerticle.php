<?php
namespace Texter\particle;

use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\utils\UUID;

/**
 * CantRemoveFloatingTextPerticle
 */
class CantRemoveFloatingTextPerticle{

  /** @var Vector3 $pos */
  public $pos = null;
  /** @var string $title */
  public $title = "";
  /** @var string $text */
  public $text = "";
  /** @var string $level */
  public $level = "";
  /** @var bool $invisible */
  public $invisible = true;
  /** @var AddPlayerPacket $apk */
  private $apk = null;
  /** @var RemoveEntityPacket $rpk */
  private $rpk = null;
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

  public function getAsAddPacket(): AddPlayerPacket{
    $pk = TexterApi::getInstance()->getAddPacket();
    $pk->uuid = UUID::fromRandom();
    $pk->username = "crftp";
    $pk->entityUniqueId = $this->eid;
  }

  public function getAsRemovePacket(): RemoveEntityPacket{
    $pk = TexterApi::getInstance()->getAddPacket();
  }
}
