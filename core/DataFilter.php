<?php
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //

namespace ImpressCMS\Core;

use GeSHi;
use icms;
use ImpressCMS\Core\Facades\Config;
use ImpressCMS\Core\Security\StopSpammer;

/**
 * Class to filter Data
 *
 * @package     ICMS\Core
 * @since       1.3
 * @author      vaughan montgomery (vaughan@impresscms.org)
 * @author      ImpressCMS Project
 * @copyright   (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
 */
class DataFilter
{

	/**
	 * @public    array
	 */
	static public $displaySmileys = [];

	/**
	 * @public    array
	 */
	static public $allSmileys = [];

// -------- Public Functions --------

	/**
	 * @param $text
	 * @param $msg
	 * @author        vaughan montgomery (vaughan@impresscms.org)
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 */
	public static function filterDebugInfo($text, $msg)
	{
		printf("<div style='padding: 5px; color: red; font-weight: bold'>%s</div>", $text);
		echo '<div><pre>';
		print_r($msg);
		echo '</pre></div>';
	}

	/**
	 * Filters out invalid strings included in URL, if any
	 *
	 * @param array $matches
	 * @return  string
	 * @author        vaughan montgomery (vaughan@impresscms.org)
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 */
	public static function _filterImgUrl($matches)
	{
		return self::checkUrlString($matches[2]) ? $matches[0] : '';
	}

	/**
	 * Checks if invalid strings are included in URL
	 *
	 * @param string $text
	 * @return  bool
	 * @author        vaughan montgomery (vaughan@impresscms.org)
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 */
	public static function checkUrlString($text)
	{
		// Check control code
		if (preg_match("/[\0-\31]/", $text)) {
			return false;
		}
		// check black pattern(deprecated)
		return !preg_match('/^(javascript|vbscript|about):/i', $text);
	}

