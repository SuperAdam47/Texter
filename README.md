## Texter
Texter is plugin that displays and deletes FloatingTextPerticle supported to multi-world.

***
## English
About bug report  
Please report on Issue tracker or report it on Twitter.

## Commands
| \ |command|usage|
|:--:|:--:|:--:|
|Add text|`/txt add`|`txt add [title] [text]`|
|Remove text|`/txt remove`|`/txt remove [ID]`|
|Update text|`/txt update`|`/txt update [title, text] [ID] [message]`|
|Help|`/txt or /txt help`|

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

## About extension
Recognize by configuring the following file structure (please implement TexterExtension.php).  
for Example (<https://github.com/fuyutsuki/Texter-ExtensionTemplete>)
```
PMMP-Texter/
　└ src/
　 　└ Texter/
　 　　　├ commands/
　 　　　├ extensions/
　 　　　|　　　├ {extensionName}/
　 　　　|　　　|　　　└ {MainFile}.php
　 　　　|　　　└ TexterExtension.php
　 　　　├ task/
　 　　　├ utils/
　　　　 ├ Main.php
　　　　 └ TexterAPI.php
```

***
## 日本語
バグ報告について  
こちらでIssueを建てていただいてもかまいませんし、Twitterにて報告して頂いても構いません。

## コマンド
| \ |コマンド|使用方法|
|:--:|:--:|:--:|
|浮き文字追加|`/txt add`|`txt add [タイトル] [テキスト]`|
|浮き文字削除|`/txt remove`|`/txt remove [ID]`|
|浮き文字更新|`/txt update`|`/txt update [title, text] [ID] [メッセージ]`|
|help|`/txt or /txt help`|

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

## 拡張について
このような構成にすることでTexter側で認識します。  
例: (<https://github.com/fuyutsuki/Texter-ExtensionTemplete>)
```
PMMP-Texter/
　└ src/
　 　└ Texter/
　 　　　├ commands/
　 　　　├ extensions/
　 　　　|　　　├ {extensionName}/
　 　　　|　　　|　　　└ {MainFile}.php
　 　　　|　　　└ TexterExtension.php
　 　　　├ task/
　 　　　├ utils/
　　　　 ├ Main.php
　　　　 └ TexterAPI.php
```
