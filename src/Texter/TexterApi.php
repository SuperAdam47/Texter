<?php

/*
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

use Texter\Main;

# Player
use pocketmine\Player;

# Entity
use pocketmine\entity\Entity;

# Item
use pocketmine\item\Item;

# Math
use pocketmine\math\Vector3;

# etc
use pocketmine\utils\UUID;

# Texter
use Texter\language\Lang;

/**
 * TexterApi
 */
class TexterAPI{

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
   * 追加用パケットを取得します
   * @return AddPlayerPacket
   */
  public function getAddPacket(): AddPlayerPacket{
    return $this->main->getAddPacket();
  }

  /**
   * 削除用パケットを取得します
   * @return RemoveEntityPacket
   */
  public function getRemovePacket(): RemoveEntityPacket{
    return $this->main->getRemovePacket();
  }
}
