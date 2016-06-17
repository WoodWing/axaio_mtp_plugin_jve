<html>
<head>
<title>BOUNCE</title> 
<?php 
list($usec, $sec) = explode(" ",microtime()); 
$now = intval(1000*((float)$usec + (float)$sec)); 
$i = (isset($_GET['i']) ? intval($_GET['i']) : 0);
$n = (isset($_GET['n']) ? intval($_GET['n']) : 10);
$s = (isset($_GET['s']) ? intval($_GET['s']) : 0);
$r = (isset($_GET['r']) ? intval($_GET['r']) : 0);
$t = (isset($_GET['t']) ? intval($_GET['t']) : 0);
?>
<?php if (0 == $i) { ?>
<meta http-equiv="Refresh" content="0; URL=bounce.php?s=<?php echo $s; ?>&r=<?php echo $r; ?>&i=1&t=<?php echo $now; ?>">
<?php } else if ($i < $n) { ?>
<meta http-equiv="Refresh" content="0; URL=bounce.php?s=<?php echo $s; ?>&r=<?php echo $r; ?>&i=<?php echo 1+$i; ?>&t=<?php echo $t; ?>">
<?php } else { ?>
<?php
$e = $now - $t;
$r = $r + $n;
$s = $s + $e;
?> 
<meta http-equiv="Refresh" content="3; URL=bounce.php?s=<?php echo $s; ?>&r=<?php echo $r; ?>">
<?php } ?>
</head>
<body>

<?php if ($i < $n) { ?>
<h1>BOUNCING</h1>
<?php } else { ?>
<h1>BOUNCE</h1>
<table border=1>
	<tr><th>milliseconds</th><th>requests</th></tr>
	<tr><td><?php echo intval($e/$n); ?></td><td><?php echo $n; ?></td><td>latest</td></tr>
	<tr><td><?php echo intval($s/$r); ?></td><td><?php echo $r; ?></td><td>cumulative</td></tr>
</table>
<?php } ?>

</body>
</html>