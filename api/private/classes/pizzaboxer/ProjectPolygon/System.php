<?php

namespace pizzaboxer\ProjectPolygon;

class System
{
	static function GetFileSize($bytes, $binaryPrefix = true) 
	{
	    $unit=array('B','KB','MB','GB','TB','PB');
	    if (!$bytes) return '0 ' . $unit[0];
	    if ($binaryPrefix) return round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2) .' '. ($unit[$i] ?? 'B');
	    return round($bytes/pow(1000,($i=floor(log($bytes,1000)))),2) .' '. ($unit[$i] ?? 'B');
	}

	static function GetFolderSize($path, $raw = false)
	{
		$io = popen("du -sb $path", "r");
		$size = (int)filter_var(explode($path, fgets($io, 4096), 2)[0], FILTER_SANITIZE_NUMBER_INT);
		pclose($io);

		if($raw) return $size;
		return self::getFileSize($size);
	}

	static function GetMemoryUsage() 
	{
		$lines = explode("\n", file_get_contents('/proc/meminfo'));
		$total = (int) filter_var($lines[0], FILTER_SANITIZE_NUMBER_INT);
		$free = (int) filter_var($lines[1], FILTER_SANITIZE_NUMBER_INT);
	       return (object)["total" => $total*1024, "free" => $free*1024];
	}
}