hk-r/lib-plum
======================

ウェブプロジェクトをプレビュー環境へデプロイするツールです。

## 導入方法 - Setup
### 1. `composer.json` に `hk-r/lib-plum` を設定する

`require` の項目に、`hk-r/lib-plum` を追加します。

```
{
	〜 中略 〜
    "require": {
        "php": ">=5.3.0" ,
        "hk-r/lib-plum": "dev-develop"
    },
	〜 中略 〜
}
```

### 2. composer update を実行する

追加したら、`composer update` を実行して変更を反映することを忘れずに。

```
$ composer update
```

### 3． plumを初期化する

各種パラメータを設定し、lib-plumのmainクラスを呼び出し初期化を行います。

```
<?php

$plum = new hk\plum\main(
	array(
		// POST
		'_POST' => $_POST,

		// GET
		'_GET' => $_GET,

		// プレビューサーバ定義
		'preview_server' => array(

			// プレビューサーバの数だけ設定する
			//
			//   string 'name':
			//     - プレビューサーバ名(任意)
			//   string 'path':
			//     - プレビューサーバ(デプロイ先)のパス
			//   string 'url':
			//     - プレビューサーバのURL
			//       Webサーバのvirtual host等で設定したURL
			//
			array(
				'name' => 'preview1',
				'path' => './../repos/preview1/',
				'url' => 'http://preview1.localhost/'
			)
		),

		// Git情報定義
		'git' => array(
			
			// リポジトリのパス
			// ウェブプロジェクトのリポジトリパスを設定。
			'repository' => './../repos/master/',

			// プロトコル
			// ※現在はhttpsのみ対応
			'protocol' => 'https',

			// ホスト
			// Gitリポジトリのhostを設定。
			'host' => 'github.com',

			// url
			// Gitリポジトリのhostを設定。
			'url' => 'github.com/hk-r/px2-sample-project.git',

			// ユーザ名
			// Gitリポジトリのユーザ名を設定。
			'username' => 'hoge',

			// パスワード
			// Gitリポジトリのパスワードを設定。
			'password' => 'fuga'
		)
	)
);
```

### 4． plumを実行する

`run()` を実行します。

```
// return: 結果表示用HTML
echo $plum->run();
```

## 更新履歴 - Change log
### lib-plum x.x (yyyy年mm月dd日)
- Initial Release.

## ライセンス - License
MIT License

## 作者 - Author
- (C)Kyota Hiyoshi hiyoshi-kyota@imjp.co.jp