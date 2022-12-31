<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Polygon;

class Image
{
	static function Process($handle, $options)
	{
		$image = $options["image"] ?? true;
		$resize = $options["resize"] ?? true;
		$keepRatio = $options["keepRatio"] ?? false;
		$scaleX = $options["scaleX"] ?? false;
		$scaleY = $options["scaleY"] ?? false;

		$handle->file_new_name_ext = "";
		$handle->file_new_name_body = $options["name"];
		
		if($image)
		{
			$handle->image_convert = "png";
			$handle->image_resize = $resize;
			if($resize)
			{
				if($keepRatio) $handle->image_ratio_fill = $options["align"];
				if($scaleX) $handle->image_ratio_x = true; else $handle->image_x = $options["x"];
				if($scaleY) $handle->image_ratio_y = true; else $handle->image_y = $options["y"];
			}
		}

		$directory = Polygon::GetSharedResource($options["dir"]);

		if(strlen($options["name"]) && file_exists($directory.$options["name"])) 
			unlink($directory.$options["name"]);

		$handle->process($directory);
		if(!$handle->processed) return $handle->error;

		return true;
	}

	static function Resize($file, $w, $h, $path = false)
	{
		list($width, $height) = getimagesize($file);
	   	$src = imagecreatefrompng($file);
	   	$dst = imagecreatetruecolor($w, $h);
	   	imagealphablending($dst, false);
	   	imagesavealpha($dst, true);
	   	imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $width, $height);

	   	// this resize function is used in conjunction with an imagepng function
	   	// to resize an existing image and upload - having to do this eve
	   	if($path) imagepng($dst, $path);

	   	return $dst;
	}

	static function MergeLayers($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
	{
		$cut = imagecreatetruecolor($src_w, $src_h);
		imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
		imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
		imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
	}

	// pre rendered thumbnails (scripts and audios) are all rendered with the same size
	// so this just sorta cleans up the whole thing
	static function RenderFromStaticImage($img, $assetID)
	{
		Image::Resize(SITE_CONFIG['paths']['thumbs']."/$img.png", 420, 420, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-420x420.png");
	}
}