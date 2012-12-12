<?php

$notify = <<<EOF
<li><a href="http://www.anwsion.com/downloads/" target="_blank">Anwsion 1.1 RC 2 版本发布</a></li>
EOF;

$notify = str_replace(array("\n", "\r", "\t"), '', $notify);

echo $_GET['jsoncallback'] . '({
	"notify" : "' . addslashes($notify) . '",
})';
