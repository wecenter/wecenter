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

define('GEO_EARTH_RADIUS', 6371);	// 地球半径, 平均半径为 6371km

class geo_class extends AWS_MODEL
{
	/**
	* 计算某个经纬度的周围某段距离的正方形的四个点
	*
	* @param longitude float 经度
	* @param latitude float 纬度
	* @param radius float 该点所在圆的半径,该圆与此正方形内切, 单位: 千米
	* @return array 正方形的四个点的经纬度坐标
	*/
	public function get_square_point($longitude, $latitude, $radius = 1)
	{
		$target_longitude = rad2deg((2 * asin(sin($radius / (2 * GEO_EARTH_RADIUS)) / cos(deg2rad($latitude)))));
		
		$target_latitude = rad2deg(($radius / GEO_EARTH_RADIUS));
		
		return array(
			'TL' => array('latitude' => $latitude + $target_latitude, 'longitude' => $longitude - $target_longitude),	// Top left point 
			//'TR' => array('latitude' => $latitude + $target_latitude, 'longitude' => $longitude + $target_longitude),	// Top right point 
			//'BL' => array('latitude' => $latitude - $target_latitude, 'longitude' => $longitude - $target_longitude),	// Bottom left point 
			'BR' => array('latitude' => $latitude - $target_latitude, 'longitude' => $longitude + $target_longitude)	// Bottom right point 
		);
	}
	
	public function set_location($item_type, $item_id, $longitude, $latitude)
	{
		$this->delete('geo_location', "`item_type` = '" . $this->quote($item_type) . "' AND `item_id` = " . intval($item_id));
		
		return $this->insert('geo_location', array(
			'item_type' => $item_type,
			'item_id' => $item_id,
			'longitude' => $longitude,
			'latitude' => $latitude,
			'add_time' => time()
		));
	}
	
	public function get_distance($longitude_a, $latitude_a, $longitude_b, $latitude_b)
	{
		$latitude_a = $latitude_a * pi()/ 180.0;   //pi()圆周率
		$latitude_b = $latitude_b * pi() / 180.0;
		$a = $latitude_a - $latitude_b;
		$b = ($lng1 * pi() / 180.0) - ($lng2 * pi() / 180.0);
		$s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($latitude_a) * cos($latitude_b) * pow(sin($b/2),2)));
		$s = $s * EARTH_RADIUS;
		$s = round($s * 1000);
		
		return round($s, 2);
	}
}