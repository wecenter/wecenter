<?php

$notify = <<<EOF
<li><a href="http://wenda.wecenter.com/question/14296" target="_blank">WeCenter 2.5 正式版发布</a></li>
<li><a href="http://wenda.wecenter.com/question/14310" target="_blank">WeCenter 2.5 IE8 补丁</a></li>
EOF;

$notify = str_replace(array("\n", "\r", "\t"), '', $notify);

echo $_GET['jsoncallback'] . '({
	"notify" : "' . addslashes($notify) . '",
})';

