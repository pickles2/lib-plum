<?php

require_once('../../../vendor/autoload.php');
$conf = array();
$conf['git'] = array();
$conf['git']['url'] = __DIR__.'/../remote/';

// Plum
$plum = new hk\plum\main(
	array(
		// 一時データ保存ディレクトリ
		'temporary_data_dir' => __DIR__.'/../temporary_data_dir/',

		// 追加パラメータ
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
				'path' => __DIR__.'/../repos/preview1/',
				'url' => 'http://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME'])).'/repos/preview1/tests/testdata/contents/',
			),
			array(
				'name' => 'preview2',
				'path' => __DIR__.'/../repos/preview2/',
				'url' => 'http://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME'])).'/repos/preview2/tests/testdata/contents/',
			),
			array(
				'name' => 'preview3',
				'path' => __DIR__.'/../repos/preview3/',
				'url' => 'http://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME'])).'/repos/preview3/tests/testdata/contents/',
			)
		),

		// Git情報定義
		'git' => $conf['git'],
	)
);

// JSON出力
$json = $plum->gpi();

header('Content-type: application/json');
echo json_encode( $json );
?>
