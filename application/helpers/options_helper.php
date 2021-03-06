<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


/**
 * Replaces the gettext function to possible alternatives
 *
 * @param type $text
 * @return type
 */
if (function_exists('_'))
{
	function __($text)
	{
		$text = _($text);
		$text = str_replace('{{FOOL_NAME}}', FOOL_NAME, $text);
		return $text;
	}
	
	function _ngettext($msgid1, $msgid2, $n)
	{
		return ngettext($msgid1, $msgid2, $n);
	}
}
else
{
	function __($text)
	{
		$text = str_replace('{{FOOL_NAME}}', FOOL_NAME, $text);
		return $text;
	}
	
	function _ngettext($msgid1, $msgid2, $n)
	{
		if($n != 1)
			return __($msgid2);
		
		return __($msgid2);
	}
}


/**
 * Check if the string is formed by numbers only
 *
 * @param string $str the string to check
 * @return boolean
 */
function is_natural($str)
{
	return (bool) preg_match('/^[0-9]+$/', $str);
}


/**
 * Check that this is a post number with possible subnumber and , or _ divisor
 *
 * @param type $str
 * @return boolean
 */
function is_post_number($str)
{
	if (is_natural($str))
	{
		return TRUE;
	}

	return (bool) preg_match('/^[0-9]+(,|_)[0-9]+$/', $str);
}

/**
 * This function is used to get values from the preferences table for
 * the option specified.
 *
 * @param string $option name of the option
 * @param mixed $fallback the fallback value for the option
 * @return mixed the value of the option
 */
if (!function_exists('get_setting'))
{


	function get_setting($option, $fallback = NULL)
	{
		$CI = & get_instance();
		$preferences = $CI->fs_options;

		if (substr($option, -1, 1) == ']' && substr($option, -2, 1) != '[')
		{
			// we have an associative array... get rid of it
			$pos = strrpos($option, '[');
			$key = substr($option, $pos + 1, -1);
			$option = substr($option, 0, $pos);
		}

		if (isset($preferences[$option]) && $preferences[$option] != NULL)
		{
			return trim($preferences[$option]);
		}

		if (!is_null($fallback))
		{
			return trim($fallback);
		}

		return FALSE;
	}

}


if (!function_exists('set_setting'))
{


	/**
	 * Update preferences' table value one by one
	 *
	 * @param string $name name of the option
	 * @param mixed $value value to set
	 */
	function set_setting($name, $value)
	{
		if (is_array($value))
		{
			$value = serialize($value);
		}

		// get_settings() won't tell us if the value exists
		$CI = & get_instance();

		$query = $CI->db->where('name', $name)->get('preferences');

		if ($query->num_rows() == 1)
		{
			$CI->db->update(
				'preferences', array('value' => $value), array('name' => $name)
			);
		}
		else
		{
			$CI->db->insert(
				'preferences', array('name' => $name, 'value' => $value)
			);
		}

		// reload settings
		load_settings();
	}

}
/**
 * This function loads all the options from the preferences table to
 * be used with get_setting().
 */
if (!function_exists('load_settings'))
{


	function load_settings()
	{
		$CI = & get_instance();
		$preferences = $CI->db->get('preferences')->result_array();

		$settings = array();
		foreach ($preferences as $item)
		{
			$settings[$item['name']] = $item['value'];
		}

		$CI->fs_options = $settings;
	}

}


if (!function_exists('enable_maintenance'))
{


	/**
	 * Enables maintenance mode via file or via database
	 *
	 * @param string $message The message to show to users during the maintenance period
	 * @param bool $full_lock use a file in root to lock access. Remove MAINTENANCE.html manually if necessary
	 */
	function enable_maintenance($message = FALSE, $full_lock = FALSE)
	{
		if ($full_lock)
		{
			$message = ($message)? : __('The system is under maintenance. Be back later.');
			$file = file_get_contents('assets/maintenance.sample.html');
			$file = str_replace(array('%%MESSAGE%%', '%%SITE_URL%%'), array($message, site_url('@default')), $file);
			file_put_contents('MAINTENANCE.html', $file);
		}
		else
		{
			set_setting('fs_maintenance_enabled', ($message)? : __('The system is under maintenance. Be back later.'));
		}
	}

}


