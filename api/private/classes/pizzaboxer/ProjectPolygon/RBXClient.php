<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Polygon;

class RBXClient
{
	private static array $brickcolors =
	[
		"F2F3F3" => 1, "A1A5A2" => 2, "F9E999" => 3, "D7C59A" => 5, "C2DAB8" => 6, "E8BAC8" => 9, "80BBDC" => 11, "CB8442" => 12, "CC8E69" => 18, "C4281C" => 21, "C470A0" => 22, "0D69AC" => 23, "F5CD30" => 24, "624732" => 25, "1B2A35" => 26, "6D6E6C" => 27, "287F47" => 28, "A1C48C" => 29, "F3CF9B" => 36, "4B974B" => 37, "A05F35" => 38, "C1CADE" => 39, "ECECEC" => 40, "CD544B" => 41, "C1DFF0" => 42, "7BB6E8" => 43, "F7F18D" => 44, "B4D2E4" => 45, "D9856C" => 47, "84B68D" => 48, "F8F184" => 49, "ECE8DE" => 50, "EEC4B6" => 100, "DA867A" => 101, "6E99CA" => 102, "C7C1B7" => 103, "6B327C" => 104, "E29B40" => 105, "DA8541" => 106, "008F9C" => 107, "685C43" => 108, "435493" => 110, "BFB7B1" => 111, "6874AC" => 112, "E5ADC8" => 113, "C7D23C" => 115, "55A5AF" => 116, "B7D7D5" => 118, "A4BD47" => 119, "D9E4A7" => 120, "E7AC58" => 121, "D36F4C" => 123, "923978" => 124, "EAB892" => 125, "A5A5CB" => 126, "DCBC81" => 127, "AE7A59" => 128, "9CA3A8" => 131, "D5733D" => 133, "D8DD56" => 134, "74869D" => 135, "877C90" => 136, "E09864" => 137, "958A73" => 138, "203A56" => 140, "27462D" => 141, "CFE2F7" => 143, "7988A1" => 145, "958EA3" => 146, "938767" => 147, "575857" => 148, "161D32" => 149, "ABADAC" => 150, "789082" => 151, "957977" => 153, "7B2E2F" => 154, "FFF67B" => 157, "E1A4C2" => 158, "756C62" => 168, "97695B" => 176, "B48455" => 178, "898788" => 179, "D7A94B" => 180, "F9D62E" => 190, "E8AB2D" => 191, "694028" => 192, "CF6024" => 193, "A3A2A5" => 194, "4667A4" => 195, "23478B" => 196, "8E4285" => 198, "635F62" => 199, "828A5D" => 200, "E5E4DF" => 208, "B08E44" => 209, "709578" => 210, "79B5B5" => 211, "9FC3E9" => 212, "6C81B7" => 213, "904C2A" => 216, "7C5C46" => 217, "96709F" => 218, "6B629B" => 219, "A7A9CE" => 220, "CD6298" => 221, "E4ADC8" => 222, "DC9095" => 223, "F0D5A0" => 224, "EBB87F" => 225, "FDEA8D" => 226, "7DBBDD" => 232, "342B75" => 268, "506D54" => 301, "5B5D69" => 302, "0010B0" => 303, "2C651D" => 304, "527CAE" => 305, "335882" => 306, "102ADC" => 307, "3D1585" => 308, "348E40" => 309, "5B9A4C" => 310, "9FA1AC" => 311, "592259" => 312, "1F801D" => 313, "9FADC0" => 314, "0989CF" => 315, "7B007B" => 316, "7C9C6B" => 317, "8AAB85" => 318, "B9C4B1" => 319, "CACBD1" => 320, "A75E9B" => 321, "7B2F7B" => 322, "94BE81" => 323, "A8BD99" => 324, "DFDFDE" => 325, "970000" => 327, "B1E5A6" => 328, "98C2DB" => 329, "FF98DC" => 330, "FF5959" => 331, "750000" => 332, "EFB838" => 333, "F8D96D" => 334, "E7E7EC" => 335, "C7D4E4" => 336, "FF9494" => 337, "BE6862" => 338, "562424" => 339, "F1E7C7" => 340, "FEF3BB" => 341, "E0B2D0" => 342, "D490BD" => 343, "965555" => 344, "8F4C2A" => 345, "D3BE96" => 346, "E2DCBC" => 347, "EDEAEA" => 348, "E9DADA" => 349, "883E3E" => 350, "BC9B5D" => 351, "C7AC78" => 352, "CABFA3" => 353, "BBB3B2" => 354, "6C584B" => 355, "A0844F" => 356, "958988" => 357, "ABA89E" => 358, "AF9483" => 359, "966766" => 360, "564236" => 361, "7E683F" => 362, "69665C" => 363, "5A4C42" => 364, "6A3909" => 365, "F8F8F8" => 1001, "CDCDCD" => 1002, "111111" => 1003, "FF0000" => 1004, "FFB000" => 1005, "B480FF" => 1006, "A34B4B" => 1007, "C1BE42" => 1008, "FFFF00" => 1009, "0000FF" => 1010, "002060" => 1011, "2154B9" => 1012, "04AFEC" => 1013, "AA5500" => 1014, "AA00AA" => 1015, "FF66CC" => 1016, "FFAF00" => 1017, "12EED4" => 1018, "00FFFF" => 1019, "00FF00" => 1020, "3A7D15" => 1021, "7F8E64" => 1022, "8C5B9F" => 1023, "AFDDFF" => 1024, "FFC9C9" => 1025, "B1A7FF" => 1026, "9FF3E9" => 1027, "CCFFCC" => 1028, "FFFFCC" => 1029, "FFCC99" => 1030, "6225D1" => 1031, "FF00BF" => 1032
	];
	
	static function CryptGetSignature($data)
	{
		$KeyLocation = sprintf("file://%s", Polygon::GetSharedResource("polygon_private.pem"));
		openssl_sign($data, $signature, openssl_pkey_get_private($KeyLocation));
		return base64_encode($signature);
	}

	static function CryptVerifySignature($data, $inputSignature)
	{
		$trustedSignature = self::CryptGetSignature($data);
		return hash_equals($trustedSignature, $inputSignature);
	}

	static function CryptSignScript($data, $assetID = false)
	{
		if($assetID) $data = "%{$assetID}%\n{$data}";
		else $data = "\n{$data}";
		$signedScript = "%" . self::CryptGetSignature($data) . "%{$data}"; 
		return $signedScript;
	}

	static function HexToBrickColor($hex)
	{
		return self::$brickcolors[$hex] ?? false;
	}

	static function BrickColorToHex($brickcolor)
	{
		return array_flip(self::$brickcolors)[$brickcolor] ?? false;
	}
}