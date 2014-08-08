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

define(IMAGE_CORE_OP_TO_FILE, 1);              // Output to file
define(IMAGE_CORE_OP_OUTPUT, 2);               // Output to browser

define(IMAGE_CORE_SC_NOT_KEEP_SCALE, 4);       // Free scale
define(IMAGE_CORE_SC_BEST_RESIZE_WIDTH, 8);    // Scale to width
define(IMAGE_CORE_SC_BEST_RESIZE_HEIGHT, 16);  // Scale to height

define(IMAGE_CORE_CM_DEFAULT, 0);               // Clipping method: default
define(IMAGE_CORE_CM_LEFT_OR_TOP, 1);           // Clipping method: left or top
define(IMAGE_CORE_CM_MIDDLE, 2);                // Clipping method: middle
define(IMAGE_CORE_CM_RIGHT_OR_BOTTOM, 3);       // Clipping method: right or bottom

class core_image
{
	var $image_library = 'gd';

	var $source_image;
	var $new_image;
	var $width;
	var $height;
	var $quality = 90;

	var $option = IMAGE_CORE_OP_TO_FILE;

	var $scale;

	// clipping method 0: default 1: left or top 2: middle 3: right or bottom
	var $clipping = IMAGE_CORE_CM_MIDDLE;

	var $start_x = 0;	// start X axis (pixel)
	var $start_y = 0;	// start Y axis (pixel)

	private $image_type = array(
		1 => 'gif',
		2 => 'jpeg',
		3 => 'png'
	);

	private $image_type_index = array(
		'gif' => 1,
		'jpg' => 2,
		'jpeg' => 2,
		'jpe' => 2,
		'png' => 3
	);

	private $image_info;
	private $image_ext;

	public function initialize($config = array())
	{
		if (!defined('IN_SAE'))
		{
			if (class_exists('Imagick', false))
			{
				$this->image_library = 'imagemagick';
			}
		}

		if (sizeof($config) > 0)
		{
			foreach ($config AS $key => $value)
			{
				$this->$key = $value;
			}
		}

		return $this;
	}

	public function resize()
	{
		if (!file_exists($this->source_image))
		{
			throw new Zend_Exception('Source file not exists: ' . $this->source_image);
		}

		$this->image_info = @getimagesize($this->source_image);

		switch ($this->image_info[2])
		{
			default:
				throw new Zend_Exception('Can\'t detect output image\'s type: ' . $this->source_image);
			break;

			case 1:
				$this->image_ext = 'gif';
			break;

			case 2:
				$this->image_ext = 'jpg';
			break;

			case 3:
				$this->image_ext = 'png';
			break;
		}

		if ($this->image_library == 'imagemagick')
		{
			return $this->imageProcessImageMagick();
		}
		else
		{
			return $this->imageProcessGD();
		}
	}

