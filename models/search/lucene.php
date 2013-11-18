<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
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

class search_lucene_class extends AWS_MODEL
{
	var $index_path;
	var $lucene;
	
	public function setup()
	{
		$this->index_path = ROOT_PATH . '/cache/lucene/index/';
		
		Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Chinese()); 
		
		if (!is_dir($this->index_path))
		{
			$this->lucene = Zend_Search_Lucene::create($this->index_path);
		}
		else
		{
			$this->lucene = Zend_Search_Lucene::open($this->index_path);
		}
	}
	
	public function push_index($tag, $title, $item_id, $data = null)
	{
		$this->delete_index($tag, $item_id);
		
		$document = new Zend_Search_Lucene_Document();
		
		$document->addField(Zend_Search_Lucene_Field::Keyword('tag', $tag));
		$document->addField(Zend_Search_Lucene_Field::Keyword('item_id', $item_id));
		
		$document->addField(Zend_Search_Lucene_Field::Text('title', $title));
		$document->addField(Zend_Search_Lucene_Field::Text('data', serialize($data)));
		
		return $this->lucene->addDocument($document);
	}
	
	public function delete_index($tag, $item_id)
	{
		if ($results = $this->lucene->find('tag:' . $this->quote($tag) . ' AND item_id:' . intval($item_id)))
		{
			foreach ($results as $result)
			{
				$this->lucene->delete($result->id);
			}
		}
	}
	
	public function find($keywords, $limit = null)
	{
		Zend_Search_Lucene::setResultSetLimit($limit);
		
		if ($results = $this->lucene->find($keywords))
		{
			foreach ($results as $result)
			{
				$data[] = array(
					'tag' => $result->tag,
					'item_id' => $result->item_id,
					'title' => $result->title,
					'url' => get_js_url($result->url),
					'data' => unserialize($result->data)
				);
			}
		}
		
		return $data;
	}
	
	public function search($q, $limit = null, $tag = null)
	{
		if (is_array($q))
		{
			$q = implode(' AND title:', $q);
		}
		
		if ($tag)
		{
			$search_query = 'tag:' . $tag . ' AND title:' . $q;
		}
		else
		{
			$search_query = $q;
		}
		
		Zend_Search_Lucene::setDefaultSearchField('title');
		
		if (is_numeric($limit))
		{
			$result = $this->find($search_query, $limit);
		}
		else
		{
			$result = $this->find($search_query, 1000);
		}
		
		if ($result)
		{
			foreach ($result AS $key => $val)
			{
				switch ($val['tag'])
				{
					case 'article':
						$search_result[$key] = array(
							'tag' => $val['tag'],
							'title' => $val['title'],
							'id' => $val['item_id'],
							'comments' => $val['data']['comments'],
							'views' => $val['data']['views']
						);
					break;
					
					case 'question':
						$search_result[$key] = array(
							'tag' => $val['tag'],
							'question_content' => $val['title'],
							'question_id' => $val['item_id'],
							'best_answer' => $val['data']['best_answer'],
							'answer_count' => $val['data']['answer_count'],
							'comment_count' => $val['data']['comment_count'],
							'focus_count' => $val['data']['focus_count'],
							'agree_count' => $val['data']['agree_count']
						);
					break;
				}
			}
		}
		
		return $search_result;
	}
}