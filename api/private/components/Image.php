<?php

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

		if(strlen($options["name"]) && file_exists(ROOT.$options["dir"].$options["name"])) 
			unlink(ROOT.$options["dir"].$options["name"]);

		$handle->process(ROOT.$options["dir"]);
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
		Image::Resize(ROOT."/thumbs/$img.png", 75, 75, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-75x75.png");
		Image::Resize(ROOT."/thumbs/$img.png", 100, 100, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-100x100.png");
		Image::Resize(ROOT."/thumbs/$img.png", 110, 110, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-110x110.png");
		Image::Resize(ROOT."/thumbs/$img.png", 250, 250, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-250x250.png");
		Image::Resize(ROOT."/thumbs/$img.png", 352, 352, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-352x352.png");
		Image::Resize(ROOT."/thumbs/$img.png", 420, 230, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-420x230.png");
		Image::Resize(ROOT."/thumbs/$img.png", 420, 420, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-420x420.png");
	}
}