pickles2/lib-plum
======================

ウェブプロジェクトをプレビュー環境へデプロイする機能を提供するライブラリです。

## 導入方法 - Setup

### 1. composerの設定

#### 1-1. `composer.json` に `pickles2/lib-plum` を設定する

`require` の項目に、`pickles2/lib-plum` を追加します。

```json
{
	〜 中略 〜
    "require": {
        "php": ">=5.3.0" ,
        "pickles2/lib-plum": "^0.1"
    },
	〜 中略 〜
}
```

#### 1-2. composer update を実行する

追加したら、`composer update` を実行して変更を反映することを忘れずに。

```
$ composer update
```

### 2. Resourceファイルの取込

plumを動作させる上で必要となるResrouceファイルをプロジェクトに取込みます。


#### 2-1. Resourceファイル取込用スクリプトをプロジェクトへコピーする

```
$ cp yourProject/vendor/pickles2/lib-plum/res_install_script.php yourProject/
```

#### 2-2. スクリプトをコマンドラインで実行する

```
$ php res_install_script.php [resourceInstallPath(ex. ./res)]
```

#### 2-3. Resourceを読込む

```html
<link rel="stylesheet" href="/[resourceInstallPath]/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="/[resourceInstallPath]/styles/common.css">

<script src="/[resourceInstallPath]/bootstrap/js/bootstrap.min.js"></script>
<script src="/[resourceInstallPath]/scripts/common.js"></script>
```

### 3. plumの実行

#### 3-1. 初期化する

各種パラメータを設定し、`lib-plum` の `main` クラスを呼び出し初期化を行います。

```php
<?php
// 実行環境に合わせてパスを設定
require_once('./vendor/autoload.php');

$plum = new hk\plum\main(
	array(
		// POSTパラメータ (省略時、`$_POST` を直接参照します)
		'_POST' => $_POST,

		// GETパラメータ (省略時、`$_GET` を直接参照します)
		'_GET' => $_GET,

		// フォーム送信時に付加する追加のパラメータ (省略可)
		'additional_params' => array(
			'hoge' => 'fuga',
		),

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
			'host' => 'host.com',

			// GitリポジトリのURL
			// Gitリポジトリのhost以下のパスを設定。
			'url' => 'https://host.com/path/to.git',

			// ユーザ名
			// Gitリポジトリのユーザ名を設定。
			'username' => 'user',

			// パスワード
			// Gitリポジトリのパスワードを設定。
			'password' => 'pass'
		)
	)
);
```

gitリポジトリの情報は、次のように1つの完全なURLの形式で設定することもできます。
このとき、 `protocol`、 `host`、 `username`、 `password` が設定されているとき、これらが優先されます。それぞれ空白に設定するようにしてください。

```php
// Git情報定義
'git' => array(
	
	// リポジトリのパス
	// ウェブプロジェクトのリポジトリパスを設定。
	'repository' => './../repos/master/',

	// Gitリポジトリの完全なURL
	'url' => 'https://user:pass@host.com/path/to.git',
)
```



#### 3-2. デプロイ先のディレクトリに書き込み権限の付与

3-1.の手順で設定した以下のディレクトリに実行ユーザの書き込み権限が無い場合は、権限を付与します。
```
preview_server -> path        ・・・ プレビューサーバ(デプロイ先)のパス
git            -> repository  ・・・ ウェブプロジェクトのリポジトリパス
```

#### 3-3. plumを実行する

`run()` を実行します。

```
// return: 結果表示用HTML
echo $plum->run();
```

## 更新履歴 - Change log

### pickles2/lib-plum 0.1.2 (リリース日未定)

- オプション `additional_params` を追加。
- オプション `_GET`, `_POST` を省略可能とした。

### pickles2/lib-plum 0.1.1 (2018年10月19日)

- git リモートリポジトリ のIDとパスワードが設定されていない場合に、認証情報なしでアクセスするようになった。
- git repository のURLを解析してコンフィグを補完するようになった。 `https://user:pass@host.com/path/to.git` のような1つの完全な URL の形で設定できる。
- OSコマンドインジェクションの脆弱性に関する修正。

### pickles2/lib-plum 0.1.0 (2018年6月7日)

- Initial Release.


## ライセンス - License
MIT License

## 作者 - Author
- (C)Kyota Hiyoshi hiyoshi-kyota@imjp.co.jp
