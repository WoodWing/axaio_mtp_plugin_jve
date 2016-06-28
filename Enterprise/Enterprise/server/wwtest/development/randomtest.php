<?php
for( $i=1; $i<1000; $i++) {
	echo genRandomKey(40)."<br>";
}

function genRandomKey($sz = 20)
{
	$key = "";
	mt_srand((double)microtime()*1000000);
	$seed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
	for ($i=0;$i<$sz;$i++) {
		$it = mt_rand(0,61);
		$key .= $seed[$it];
	}
	return $key;
}
?>