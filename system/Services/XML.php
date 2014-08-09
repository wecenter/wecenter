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

/* Example Usage:
* <code>
* <productlist name="myname" version="1.0">
*  <productgroup name="thisgroup">
*   <product id="1.0">
*    <description>This is a descrption</description>
*    <title>Baked Beans</title>
*    <room store="1">103</room>
*   </product>
*	 <product id="2.0">
*    <description>This is another descrption</description>
*    <title>Green Beans</title>
*    <room store="2">104</room>
*   </product>
*  </productgroup>
* </productlist>
*
* Creating...
* $xml = new Services_XML('utf-8');
* $xml->new_document();
*
* /* Create a root element * /
* $xml->add_element('productlist', '', array('name' => 'myname', 'version' => '1.0'));
* /* Add a child.... * /
* $xml->add_element('productgroup', 'productlist', array('name' => 'thisgroup'));
* $xml->add_element_as_record('productgroup',
* 									array('product', array('id' => '1.0')),
* 											array('description' => array('This is a description'),
* 												   'title'		 => array('Baked Beans'),
* 												   'room'		 => array('103', array('store' => 1))
* 												)
* 						);
* $xml->add_element_as_record('productgroup',
* 									array('product', array('id' => '2.0')),
* 											array('description' => array('This is another description'),
* 												   'title'		 => array('Green Beans'),
* 												   'room'		 => array('104', array('store' => 2))
* 												)
* 						);
*
* $xml_data = $xml->fetch_document();
*
* /*Convering XML into an array * /
* $xml->load_xml($xml_data);
*
* /* Grabbing specific data values from all 'products'... * /
* foreach($xml->fetch_elements('product') as $products)
* {
* 	print $xml->fetch_item($products, 'title') . "\\";
* }
*
* /* Prints... * /
* Baked Beans
* Green Beans
*
* /* Print all array data - auto converts XML_TEXT_NODE and XML_CDATA_SECTION_NODE into #alltext for brevity * /
* print_r($xml->fetch_xml_as_array());
*
* /* Prints * /
* Array
* (
*     [productlist] => Array
*         (
*             [@attributes] => Array
*                 (
*                     [name] => myname
*                     [version] => 1.0
*                )
*             [productgroup] => Array
*                 (
*                     [@attributes] => Array
*                         (
*                             [name] => thisgroup
*                        )
*                     [product] => Array
*                         (
*                             [0] => Array
*                                 (
*                                     [@attributes] => Array
*                                         (
*                                             [id] => 1.0
*                                        )
*                                     [description] => Array
*                                         (
*                                             [#alltext] => This is a description
*                                        )
*                                     [title] => Array
*                                         (
*                                             [#alltext] => Baked Beans
*                                        )
*                                     [room] => Array
*                                         (
*                                             [@attributes] => Array
*                                                 (
*                                                     [store] => 1.0
*                                                )
*                                             [#alltext] => 103
*                                        )
*                                )
*                             [1] => Array
*                                 (
*                                     [@attributes] => Array
*                                         (
*                                             [id] => 2.0
*                                        )
*                                     [description] => Array
*                                         (
*                                             [#alltext] => This is another description
*                                        )
*                                     [title] => Array
*                                         (
*                                             [#alltext] => Green Beans
*                                        )
*                                     [room] => Array
*                                         (
*                                             [@attributes] => Array
*                                                 (
*                                                     [store] => 1.0
*                                                )
*                                             [#alltext] => 104
*                                        )
*                                )
*                        )
*                )
*        )
*)
*
* </code>
*
*/

class Services_XML
{
    /**
     * XML 文档编码
     *
     * @var		string
     */
    protected $_xml_char_set = 'utf-8';

    /**
     * 当前 XML 文档对象
     *
     * @var		object
     */
    protected $_dom;

    /**
     * 当前 XML 文档对象数组
     *
     * @var		array
     */
    protected $_dom_objects = array();

    /**
     * XML 数组
     *
     * @var		array
     */
    protected $_xml_array = array();

    /**
     * 构建
     *
     * @param	string
     * @return	@e void
     */
    public function __construct($char_set)
    {
        $this->_xml_char_set = strtolower($char_set);
    }

    /**
     * 建立新文档
     *
     * @return	@e void
     */
    public function new_document()
    {
        $this->_dom = new DOMDocument('1.0', 'utf-8');
    }

    /**
     * 载入文档
     *
     * @return	@e string
     */
    public function fetch_document()
    {
        $this->_dom->formatOutput = TRUE;
        return $this->_dom->saveXML();
    }

    /**
     * 添加元素至文档
     *
     * @param	string
     * @param	string
     * @param	array
     * @param	string
     * @return	@e void
     */
    public function add_element($tag, $parent_tag = '', $attributes = array(), $namespace_uri = '')
    {
        $this->_dom_objects[$tag] = $this->_node($parent_tag)->appendChild(new DOMElement($tag, '', $namespace_uri));

        $this->add_attributes($tag, $attributes);
    }