	/**
	 * Convert linebreaks to <br /> tags
	 *
	 * @param string $text
	 * @return   string
	 */
	public static function nl2Br($text)
	{
		return preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $text);
	}

	/**
	 * for displaying data in html textbox forms
	 *
	 * @param string $text
	 * @return   string
	 */
	public static function htmlSpecialChars($text)
	{
		return preg_replace(array('/&amp;/i', '/&nbsp;/i'), array('&', '&amp;nbsp;'),
			@htmlspecialchars($text, ENT_QUOTES, _CHARSET));
	}

	/**
	 * Reverses htmlSpecialChars
	 *
	 * @param string $text
	 * @return  string
	 */
	public static function undoHtmlSpecialChars($text)
	{
		return htmlspecialchars_decode($text, ENT_QUOTES);
	}

	/**
	 * Converts text string with HTML entities
	 *
	 * @param string $text Text to add HTML entities
	 *
	 * @return string|string[]|null
	 */
	public static function htmlEntities($text)
	{
		return preg_replace(array('/&amp;/i', '/&nbsp;/i'), array('&', '&amp;nbsp;'),
			@htmlentities($text, ENT_QUOTES, _CHARSET));
	}

	/**
	 * Add slashes to the text if magic_quotes_gpc is turned off.
	 *
	 * @param string $text
	 *
	 * @return  string
	 *
	 * @deprecated 2.0 Use \addslashes instead!
	 */
	public static function addSlashes($text)
	{
		return addslashes($text);
	}

	/**
	 * @param string $text
	 * @return   string
	 *
	 * @deprecated 2.0 This function does nothing. So do not use it in the future!
	 */
	public static function stripSlashesGPC($text)
	{
		return $text;
	}

	/**
	 * Filters Multidimensional Array Recursively removing keys with empty values
	 * @param array $array Array to be filtered
	 * @return      array     $array
	 * @author        vaughan montgomery (vaughan@impresscms.org)
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 */
	public static function cleanArray($arr)
	{
		$rtn = array();

		foreach ($arr as $key => $a) {
			if (!is_array($a) && (!empty($a) || $a === 0)) {
				$rtn[$key] = $a;
			} elseif (is_array($a)) {
				if (count($a) > 0) {
					$a = self::cleanArray($a);
					$rtn[$key] = $a;
					if (count($a) === 0) {
						unset($rtn[$key]);
					}
				}
			}
		}
		return $rtn;
	}

	/*
	* Public Function checks Variables using specified filter type
	*
	* @TODO needs error trapping for debug if invalid types and options used!!
	*
	* @author		vaughan montgomery (vaughan@impresscms.org)
	* @copyright	(c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	*
	* @param	string		$data		Data to be checked
	* @param	string		$type		Type of Filter To use for Validation
	*			Valid Filter Types:
	*					'url' = Checks & validates URL
	*					'email' = Checks & validates Email Addresses
	*					'ip' = Checks & validates IP Addresses
	*					'str' = Checks & Sanitizes String Values
	*					'int' = Validates Integer Values
	*					'html' = Validates HTML
	*					'text' = Validates plain textareas (Non HTML)
	*					'special' htmlspecialchars filter options
	*
	* @param	mixed		$options1	Options to use with specified filter
	*			Valid Filter Options:
	*				URL:
	*					'scheme' = URL must be an RFC compliant URL (like http://example)
	*					'host' = URL must include host name (like http://www.example.com)
	*					'path' = URL must have a path after the domain name (like www.example.com/example1/)
	*					'query' = URL must have a query string (like "example.php?name=Vaughan&age=34")
	*				EMAIL:
	*					'TRUE' = Generate an email address that is protected from spammers
	*					'FALSE' = Generate an email address that is NOT protected from spammers
	*				IP:
	*					'ipv4' = Requires the value to be a valid IPv4 IP (like 255.255.255.255)
	*					'ipv6' = Requires the value to be a valid IPv6 IP (like 2001:0db8:85a3:08d3:1319:8a2e:0370:7334)
	*					'rfc' = Requires the value to be a RFC specified private range IP (like 192.168.0.1)
	*					'res' = Requires that the value is not within the reserved IP range. both IPV4 and IPV6 values
	*				STR:
	*					'noencode' = Do NOT encode quotes
	*					'striplow' = Strip characters with ASCII value below 32
	*					'striphigh' = Strip characters with ASCII value above 127
	*					'encodelow' = Encode characters with ASCII value below 32
	*					'encodehigh' = Encode characters with ASCII value above 127
	*					'encodeamp' = Encode the & character to &amp;
	*				SPECIAL:
	*					'striplow' = Strip characters with ASCII value below 32
	*					'striphigh' = Strip characters with ASCII value above 32
	*					'encodehigh' = Encode characters with ASCII value above 32
	*				INT:
	*					minimum integer range value
	*				HTML:
	*					'input' = Filters HTML for input to DB
	*					'output' = Filters HTML for rendering output
	*					'print' = Filters HTML for output to Printer
   *                   'edit' = used for edit content forms
	*				TEXT:
	*					'input' = Filters plain text for input to DB
	*					'output' = Filters plain text for rendering output
	*					'print' = Filters plain text for output to printer
	*
	* @param	mixed		$options2	Options to use with specified filter options1
	*				URL:
	*					'TRUE' = URLEncode the URL (ie. http://www.example > http%3A%2F%2Fwww.example)
	*					'FALSE' = Do Not URLEncode the URL
	*				EMAIL:
	*					'TRUE' = Reject if email is banned (Uses: $icmsConfigUser['bad_emails'])
	*					'FALSE' = Do Not use Email Blacklist
	*				IP:
	*					NOT USED!
	*				INT:
	*					maximum integer range value
	*
	* @return	mixed
	*/

	/**
	 * @param $data
	 * @param $type
	 * @param $options1
	 * @param $options2
	 * @return bool|mixed|string|string[]
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 * @author        vaughan montgomery (vaughan@impresscms.org)
	 */
	public static function checkVar($data, $type, $options1 = '', $options2 = '')
	{
		if (!$data || !$type) {
			return false;
		}

		$valid_types = ['url', 'email', 'ip', 'str', 'int', 'special', 'html', 'text'];
		if (!in_array($type, $valid_types, false)) {
			return false;
		} else {
			switch ($type) {
				case 'url':
					$valid_options1 = ['scheme', 'path', 'host', 'query'];
					$valid_options2 = [0, 1];
					if (!isset($options1) || $options1 == '' || !in_array($options1, $valid_options1)) {
						$options1 = '';
					}
					if (!isset($options2) || $options2 == '' || !in_array($options2, $valid_options2)) {
						$options2 = 0;
					} else {
						$options2 = 1;
					}
					break;

				case 'email':
					$valid_options1 = [0, 1];
					$valid_options2 = [0, 1];
					if (!isset($options1) || $options1 == '' || !in_array($options1, $valid_options1)) {
						$options1 = 0;
					} else {
						$options1 = 1;
					}
					if (!isset($options2) || $options2 == '' || !in_array($options2, $valid_options2)) {
						$options2 = 0;
					} else {
						$options2 = 1;
					}
					break;

				case 'ip':
					$valid_options1 = array('ipv4', 'ipv6', 'rfc', 'res');
					$options2 = '';
					if (!isset($options1) || $options1 == '' || !in_array($options1, $valid_options1)) {
						$options1 = 'ipv4';
					}
					break;

				case 'str':
					$valid_options1 = array('noencode', 'striplow', 'striphigh', 'encodelow', 'encodehigh', 'encodeamp');
					$options2 = '';

					if (!isset($options1) || $options1 == '' || !in_array($options1, $valid_options1)) {
						$options1 = '';
					}
					break;

				case 'special':
					$valid_options1 = array('striplow', 'striphigh', 'encodehigh');
					$options2 = '';

					if (!isset($options1) || $options1 == '' || !in_array($options1, $valid_options1)) {
						$options1 = '';
					}
					break;

				case 'int':
					if (!is_int($options1) || !is_int($options2)) {
						$options1 = '';
						$options2 = '';
					} else {
						$options1 = (int)$options1;
						$options2 = (int)$options2;
					}
					break;

				case 'html':
					$valid_options1 = array('input', 'output', 'print', 'edit');
					$options2 = '';
					if (!isset($options1) || $options1 == '' || !in_array($options1, $valid_options1)) {
						$options1 = 'input';
					}
					break;

				case 'text':
					$valid_options1 = array('input', 'output', 'print');
					$options2 = '';
					if (!isset($options1) || $options1 == '' || !in_array($options1, $valid_options1)) {
						$options1 = 'input';
					}
					break;
			}
		}
		return self::priv_checkVar($data, $type, $options1, $options2);
	}

	/**
	 * Filter an array of variables, such as $_GET or $_POST, using a set of filters.
	 *
	 * Any items in the input array not found in the filter array will be filtered as
	 * a string.
	 *
	 * @param array $input items to be filtered
	 * @param array $filters the keys of this array should match the keys in
	 *                                the input array and the values should be valid types
	 *                                for the checkVar method
	 * @param bool $strict when TRUE (default), items not in the filter array will be discarded
	 *                                when FALSE, items not in the filter array will be filtered as strings and included
	 * @return    array
	 * @author        vaughan montgomery (vaughan@impresscms.org)
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 */
	static public function checkVarArray(array $input, array $filters, $strict = true)
	{
		foreach (array_intersect_key($input, $filters) as $key => $value) {
			$options[0] = $options[1] = '';
			if (isset($filters[$key]['options'])
				&& is_array($filters[$key]['options'])
				&& isset($filters[$key]['options'][0])
			) {
				$options[0] = $filters[$key]['options'][0];
			}
			if (isset($filters[$key]['options'])
				&& is_array($filters[$key]['options'])
				&& isset($filters[$key]['options'][1])
			) {
				$options[1] = $filters[$key]['options'][1];
			}
			if (is_array($filters[$key])) {
				$filter = $filters[$key][0];
			} else {
				$filter = $filters[$key];
			}
			if (is_array($input[$key])) {
				$output[$key] = self::checkVarArray($input[$key], array($key => $filter), false);
			} else {
				$output[$key] = self::checkVar($input[$key], $filter, $options[0], $options[1]);
			}
		}

		if (!$strict) {
			foreach ($diff = array_diff_key($input, $filters) as $key => $value) {
				if (is_array($diff[$key])) {
					$output[$key] = self::checkVarArray($diff[$key], array($key => 'str'), false);
				} else {
					$output[$key] = self::checkVar($diff[$key], 'str');
				}
			}
		}
		return $output;
	}

	/**
	 * Filters textarea form data for INPUt to DB (text only!!)
	 * For HTML please use HTMLFilter::filterHTMLinput()
	 *
	 * @param string $text
	 * @return  string
	 * @author        vaughan montgomery (vaughan@impresscms.org)
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 */
	public static function filterTextareaInput($text)
	{
		icms::$preload->triggerEvent('beforeFilterTextareaInput', array(&$text));

		$text = self::htmlSpecialChars($text);
		$text = self::stripSlashesGPC($text);

		icms::$preload->triggerEvent('afterFilterTextareaInput', array(&$text));

		return $text;
	}

	/**
	 * Filters textarea for DISPLAY purposes (text only!!)
	 * For HTML please use HTMLFilter::filterHTMLdisplay()
	 *
	 * @param string $text
	 * @param bool $smiley allow smileys?
	 * @param bool $icode allow icmscode?
	 * @param bool $image allow inline images?
	 * @param bool $br convert linebreaks?
	 * @return  string
	 */
	public static function filterTextareaDisplay($text, $smiley = 1, $icode = 1, $image = 1, $br = 1)
	{
		icms::$preload->triggerEvent('beforeFilterTextareaDisplay', array(&$text, $smiley, $icode, $image, $br));

		// neccessary for the time being until we rework the IPF & Data Object Types in 2.0
		$text = str_replace('<!-- input filtered -->', '', $text);
		$text = str_replace('<!-- filtered with htmlpurifier -->', '', $text);

		$text = self::htmlSpecialChars($text);
		$text = self::codePreConv($text, $icode);
		$text = self::makeClickable($text);
		if ($smiley != 0) {
			$text = self::smiley($text);
		}
		if ($icode != 0) {
			if ($image != 0) {
				$text = self::codeDecode($text);
			} else {
				$text = self::codeDecode($text, 0);
			}
		}
		if ($br !== 0) {
			$text = self::nl2Br($text);
		}
		$text = self::codeConv($text, $icode, $image);

		icms::$preload->triggerEvent('afterFilterTextareaDisplay', array(&$text, $smiley, $icode, $image, $br));
		return $text;
	}

	/**
	 * Filters HTML form data for INPUT to DB
	 *
	 * @param string $html
	 * @param bool $smiley allow smileys?
	 * @param bool $icode allow icmscode?
	 * @param bool $image allow inline images?
	 * @return  string
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 * @author        vaughan montgomery (vaughan@impresscms.org)
	 */
	public static function filterHTMLinput($html, $smiley = 1, $icode = 1, $image = 1, $br = 0)
	{
		icms::$preload->triggerEvent('beforeFilterHTMLinput', array(&$html, 1, 1, 1, $br));

		$html = str_replace('<!-- input filtered -->', '', $html);

		$html = self::codePreConv($html, 1);
		$html = self::smiley($html);
		$html = self::codeDecode($html);
		$html = self::codeConv($html, 1, 1);

		$html = HTMLFilter::filterHTML($html);

		$purified = strpos($html, '<!-- filtered with htmlpurifier -->');
		if ($purified === false && $br == 1) {
			$html = self::nl2Br($html);
		}

		$html .= '<!-- input filtered -->';

		icms::$preload->triggerEvent('afterFilterHTMLinput', array(&$html, 1, 1, 1, $br));
		return $html;
	}

	/**
	 * Filters HTML form data for Display Only
	 * we don't really require the icmscode stuff, but we need to for content already in the DB before
	 * we start filtering on INPUT instead of OUTPUT!!
	 *
	 * @param string $html
	 * @param bool $icode allow icmscode?
	 * @return  string
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 * @author        vaughan montgomery (vaughan@impresscms.org)
	 */
	public static function filterHTMLdisplay($html, $icode = 1, $br = 0)
	{
		icms::$preload->triggerEvent('beforeFilterHTMLdisplay', array(&$html, 1, $br));

		$ifiltered = strpos($html, '<!-- input filtered -->');
		if ($ifiltered === false) {
			$html = self::codePreConv($html, 1);
			$html = self::smiley($html);
			$html = self::codeDecode($html);
			$html = self::codeConv($html, 1, 1);

			$html = HTMLFilter::filterHTML($html);

			$html .= '<!-- warning! output filtered only -->';

			$purified = strpos($html, '<!-- filtered with htmlpurifier -->');
			if ($purified === false || $br = 1) {
				$html = self::nl2Br($html);
			}
		}

		$html = self::makeClickable($html);
		$html = self::censorString($html);

//        $html = str_replace('<!-- input filtered -->', '', $html);
//        $html = str_replace('<!-- filtered with htmlpurifier -->', '', $html);

		icms::$preload->triggerEvent('afterFilterHTMLdisplay', array(&$html, 1, $br));
		return $html;
	}

	/**
	 * Replace icmsCodes with their equivalent HTML formatting
	 *
	 * @param string $text
	 * @param bool $allowimage Allow images in the text?
	 *                  On FALSE, uses links to images.
	 * @return  string
	 */
	public static function codeDecode(&$text, $allowimage = 1)
	{
		$patterns = array();
		$replacements = array();
		$patterns[] = "/\[siteurl=(['\"]?)([^\"'<>]*)\\1](.*)\[\/siteurl\]/sU";
		$replacements[] = '<a href="' . ICMS_URL . '/\\2">\\3</a>';
		$patterns[] = "/\[url=(['\"]?)(http[s]?:\/\/[^\"'<>]*)\\1](.*)\[\/url\]/sU";
		$replacements[] = '<a href="\\2" rel="external">\\3</a>';
		$patterns[] = "/\[url=(['\"]?)(ftp?:\/\/[^\"'<>]*)\\1](.*)\[\/url\]/sU";
		$replacements[] = '<a href="\\2" rel="external">\\3</a>';
		$patterns[] = "/\[url=(['\"]?)([^\"'<>]*)\\1](.*)\[\/url\]/sU";
		$replacements[] = '<a href="http://\\2" rel="external">\\3</a>';
		$patterns[] = "/\[color=(['\"]?)([a-zA-Z0-9]*)\\1](.*)\[\/color\]/sU";
		$replacements[] = '<span style="color: #\\2;">\\3</span>';
		$patterns[] = "/\[size=(['\"]?)([a-z0-9-]*)\\1](.*)\[\/size\]/sU";
		$replacements[] = '<span style="font-size: \\2;">\\3</span>';
		$patterns[] = "/\[font=(['\"]?)([^;<>\*\(\)\"']*)\\1](.*)\[\/font\]/sU";
		$replacements[] = '<span style="font-family: \\2;">\\3</span>';
		$patterns[] = "/\[email]([^;<>\*\(\)\"']*)\[\/email\]/sU";
		$replacements[] = '<a href="mailto:\\1">\\1</a>';
		$patterns[] = "/\[b](.*)\[\/b\]/sU";
		$replacements[] = '<strong>\\1</strong>';
		$patterns[] = "/\[i](.*)\[\/i\]/sU";
		$replacements[] = '<em>\\1</em>';
		$patterns[] = "/\[u](.*)\[\/u\]/sU";
		$replacements[] = '<u>\\1</u>';
		$patterns[] = "/\[d](.*)\[\/d\]/sU";
		$replacements[] = '<del>\\1</del>';
		$patterns[] = "/\[center](.*)\[\/center\]/sU";
		$replacements[] = '<div align="center">\\1</div>';
		$patterns[] = "/\[left](.*)\[\/left\]/sU";
		$replacements[] = '<div align="left">\\1</div>';
		$patterns[] = "/\[right](.*)\[\/right\]/sU";
		$replacements[] = '<div align="right">\\1</div>';
		$patterns[] = "/\[img align=center](.*)\[\/img\]/sU";
		if ($allowimage != 1) {
			$replacements[] = '<div style="margin: 0 auto; text-align: center;"><a href="\\1" rel="external">\\1</a></div>';
		} else {
			$replacements[] = '<div style="margin: 0 auto; text-align: center;"><img src="\\1" alt="" /></div>';
		}
		$patterns[] = "/\[img align=(['\"]?)(left|right)\\1]([^\"\(\)\?\&'<>]*)\[\/img\]/sU";
		$patterns[] = "/\[img]([^\"\(\)\?\&'<>]*)\[\/img\]/sU";
		$patterns[] = "/\[img align=(['\"]?)(left|right)\\1 id=(['\"]?)([0-9]*)\\3]([^\"\(\)\?\&'<>]*)\[\/img\]/sU";
		$patterns[] = "/\[img id=(['\"]?)([0-9]*)\\1]([^\"\(\)\?\&'<>]*)\[\/img\]/sU";
		if ($allowimage != 1) {
			$replacements[] = '<a href="\\3" rel="external">\\3</a>';
			$replacements[] = '<a href="\\1" rel="external">\\1</a>';
			$replacements[] = '<a href="' . ICMS_URL . '/image.php?id=\\4" rel="external">\\5</a>';
			$replacements[] = '<a href="' . ICMS_URL . '/image.php?id=\\2" rel="external">\\3</a>';
		} else {
			$replacements[] = '<img src="\\3" align="\\2" alt="" />';
			$replacements[] = '<img src="\\1" alt="" />';
			$replacements[] = '<img src="' . ICMS_URL . '/image.php?id=\\4" align="\\2" alt="\\5" />';
			$replacements[] = '<img src="' . ICMS_URL . '/image.php?id=\\2" alt="\\3" />';
		}
		$patterns[] = "/\[quote]/sU";
		$replacements[] = _QUOTEC . '<div class="icmsQuote"><blockquote><p>';
		$patterns[] = "/\[\/quote]/sU";
		$replacements[] = '</p></blockquote></div>';
		$text = str_replace("\x00", "", $text);
		$c = "[\x01-\x1f]*";
		$patterns[] = "/j{$c}a{$c}v{$c}a{$c}s{$c}c{$c}r{$c}i{$c}p{$c}t{$c}:/si";
		$replacements[] = "(script removed)";
		$patterns[] = "/a{$c}b{$c}o{$c}u{$c}t{$c}:/si";
		$replacements[] = "about :";
		$text = preg_replace($patterns, $replacements, $text);
		$text = self::codeDecode_extended($text);
		return $text;
	}

	/**
	 * Make links in the text clickable
	 *
	 * @param string $text
	 * @return  string
	 */
	public static function makeClickable($text)
	{
		global $icmsConfigPersona;
		$text = ' ' . $text;
		$patterns = array(
			"/(^|[^]_a-z0-9-=\"'\/])([a-z]+?):\/\/([^, \r\n\"\(\)'<>]+)/i",
			"/(^|[^]_a-z0-9-=\"'\/])www\.([a-z0-9\-]+)\.([^, \r\n\"\(\)'<>]+)/i",
			"/(^|[^]_a-z0-9-=\"'\/])ftp\.([a-z0-9\-]+)\.([^,\r\n\"\(\)'<>]+)/i"
			/*,	"/(^|[^]_a-z0-9-=\"'\/:\.])([a-z0-9\-_\.]+?)@([^, \r\n\"\(\)'<>\[\]]+)/i"*/
		);
		$replacements = array(
			"\\1<a href=\"\\2://\\3\" rel=\"external\">\\2://\\3</a>",
			"\\1<a href=\"http://www.\\2.\\3\" rel=\"external\">www.\\2.\\3</a>",
			"\\1<a href=\"ftp://ftp.\\2.\\3\" rel=\"external\">ftp.\\2.\\3</a>"
			/*,	"\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>"*/
		);
		$text = preg_replace($patterns, $replacements, $text);
		if ($icmsConfigPersona['shorten_url'] == 1) {
			$links = explode('<a', $text);
			$countlinks = count($links);
			for ($i = 0; $i < $countlinks; $i++) {
				$link = $links[$i];
				$link = (preg_match('#(.*)(href=")#is', $link)) ? '<a' . $link : $link;
				$begin = strpos($link, '>') + 1;
				$end = strpos($link, '<', $begin);
				$length = $end - $begin;
				$urlname = substr($link, $begin, $length);

				$maxlength = (int)($icmsConfigPersona['max_url_long']);
				$cutlength = (int)($icmsConfigPersona['pre_chars_left']);
				$endlength = -(int)($icmsConfigPersona['last_chars_left']);
				$middleurl = " ... ";
				$chunked = (strlen($urlname) > $maxlength && preg_match('#^(https://|http://|ftp://|www\.)#is',
						$urlname)) ? substr_replace($urlname, $middleurl, $cutlength, $endlength) : $urlname;
				$text = str_replace('>' . $urlname . '<', '>' . $chunked . '<', $text);
			}
		}
		$text = substr($text, 1);
		return $text;
	}

	/**
	 *
	 * @param $message
	 */
	public static function smiley($message)
	{
		return self::priv_smiley($message);
	}

	/**
	 *
	 * @param $message
	 */
	public static function getSmileys($all = false)
	{
		return self::priv_getSmileys($all);
	}

	/**
	 * Replaces banned words in a string with their replacements
	 *
	 * @param string $text
	 * @return  string
	 *
	 */
	public static function censorString(&$text)
	{
		$icmsConfigCensor = icms::$config->getConfigsByCat(Config::CATEGORY_CENSOR);
		if ($icmsConfigCensor['censor_enable']) {
			$replacement = $icmsConfigCensor['censor_replace'];
			if (!empty($icmsConfigCensor['censor_words'])) {
				foreach ($icmsConfigCensor['censor_words'] as $bad) {
					if (!empty($bad)) {
						$bad = quotemeta($bad);
						$patterns[] = "/(\s)" . $bad . '/siU';
						$replacements[] = "\\1" . $replacement;
						$patterns[] = '/^' . $bad . '/siU';
						$replacements[] = $replacement;
						$patterns[] = "/(\n)" . $bad . '/siU';
						$replacements[] = "\\1" . $replacement;
						$patterns[] = '/]' . $bad . '/siU';
						$replacements[] = ']' . $replacement;
						$text = preg_replace($patterns, $replacements, $text);
					}
				}
			}
		}
		return $text;
	}

	/**#@+
	 * Sanitizing of [code] tag
	 * @param $text
	 * @param int $imcode
	 * @return string|string[]|null
	 */
	public static function codePreConv($text, $imcode = 1)
	{
		if ((int)$imcode !== 0) {
			$patterns = "/\[code](.*)\[\/code\]/sU";
			$text = preg_replace_callback($patterns, function ($match) {
				return base64_encode($match[1]);
			}, $text);
		}
		return $text;
	}

	/**
	 * Converts text to imcode
	 *
	 * @param string $text Text to convert
	 * @param int $imcode Is the code Xcode?
	 * @param int $image configuration for the purifier
	 * @return    string    $text     the converted text
	 */
	public static function codeConv($text, $imcode = 1, $image = 1)
	{
		if ((int)$imcode !== 0) {
			$patterns = "/\[code](.*)\[\/code\]/sU";
			$text = preg_replace_callback($patterns, function ($matches) use ($image) {
				$code = DataFilter::codeSanitizer($matches[1], ($image != 0) ? 1 : 0);
				return '<div class=\"icmsCode\">' . $code . '</div>';
			}, $text);
		}
		return $text;
	}

	/**
	 * Sanitizes decoded string
	 *
	 * @param string $str String to sanitize
	 * @param string $image Is the string an image
	 * @return  string    $str      The sanitized decoded string
	 */
	public static function codeSanitizer($str, $image = 1)
	{
		$str = self::htmlSpecialChars(str_replace('\"', '"', base64_decode($str)));
		return self::codeDecode($str, $image);
	}

	/**
	 * This function gets allowed plugins from DB and loads them in the sanitizer
	 *
	 * @param string $text Plugin name
	 * @param int $allowimage Allow image?
	 * @return string
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 */
	public static function codeDecode_extended($text, $allowimage = 1)
	{
		global $icmsConfigPlugins;
		if (!empty($icmsConfigPlugins['sanitizer_plugins'])) {
			foreach ($icmsConfigPlugins['sanitizer_plugins'] as $item) {
				$text = self::executeExtension($item, $text);
			}
		}
		return $text;
	}

	/**
	 * loads the textsanitizer plugins
	 *
	 * @copyright	(c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 * @param	 string	$name	 Name of the extension to load
	 * @return	bool
	 *
	 * @deprecated Does nothing will be removed in 2.1
	 */
	static public function loadExtension($name) {

	}

	/**
	 * Executes file with a certain extension using call_user_func_array
	 *
	 * @param string $name Name of the file to load
	 * @param string $text Text to show if the function doesn't exist
	 * @return    string     the return of the called function
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 */
	public static function executeExtension($name, $text)
	{
		self::loadExtension($name);
		$func = "textsanitizer_{$name}";
		if (!function_exists($func)) {
			return $text;
		}
		$args = array_slice(func_get_args(), 1);
		return call_user_func_array($func, $args);
	}

	/**
	 * Syntaxhighlight the code
	 *
	 * @param string $text purifies (lightly) and then syntax highlights the text
	 * @return    string    $text     the syntax highlighted text
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 */
	public static function textsanitizer_syntaxhighlight(&$text)
	{
		global $icmsConfigPlugins;
		if ($icmsConfigPlugins['code_sanitizer'] == 'php') {
			$text = self::undoHtmlSpecialChars($text);
			$text = self::textsanitizer_php_highlight($text);
		} elseif ($icmsConfigPlugins['code_sanitizer'] == 'geshi') {
			$text = self::undoHtmlSpecialChars($text);
			$text = '<code>' . self::textsanitizer_geshi_highlight($text) . '</code>';
		} else {
			$text = '<pre><code>' . $text . '</code></pre>';
		}
		return $text;
	}

	/**
	 * Syntaxhighlight the code using PHP highlight
	 *
	 * @param string $text Text to highlight
	 * @return    string    $buffer   the highlighted text
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 */
	public static function textsanitizer_php_highlight($text)
	{
		$text = trim($text);
		$addedtag_open = 0;
		if (!strpos($text, '<?php') && (strpos($text, '<?php') !== 0)) {
			$text = "<?php\n" . $text;
			$addedtag_open = 1;
		}
		$addedtag_close = 0;
		if (!strpos($text, '?>')) {
			$text .= '?>';
			$addedtag_close = 1;
		}
		$oldlevel = error_reporting(0);
		$buffer = highlight_string($text, true);
		error_reporting($oldlevel);
		$pos_open = $pos_close = 0;
		if ($addedtag_open) {
			$pos_open = strpos($buffer, '&lt;?php');
		}
		if ($addedtag_close) {
			$pos_close = strrpos($buffer, '?&gt;');
		}

		$str_open = ($addedtag_open) ? substr($buffer, 0, $pos_open) : '';
		$str_close = ($pos_close) ? substr($buffer, $pos_close + 5) : '';

		$length_open = ($addedtag_open) ? $pos_open + 8 : 0;
		$length_text = ($pos_close) ? $pos_close - $length_open : 0;
		$str_internal = ($length_text) ? substr($buffer, $length_open, $length_text) : substr($buffer, $length_open);

		return $str_open . $str_internal . $str_close;
	}

	/**
	 * Syntaxhighlight the code using Geshi highlight
	 *
	 * @param string $text The text to highlight
	 * @return    string    $code     the highlighted text
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 */
	public static function textsanitizer_geshi_highlight($text)
	{
		global $icmsConfigPlugins;

		$language = str_replace('.php', '', $icmsConfigPlugins['geshi_default']);

		// Create the new GeSHi object, passing relevant stuff
		$geshi = new GeSHi($text, $language);

		// Enclose the code in a <div>
		$geshi->set_header_type(GESHI_HEADER_NONE);

		// Sets the proper encoding charset other than "ISO-8859-1"
		$geshi->set_encoding(_CHARSET);

		$geshi->set_link_target('_blank');

		// Parse the code
		$code = $geshi->parse_code();

		return $code;
	}

	/**
	 * Trims certain text
	 *
	 * Replaces include/functions.php :: xoops_trim()
	 *
	 * @param string $text The Text to trim
	 * @return    string    $text    The trimmed text
	 */
	public static function icms_trim($text)
	{
		if (function_exists('xoops_language_trim')) {
			return xoops_language_trim($text);
		}
		return trim($text);
	}

	/**
	 * Function to reverse given text with utf-8 character sets
	 *
	 * credit for this function should goto lwc courtesy of php.net.
	 *
	 * @param string $str The text to be reversed.
	 * @param string $reverse TRUE will reverse everything including numbers, FALSE will reverse text only but numbers will be left intact.
	 *                example: when TRUE: impresscms 2008 > 8002 smcsserpmi, FALSE: impresscms 2008 > 2008 smcsserpmi
	 * @return string
	 */
	public static function utf8_strrev($str, $reverse = false)
	{
		preg_match_all('/./us', $str, $ar);
		if ($reverse) {
			return implode('', array_reverse($ar[0]));
		} else {
			$temp = array();
			foreach ($ar[0] as $value) {
				if (is_numeric($value) && !empty($temp[0]) && is_numeric($temp[0])) {
					foreach ($temp as $key => $value2) {
						if (is_numeric($value2)) {
							$pos = ($key + 1);
						} else {
							break;
						}
						$temp2 = array_splice($temp, $pos);
						$temp = array_merge($temp, array($value), $temp2);
					}
				} else {
					array_unshift($temp, $value);
				}
			}
			return implode('', $temp);
		}
	}

	/**
	 * Returns the portion of string specified by the start and length parameters.
	 * If $trimmarker is supplied, it is appended to the return string.
	 * This function works fine with multi-byte characters if mb_* functions exist on the server.
	 *
	 * Replaces legacy include/functions.php :: xoops_substr()
	 *
	 * @param string $str
	 * @param int $start
	 * @param int $length
	 * @param string $trimmarker
	 *
	 * @return   string
	 */
	public static function icms_substr($str, $start, $length, $trimmarker = '...')
	{
		global $icmsConfigMultilang;

		if ($icmsConfigMultilang['ml_enable']) {
			$tags = explode(',', $icmsConfigMultilang['ml_tags']);
			$strs = [];
			$hasML = false;
			foreach ($tags as $tag) {
				if (preg_match("/\[" . $tag . "](.*)\[\/" . $tag . "\]/sU", $str, $matches) && count($matches) > 0) {
					$hasML = true;
					$strs[] = $matches[1];
				}
			}
		} else {
			$hasML = false;
		}

		if (!$hasML) {
			$strs = [$str];
		}

		for ($i = 0; $i <= count($strs) - 1; $i++) {
			if (!XOOPS_USE_MULTIBYTES) {
				$strs[$i] = (strlen($strs[$i]) - $start <= $length) ? substr($strs[$i], $start, $length) : substr($strs[$i], $start, $length - strlen($trimmarker)) . $trimmarker;
			}
			if (function_exists('mb_internal_encoding') && @mb_internal_encoding(_CHARSET)) {
				$str2 = mb_strcut($strs[$i], $start, $length - strlen($trimmarker));
				$strs[$i] = $str2 . (mb_strlen($strs[$i]) !== mb_strlen($str2) ? $trimmarker : '');
			}

			$DEP_CHAR = 127;
			$pos_st = 0;
			$action = false;
			for ($pos_i = 0, $pos_iMax = strlen($strs[$i]); $pos_i < $pos_iMax; $pos_i++) {
				if (ord(substr($strs[$i], $pos_i, 1)) > 127) {
					$pos_i++;
				}
				if ($pos_i <= $start) {
					$pos_st = $pos_i;
				}
				if ($pos_i >= $pos_st + $length) {
					$action = true;
					break;
				}
			}
			$strs[$i] = ($action) ? substr($strs[$i], $pos_st, $pos_i - $pos_st - strlen($trimmarker)) . $trimmarker : $strs[$i];
			$strs[$i] = ($hasML) ? '[' . $tags[$i] . ']' . $strs[$i] . '[/' . $tags[$i] . ']' : $strs[$i];
		}
		return implode('', $strs);
	}