if (!function_exists('disable_maintenance'))
{


	/**
	 * Disables maintenance mode, via file or via database
	 *
	 * @param string $message The message to show to users during the maintenance period
	 * @param bool $full_lock use a file in root to lock access. Remove MAINTENANCE.html manually if necessary
	 */
	function disable_maintenance($full_lock = FALSE)
	{
		if ($full_lock)
		{
			if (file_exists('MAINTENANCE.html'))
				unlink('MAINTENANCE.html');
		}
		else
		{
			set_setting('fs_maintenance_enabled', '');
		}
	}

}


/**
 * This function escapes all html entities for fuuka.
 */
if (!function_exists('fuuka_htmlescape'))
{


	function fuuka_htmlescape($input)
	{
		$input = preg_replace('/[\x80]+/S', '', $input);
		$input = remove_invisible_characters($input);
		return htmlentities($input, ENT_COMPAT | ENT_IGNORE, 'UTF-8');
	}

}

/**
 * These functions preforms URLSAFE modifications to the base64
 * encoding and decoding process.
 */
if (!function_exists('urlsafe_b64encode'))
{


	function urlsafe_b64encode($string)
	{
		$string = base64_encode($string);
		return str_replace(array('+', '/', '='), array('-', '_', ''), $string);
	}

}


if (!function_exists('urlsafe_b64decode'))
{


	function urlsafe_b64decode($string)
	{
		$string = str_replace(array('-', '_'), array('+', '/'), $string);
		return base64_decode($string);
	}

}


/**
 * Return selected radix' shortname
 *
 * @author Woxxy
 */
/**
 * This function returns the current selected radix board.
 */
if (!function_exists('get_selected_radix'))
{


	function get_selected_radix()
	{
		$CI = & get_instance();
		return $CI->radix->get_selected();
	}

}


/**
 * Locate ImageMagick and determine if it has been installed or not.
 */


/**
 * This function locates and determines if the ImageMagick
 * @return bool
 */
function locate_imagemagick()
{
	$CI = & get_instance();

	// initialize ff_imagemagick or return available value
	if (!isset($CI->ff_imagemagick))
	{
		$CI->ff_imagemagick = new stdClass();
	}
	elseif (isset($CI->ff_imagemagick->available))
	{
		return $CI->ff_imagemagick->available;
	}

	// set default values
	$CI->ff_imagemagick->path = '';
	$CI->ff_imagemagick->exec = FALSE;
	$CI->ff_imagemagick->available = FALSE;

	// begin locating paths
	if (ini_get('safe_mode') || !in_array('exec', explode(',', ini_get('disabled_functions'))))
	{
		$CI->ff_imagemagick->exec = TRUE;

		$paths = array(get_setting('ff_path_imagemagick_bin', '/usr/bin'), '/usr/local/bin');
		foreach ($paths as $path)
		{
			if (!preg_match('/convert$/i', $path))
			{
				$binary = rtrim($path, '/') . '/' . 'convert';
			}

			if (@file_exists($binary) || @file_exists($binary . '.exe'))
			{
				$CI->ff_imagemagick->path = rtrim($path, '/');
				break;
			}
		}

		if ($CI->ff_imagemagick->path !== '')
		{
			exec($CI->ff_imagemagick->path . '/' . 'convert' . ' -version', $res);
			if (preg_match('/ImageMagick/i', $res[0]))
			{
				$CI->ff_imagemagick->available = TRUE;
				return TRUE;
			}
		}
	}

	return FALSE;
}

