pickles2/lib-plum
======================

ウェブプロジェクトをステージング環境へデプロイする機能を提供するライブラリです。


## インストール - Install

```
$ composer require pickles2/lib-plum
```


## 導入方法 - Setup

### クライアントサイド

```html
<link rel="stylesheet" href="/path/to/vendor/pickles2/lib-plum/dist/plum.css" />
<script src="/path/to/vendor/pickles2/lib-plum/dist/plum.js"></script>


<div id="plum-area"></div>

<script>
var plum = new Plum(
	document.getElementById('plum-area'),
	{
		'gpiBridge': function(data, callback){
			$.ajax({
				'url': '/api.php',
				'method': 'POST',
				'data': {
					'data': data
				},
				'success': function(result){
					callback(result);
				}
			});
		}
	}
);
plum.init();

window.addEventListener('message', function(e){
	plum.broadcastMessage(e.message);
});
</script>
```


### サーバーサイド

```php
<?php
// 実行環境に合わせてパスを設定
require_once('./vendor/autoload.php');

$plum = new hk\plum\main(
	array(
		// Plumが内部で使用するデータ保管用ディレクトリ
		// (書き込みが許可されたディレクトリを指定)
		'data_dir' => '/path/to/data_dir/',

		// ステージングサーバ定義
		'staging_server' => array(

			// ステージングサーバの数だけ設定する
			//
			//   string 'name':
			//     - ステージングサーバ名(任意)
			//   string 'path':
			//     - ステージングサーバ(デプロイ先)のパス
			//   string 'url':
			//     - ステージングサーバのURL
			//       Webサーバのvirtual host等で設定したURL
			//
			array(
				'name' => 'Staging 1',
				'path' => './../repos/stg1/',
				'url' => 'http://stg1.localhost/'
			)
		),

		// Git情報定義
		'git' => array(
			// リモートのURL
			'url' => 'https://host.com/path/to.git',

			// ユーザ名
			// Gitリポジトリのユーザ名を設定。
			'username' => 'user',

			// パスワード
			// Gitリポジトリのパスワードを設定。
			'password' => 'pass'
		),
	)
);
$plum->set_async_callbacks(array(
	'async' => function( $params ){
		/*
		async
		非同期で実行するコールバック関数を拡張します。
		ここで受け取った `$message` を、
		非同期に `$plum->async($params);` へ転送してください。
		*/
	},
	'broadcast' => function( $message ){
		/*
		メッセージをブラウザに送るコールバック関数を拡張します。
		ここで受け取った `$message` を、
		フロントエンドの `plum.broadcastMessage($message);` へ転送してください。
		*/
	}
));

$json = $plum->gpi( $_POST['data'] );

header('Content-type: application/json');
echo json_encode( $json );
```

非同期の再呼び出し

```php
<?php
require_once('./vendor/autoload.php');

$plum = new hk\plum\main(

	/* 中略 */

);

$json = $plum->async( $params );
```



#### デプロイ先のディレクトリに書き込み権限の付与

以下のディレクトリに実行ユーザの書き込み権限が無い場合は、権限を付与します。

- `staging_server->path` ・・・ ステージングサーバ(デプロイ先)のパス
- `data_dir` ・・・ データディレクトリのパス




## 更新履歴 - Change log

### pickles2/lib-plum v0.2.1 (リリース日未定)

- オプション `temporary_data_dir` を `data_dir` に改名した。
- ローカルマスターリポジトリでは `git fetch` まで実行し、 `git pull` しないようにした。ディスク容量の節約のため。
- UIの改善。各ステージング毎に詳細画面を追加した。
- 各ステージング毎にパスワードを設定できる機能を追加した。
- 時間がかかる処理を非同期に実行できるようになった。

### pickles2/lib-plum v0.2.0 (2020年11月7日)

- リモートリポジトリがローカルディスクにある場合に対応した。
- オプション `git->protocol`、 `git->host`、 `git->repository`、 `_GET`、 `_POST`、 `additional_params` を廃止、 `preview_server` を `staging_server` に改名した。
- オプション `temporary_data_dir` を追加した。
- クライアントサイドライブラリのファイル構成を変更した。
- CSS、JSの影響が外部に及ばないように隠蔽させた。
- JavaScript環境を明示的に呼び出すように変更した。
- GPIを追加。
- その他の細かい修正。

### pickles2/lib-plum v0.1.3 (2020年11月3日)

- 細かい不具合と内部コードの修正。

### pickles2/lib-plum v0.1.2 (2019年6月12日)

- オプション `additional_params` を追加。
- オプション `_GET`, `_POST` を省略可能とした。

### pickles2/lib-plum v0.1.1 (2018年10月19日)

- git リモートリポジトリ のIDとパスワードが設定されていない場合に、認証情報なしでアクセスするようになった。
- git repository のURLを解析してコンフィグを補完するようになった。 `https://user:pass@host.com/path/to.git` のような1つの完全な URL の形で設定できる。
- OSコマンドインジェクションの脆弱性に関する修正。

### pickles2/lib-plum v0.1.0 (2018年6月7日)

- Initial Release.


## ライセンス - License

Copyright (c)Kyota Hiyoshi, Tomoya Koyanagi, and Pickles Project<br />
MIT License https://opensource.org/licenses/mit-license.php

## 作者 - Author

- Kyota Hiyoshi <hiyoshi-kyota@imjp.co.jp>
- Tomoya Koyanagi <tomk79@gmail.com>