    /**
     * 添加元素到文档中的一行记录
     * 你可以设置 $tag 为字符串或一个数组
     *
     * $xml->add_element_as_record('parentTag', 'myTag', $data);
     * $xml->add_element_as_record('parentTag', array('myTag', array('attr' => 'value')), $data);
     *
     * @param	string
     * @param	mixed
     * @param	array
     * @param	string
     * @return	@e void
     */
    public function add_element_as_record($parent_tag, $tag, $data, $namespace_uri = '')
    {
        /* A little set up if you please... */
        $_tag      = $tag;
        $_tag_attr = array();

        if (is_array($tag))
        {
            $_tag      = $tag[0];
            $_tag_attr = $tag[1];
        }

        $record = $this->_node($parent_tag)->appendChild(new DOMElement($_tag, (is_array($data) ? NULL : $data), $namespace_uri));

        if (is_array($_tag_attr) AND count($_tag_attr))
        {
            foreach($_tag_attr as $k => $v)
            {
                $record->appendChild(new DOMAttr($k, $v));
            }
        }

        /* Now to add the data */
        if (is_array($data) AND count($data))
        {
            foreach($data as $rowTag => $rowData)
            {
                /* You can pass an array.. or not if you don't need attributes */
                if (! is_array($rowData))
                {
                    $rowData = array(0 => $rowData);
                }

                if (preg_match("/['\"\[\]<>&]/", $rowData[0]))
                {
                    $_child = $record->appendChild(new DOMElement($rowTag));
                    $_child->appendChild(new DOMCDATASection($this->_input_to_xml($rowData[0])));
                }
                else
                {
                    $_child = $record->appendChild(new DOMElement($rowTag, $this->_input_to_xml($rowData[0])));
                }

                if ($rowData[1])
                {
                    foreach($rowData[1] as $_k => $_v)
                    {
                        $_child->appendChild(new DOMAttr($_k, $_v));
                    }
                }

                unset($_child);
            }
        }
    }

    /**
     * 将属性添加到一个节点
     *
     * @param	string
     * @param	array
     * @return	@e void
     */
    public function add_attributes($tag, $data)
    {
        if (is_array($data) AND count($data))
        {
            foreach($data as $k => $v)
            {
                $this->_node($tag)->appendChild(new DOMAttr($k, $v));
            }
        }
    }

    /**
     * 从文件中加载文档
     *
     * @param	string
     * @return	@e void
     */
    public function load($filename)
    {
        $this->_dom = new DOMDocument;
        $this->_dom->load($filename);
    }

    /**
     * 从字符串中加载文档
     *
     * @param	string
     * @return	@e bool
     */
    public function load_xml($xml_data)
    {
        $this->_dom = new DOMDocument;

        if (defined('LIBXML_PARSEHUGE'))
        {
            return @$this->_dom->loadXML($xml_data, LIBXML_PARSEHUGE);
        }
        else
        {
            return @$this->_dom->loadXML($xml_data);
        }
    }

    /**
     * 从标签名获取元素
     *
     * @param	string
     * @param	object
     * @return	@e array
     */
    public function fetch_elements($tag, $node = null)
    {
        $start		= $node ? $node : $this->_dom;
        $_elements = $start->getElementsByTagName($tag);

        return ($_elements->length) ? $_elements : array();
    }

    /**
     * 从父标签获取所有元素
     *
     * @param	object
     * @param	array
     * @return	@e array
     */
    public function fetch_elements_from_record($dom, $skip=array())
    {
        $array = array();

        foreach($dom->childNodes as $node)
        {
            if ($node->nodeType == XML_ELEMENT_NODE)
            {
                if (is_array($skip))
                {
                    if (in_array($node->nodeName, $skip))
                    {
                        continue;
                    }
                }

                $array[$node->nodeName] = $this->_xml_to_output($node->nodeValue);
            }
        }

        return $array;
    }

    /**
     * 从元素节点获取条目
     *
     * @param	object
     * @param	string
     * @return	@e string
     */
    public function fetch_item($dom, $tag = '')
    {
        if ($tag)
        {
            $_child = $dom->getElementsByTagName($tag);
            return $this->_xml_to_output($_child->item(0)->firstChild->nodeValue);
        }
        else
        {
            return $this->_xml_to_output($dom->nodeValue);
        }
    }

    /**
     * 从元素节点获取属性项
     *
     * @param	object
     * @param	string
     * @param	string
     * @return	@e string
     */
    public function fetch_attribute($dom, $attribute, $tag = '')
    {
        if ($tag)
        {
            $_child = $dom->getElementsByTagName($tag);
            return $_child->item(0)->getAttribute($attribute);
        }
        else
        {
            return $dom->getAttribute($attribute);
        }
    }

