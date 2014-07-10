<?php
require_once '../version.php';

echo json_encode(array(
    'version' => G_VERSION,
    'build_day' => G_VERSION_BUILD
));
