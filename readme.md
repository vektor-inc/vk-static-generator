# VK Block Pattern Plugin Generator

## 概要

* ブロックパターン集のプラグインを作成する事ができます。
* 一度書き出したパターンのプラグインからパターンの情報を再度インポート・編集・再書き出しがスムーズに行えます。
* メディアアップローダーでアップした画像やリモートサーバーの画像を使用している場合も作成したブロックパターンプラグインに書き出します。
* ブロックパターンとカテゴリー情報はjson形式で書き出して運用します。
* このプラグインから生成するプラグイン以外の既存のプラグイン内で出力・読み込みも可能です。
* ブロックパターン用のCSSファイルの適用や管理画面からのCSS編集も可能です。
* 利用中のテーマ・プラグインに依存する事なく利用可能です。

---
## 使い方

まずはWordPressでこのプラグインを有効化（ローカル推奨）

### 新規プラグインの作成

1. 投稿タイプ「Pattern Generator」でパターンを作成  
カテゴリーや画像が指定できます。
1. カテゴリーには階層をつけないでください。複数チェックしてもかまいません。
1. パターンができたら投稿タイプ「Pattern Generator」のメニューのサブメニュー項目「データ管理・設定」画面の下部「プラグイン新規出力」のところで、プラグイン名を英数字で入力して、「新規プラグインとしてエクスポートする」をクリック
1. 入力したプラグイン名でプラグインが生成されます。
  ※パターンのデータは、生成されたプラグインディレクトリ内の /patterns-data/ ディレクトリに格納されます。
  ※不具合発生時を考慮してプラグインの自動有効化はしていません。
  ※出力すると投稿タイプに保存してあったデータは一旦削除されます。
1. プラグイン一覧画面から有効化してください。
1. 記事投稿画面で作成したブロックパターンがあるか確認してください。

### 既存パターンデータの編集

1. 投稿タイプ「Pattern Generator」のメニューのサブメニュー項目「データ管理・設定」画面で、「データディレクトリ」の指定に間違いがないか確認してください。
1. 「インポート & 編集開始」ボタンを押すと、パターンデータを読み込んで、投稿タイプ「Pattern Generator」に投稿されます。
1. 投稿・カテゴリを編集
1. 「エクスポート & 編集終了」ボタンをクリックして編集を終了します。 
  パターン及びカテゴリーのjsonデータと画像、CSSが書き出されます
  投稿タイプ「管理中のパターン」のデータは削除されます。

---

## ディレクトリ構成

| ディレクトリ名 | 役割 |
----|---- 
| inc/block-pattern-manager | コアの処理ファイル |
| patterns-data-sample | 動作確認や練習用のダミーファイル。無くても良い。 |
| plugin-template | 新規プラグイン生成するためのテンプレート |
| tests | phpunitテスト用 |