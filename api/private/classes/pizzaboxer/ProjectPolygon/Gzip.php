<?php

namespace pizzaboxer\ProjectPolygon;

class Gzip
{
	// this is to compress models and places to help conserve space
	// this should be used only for models and places, nothing else

	//http://stackoverflow.com/questions/6073397/how-do-you-create-a-gz-file-using-php
	static function Compress(string $inFilename, int $level = 9): string
	{
		// Is the file gzipped already?
		$extension = pathinfo($inFilename, PATHINFO_EXTENSION);
		if ($extension == "gz") { return $inFilename; }

		// Open input file
		$inFile = fopen($inFilename, "rb");
		if ($inFile === false) { throw new \Exception("Unable to open input file: $inFilename"); }

		// Open output file
		$gzFilename = $inFilename.".gz";
		$gzFile = gzopen($gzFilename, "wb".$level);
		if ($gzFile === false) 
		{
			fclose($inFile);
			throw new \Exception("Unable to open output file: $gzFilename");
		}

		// Stream copy
		$length = 65536 * 1024; // 512 kB
		while (!feof($inFile)) { gzwrite($gzFile, fread($inFile, $length)); }

		// Close files
		fclose($inFile);
		gzclose($gzFile);

		// Return the new filename
		//delete original
		unlink($inFilename);
		rename($gzFilename, $inFilename);
		return $gzFilename;
	}

	static function Decompress($filename, $buffer_size = 8192) 
	{
		$buffer = "";
		$file = gzopen($filename, 'rb');
		while(!gzeof($file)) { $buffer .= gzread($file, $buffer_size); }
		gzclose($file);
		return $buffer;
	}

	static function IsGzEncoded($Data)
	{
		return mb_strpos($Data, "\x1f\x8b\x08") === 0;
	}
}