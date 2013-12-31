<?php

$notify = <<<EOF
<li><a href="http://wenda.wecenter.com/question/13876" target="_blank">WeCenter 2.5 BETA 5 发布</a></li>
<li><a href="http://www.anwsion.com/downloads/" target="_blank">WeCenter 2.2.7 发布</a></li>
EOF;

$notify = str_replace(array("\n", "\r", "\t"), '', $notify);

echo $_GET['jsoncallback'] . '({
	"notify" : "' . addslashes($notify) . '",
})';

