<?php

if (!defined('IN_ANWSION'))
{
    die;
}

$question_list = $this->model('question')->query_all('SELECT `question_id`, `unverified_modify` FROM ' . get_table('question') . ' WHERE unverified_modify IS NOT NULL AND unverified_modify <> "a:0:{}"');

if ($question_list)
{
    foreach ($question_list AS $question_info)
    {
        $counter = 0;

        $question_info['unverified_modify'] = @unserialize($question_info['unverified_modify']);

        if ($question_info['unverified_modify'])
        {
            foreach ($question_info['unverified_modify'] AS $val)
            {
                $counter = $counter + sizeof($val);
            }

            $this->model('question')->update('question', array(
                'unverified_modify_count' => $counter
            ), 'question_id = ' . $question_info['question_id']);
        }
    }
}
