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


<div id="cont-plum-test"></div>
<script>
window.onload = function(){
	const plum = new window.Plum( document.getElementById('cont-plum-test') );
	plum.init();
};
</script>


</body>
</html>
