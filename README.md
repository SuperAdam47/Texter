<img src="/assets/Texter.png" width="400px">

## Overview
Select Language: [English](#eng), [日本語](#jpn)

***
<a name="eng"></a>
## English

<!--
## !! Caution !!
This branch is under development.
It may have many bugs.
-->

## Texter
Texter is plugin that displays and deletes FloatingTextPerticle supported to multi-world.  
Latest: ver **2.2.2** _Papilio dehaanii(カラスアゲハ)_  

### Supporting
- [x] Multi-language (eng, jpn)
- [x] Multi-world display
- [x] MCPE v1.1.x
- [ ] Minecraft(Bedrock) v1.2.x // TODO

### Commands
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
|Info|`/txtadm info`|`none`|`/tadm i`|
|Help|`/txtadm or /txtadm help`|`none`|`/tadm ?`|

**Please use `#` for line breaks.**

### json notation
```json
"anythingString": {
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
<a name="jpn"></a>
## 日本語

<!--
## !! 注意 !!
このブランチは開発中です。多くのバグを含む可能性があります。
-->

## Texter
TexterはFloatingTextPerticleを複数ワールドに渡り表示、削除ができるプラグインです。  
最新バージョン: **2.2.2** _Papilio dehaanii(カラスアゲハ)_  

### 対応状況
- [x] 複数言語 (eng, jpn)
- [x] 複数ワールドの表示
- [x] MCPE v1.1.x
- [ ] Minecraft(Bedrockエンジン) v1.2.x // TODO

### コマンド
#### 一般用コマンド
| \ |コマンド|引数|エイリアス|
|:--:|:--:|:--:|:--:|
|浮き文字追加|`/txt add`|`<タイトル> [テキスト]`|`/txt a`|
|浮き文字削除|`/txt remove`|`<ID>`|`/txt r`|
|浮き文字更新|`/txt update`|`<タイトル, テキスト> <ID> <メッセージ>`|`/txt u`|
|ヘルプ|`/txt or /txt help`|`無し`|`/txt ?`|

#### 管理用コマンド
| \ |コマンド|引数|エイリアス|
|:--:|:--:|:--:|:--:|
|浮き文字すべて削除|`/txtadm allremove`|`none`|`/tadm ar`|
|ユーザーの浮き文字を削除|`/txtadm userremove`|`<username>`|`/tadm ur`|
|浮き文字の各種情報を見る|`/txtadm info`|`none`|`/tadm i`|
|ヘルプ|`/txtadm or /txtadm help`|`none`|`/tadm ?`|

**改行の際には `#` を使用してください。**

### json記法
```json
{
  "何かの文字列": {
    "WORLD" : "world",
    "Xvec" : 128,
    "Yvec" : 90,
    "Zvec" : 128,
    "TITLE" : "title",
    "TEXT" : "1st Line#2nd Line"
  },
  "何かの文字列2(他のものと同じではいけない)": {
    "WORLD" : "world",
    "Xvec" : 128,
    "Yvec" : 90,
    "Zvec" : 128,
    "TITLE" : "title",
    "TEXT" : "1st Line#2nd Line"
  }
}
```

こう書くことで以下のように出力されます。  
<img src="https://cloud.githubusercontent.com/assets/16377174/24609877/642d64f6-18b7-11e7-9b38-488e0ada3f1e.JPG" width="320px">
