<?php
require_once('../../../vendor/autoload.php');
$conf = array();
$conf['git'] = array();
$conf['git']['url'] = __DIR__.'/../remote/';
?>
<!doctype html>
<html>
<head>
<title>Plum - develop</title>
<link rel="stylesheet" href="../../../dist/plum.css">
<script src="../../../dist/plum.js"></script>

<style>
:root{
	--px2-main-color: #f96;
}
body {
	background-color: #fff;
	cursor: default;
	color: #333;
}
</style>
</head>
<body>
<h1>plum</h1>

<?php
// Plum
$plum = new hk\plum\main(
	array(
		// POST
		'_POST' => $_POST,

		// GET
		'_GET' => $_GET,

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
// return: 結果表示用HTML
echo $plum->run();
?>

<script>
window.onload = function(){
	const plum = new window.Plum();
	plum.init();
};
</script>

</body>
</html>
