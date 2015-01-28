<?php

$notify = <<<EOF
<li><a href="http://wenda.wecenter.com/question/21079" target="_blank">WeCenter 3.0.2 发布</a></li>
EOF;

$notify = str_replace(array("\n", "\r", "\t"), '', $notify);

echo $_GET['jsoncallback'] . '({
	"notify" : "' . addslashes($notify) . '",
})';

