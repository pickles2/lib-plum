<?php
require_once('../../../vendor/autoload.php');

/**
 * Plum
 */
$plum = new hk\plum\main(
	array(
		// データディレクトリ
		'data_dir' => __DIR__.'/../temporary_data_dir/',

		// プレビューサーバ定義
		'staging_server' => array(

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
				'url' => 'http://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME'])).'/repos/preview1/',
			),
			array(
				'name' => 'preview2',
				'path' => __DIR__.'/../repos/preview2/',
				'url' => 'http://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME'])).'/repos/preview2/',
			),
			array(
				'name' => 'preview3',
				'path' => __DIR__.'/../repos/preview3/',
				'url' => 'http://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME'])).'/repos/preview3/',
			)
		),

		// Git情報定義
		'git' => array(
			'url' => __DIR__.'/../remote/',
		),
	)
);

// JSON出力
$json = $plum->gpi( $_POST['data'] );

header('Content-type: application/json');
echo json_encode( $json );
