<?php

$output = shell_exec('df -h');
$outputArray=explode($output, "root");

$outputArray2=explode($outputArray[1], "/");

print_r($output);

die;

$output=$outputArray2[0];
echo $outputArray2[0]."<br>";
echo "<pre>$output</pre>";

?>