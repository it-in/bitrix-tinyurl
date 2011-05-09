<?php
/**
 * Provides URL shortening functionality, like tinyurl.com, bit.ly, ow.ly and other popular services.
 * (c) 2011, it-in, http://it-in.ru
 * @author Sergey Kovalev <kovalev_s@it-in.ru>
 * @version 1.0
 */

/**
* Basic URL path, to which short code will be added.
*/
define("BASE_SHORT_PATH", "http://it-in.ru/~");

/**
* ID of the infoblock which holds information about shortned URLs.
*/
define("TINYURL_IBLOCK_ID", 11);

Class TinyURL
{
	/**
	* Converts decimal number to any base
	* @param integer $num Your decimal integer
	* @param integer $base Base to which you wish to convert $num (leave it 0 if you are providing $index or omit if you're using default (62))
	* @param string $index If you wish to use the default list of digits (0-1a-zA-Z), omit this option, otherwise provide a string (ex.: "zyxwvu")
	* @return string
	* @link http://www.php.net/manual/ru/function.base-convert.php#52450
	*/
	private static function dec2any( $num, $base=62, $index=false ) {
		if (! $base ) {
			$base = strlen( $index );
		} else if (! $index ) {
			$index = substr( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ,0 ,$base );
		}
		$out = "";
		for ( $t = floor( log10( $num ) / log10( $base ) ); $t >= 0; $t-- ) {
			$a = floor( $num / pow( $base, $t ) );
			$out = $out . substr( $index, $a, 1 );
			$num = $num - ( $a * pow( $base, $t ) );
		}
		return $out;
	}

	/**
	* Converts number in any base to decimal
	* @param integer $num Your custom-based number (string) (ex.: "11011101")
	* @param integer $base Base with which $num was encoded (leave it 0 if you are providing $index or omit if you're using default (62))
	* @param string $index If you wish to use the default list of digits (0-1a-zA-Z), omit this option, otherwise provide a string (ex.: "abcdef")
	* @return integer
	* @link http://www.php.net/manual/ru/function.base-convert.php#52450
	*/
	private static function any2dec( $num, $base=62, $index=false ) {
		if (! $base ) {
			$base = strlen( $index );
		} else if (! $index ) {
			$index = substr( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 0, $base );
		}
		$out = 0;
		$len = strlen( $num ) - 1;
		for ( $t = 0; $t <= $len; $t++ ) {
			$out = $out + strpos( $index, substr( $num, $t, 1 ) ) * pow( $base, $len - $t );
		}
		return $out;
	}

	/**
	* Shortens URL.
	* @param string $url Absolute URL to be shortened, like http://www.yandex.ru.
	* @return string
	*/
	public static function shorten($url)
	{
		CModule::IncludeModule("iblock") || die("Couldn't load one of the required modules. Error fe51e037.");
		// Check if there is already shortened version of the required URL.
		$res = CIBlockElement::GetList(
			array(),
			array('IBLOCK_ID' => TINYURL_IBLOCK_ID, 'PREVIEW_TEXT' => $url),
			false,
			false,
			array('ID')
		);
		if($ob = $res->GetNextElement())
		{
			$arFields = $ob->GetFields();
			return BASE_SHORT_PATH . self::dec2any($arFields['ID']);
		}
		
		// Shorten new URL and create a record in database.
		$el = new CIBlockElement;
		$ELEMENT_ID = $el->Add(array(
			'IBLOCK_ID' => TINYURL_IBLOCK_ID,
			'NAME' => $url,
			'PREVIEW_TEXT' => $url,
			'PREVIEW_TEXT_TYPE' => 'html',
		));
		if($ELEMENT_ID)
		  return BASE_SHORT_PATH . self::dec2any($ELEMENT_ID);
		else
		  die($el->LAST_ERROR);
	}
	
	/**
	* Converts short code to full URL, e.g. 8UdA -> http://yandex.ru.
	* @param string $short_code
	* @return string Full URL.
	*/
	public static function unshorten($short_code)
	{
		CModule::IncludeModule("iblock") || die("Couldn't load one of the required modules. Error e7c42163.");
		
		// Convert short code to decimal element ID
		$ELEMENT_ID = self::any2dec($short_code);
		
		// Die if no corresponding element found
		// Check if there is already shortened version of the required URL.
		$res = CIBlockElement::GetList(
			array(),
			array('IBLOCK_ID' => TINYURL_IBLOCK_ID, 'ID' => $ELEMENT_ID, 'PREVIEW_TEXT_TYPE' => 'html'),
			false,
			false,
			array('PREVIEW_TEXT')
		);
		if(!($ar_res = $res->GetNext())) die('No URL found. Error 708cc6af.');
		
		//Return full URL.
		return $ar_res['PREVIEW_TEXT'];
	}
	
	/**
	* Redirects browser to the full URL being given just a short code.
	* If 'statistic' module for web analytics is available, raises corresponding event.
	* @param string $short_code
	*/
	public static function redirect($short_code)
	{
		// Add event if analytics module is available.
		if (CModule::IncludeModule("statistic")
			&& !($_SESSION["SESS_SEARCHER_ID"]>0)) // We don't want to count bot clicks
		{
			$referer = empty($_SERVER['HTTP_REFERER']) ? 'direct' : $_SERVER['HTTP_REFERER'];
			$refererDomain = empty($_SERVER['HTTP_REFERER']) ? 'direct' : parse_url($_SERVER['HTTP_REFERER'],PHP_URL_HOST);
			CStatEvent::AddCurrent("shorturl", $refererDomain, $referer, "", "", self::unshorten($short_code));
		}
		header('Location: '.self::unshorten($short_code));
		exit;
	}
}
?>