// -------- Private Functions --------

	/**
	 * Private Function checks & Validates Data
	 *
	 * @copyright The ImpressCMS Project <http://www.impresscms.org>
	 *
	 * See public function checkVar() for parameters
	 *
	 * @return
	 */
	private static function priv_checkVar($data, $type, $options1, $options2)
	{
		switch ($type) {
			case 'url': // returns False if URL invalid, returns $string if Valid
				$data = filter_var($data, FILTER_SANITIZE_URL);

				switch ($options1) {

					case 'path':
						$valid = filter_var($data, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
						break;

					case 'query':
						$valid = filter_var($data, FILTER_VALIDATE_URL, FILTER_FLAG_QUERY_REQUIRED);
						break;

					case 'scheme':
					case 'host':
					default:
						$valid = filter_var($data, FILTER_VALIDATE_URL);
						break;
				}
				if ($valid) {
					if (isset($options2) && (int)$options2 === 1) {
						return filter_var($data, FILTER_SANITIZE_ENCODED);
					}
					return $data;
				}
				return false;

			case 'email': // returns False if email is invalid, returns $string if valid
				global $icmsConfigUser;

				$data = filter_var($data, FILTER_SANITIZE_EMAIL);

				if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
					if ((int)$options2 === 1 && is_array($icmsConfigUser['bad_emails'])) {
						foreach ($icmsConfigUser['bad_emails'] as $be) {
							if ((!empty($be) && preg_match('/' . $be . '/i', $data))) {
								return false;
							}
						}
						$icmsStopSpammers = new StopSpammer();
						if ($icmsStopSpammers->badEmail($data)) {
							return false;
						}
					}
				} else {
					return false;
				}
				if ((int)$options1 === 1) {
					$data = str_replace(['@', '.'], [' at ', ' dot '], $data);
				}
				return $data;

			case 'ip': // returns False if IP is invalid, returns TRUE if valid
				switch ($options1) {
					case 'ipv4':
						return filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);

					case 'ipv6':
						return filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

					case 'rfc':
						return filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);

					case 'res':
						return filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);

					default:
						return filter_var($data, FILTER_VALIDATE_IP);
				}

			case 'str': // returns $string
				switch ($options1) {
					case 'noencode':
						return filter_var($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);

					case 'striplow':
						return filter_var($data, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);

					case 'striphigh':
						return filter_var($data, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);

					case 'encodelow':
						return filter_var($data, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

					case 'encodehigh':
						return filter_var($data, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_HIGH);

					case 'encodeamp':
						return filter_var($data, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_AMP);

					default:
						return htmlspecialchars($data);
				}

			case 'int': // returns $int, returns FALSE if $opt1 & 2 set & $data is not inbetween values of $opt1 & 2
				if (isset($options1, $options2) && is_int($options1) && is_int($options2)) {
					$option = array('options' => array('min_range' => $options1,
						'max_range' => $options2
					));

					return filter_var($data, FILTER_VALIDATE_INT, $option);
				}

				return filter_var($data, FILTER_VALIDATE_INT);

			case 'special': // returns $string
				switch ($options1) {
					case 'striplow':
						return filter_var($data, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW);

					case 'striphigh':
						return filter_var($data, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_HIGH);

					case 'encodehigh':
						return filter_var($data, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH);

					default:
						return filter_var($data, FILTER_SANITIZE_SPECIAL_CHARS);
				}

			case 'html': // returns $string
				switch ($options1) {
					case 'input':
					default:
						$data = self::stripSlashesGPC($data);
						return self::filterHTMLinput($data);

					case 'output':
						return self::filterHTMLdisplay($data);

					case 'edit':
						$filtered = strpos($data, '<!-- input filtered -->');
						if ($filtered !== false) {
							$data = str_replace(['<!-- input filtered -->', '<!-- filtered with htmlpurifier -->'], '', $data);
						}
						return htmlspecialchars($data, ENT_QUOTES, _CHARSET, false);

					case 'print':
						// do nothing yet
						break;
				}

			case 'text': // returns $string
				switch ($options1) {
					case 'input':
					default:
						$data = self::stripSlashesGPC($data);
						return self::filterTextareaInput($data);

					case 'output':
						$data = self::stripSlashesGPC($data);
						return self::filterTextareaDisplay($data);
						break;

					case 'print':
						// do nothing yet
						break;
				}
		}
	}

	/**
	 * Replace emoticons in the message with smiley images
	 *
	 * @param string $message
	 * @return   string
	 * @author        vaughan montgomery (vaughan@impresscms.org)
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 */
	private static function priv_smiley($message)
	{
		$smileys = self::priv_getSmileys(true);
		foreach ($smileys as $smile) {
			$message = str_replace(
				$smile['code'],
				'<img src="' . ICMS_UPLOAD_URL . '/' . htmlspecialchars($smile['smile_url'])
				. '" alt="" />',
				$message
			);
		}
		return $message;
	}

	/**
	 * Get the smileys
	 *
	 * @param bool $all
	 * @return   array
	 * @author        vaughan montgomery (vaughan@impresscms.org)
	 * @copyright    (c) 2007-2010 The ImpressCMS Project - www.impresscms.org
	 *
	 */
	private static function priv_getSmileys($all = false)
	{
		if ((count(self::$allSmileys) === 0) && ($result = icms::$xoopsDB->query('SELECT * FROM ' . icms::$xoopsDB->prefix('smiles')))) {
			while ($smiley = icms::$xoopsDB->fetchArray($result)) {
				if ($smiley['display']) {
					self::$displaySmileys[] = $smiley;
				}
				self::$allSmileys[] = $smiley;
			}
		}
		return $all ? self::$allSmileys : self::$displaySmileys;
	}
}
