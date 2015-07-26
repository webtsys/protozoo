<?php

/*$arr_composer['require']['symfony/process']='^2.7';
$arr_composer['require']['league/flysystem-sftp']='^1.0';
$arr_composer['require']['phpseclib/phpseclib']='^0.3.10';*/

$arr_composer['repositories'][]=array('type' => 'vcs', 'url' => 'https://github.com/phangoapp/phaexec.git');
$arr_composer['require']['phangoapp/phaexec']='dev-master';

$arr_composer['require']['symfony/process']='^2.7';

?>
