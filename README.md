<img src="/image/Texter.png" width="400px">

## Texter
Texter is plugin that displays and deletes FloatingTextPerticle supported to multi-world.  
Latest: ver **2.1.9** _Convallaria majalis(鈴蘭)_  

### Supporting(サポート)
- [x] Multi-world display
- [x] MCPE v1.1.x
- [ ] Minecraft(Bedrock) v1.2.x // TODO

### Notice(お知らせ)
##### English
If your src is `pocketmine\network\protocol\Packets`, please write `path: old` in *config.yml* .  
If not, please write `path: new`(initial value)  
**※It will be deleted in the future.**

##### 日本語
もしあなたがお使いのsrcが `pocketmine\network\protocol\{パケット}` という構成の場合、*config.yml* に、`path: old` と書いてください。  
そうでない場合は、 `path: new` (初期値)で動作します。  
**※今後この機能は削除されます**

***
## English
About bug report  
Please report on Issue tracker or report it on Twitter.

## Commands

#### General command
| \ |command|argument|alias|
|:--:|:--:|:--:|:--:|
|Add text|`/txt add`|`<title> [text]`|`/txt a`|
|Remove text|`/txt remove`|`<ID>`|`/txt r`|
|Update text|`/txt update`|`<title, text> <ID> <message>`|`/txt u`|
|Help|`/txt or /txt help`|`none`|`/txt ?`|

#### Management command
| \ |command|argument|alias|
|:--:|:--:|:--:|:--:|
|Remove all|`/txtadm allremove`|`none`|`/tadm ar`|
|Remove texts/user|`/txtadm userremove`|`<username>`|`/tadm ur`|
|View extensions|`/txtadm extensions`|`none`|`/tadm exts`|
|Help|`/txtadm or /txtadm help`|`none`|`/tadm ?`|

**Please use `#` for line breaks.**

## json notation

``` json
"0": {
  "WORLD" : "worldName",
  "Xvec" : 128,
  "Yvec" : 90,
  "Zvec" : 128,
  "TITLE" : "title",
  "TEXT" : "1st Line#2nd Line..."
}
```
It is output as follows.  
<img src="https://cloud.githubusercontent.com/assets/16377174/24609877/642d64f6-18b7-11e7-9b38-488e0ada3f1e.JPG" width="320px">

***
## 日本語
バグ報告について  
こちらでIssueを建てていただいてもかまいませんし、Twitterにて報告して頂いても構いません。

## コマンド

#### 一般用コマンド
| \ |コマンド|引数|エイリアス|
|:--:|:--:|:--:|:--:|
|浮き文字追加|`/txt add`|`<タイトル> [テキスト]`|`/txt a`|
|浮き文字削除|`/txt remove`|`<ID>`|`/txt r`|
|浮き文字更新|`/txt update`|`<タイトル, テキスト> <ID> <メッセージ>`|`/txt u`|
|help|`/txt or /txt help`|`無し`|`/txt ?`|

#### 管理用コマンド
| \ |コマンド|引数|エイリアス|
|:--:|:--:|:--:|:--:|
|浮き文字すべて削除|`/txtadm allremove`|`none`|`/tadm ar`|
|ユーザーの浮き文字を削除|`/txtadm userremove`|`<username>`|`/tadm ur`|
|拡張を見る|`/txtadm extensions`|`none`|`/tadm exts`|
|help|`/txtadm or /txtadm help`|`none`|`/tadm ?`|

**改行の際には `#` を使用してください。**

## json記法

``` json
"0": {
  "WORLD" : "world",
  "Xvec" : 128,
  "Yvec" : 90,
  "Zvec" : 128,
  "TITLE" : "title",
  "TEXT" : "1st Line#2nd Line"
}
```

こう書くことで以下のように出力されます。  
<img src="https://cloud.githubusercontent.com/assets/16377174/24609877/642d64f6-18b7-11e7-9b38-488e0ada3f1e.JPG" width="320px">
