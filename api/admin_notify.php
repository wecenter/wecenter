<?php

$notify = <<<EOF
<li><a href="http://www.anwsion.com/downloads/" target="_blank">WeCenter 2.2.6 发布</a></li>
EOF;

$notify = str_replace(array("\n", "\r", "\t"), '', $notify);

echo $_GET['jsoncallback'] . '({
	"notify" : "' . addslashes($notify) . '",
})';

