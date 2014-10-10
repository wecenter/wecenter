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

class chapter_class extends AWS_MODEL
{
    public function get_chapter_list()
    {
        return $this->fetch_all('chapter', null, 'sort ASC');
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
            $chapter_list[$id] = $this->fetch_row('chapter', 'id = ' . $id);
        }

        return $chapter_list[$id];
    }

    public function get_data_list($chapter_id = null, $max_num = null, $sort = false)
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
                $data_list[$data_info['chapter_id']] = $data_info;
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
            return $this->update('chapter', $chapter_info, 'id = ' . $id);
        }
        else
        {
            return $this->insert('chapter', $chapter_info);
        }
    }

    public function remove_chapter($id)
    {
        if (!is_digits($id) OR !$this->delete('chapter', 'id = ' . $id))
        {
            return false;
        }

        @unlink(get_setting('upload_dir') . '/chapter/' . $id . '.jpg');

        return true;
    }

    public function set_chapter_sort($id, $sort)
    {
        if (!is_digits($id))
        {
            return false;
        }

        return $this->update('chapter', array(
            'sort' => intval($sort)
        ), 'id = ' . $id);
    }
}
