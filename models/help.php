<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

if (!defined('IN_ANWSION'))
{
    die;
}

class help_class extends AWS_MODEL
{
    public function get_chapter_list($sort = true)
    {
        $sort = ($sort) ? 'sort ASC' : 'id ASC';

        $chapter_query = $this->fetch_all('help_chapter', null, $sort);

        if (!$chapter_query)
        {
            return false;
        }

        foreach ($chapter_query AS $chapter_info)
        {
            $chapter_list[$chapter_info['id']] = $chapter_info;
        }

        return $chapter_list;
    }

    public function get_chapter_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        static $chapter_list;

        if (!$chapter_list[$id])
        {
            $chapter_list[$id] = $this->fetch_row('help_chapter', 'id = ' . $id);
        }

        return $chapter_list[$id];
    }

    public function get_chapter_by_url_token($url_token)
    {
        return $this->fetch_row('help_chapter', 'url_token = "' . $this->quote($url_token) . '"');
    }

    public function get_data_list($chapter_id = null, $max_num = null, $sort = true)
    {
        if (isset($chapter_id))
        {
            if (!is_digits($chapter_id))
            {
                return false;
            }

            $where[] = 'chapter_id = ' . $chapter_id;
        }
        else
        {
            $where[] = 'chapter_id IS NOT NULL';
        }

        $article_list = $this->fetch_all('article', implode(' AND ', $where), 'sort ASC');

        if ($article_list)
        {
            foreach ($article_list AS $article_info)
            {
                $data_raw[] = array(
                    'type' => 'article',
                    'id' => $article_info['id'],
                    'title' => $article_info['title'],
                    'update_time' => $article_info['add_time'],
                    'chapter_id' => $article_info['chapter_id'],
                    'sort' => $article_info['sort']
                );
            }
        }

        $question_list = $this->fetch_all('question', implode(' AND ', $where), 'sort ASC');

        if ($question_list)
        {
            foreach ($question_list AS $question_info)
            {
                $data_raw[] = array(
                    'type' => 'question',
                    'id' => $question_info['question_id'],
                    'title' => $question_info['question_content'],
                    'update_time' => ($question_info['update_time']) ? $question_info['update_time'] : $question_info['add_time'],
                    'chapter_id' => $question_info['chapter_id'],
                    'sort' => $question_info['sort']
                );
            }
        }

        if (!$data_raw)
        {
            return false;
        }

        if ($chapter_id)
        {
            if ($sort)
            {
                $this->sort_list($data_raw);
            }

            $data_list = ($max_num) ? array_slice($data_raw, 0, $max_num) : $data_raw;
        }
        else
        {
            foreach ($data_raw AS $data_info)
            {
                $data_list[$data_info['chapter_id']][] = $data_info;
            }

            if ($sort)
            {
                foreach ($data_list AS $chapter_id => $data_raw)
                {
                    $data_list[$chapter_id] = $this->sort_list($data_raw);
                }
            }

            if ($max_num)
            {
                foreach ($data_list AS $chapter_id => $data_raw)
                {
                    $data_list[$chapter_id] = array_slice($data_raw, 0, $max_num);
                }
            }
        }

        return $data_list;
    }

    public function sort_list($data_raw)
    {
        if (!$data_raw OR !is_array($data_raw))
        {
            return false;
        }

        foreach ($data_raw as $key => $data_info)
        {
            $sort[$key] = $data_info['sort'];

            $update_time[$key] = $data_info['update_time'];
        }

        array_multisort($sort, SORT_ASC, SORT_NUMERIC, $update_time, SORT_DESC, SORT_NUMERIC, $data_raw);

        return $data_raw;
    }

    public function save_chapter($id = null, $title, $description = null, $url_token = null)
    {
        if (isset($id) AND !is_digits($id) OR !$title)
        {
            return false;
        }

        $chapter_info = array(
            'title' => htmlspecialchars($title),
            'description' => htmlspecialchars($description),
            'url_token' => $url_token
        );

        if ($id)
        {
            return $this->update('help_chapter', $chapter_info, 'id = ' . $id);
        }
        else
        {
            return $this->insert('help_chapter', $chapter_info);
        }
    }

    public function remove_chapter($id)
    {
        if (!is_digits($id) OR !$this->delete('help_chapter', 'id = ' . $id))
        {
            return false;
        }

        $this->query('UPDATE ' . $this->get_table('question') . ' SET `chapter_id` = NULL, `sort` = "0"', null, null, 'chapter_id = ' . $id);

        $this->query('UPDATE ' . $this->get_table('article') . ' SET `chapter_id` = NULL, `sort` = "0"', null, null, 'chapter_id = ' . $id);

        @unlink(get_setting('upload_dir') . '/chapter/' . $id . '-max.jpg');

        @unlink(get_setting('upload_dir') . '/chapter/' . $id . '-min.jpg');

        return true;
    }

    public function set_chapter_sort($id, $sort)
    {
        if (!is_digits($id) OR !is_digits($sort) OR $sort < 0 OR $sort > 99)
        {
            return false;
        }

        return $this->update('help_chapter', array(
            'sort' => intval($sort)
        ), 'id = ' . $id);
    }

    public function add_data($id, $type, $item_id)
    {
        $chapter_info = $this->get_chapter_by_id($id);

        if (!$chapter_info)
        {
            return false;
        }

        switch ($type)
        {
            case 'question':
                $question_info = $this->model('question')->get_question_info_by_id($item_id);

                if (!$question_info)
                {
                    return false;
                }

                $this->update('question', array('chapter_id' => $chapter_info['id']), 'question_id = ' . $question_info['question_id']);

                break;

            case 'article':
                $article_info =  $this->model('article')->get_article_info_by_id($item_id);

                if (!$article_info)
                {
                    return false;
                }

                $this->update('article', array('chapter_id' => $chapter_info['id']), 'id = ' . $article_info['id']);

                break;

            default:
                return false;

                break;
        }

        return true;
    }

    public function remove_data($type, $item_id)
    {
        switch ($type)
        {
            case 'question':
                $question_info = $this->model('question')->get_question_info_by_id($item_id);

                if (!$question_info OR !$question_info['chapter_id'])
                {
                    return false;
                }

                $this->query('UPDATE ' . $this->get_table('question') . ' SET `chapter_id` = NULL, `sort` = "0"', null, null, 'question_id = ' . $question_info['question_id']);

                break;

            case 'article':
                $article_info =  $this->model('article')->get_article_info_by_id($item_id);

                if (!$article_info OR !$article_info['chapter_id'])
                {
                    return false;
                }

                $this->query('UPDATE ' . $this->get_table('article') . ' SET `chapter_id` = NULL, `sort` = "0"', null, null, 'id = ' . $article_info['id']);

                break;

            default:
                return false;

                break;
        }

        return true;
    }

    public function set_data_sort($item_id, $type, $sort)
    {
        if (!is_digits($item_id) OR !is_digits($sort) OR $sort < 0 OR $sort > 99)
        {
            return false;
        }

        switch ($type)
        {
            case 'question':
                return $this->update('question', array('sort' => $sort), 'question_id = ' . $item_id);

                break;

            case 'article':
                return $this->update('article', array('sort' => $sort), 'id = ' . $item_id);

                break;

            default:
                return false;

                break;
        }
    }
}
