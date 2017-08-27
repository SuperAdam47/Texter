<?php

/**
 * ## To English-speaking countries
 *
 * Texter, the display FloatingTextPerticle plugin for PocketMine-MP
 * Copyright (c) 2017 yuko fuyutsuki < https://twitter.com/y_fyi >
 *
 * Released under the "MIT license".
 * You should have received a copy of the MIT license
 * along with this program.  If not, see
 * < http://opensource.org/licenses/mit-license.php >.
 *
 * ---------------------------------------------------------------------
 * ## 日本の方へ
 *
 * TexterはPocketMine-MP向けのFloatingTextPerticleを表示するプラグインです。
 * Copyright (c) 2017 yuko fuyutsuki < https://twitter.com/y_fyi >
 *
 * このソフトウェアは"MITライセンス"下で配布されています。
 * あなたはこのプログラムと共にMITライセンスのコピーを受け取ったはずです。
 * 受け取っていない場合、下記のURLからご覧ください。
 * < http://opensource.org/licenses/mit-license.php >
 */

namespace Texter;

# Pocketmine
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\{
  Level,
  Position};
use pocketmine\math\Vector3;
use pocketmine\utils\UUID;

# Texter
use Texter\Main;
use Texter\language\Lang;
use Texter\text\{
  CantRemoveFloatingText,
  FloatingText};

/**
 * TexterApi
 */
class TexterAPI{

  /** @var array $crfts[$levelName][] = $pk */
  private $crfts = [];
  /** @var array $ft[$levelName][] = $pk */
  private $fts = [];
  /** @var TexterAPI */
  private static $instance = null;

  public function __construct(Main $main){
    $this->main = $main;
    self::$instance = $this;
  }

  /****************************************************************************/
  /* get/set(情報取得/変更) 関連 */
  /**
   * インスタンスを取得
   * @return TexterApi
   */
  public static function getInstance(): TexterApi{
    return self::$instance;
  }

  /**
   * 現在使用中の言語を取得します
   * @return string "eng"|"jpn"
   */
  public function getLanguage(): string{
    return Lang::getInstance()->getLang();
  }

  /**
   * パーティクルをTexterに登録します
   * @param  CRFT|FT $text
   * @return bool
   */
  public function registerTexts($text){
    if ($text instanceof CantRemoveFloatingText) {
      $this->crfts[$text->getLevel()->getName()][$text->getEntityId()] = $text;
    }elseif ($text instanceof FloatingText) {
      $this->fts[$text->getLevel()->getName()][$text->getEntityId()] = $text;
    }else {
      $this->getLogger()->warning($this->language->transrateString(""));// TODO:
    }
  }

  /**
   * すべてのcrftを返します
   * @return array $this->crfts
   */
  public function getCrfts(): array{
    return $this->crfts;
  }

  /**
   * 指定されたワールドのすべてのcrftを返します
   * @param  Level      $level
   * @return bool|array
   */
  public function getCrftsByLevel(Level $level){
    $levelName = $level->getName();
    if (!isset($this->crfts[$levelName])) {
      return false;
    }else {
      return $this->crfts[$levelName];
    }
  }

  /**
   * 指定されたワールドのすべてのcrftを返します
   * @param  string     $levelName
   * @return bool|array
   */
  public function getCrftsByLevelName(string $levelName){
    if (!isset($this->crfts[$levelName])) {
      return false;
    }else {
      return $this->crfts[$levelName];
    }
  }

  /**
   * 指定されたワールド, eidのcrftを取得します
   * @param  string $levelName
   * @param  int    $entityId
   * @return booL|CantRemoveFloatingText
   */
  public function getCrft(string $levelName, int $entityId){
    if (!isset($this->crfts[$levelName][$entityId])) {
      return false;
    }else{
      return $this->crfts[$levelName][$entityId];
    }
  }

  /**
   * すべてのftを返します
   * @return array $this->fts
   */
  public function getFts(): array{
    return $this->fts;
  }

  /**
   * 指定されたワールドのすべてのftを返します
   * @param  Level      $level
   * @return bool|array
   */
  public function getFtsByLevel(Level $level){
    $levelName = $level->getName();
    if (!isset($this->crfts[$levelName])) {
      return false;
    }else {
      return $this->crfts[$levelName];
    }
  }

  /**
   * 指定されたワールドのすべてのftを返します
   * @param  string     $levelName
   * @return bool|array
   */
  public function getFtsByLevelName(string $levelName){
    if (!isset($this->crfts[$levelName])) {
      return false;
    }else {
      return $this->crfts[$levelName];
    }
  }

  /**
   * 指定されたワールド,eidのftを取得します
   * @param  string $levelName
   * @param  int    $entityId
   * @return bool|FloatingText
   */
  public function getFt(string $levelName, int $entityId){
    if (!isset($this->fts[$levelName][$entityId])) {
      return false;
    }else{
      return $this->fts[$levelName][$entityId];
    }
  }

  /**
   * 有用な追加用パケットを取得します
   * @return AddPlayerPacket
   */
  public function getAddPacket(){
    return $this->main->getAddPacket();
  }

  /**
   * 有用な削除用パケットを取得します
   * @return RemoveEntityPacket
   */
  public function getRemovePacket(){
    return $this->main->getRemovePacket();
  }
}