	private function imageProcessImageMagick()
	{
		$this->source_image_w = $this->image_info[0];
		$this->source_image_h = $this->image_info[1];

		$this->source_image_x = 0;
		$this->source_image_y = 0;

		$dst_x = 0;
		$dst_y = 0;

		if ($this->clipping != IMAGE_CORE_CM_DEFAULT)
		{
			// clipping method 1: left or top 2: middle 3: right or bottom

			$this->source_image_w -= $this->start_x;
			$this->source_image_h -= $this->start_y;

			if ($this->source_image_w * $this->height > $this->source_image_h * $this->width)
			{
				$match_w = round($this->width * $this->source_image_h / $this->height);
				$match_h = $this->source_image_h;
			}
			else
			{
				$match_h = round($this->height * $this->source_image_w / $this->width);
				$match_w = $this->source_image_w;
			}

			switch ($this->clipping)
			{
				case IMAGE_CORE_CM_LEFT_OR_TOP:
					$this->source_image_x = 0;
					$this->source_image_y = 0;
				break;

				case IMAGE_CORE_CM_MIDDLE:
					$this->source_image_x = round(($this->source_image_w - $match_w) / 2);
					$this->source_image_y = round(($this->source_image_h - $match_h) / 2);
				break;

				case IMAGE_CORE_CM_RIGHT_OR_BOTTOM:
					$this->source_image_x = $this->source_image_w - $match_w;
					$this->source_image_y = $this->source_image_h - $match_h;
				break;
			}

			$this->source_image_w = $match_w;
			$this->source_image_h = $match_h;
			$this->source_image_x += $this->start_x;
			$this->source_image_y += $this->start_y;
		}

		$resize_height = $this->height;
		$resize_width = $this->width;

		if ($this->scale != IMAGE_CORE_SC_NOT_KEEP_SCALE)
		{
			if ($this->scale == IMAGE_CORE_SC_BEST_RESIZE_WIDTH)
			{
				$resize_height = round($this->width * $this->source_image_h / $this->source_image_w);
				$resize_width = $this->width;
			}
			else if ($this->scale == IMAGE_CORE_SC_BEST_RESIZE_HEIGHT)
			{
				$resize_width = round($this->height * $this->source_image_w / $this->source_image_h);
				$resize_height = $this->height;
			}
		}

		$im = new Imagick();

		$im->readimageblob(file_get_contents($this->source_image));

		$im->setCompressionQuality($this->quality);

		if ($this->source_image_x OR $this->source_image_y)
		{
			$im->cropImage($this->source_image_w, $this->source_image_h, $this->source_image_x, $this->source_image_y);
		}

		$im->thumbnailImage($resize_width, $resize_height, true);

		if ($this->option == IMAGE_CORE_OP_TO_FILE AND $this->new_image)
		{
			file_put_contents($this->new_image, $im->getimageblob());
		}
		else if ($this->option == IMAGE_CORE_OP_OUTPUT)
		{
			$output = $im->getimageblob();
  			$outputtype = $im->getFormat();

			header("Content-type: $outputtype");
			echo $output;
			die;
		}

		return TRUE;
	}