/**
 * This function parses the input and generates valid clickable links.
 * MODIFIED VERSION OF CODEIGNITER'S FUNCTION auto_link()
 */
if (!function_exists('auto_linkify'))
{


	function auto_linkify($str, $type = 'both', $popup = FALSE)
	{
		if ($type != 'email')
		{
			if (preg_match_all("#(^|\s|\(|\])((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $str, $matches))
			{
				$pop = ($popup == TRUE) ? " target=\"_blank\" " : "";

				for ($i = 0; $i < count($matches['0']); $i++)
				{
					$period = '';
					if (preg_match("|\.$|", $matches['6'][$i]))
					{
						$period = '.';
						$matches['6'][$i] = substr($matches['6'][$i], 0, -1);
					}

					$internal = (strpos($matches['6'][$i], $_SERVER['HTTP_HOST']) === 0);

					if (!$internal && defined('FOOL_SUBDOMAINS_ENABLED') && FOOL_SUBDOMAINS_ENABLED == TRUE)
					{
						$subdomains = array(
							FOOL_SUBDOMAINS_SYSTEM,
							FOOL_SUBDOMAINS_BOARD,
							FOOL_SUBDOMAINS_ARCHIVE,
							FOOL_SUBDOMAINS_DEFAULT
						);

						foreach ($subdomains as $subdomain)
						{
							if (strpos($matches['6'][$i], rtrim($subdomain, '.')) === 0)
							{
								$host_array = explode('.', $_SERVER['HTTP_HOST']);
								array_shift($host_array);
								array_unshift($host_array, $subdomain);
								$host = implode('.', $host_array);
								if (strpos($matches['6'][$i], $host) === 0)
								{
									$internal = TRUE;
									break;
								}
							}
						}
					}

					if ($internal)
					{
						$str = str_replace($matches['0'][$i],
							$matches['1'][$i] . '<a href="//' .
							$matches['5'][$i] .
							preg_replace('/[[\/\!]*?[^\[\]]*?]/si', '', $matches['6'][$i]) . '"' . $pop . '>http' .
							$matches['4'][$i] . '://' .
							$matches['5'][$i] .
							$matches['6'][$i] . '</a>' .
							$period, $str);
					}
					else
					{
						$str = str_replace($matches['0'][$i],
							$matches['1'][$i] . '<a href="http' .
							$matches['4'][$i] . '://' .
							$matches['5'][$i] .
							preg_replace('/[[\/\!]*?[^\[\]]*?]/si', '', $matches['6'][$i]) . '"' . $pop . '>http' .
							$matches['4'][$i] . '://' .
							$matches['5'][$i] .
							$matches['6'][$i] . '</a>' .
							$period, $str);
					}
				}
			}
		}

		if ($type != 'url')
		{
			if (preg_match_all("/([a-zA-Z0-9_\.\-\+]+)@([a-zA-Z0-9\-]+)\.([a-zA-Z0-9\-\.]*)/i", $str, $matches))
			{
				for ($i = 0; $i < count($matches['0']); $i++)
				{
					$period = '';
					if (preg_match("|\.$|", $matches['3'][$i]))
					{
						$period = '.';
						$matches['3'][$i] = substr($matches['3'][$i], 0, -1);
					}

					$str = str_replace($matches['0'][$i],
						safe_mailto($matches['1'][$i] . '@' . $matches['2'][$i] . '.' . $matches['3'][$i]) . $period, $str);
				}
			}
		}

		return $str;
	}

}


/**
 * This function parses the input and converts BBCODE tags to valid HTML output.
 * It uses a class written by Christian Seiler.
 */
if (!function_exists('parse_bbcode'))
{


	function parse_bbcode($str, $special = FALSE)
	{
		require_once(FCPATH . "assets/stringparser-bbcode/library/stringparser_bbcode.class.php");
		$CI = & get_instance();

		$bbcode = new StringParser_BBCode();

		// add list of bbcode for formatting
		$bbcode->addCode('code', 'simple_replace', NULL, array('start_tag' => '<code>', 'end_tag' => '</code>'), 'code',
			array('block', 'inline'), array());
		$bbcode->addCode('spoiler', 'simple_replace', NULL,
			array('start_tag' => '<span class="spoiler">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'),
			array('code'));
		$bbcode->addCode('sub', 'simple_replace', NULL, array('start_tag' => '<sub>', 'end_tag' => '</sub>'), 'inline',
			array('block', 'inline'), array('code'));
		$bbcode->addCode('sup', 'simple_replace', NULL, array('start_tag' => '<sup>', 'end_tag' => '</sup>'), 'inline',
			array('block', 'inline'), array('code'));
		$bbcode->addCode('b', 'simple_replace', NULL, array('start_tag' => '<b>', 'end_tag' => '</b>'), 'inline',
			array('block', 'inline'), array('code'));
		$bbcode->addCode('i', 'simple_replace', NULL, array('start_tag' => '<em>', 'end_tag' => '</em>'), 'inline',
			array('block', 'inline'), array('code'));
		$bbcode->addCode('m', 'simple_replace', NULL, array('start_tag' => '<tt class="code">', 'end_tag' => '</tt>'),
			'inline', array('block', 'inline'), array('code'));
		$bbcode->addCode('o', 'simple_replace', NULL, array('start_tag' => '<span class="overline">', 'end_tag' => '</span>'),
			'inline', array('block', 'inline'), array('code'));
		$bbcode->addCode('s', 'simple_replace', NULL,
			array('start_tag' => '<span class="strikethrough">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'),
			array('code'));
		$bbcode->addCode('u', 'simple_replace', NULL,
			array('start_tag' => '<span class="underline">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'),
			array('code'));

		// if $special == TRUE, add special bbcode
		if ($special === TRUE)
		{
			if ($CI->theme->get_selected_theme() == 'fuuka' || $CI->theme->get_selected_theme() == 'yotsuba')
			{
				$bbcode->addCode('moot', 'simple_replace', NULL,
					array('start_tag' => '<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">', 'end_tag' => '</div>'),
					'inline', array('block', 'inline'), array());
			}
			else
			{
				$bbcode->addCode('moot', 'simple_replace', NULL, array('start_tag' => '', 'end_tag' => ''), 'inline',
					array('block', 'inline'), array());
			}
		}

		return $bbcode->parse($str);
	}

}


/**
 * Parse Posted Data
 */
function check_commentdata($data = array(), $hash = FALSE)
{
	require_once(FCPATH . "assets/anti-spam/nospam.php");

	$nospam = new NoSpam();
	$nospam->compile_spam_database();

	return $nospam->is_spam($data, $hash);
}


/**
 * Compare with the local IP list if the IP is a possible spammer
 *
 * @param string $ip
 * @return bool  Returns true if the IP is in the stopforumspam repository
 */
function check_stopforumspam_ip($ip)
{
	$CI = & get_instance();
	$query = $CI->db->query('
		SELECT ip FROM ' . $CI->db->protect_identifiers('stopforumspam', TRUE) . '
		WHERE ip = INET_ATON(' . $CI->db->escape($ip) . ')
		LIMIT 0,1;
	');
	if ($query->num_rows() > 0)
		print_r($query->result());
	return $query->num_rows() > 0;
}


function compress_html()
{
	$CI = & get_instance();
	$buffer = $CI->output->get_output();

	$search = array(
		'/\>[^\S ]+/s', //strip whitespaces after tags, except space
		'/[^\S ]+\</s', //strip whitespaces before tags, except space
		'/(\s)+/s' // shorten multiple whitespace sequences
	);
	$replace = array(
		'> ',
		' <',
		'\\1'
	);
	$buffer = preg_replace($search, $replace, $buffer);

	$CI->output->set_output($buffer);
	$CI->output->_display();
}


/**
 * Convert an IP address from presentation to decimal(39,0) format suitable for storage in MySQL
 *
 * @param string $ip_address An IP address in IPv4, IPv6 or decimal notation
 * @return string The IP address in decimal notation
 */
function inet_ptod($ip_address)
{
	$fallback = FALSE;

	if (!function_exists('bcadd'))
	{
		$fallback = TRUE;
		$CI = & get_instance();
		$CI->load->library('Math_BigInteger');
	}

	// IPv4 address
	if (strpos($ip_address, ':') === false && strpos($ip_address, '.') !== false)
	{
		$ip_address = '::' . $ip_address;
	}

	// IPv6 address
	if (strpos($ip_address, ':') !== false)
	{
		$network = inet_pton($ip_address);
		$parts = unpack('N*', $network);

		foreach ($parts as &$part)
		{
			if ($part < 0)
			{
				if ($fallback)
				{
					$part = bcadd((string) $part, '4294967296');
				}
				else
				{
					$part = new Math_BigInteger($part);
					$magic = new Math_BigInteger('4294967296');
					$part = $part->add($magic)->toString();
				}
			}

			if (!is_string($part))
			{
				$part = (string) $part;
			}
		}

		$decimal = $parts[4];
		if ($fallback)
		{
			$decimal = bcadd($decimal, bcmul($parts[3], '4294967296'));
			$decimal = bcadd($decimal, bcmul($parts[2], '18446744073709551616'));
			$decimal = bcadd($decimal, bcmul($parts[1], '79228162514264337593543950336'));
		}
		else
		{
			$parts_big = array();
			$parts_big_mul = array();

			$decimal = new Math_BigInteger($decimal);

			$parts_big[3] = new Math_BigInteger($parts[3]);
			$parts_big_mul[3] = new Math_BigInteger('4294967296');
			$parts_mul = $parts_big[3]->multiply($parts_big_mul[3]);
			$decimal = $parts_mul->add($decimal);

			$parts_big[2] = new Math_BigInteger($parts[2]);
			$parts_big_mul[2] = new Math_BigInteger('18446744073709551616');
			$parts_mul = $parts_big[2]->multiply($parts_big_mul[2]);
			$decimal = $parts_mul->add($decimal);

			$parts_big[1] = new Math_BigInteger($parts[1]);
			$parts_big_mul[1] = new Math_BigInteger('79228162514264337593543950336');
			$parts_mul = $parts_big[1]->multiply($parts_big_mul[1]);
			$decimal = $parts_mul->add($decimal);

			$decimal = $decimal->toString();
		}

		return $decimal;
	}

	// Decimal address
	return $ip_address;
}


/**
 * Convert an IP address from decimal format to presentation format
 *
 * @param string $decimal An IP address in IPv4, IPv6 or decimal notation
 * @return string The IP address in presentation format
 */
function inet_dtop($decimal)
{
	// fallback since BC Math is something to add at compile time
	$fallback = FALSE;

	if (!extension_loaded('bcmath'))
	{
		$fallback = TRUE;
		$CI = & get_instance();
		$CI->load->library('Math_BigInteger');
	}

	// IPv4 or IPv6 format
	if (strpos($decimal, ':') !== false || strpos($decimal, '.') !== false)
	{
		return $decimal;
	}

	// Decimal format
	if (!$fallback)
	{
		$parts = array();
		$parts[1] = bcdiv($decimal, '79228162514264337593543950336', 0);
		$decimal = bcsub($decimal, bcmul($parts[1], '79228162514264337593543950336'));
		$parts[2] = bcdiv($decimal, '18446744073709551616', 0);
		$decimal = bcsub($decimal, bcmul($parts[2], '18446744073709551616'));
		$parts[3] = bcdiv($decimal, '4294967296', 0);
		$decimal = bcsub($decimal, bcmul($parts[3], '4294967296'));
		$parts[4] = $decimal;
	}
	else
	{
		$parts_big = array();
		$parts_big_mul = array();

		$decimal = new Math_BigInteger($decimal);

		$parts_big_mul[1] = new Math_BigInteger('79228162514264337593543950336');
		list($parts_big[1]) = $decimal->divide($parts_big_mul[1]);
		$decimal = $decimal->subtract($parts_big[1]->multiply($parts_big_mul[1]));

		$parts_big_mul[2] = new Math_BigInteger('18446744073709551616');
		list($parts_big[2]) = $decimal->divide($parts_big_mul[2]);
		$decimal = $decimal->subtract($parts_big[2]->multiply($parts_big_mul[2]));

		$parts_big_mul[3] = new Math_BigInteger('4294967296');
		list($parts_big[3]) = $decimal->divide($parts_big_mul[3]);
		$decimal = $decimal->subtract($parts_big[3]->multiply($parts_big_mul[3]));

		$decimal = $decimal->toString();
		$parts_big[] = $decimal;

		$parts = $parts_big;
	}

	foreach ($parts as &$part)
	{
		if (!$fallback)
		{
			if (bccomp($part, '2147483647') == 1)
			{
				$part = bcsub($part, '4294967296');
			}

			$part = (int) $part;
		}
		else
		{
			$part = new Math_BigInteger($part);
			if ($part->compare($parts_big_mul[3]))
			{
				$part = $part->subtract($parts_big_mul[3]);
			}

			$part = (int) $part->toString();
		}
	}

	$network = pack('N4', $parts[1], $parts[2], $parts[3], $parts[4]);
	$ip_address = inet_ntop($network);

	// Turn IPv6 to IPv4 if it's IPv4
	if (preg_match('/^::\d+.\d+.\d+.\d+$/', $ip_address))
	{
		return substr($ip_address, 2);
	}

	return $ip_address;
}


function get_webserver_user()
{
	$whoami = FALSE;

	// if exec is enable, just check with whoami function who's running php
	if (exec_enabled())
		$whoami = exec('whoami');

	// if exec is not enabled, write a file and check who has the permissions on it
	if (!$whoami && is_writable('content') && function_exists('posix_getpwid'))
	{
		write_file('content/testing_123.txt', 'testing_123');
		$whoami = posix_getpwuid(fileowner('content/testing_123.txt'));
		$whoami = $whoami['name'];
		unlink('content/testing_123.txt');
	}

	// if absolutely unable to tell who's the php user, just apologize
	// else, give a precise command for shell to enter
	if (!$whoami)
		return FALSE;
	else
		return $whoami;
}


function get_webserver_group()
{
	$whoami = FALSE;

	// if exec is enable, just check with groups function who's running php's groups
	if (exec_enabled())
	{
		$whoami = exec('groups');
		// it might be a list, get only the first
		$whoami = explode(' ', $whoami);
		if (count($whoami) > 0)
			return $whoami[0];
	}

	// if exec is not enabled, write a file and check who has the permissions on it
	if (is_writable('content') && function_exists('posix_getpwid'))
	{
		write_file('content/testing_123.txt', 'testing_123');
		$whoami = posix_getgrgid(filegroup('content/testing_123.txt'));
		$whoami = $whoami['name'];
		unlink('content/testing_123.txt');
		return $whoami;
	}

	// if absolutely unable to tell who's the php user, just apologize
	// else, give a precise command for shell to enter
	return FALSE;
}


function exec_enabled()
{
	$disabled = explode(',', ini_get('disable_functions'));
	return !in_array('exec', $disabled);
}


function java_enabled()
{
	if (exec_enabled())
	{
		$res = popen('java -version 2>&1', 'r');
		$read = fread($res, 128);
		if (strpos($read, 'java') === 0)
		{
			return TRUE;
		}
		pclose($res);
	}
	return FALSE;
}
