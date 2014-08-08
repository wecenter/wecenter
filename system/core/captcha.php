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

class core_captcha
{
	private $captcha;

	public function __construct()
	{
		if (defined('IN_SAE'))
		{
			$img_dir = SAE_TMP_PATH;
		}
		else
		{
			$img_dir = ROOT_PATH . 'cache/captcha/';

			if (!is_dir($img_dir))
			{
				@mkdir($img_dir);
			}
		}

		$this->captcha = new Zend_Captcha_Image(array(
			'font' => $this->get_font(),
			'imgdir' => $img_dir,
			'fontsize' => rand(20, 22),
			'width' => 100,
			'height' => 40,
			'wordlen' => 4,
			'session' => new Zend_Session_Namespace(G_COOKIE_PREFIX . '_Captcha'),
			'timeout' => 600
		));

		$this->captcha->setDotNoiseLevel(rand(3, 6));
		$this->captcha->setLineNoiseLevel(rand(1, 2));
	}

	public function get_font()
	{
		if (!$captcha_fonts = AWS_APP::cache()->get('captcha_fonts'))
		{
			$captcha_fonts = fetch_file_lists(AWS_PATH . 'core/fonts/');

			AWS_APP::cache()->set('captcha_fonts', $captcha_fonts, get_setting('cache_level_normal'));
		}

		return array_random($captcha_fonts);
	}

	public function generate()
	{
		$this->captcha->generate();

		HTTP::no_cache_header();

		readfile($this->captcha->getImgDir() . $this->captcha->getId() . $this->captcha->getSuffix());

		die;
	}

	public function is_validate($validate_code)
	{
		if (strtolower($this->captcha->getWord()) == strtolower($validate_code))
		{
			$this->captcha->generate();

			return true;
		}

		return false;
	}
}