    /**
     * 从元素节点获取所有属性项
     *
     * @param	object
     * @param	string
     * @return	@e array
     */
    public function fetch_attributes_as_array($dom, $tag)
    {
        $attrs      = array();
        $_child     = $dom->getElementsByTagName($tag);
        $attributes = $_child->item(0)->attributes;

        foreach($attributes as $val)
        {
            $attrs[$val->nodeName] = $val->nodeValue;
        }

        return $attrs;
    }

    /**
     * 将 DOM Tree 转换为数组
     *
     * @return	@e array
     */
    public function fetch_xml_as_array()
    {
        return $this->_fetch_xml_as_array($this->_dom);
    }

    /**
     * 将 DOM Tree 转换为数组
     *
     * @param	DOM object
     * @return	@e array
     */
    protected function _fetch_xml_as_array($node)
    {
        $_xml_array = array();

        if ($node->nodeType == XML_TEXT_NODE)
        {
            $_xml_array = $this->_xml_to_output($node->nodeValue);
        }
        else if ($node->nodeType == XML_CDATA_SECTION_NODE)
        {
            $_xml_array = $this->_xml_to_output($node->nodeValue);
        }
        else
        {
            if ($node->hasAttributes())
            {
                $attributes = $node->attributes;

                if (! is_null($attributes))
                {
                    foreach($attributes as $index => $attr)
                    {
                        $_xmlArray['@attributes'][$attr->name] = $attr->value;
                    }
                }
            }

            if ($node->hasChildNodes())
            {
                $children  = $node->childNodes;
                $occurance = array();

                foreach($children as $nc)
                {
                    if ($nc->nodeName != '#text' AND $nc->nodeName != '#cdata-section')
                    {
                        $occurance[$nc->nodeName]	= isset($occurance[$nc->nodeName]) ? $occurance[$nc->nodeName] + 1 : 1;
                    }
                }

                for($i = 0 ; $i < $children->length ; $i++)
                {
                    $child = $children->item($i);
                    $_name = $child->nodeName;

                    if ($child->nodeName == '#text' OR $child->nodeName == '#cdata-section')
                    {
                        $_name = '#alltext';
                    }

                    if (isset($occurance[$child->nodeName]) AND $occurance[$child->nodeName] > 1)
                    {
                        $_xmlArray[$_name][] = $this->_fetch_xml_as_array($child);
                    }
                    else
                    {
                        $_xmlArray[$_name] = $this->_fetch_xml_as_array($child);
                    }
                }
            }
        }

        return $_xmlArray;
    }

    /**
     * 编码 CDATA XML 属性
     *
     * @param	string
     * @return	@e string
     */
    protected function _xml_convert_safecdata($v)
    {
        $v = str_replace("<![CDATA[", "<!#^#|CDATA|", $v);
        $v = str_replace("]]>"      , "|#^#]>"      , $v);

        return $v;
    }

    /**
     * 解码 CDATA XML 属性
     *
     * @param	string
     * @return	@e string
     */
    protected function _xml_unconvert_safe_cdata($v)
    {
        $v = str_replace("<!#^#|CDATA|", "<![CDATA[", $v);
        $v = str_replace("|#^#]>"      , "]]>"      , $v);

        return $v;
    }

    /**
     * 返回标签对象
     *
     * @param	string
     * @return	@e object
     */
    protected function _node($tag)
    {
        if (isset($this->_dom_objects[$tag]))
        {
            return $this->_dom_objects[$tag];
        }
        else
        {
            return $this->_dom;
        }
    }

    /**
     * 将输入数据转换为 XML
     *
     * @param	string
     * @return	@e string
     */
    protected function _input_to_xml($text)
    {
        /* Do we need to make safe on CDATA? */
        if (preg_match("/['\"\[\]<>&]/", $text))
        {
            $text = $this->_xml_convert_safecdata($text);
        }

        /* Using UTF-8 */
        if ($this->_xml_char_set == 'utf-8')
        {
            return $text;
        }
        /* Are we using the most common ISO-8559-1... */
        else if ($this->_xml_char_set == 'iso-8859-1')
        {
            return utf8_encode($text);
        }
        else
        {
            return convert_encoding($text, $this->_xml_char_set, 'utf-8');
        }
    }

    /**
     * 将 XML 转换为输出数据
     *
     * @param	string
     * @return	@e string
     */
    protected function _xml_to_output($text)
    {
        /* Unconvert cdata */
        $text = $this->_xml_unconvert_safe_cdata($text);

        /* Using UTF-8 */
        if ($this->_xml_char_set == 'utf-8')
        {
            return $text;
        }
        /* Are we using the most common ISO-8559-1... */
        else if ($this->_xml_char_set == 'iso-8859-1')
        {
            return utf8_decode($text);
        }
        else
        {
            return convert_encoding($text, 'utf-8', $this->_xml_char_set);
        }
    }
}