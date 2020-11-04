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

<script src="./index_files/jquery-3.5.1.min.js"></script>

<link rel="stylesheet" href="../../../dist/plum.css" />
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


<div id="cont-plum-test"></div>
<script>
window.onload = function(){
	const plum = new window.Plum(
		document.getElementById('cont-plum-test'),
		{
			'gpiBridge': function(data, callback){
				$.ajax({
					'url': './api.php',
					'method': 'POST',
					'data': {
						'data': data
					},
					'success': function(result){
						console.log('------------', result);
						callback(result);
					}
				});
			}
		}
	);
	plum.init();
};
</script>


</body>
</html>
