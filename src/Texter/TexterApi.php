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
use pocketmine\math\Vector3;
use pocketmine\utils\UUID;

# Texter
use Texter\Main;
use Texter\language\Lang;
use Texter\text\{
  CantRemoveFloatingText,
  FloatingTextPerticle};

/**
 * TexterApi
 */
class TexterAPI{

  /** @link registerParticle() */
  const PARTICLE_CRFTP = 0;
  const PARTICLE_FTP = 1;

  /** @var array $crftp[$levelName][] = $pk */
  private $crftp = [];
  /** @var array $ftp[$levelName][] = $pk */
  private $ftp = [];
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
   * @param  CRFTP|FTP $particle
   * @return bool
   */
  public function registerParticle($particle){
    if ($particle instanceof CantRemoveFloatingTextPerticle) {
      $this->crftps[$particle->getLevel()->getName()][$particle->getEntityId()] = $particle;
    }elseif ($particle instanceof FloatingTextPerticle) {
      $this->ftps[$particle->getLevel()->getName()][$particle->getEntityId()] = $particle;
    }else {
      $this->getLogger()->warning($this->language->transrateString(""));// TODO:
    }
  }

  /**
   * すべてのcrftpを返します
   * @return array $this->crftps
   */
  public function getCrftps(): array{
    return $this->crftps;
  }

  /**
   * 指定されたワールドのすべてのcrftpを返します
   * @param  Level      $level
   * @return bool|array
   */
  public function getCrftpsByLevel(Level $level){
    $levelName = $level->getName();
    if (!isset($this->crftps[$levelName])) {
      return false;
    }else {
      return $this->crftps[$levelName];
    }
  }

  /**
   * 指定されたワールドのすべてのcrftpを返します
   * @param  string     $levelName
   * @return bool|array
   */
  public function getCrftpsByLevelName(string $levelName){
    if (!isset($this->crftps[$levelName])) {
      return false;
    }else {
      return $this->crftps[$levelName];
    }
  }

  /**
   * 指定されたワールド, eidのcrftpを取得します
   * @param  string $levelName
   * @param  int    $entityId
   * @return booL|CantRemoveFloatingText
   */
  public function getCrftp(string $levelName, int $entityId){
    if (!isset($this->crftps[$levelName][$entityId])) {
      return false;
    }else{
      return $this->crftps[$levelName][$entityId];
    }
  }

  /**
   * すべてのftpを返します
   * @return array $this->ftps
   */
  public function getFtps(): array{
    return $this->ftps;
  }

  /**
   * 指定されたワールドのすべてのftpを返します
   * @param  Level      $level
   * @return bool|array
   */
  public function getFtpsByLevel(Level $level){
    $levelName = $level->getName();
    if (!isset($this->crftps[$levelName])) {
      return false;
    }else {
      return $this->crftps[$levelName];
    }
  }

  /**
   * 指定されたワールドのすべてのftpを返します
   * @param  string     $levelName
   * @return bool|array
   */
  public function getFtpsByLevelName(string $levelName){
    if (!isset($this->crftps[$levelName])) {
      return false;
    }else {
      return $this->crftps[$levelName];
    }
  }

  /**
   * 指定されたワールド,eidのftpを取得します
   * @param  string $levelName
   * @param  int    $entityId
   * @return bool|FloatingText
   */
  public function getFtp(string $levelName, int $entityId){
    if (!isset($this->ftps[$levelName][$entityId])) {
      return false;
    }else{
      return $this->ftps[$levelName][$entityId];
    }
  }

  /**
   * 有用な追加用パケットを取得します
   * @return AddPlayerPacket
   */
  public function getAddPacket(): AddPlayerPacket{
    return $this->main->getAddPacket();
  }

  /**
   * 有用な削除用パケットを取得します
   * @return RemoveEntityPacket
   */
  public function getRemovePacket(): RemoveEntityPacket{
    return $this->main->getRemovePacket();
  }
}
