<?php

// WeCenter 管理页面
if (!defined('IN_ANWSION')) {
    exit('Access Denied');
}
include AWS_PATH . '../app/shenjianshou/functions.php';

class main extends AWS_CONTROLLER {

    public function get_access_rule() {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'] = array(
            'article_post',
            'discussion_post',
            'details',
            'version'
        );
        return $rule_action;
    }

    //默认地址
    public function index_action() {
        Header("Location:http://www.shenjianshou.cn/");
    }

    //获取详细信息的地址
    public function details_action() {
        $this->validation();
        if ($_POST["type"] == "cate") {
            $ret = array(array("value" => "", "text" => urlencode("选择发布分类")));
            $cates = $this->model('category')->fetch_all("category");

            foreach ($cates as $cate) {
                array_push($ret, array("value" => urlencode($cate["title"]), "text" => urlencode($cate["title"])));
            }
            ta_success($ret);
        }
    }

    //获取版本信息的地址
    public function version_action() {
        $this->validation();
        $version = array(
            'protocol' => '1',
            'protocolVersion' => '1',
            'supportStdVersion' => array(
                'article' => '1.0.0',
                'question' => '1.0.0'
            ),
            'php' => PHP_VERSION,
            'supportVersion' => '3.1.9',
            'version' => '3.1.3',
            'pubVersion' => '3.1.3',
            'versionDetail' => array('wecenter' => G_VERSION), //set by this service
            'otherInfo' => array()
        );
        ta_success($version);
    }

    //验证方法
    private function validation() {
        if (empty($_POST['__sign'])) {
            ta_fail(TA_ERROR_INVALID_PWD, "password is empty", "发布密码为空");
        }
        if (file_exists(AWS_PATH . '/config/ta_config.php')) {
            require AWS_PATH . '/config/ta_config.php';
        }

        if (isset($config['ta_config'])) {
            $ta_config = $config['ta_config'];
        } else {
            $ta_config = array(
                'ta_password' => "ltm",
            );
        }

        if ($_POST['__sign'] != $ta_config["ta_password"]) {
            ta_fail(TA_ERROR_INVALID_PWD, "password is wrong", "发布密码填写错误");
        }
    }

    //发布文章地址
    public function article_post_action() {
        $this->validation();
        $title = $_POST['article_title'];
        $content = $_POST['article_content'];
        if (empty($title)) {
            ta_fail(TA_ERROR_MISSING_FIELD, 'article_title is empty', "缺少文章标题");
        }
        if (empty($content)) {
            ta_fail(TA_ERROR_MISSING_FIELD, 'article_content is empty', "缺少文章内容");
        }

        $author = isset($_POST['article_author']) ? $_POST['article_author'] : '';

        if (!empty($author)) {
            $user = $this->model('account')->get_user_info_by_username($author);
        }

        if (!empty($user)) {
            $user_id = $user['uid'];
        } else if (!empty($author)) {
            $user_id = $this->add_user($author);
        } else {
            $user_id = $this->model('account')->fetch_one('users', 'uid');
        }
        if (empty($user_id)) {
            ta_fail(TA_ERROR_ERROR, 'user_id is empty', "请先设置文章作者");
        }

        $categories = json_decode($_POST['article_categories'], true);
        $category_id = 1; //默认分类
        $cate_parent_id = 0;
        $count = count($categories);
        $i = 0;
        for ($i = 0; $i < $count; $i++) {
            $category_title = $categories[$i];
            $where = "title='{$category_title}' and parent_id={$cate_parent_id}";
            $cate = $this->model('category')->fetch_row('category', $where);
            if (!empty($cate)) {
                $category_id = $cate['id'];
                $cate_parent_id = $cate['id'];
            } else {
                break;
            }
        }
        for (; $i < $count; $i++) {
            $category_title = $categories[$i];
            $category_id = $this->model('category')
                    ->add_category('question', $category_title, $cate_parent_id);
            if (empty($category_id))
                break;
            $cate_parent_id = $category_id;
        }


        $topics = json_decode($_POST['article_topics'], true);
        $article_title = $this->parse_html($title);
        $article_content = $this->parse_html($content);
        $article_id = $this->model('publish')
                ->publish_article($article_title, $article_content, $user_id, $topics, $category_id);
        if (empty($article_id)) {
            ta_fail(TA_ERROR_ERROR, 'insert failed', "文章插入失败");
        }

        if (isset($_POST['article_view_count']) && $_POST['article_view_count']) {
            $this->model('publish')->shutdown_update('article', array(
                'views' => intval($_POST['article_view_count'])
                    ), 'id = ' . $article_id);
            $this->model('posts')->shutdown_update('posts_index', array(
                'view_count' => intval($_POST['article_view_count'])
                    ), "post_id = " . intval($article_id) . " AND post_type = 'article'");
        }

        $publish_time = isset($_POST['article_publish_time']) ? intval($_POST['article_publish_time']): time();
        $userPublishTime = isset($_POST['publishTime']) ? intval($_POST['publishTime']) : 0;
        if ($userPublishTime == 2) {
            $add_time = time();
        } else {
            if (!empty($publish_time)) {
                if (is_numeric($publish_time)) {
                    $add_time = intval($publish_time);
                } else {
                    $add_time = intval(strtotime($publish_time));
                }
            }
        }
        if (!empty($add_time)) {
            $update_data = array('add_time' => $add_time);
            $where = "id={$article_id}";
            $this->model('article')->update('article', $update_data, $where);
        }

        ta_success(array("url" => base_url() . "/" . G_INDEX_SCRIPT . "article/" . $article_id));
    }

    //发布问答地址
    public function discussion_post_action() {
        $this->validation();
        $question = isset($_POST['question_title']) ? $_POST['question_title'] : '';
        if (empty($question)) {
            ta_fail(TA_ERROR_MISSING_FIELD, 'question_title is empty', "缺少问答标题");
        }
        $question_author = isset($_POST['question_author']) ? $_POST['question_author'] : '';
        $question_detail = isset($_POST['question_detail']) ? $_POST['question_detail'] : '';
        $question_avatar = isset($_POST['question_author_avatar']) ? $_POST['question_author_avatar'] : '';
        $question_time = isset($_POST['question_publish_time']) ? intval($_POST['question_publish_time']) : time() - 1000;

        $topics = json_decode((isset($_POST['question_topics']) ? $_POST['question_topics'] : ''), true);
        $answers_json = preg_replace("/[\r\n\t]/", '', $_POST['question_answer']);
        $answers = json_decode($answers_json, true);

        $user_id = 0;
        $anonymous = 1;

        if (!empty($question_author) && strstr($question_author, "匿名") === false) {
            $user = $this->model('account')->get_user_info_by_username($question_author);
            if (!empty($user)) {
                $user_id = $user['uid'];
                $anonymous = 0;
            } else {
                $user_id = $this->add_user($question_author);
                if (!empty($user_id)) {
                    $anonymous = 0;
                }
            }
            if (!empty($question_avatar) && $anonymous == 0) {
                $this->set_user_avatar($user_id, $question_avatar);
            }
        }
        //insert question
        $q_title = $this->parse_html($question);
        $q_content = $this->parse_html($question_detail);
        $question_id = $this->model('question')
                ->save_question($q_title, $q_content, $user_id, $anonymous);
        if (empty($question_id)) {
            ta_fail(TA_ERROR_ERROR, 'insert fail', "问答插入失败");
        }

        ACTION_LOG::save_action($user_id, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_QUESTION, $q_content, $q_content, null, $anonymous);

        $categories = json_decode((isset($_POST['question_categories']) ? $_POST['question_categories'] : ''), true);
        $category_id = 1; //默认分类
        $cate_parent_id = 0;
        $count = count($categories);
        $i = 0;
        for ($i = 0; $i < $count; $i++) {
            $category_title = $categories[$i];
            $where = "title='{$category_title}' and parent_id={$cate_parent_id}";
            $cate = $this->model('category')->fetch_row('category', $where);
            if (!empty($cate)) {
                $category_id = $cate['id'];
                $cate_parent_id = $cate['id'];
            } else {
                break;
            }
        }
        for (; $i < $count; $i++) {
            $category_title = $categories[$i];
            $category_id = $this->model('category')
                    ->add_category('question', $category_title, $cate_parent_id);
            if (empty($category_id))
                break;
            $cate_parent_id = $category_id;
        }

        if ($category_id) {
            $update_data = array(
                'category_id' => intval($category_id)
            );
            $where = 'question_id = ' . intval($question_id);
            $this->model('question')->update('question', $update_data, $where);
        }

        //insert topics
        if ($topics && count($topics) && is_array($topics)) {
            foreach ($topics as $topic) {
                if (!$topic) {
                    continue;
                }
                $topic_id = $this->model('topic')->save_topic($topic);
                if (!empty($topic_id)) {
                    $this->model('topic')->save_topic_relation(0, $topic_id, $question_id, 'question');
                }
            }
        }

        //insert answers
        $last_answer = 0;
        $count = count($answers);
        $table = $this->model('question')->get_table('question');
        $first_answer_time = 0;

        for ($i = 0; $i < $count; $i++) {
            $answer = $answers[$i];
            $user_id = 0;
            $anonymous = 1;

            $author = isset($answer['question_answer_author']) ? $answer['question_answer_author'] : '';
            $avatar = isset($answer['question_answer_author_avatar']) ? $answer['question_answer_author_avatar'] : '';

            if (strstr($author, "知乎") === false &&
                    strstr($author, "匿名") === false) {
                $user = $this->model('account')->get_user_info_by_username($author);
                if (!empty($user)) {
                    $user_id = $user['uid'];
                    $anonymous = 0;
                } else {
                    $user_id = $this->add_user($author);
                    if (!empty($user_id)) {
                        $anonymous = 0;
                    }
                }

                if (!empty($avatar) && $anonymous == 0) {
                    $this->set_user_avatar($user_id, $avatar);
                }
            }


            $answer_content = $this->parse_html($answer['question_answer_content']);
            $answer_id = $this->model('answer')
                    ->save_answer($question_id, $answer_content, $user_id, $anonymous);
            if (!empty($answer_id)) {

                $agree_count = intval($answer['question_answer_agree_count']);
                $publish_time = isset($answer['question_answer_publish_time']) ? intval($answer['question_answer_publish_time']) : time() - 2000;
                $update_args = array('agree_count' => $agree_count);

                if (!empty($publish_time)) {
                    $update_args['add_time'] = $publish_time;
                    if ($first_answer_time == 0 || $first_answer_time > $publish_time) {
                        $first_answer_time = $publish_time;
                    }
                }
                $this->model('answer')->update_answer_by_id($answer_id, $update_args);
                $last_answer = $answer_id;

                ACTION_LOG::save_action($user_id, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ANSWER_QUESTION, '', $answer_id, null, $anonymous);

                ACTION_LOG::save_action($user_id, $answer_id, ACTION_LOG::CATEGORY_ANSWER, ACTION_LOG::ANSWER_QUESTION, '', $question_id, null, $anonymous);

                $comments = isset($answer['question_answer_comment']) ? $answer['question_answer_comment']: array();
                $comments_count = count($comments);
                for ($j = 0; $j < $comments_count; $j++) {
                    $comment = $comments[$j];
                    $cuser_id = 0;
                    $canonymous = TRUE;
                    $cauthor = isset($comment['question_answer_comment_author']) ? $comment['question_answer_comment_author'] : '';
                    $cavatar = isset($comment['question_answer_comment_author_avatar']) ? $comment['question_answer_comment_author_avatar'] : '';

                    if (strstr($cauthor, "知乎") === false &&
                            strstr($cauthor, "匿名") === false) {
                        $cuser = $this->model('account')->get_user_info_by_username($cauthor);
                        if (!empty($cuser)) {
                            $cuser_id = $cuser['uid'];
                            $canonymous = FALSE;
                        } else {
                            $cuser_id = $this->add_user($cauthor);
                            if (!empty($cuser_id)) {
                                $canonymous = FALSE;
                            }
                        }
                        if (!empty($cavatar) && !$canonymous) {
                            $this->set_user_avatar($cuser_id, $cavatar);
                        }
                    }


                    //TODO 匿名用户评论的用户名生成机制
                    if (!$canonymous) {
                        $comment_content = $this->parse_html($comment['question_answer_comment_content']);
                        $comment_id = $this->model('answer')
                                ->insert_answer_comment($answer_id, $cuser_id, $comment_content);
                        if (!empty($comment_id)) {
                            $cpublish_time = isset($comment['question_answer_comment_publish_time']) ? intval($comment['question_answer_comment_publish_time']) : time() - 1500;
                            if (!empty($cpublish_time)) {
                                $update_data = array(
                                    'time' => intval($cpublish_time)
                                );
                                $where = "id = " . intval($comment_id);
                                $this->model('answer')
                                        ->shutdown_update('answer_comments', $update_data, $where);
                            }
                            ACTION_LOG::save_action($user_id, $question_id, ACTION_LOG::CATEGORY_COMMENT, ACTION_LOG::ADD_COMMENT_ARTICLE, '', $comment_id, null, $anonymous);
                        }
                    }
                }
            }

            $sql = "UPDATE {$table} SET view_count=view_count+1 WHERE question_id={$question_id}";
            $this->model('question')->shutdown_query($sql);
        }

        if ($first_answer_time > 0 && (empty($question_time) || $question_time > $first_answer_time)) {
            //修复答案时间早于问题时间
            $question_time = $first_answer_time - 60; //60秒前
        }

        if (!empty($question_time)) {
            $update_data = array(
                'add_time' => intval($question_time),
                'update_time' => intval($question_time)
            );
            $where = 'question_id = ' . intval($question_id);
            $this->model('question')->update('question', $update_data, $where);
        }

        $this->model('question')->save_last_answer($question_id, $last_answer);
        $this->model('posts')->set_posts_index($question_id, 'question');

        if (isset($_POST['question_view_count']) && $_POST['question_view_count']) {
            $this->model('question')->shutdown_update('question', array(
                'view_count' => intval($_POST['question_view_count'])
                    ), 'question_id = ' . $question_id);
            $this->model('posts')->shutdown_update('posts_index', array(
                'view_count' => intval($_POST['question_view_count'])
                    ), "post_id = " . intval($question_id) . " AND post_type = 'question'");
        }

        ta_success(array("url" => base_url() . "/" . G_INDEX_SCRIPT . "question/" . $question_id));
    }

    private function parse_html($text) {
        $recursive_tags = array(
            //'#\s*?<div(( .*?)?)>(.*?)</div>\s*?#si' => '\\3',
            //'#<span(( .*?)?)>(.*?)</span>#si' => '\\3',
            '#<ul(( .*?)?)>(.*?)</ul>#si' => '[list]\\3[/list]',
            '#<ol(( .*?)?)>(.*?)</ol>#si' => '[list=1]\\3[/list]',
                //'#<font(.*?)>(.*?)</font>#si' => '\\2',
        );
        $tags = array(
            '#\s*?<strong>(.*?)</strong>\s*?#si' => '[b]\\1[/b]',
            '#<b(( .*?)?)>(.*?)</b>#si' => '[b]\\3[/b]',
            '#<em(( .*?)?)>(.*?)</em>#si' => '[i]\\3[/i]',
            '#<i(( .*?)?)>(.*?)</i>#si' => '[i]\\3[/i]',
            '#<u(( .*?)?)>(.*?)</u>#si' => '[u]\\3[/u]',
            '#<s(( .*?)?)>(.*?)</s>#si' => '[s]\\3[/s]',
            '#<p(( .*?)?)>(.*?)</p>#si' => "\\3\n",
            //'#<small(.*?)>(.*?)</small>#si' => '\\2',
            //'#<big(.*?)>(.*?)</big>#si' => '\\2',
            '#<img (.*?)src="(.*?)"(.*?)>#si' => "[img]\\2[/img]\n",
            '#<a (.*?)href="(.*?)mailto:(.*?)"(.*?)>(.*?)</a>#si' => '\\3',
            '#<a (.*?)href="(.*?)"(.*?)>(.*?)</a>#si' => '[url=\\2]\\4[/url]',
            '#<code(( .*?)?)>(.*?)</code>#si' => '[code]\\3[/code]',
            '#<iframe style="(.*?)" id="ytplayer" type="text/html" width="534" height="401" src="(.*?)/embed/(.*?)" frameborder="0"/></iframe>#si' => '[youtube]\\3[/youtube]',
            '#\s*?<br(.*?)>\s*?#si' => "\n",
            '#<h2(( .*?)?)>(.*?)</h2>#si' => '[h1]\\3[/h1]',
            '#<h3(( .*?)?)>(.*?)</h3>#si' => '[h2]\\3[/h2]',
            '#<h4(( .*?)?)>(.*?)</h4>#si' => '[h3]\\3[/h3]',
            '#<li(( .*?)?)>(.*?)</li>#si' => '[*]\\3[/*]',
            '#<center(( .*?)?)>(.*?)</center>#si' => '[center]\\3[/center]',

            //'#<p(( .*?)?)>(.*?)</p>#si' => '[code \2]\\3[code]',
            '#<blockquote(( .*?)?)>(.*?)</blockquote>#si' => '[quote]\\3[/quote]',
            //'#<pre>(.*?)</pre>#si' => '\\1',
            '#<noscript(( .*?)?)>(.*?)</noscript>#si' => '\\3',
            '#<object(.*?)>.*?<param .*?name="movie"[^<]*?value="(.*?)".*?(></param>|/>|>).*?</object>#si' => '[video]\\2[/video]',
            '#<object(.*?)>.*?<param .*?value="(.*?)"[^<]*?name="movie".*?(></param>|/>|>).*?</object>#si' => '[video]\\2[/video]',
            '#<embed (.*?)src="([^<]*?)"[^<]*?flashvars="([^<]*?)"([^<]*?)(></embed>|/>|>)#si' => '[video]\\2?\\3[/video]',
            '#<embed (.*?)src="([^<]*?)"([^<]*?)(></embed>|/>|>)#si' => '[video]\\2[/video]',
        );
        foreach ($recursive_tags as $search => $replace) {
            $text2 = $text;
            do {
                $text = $text2;
                $text2 = preg_replace($search, $replace, $text);
            } while ($text2 != $text);
        }
        foreach ($tags as $search => $replace) {
            $text = preg_replace($search, $replace, $text);
        }

        //$html = $this->decode_html($text);
        return strip_tags($text);
    }

    private function decode_html($text) {
        $text2 = $text;
        do {
            $text = $text2;
            $text2 = html_entity_decode($text, ENT_COMPAT, "UTF-8");
        } while ($text2 != $text);
        return $text;
    }

    function add_user($name) {
        $uid = $this->model('account')->insert_user($name, '123456');
        $this->model('account')->update('users', array(
            'group_id' => 4,
            'reputation_group' => 5,
            'invitation_available' => get_setting('newer_invitation_num'),
            'is_first_login' => 1
                ), 'uid = ' . intval($uid));

        return $uid;
    }

    function set_user_avatar($user_id, $avatar) {
        $image_url = ta_redirect_url($avatar);
        if (isset($image_url['realurl']) && $image_url['realurl'] !== false) {
            $this->model('account')->associate_remote_avatar($user_id, $image_url['realurl']);
        }
    }

}
