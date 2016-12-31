## Texter

> Texter is plugin that displays and deletes FloatingTextPerticle supported to multi-world.

***

## バグ報告について
こちらでIssueを建てていただいてもかまいませんし、Twitterにて報告して頂いても構いません。

## コマンド
|   |コマンド|使い方|
|:--:|:--:|:--:|
|浮き文字追加|`/txt add`|`txt add [タイトル] [テキスト]`|
|浮き文字削除|`/txt remove`|`/txt remove [ID]`|

## yml記法
```yaml
1: #(数)
  WORLD: world #(ワールド名。入力しない場合、自動的に"world"になります。)
  Xvec: 128 #x座標
  Yvec: 90 #y座標
  Zvec: 128 #z座標
  TITLE: "タイトル" #(\nを入れるとテキストの一行目に)
  TEXT: "テキスト" #(\nを入れると改行)
```
