<?php

if (!defined('IN_ANWSION'))
{
    die;
}

$receiving_email_global_config = get_setting('receiving_email_global_config');

if ($receiving_email_global_config['enabled'] == 'Y')
{
    $receiving_email_global_config['enabled'] == 'question';

    $this->model('setting')->set_vars(array(
        'receiving_email_global_config' => $receiving_email_global_config
    ));
}
