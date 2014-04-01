<?php

$notify = <<<EOF
<li><a href="http://wenda.wecenter.com/question/16237" target="_blank">WeCenter 2.5.9 发布</a></li>
EOF;

$notify = str_replace(array("\n", "\r", "\t"), '', $notify);

echo $_GET['jsoncallback'] . '({
	"notify" : "' . addslashes($notify) . '",
})';