	private function imageProcessGD()
	{
		$func_output = 'image' . $this->image_type[$this->image_type_index[$this->image_ext]];

		if (!function_exists($func_output))
		{
			throw new Zend_Exception('Function not exists for output: ' . $func_output);
		}

		$func_create = 'imagecreatefrom' . $this->image_type[$this->image_info[2]];

		if (!function_exists($func_create))
		{
			throw new Zend_Exception('Function not exists for output: ' . $func_create);
		}

		$im = $func_create($this->source_image);

		$this->source_image_w = $this->image_info[0];
		$this->source_image_h = $this->image_info[1];

		$this->source_image_x = 0;
		$this->source_image_y = 0;

		$dst_x = 0;
		$dst_y = 0;

		if ($this->scale == IMAGE_CORE_SC_BEST_RESIZE_WIDTH)
		{
			$this->height = round($this->width * $this->source_image_h / $this->source_image_w);
		}

		if ($this->scale & IMAGE_CORE_SC_BEST_RESIZE_HEIGHT)
		{
			$this->width = round($this->height * $this->source_image_w / $this->source_image_h);
		}

		$fdst_w = $this->width;
		$fdst_h = $this->height;

		if ($this->clipping != IMAGE_CORE_CM_DEFAULT)
		{
			// clipping method 1: left or top 2: middle 3: right or bottom

			$this->source_image_w -= $this->start_x;
			$this->source_image_h -= $this->start_y;

			if ($this->source_image_w * $this->height > $this->source_image_h * $this->width)
			{
				$match_w = round($this->width * $this->source_image_h / $this->height);
				$match_h = $this->source_image_h;
			}
			else
			{
				$match_h = round($this->height * $this->source_image_w / $this->width);
				$match_w = $this->source_image_w;
			}

			switch ($this->clipping)
			{
				case IMAGE_CORE_CM_LEFT_OR_TOP:
					$this->source_image_x = 0;
					$this->source_image_y = 0;
				break;

				case IMAGE_CORE_CM_MIDDLE:
					$this->source_image_x = round(($this->source_image_w - $match_w) / 2);
					$this->source_image_y = round(($this->source_image_h - $match_h) / 2);
				break;

				case IMAGE_CORE_CM_RIGHT_OR_BOTTOM:
					$this->source_image_x = $this->source_image_w - $match_w;
					$this->source_image_y = $this->source_image_h - $match_h;
				break;
			}

			$this->source_image_w = $match_w;
			$this->source_image_h = $match_h;
			$this->source_image_x += $this->start_x;
			$this->source_image_y += $this->start_y;

		}
		else if ($this->scale != IMAGE_CORE_SC_NOT_KEEP_SCALE)
		{
			if ($this->source_image_w * $this->height > $this->source_image_h * $this->width)
			{
				$fdst_h = round($this->source_image_h * $this->width / $this->source_image_w);
				$dst_y = floor(($this->height - $fdst_h) / 2);
				$fdst_w = $this->width;
			}
			else
			{
				$fdst_w = round($this->source_image_w * $this->height / $this->source_image_h);
				$dst_x = floor(($this->width - $fdst_w) / 2);
				$fdst_h = $this->height;
			}

			if ($dst_x < 0)
			{
				$dst_x = 0;
				$dst_y = 0;
			}

			if ($dst_x > ($this->width / 2))
			{
				$dst_x = floor($this->width / 2);
			}

			if ($dst_y > ($this->height / 2))
			{
				$dst_y = floor($this->height / 2);
			}
		}

		if (function_exists('imagecopyresampled') AND function_exists('imagecreatetruecolor'))	// GD Version Check
		{
			$func_create = 'imagecreatetruecolor';
			$func_resize = 'imagecopyresampled';
		}
		else
		{
			$func_create = 'imagecreate';
			$func_resize = 'imagecopyresized';
		}

		$dst_img = $func_create($this->width, $this->height);

		if ($this->image_ext == 'png') // png we can actually preserve transparency
		{
			imagealphablending($dst_img, FALSE);
			imagesavealpha($dst_img, TRUE);
		}

		$func_resize($dst_img, $im, $dst_x, $dst_y, $this->source_image_x, $this->source_image_y, $fdst_w, $fdst_h, $this->source_image_w, $this->source_image_h);

		if ($this->option == IMAGE_CORE_OP_TO_FILE AND $this->new_image)
		{
			if (file_exists($this->new_image))
			{
				@unlink($this->new_image);
			}

			if (defined('IN_SAE'))
			{
				$this->new_image = str_replace(get_setting('upload_dir'), '', $this->new_image);

				$sae_storage = new SaeStorage();
			}

			switch ($this->image_type_index[$this->image_ext])
			{
				case 1:
				case 3:
					if (defined('IN_SAE'))
					{
						ob_start();

						$func_output($dst_img);
						$sae_storage->write('uploads', $this->new_image, ob_get_contents());

						ob_end_clean();
					}
					else
					{
						$func_output($dst_img, $this->new_image);
					}
				break;

				case 2:	// JPEG
					if (defined('IN_SAE'))
					{
						ob_start();

						$func_output($dst_img, null, $this->quality);
						$sae_storage->write('uploads', $this->new_image, ob_get_contents());

						ob_end_clean();
					}
					else
					{
						$func_output($dst_img, $this->new_image, $this->quality);
					}
				break;
			}
		}
		else if ($this->option == IMAGE_CORE_OP_OUTPUT)
		{
			if (function_exists("headers_sent") AND headers_sent())
			{
				throw new Zend_Exception('HTTP already sent, can\'t output image to browser.');
			}

			header('Content-Type: image/' . $this->image_type[$this->image_type_index[$this->image_ext]]);

			switch ($this->image_type_index[$this->image_ext])
			{
				case 1:
				case 3:
					$func_output($dst_img);
				break;

				case 2:	// JPEG
					$func_output($dst_img, '', $this->quality);
				break;
			}

			die;
		}

		@imagedestroy($im);
		@imagedestroy($dst_img);

		return TRUE;
	}
}