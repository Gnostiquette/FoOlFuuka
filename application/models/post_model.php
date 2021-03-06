<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Post_model extends CI_Model
{
	// store all relavent data regarding posts displayed
	var $posts_arr = array();
	var $backlinks = array();

	// global variables used for processing due to callbacks
	var $backlinks_hash_only_url = FALSE;
	var $current_p = NULL;
	var $features = TRUE;
	var $realtime = FALSE;


	function __construct()
	{
		parent::__construct();
	}

	/**
	 * The functions with an underscore prefix will respond to plugins before and after
	 *
	 * @param string $name
	 * @param array $parameters
	 */
	function __call($name, $parameters)
	{
		$before = $this->plugins->run_hook('fu_post_model_before_' . $name, $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the 
		// replaced function wont' be run
		$replace = $this->plugins->run_hook('fu_post_model_replace_' . $name, $parameters, array($parameters));

		if($replace['return'] !== NULL)
		{
			$return = $replace['return'];
		}
		else
		{
			switch (count($parameters)) {
				case 0:
					$return = $this->{'p_' . $name}();
					break;
				case 1:
					$return = $this->{'p_' . $name}($parameters[0]);
					break;
				case 2:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1]);
					break;
				case 3:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2]);
					break;
				case 4:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
					break;
				case 5:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
					break;
				default:
					$return = call_user_func_array(array(&$this, 'p_' . $name), $parameters);
				break;
			}
		}

		// in the after, the last parameter passed will be the result
		array_push($parameters, $return);
		$after = $this->plugins->run_hook('fu_post_model_after_' . $name, $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}


	/**
	 * @param object $board
	 * @param null|string $join_on
	 * @return string
	 */
	function sql_report_join($board, $join_on = NULL)
	{
		// only show report notifications to certain users
		if (!$this->tank_auth->is_allowed())
		{
			return '';
		}

		return '
			LEFT JOIN
			(
				SELECT
					id AS report_id, doc_id AS report_doc_id, reason AS report_reason, ip_reporter as report_ip_reporter,
					status AS report_status, created AS report_created
				FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
				WHERE `board_id` = ' . $board->id . '
			) AS r
			ON
			' . ($join_on ? $join_on : $this->radix->get_table($board)) . '.`doc_id`
			=
			' . $this->db->protect_identifiers('r') . '.`report_doc_id`
		';
	}


	/**
	 * @param object $board
	 * @param null|string $join_on
	 * @return string
	 */
	function sql_media_join($board, $join_on = NULL)
	{
		return '
			LEFT JOIN
				' . $this->radix->get_table($board, '_images') . ' AS `mg`
			ON
			' . ($join_on ? $join_on : $this->radix->get_table($board)) . '.`media_id`
			=
			' . $this->db->protect_identifiers('mg') . '.`media_id`
		';
	}


	/**
	 * @param array|object $posts
	 */
	function populate_posts_arr($post)
	{
		if (is_array($post))
		{
			foreach ($post as $p)
			{
				$this->populate_posts_arr($p);
			}
		}

		if (is_object($post))
		{
			if ($post->op == 1)
			{
				$this->posts_arr[$post->num][] = $post->num;
			}
			else
			{
				if ($post->subnum == 0)
					$this->posts_arr[$post->thread_num][] = $post->num;
				else
					$this->posts_arr[$post->thread_num][] = $post->num . ',' . $post->subnum;
			}
		}
	}


	/**
	 * @param object $board
	 * @param object $post
	 * @param bool $thumbnail
	 * @return bool|string
	 */
	function get_media_dir($board, $post, $thumbnail = FALSE)
	{
		if (!$post->media_hash)
		{
			return FALSE;
		}

		if ($thumbnail === TRUE)
		{
			if (isset($post->op) && $post->op == 1)
			{
				$image = $post->preview_op ? $post->preview_op : $post->preview_reply;
			}
			else
			{
				$image = $post->preview_reply ? $post->preview_reply : $post->preview_op;
			}
		}
		else
		{
			$image = $post->media;
		}

		// if we don't check, the return will return a valid folder that will evaluate file_exists() as TRUE
		if(is_null($image))
		{
			return FALSE;
		}

		return get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $board->shortname . '/'
			. ($thumbnail ? 'thumb' : 'image') . '/' . substr($image, 0, 4) . '/' . substr($image, 4, 2) . '/' . $image;
	}


	/**
	 * @param object $board
	 * @param object $row
	 * @param bool $thumbnail
	 * @param bool $no_site_url
	 * @return bool|string
	 */
	function get_media_link($board, $post, $thumbnail = FALSE)
	{
		if (!$post->media_hash)
		{
			return FALSE;
		}

		// these features will only affect guest users
		if ($board->hide_thumbnails && !$this->tank_auth->is_allowed())
		{
			// hide all thumbnails for the board
			if (!$board->hide_thumbnails)
			{
				if ($thumbnail === TRUE)
				{
					// we need to define the size of the image
					$post->preview_h = 150;
					$post->preview_w = 150;
					return site_url() . 'content/themes/default/images/null-image.png';
				}

				return FALSE;
			}

			// add a delay of 1 day to all thumbnails
			if ($board->delay_thumbnails)
			{
				if (isset($post->timestamp) && ($post->timestamp + 86400) > time())
				{
					if ($thumbnail === TRUE)
					{
						// we need to define the size of the image
						$post->preview_h = 150;
						$post->preview_w = 150;
						return site_url() . 'content/themes/default/images/null-image.png';
					}

					return FALSE;
				}
			}
		}

		// this post contain's a banned media, do not display
		if ($post->banned == 1)
		{
			// we need to define the size of the image
			$post->preview_h = 150;
			$post->preview_w = 150;
			return site_url() . 'content/themes/default/images/banned-image.png';
		}

		// locate the image
		if (file_exists($this->get_media_dir($board, $post, $thumbnail)) !== FALSE
			|| file_exists($this->get_media_dir($board, $post, FALSE)) !== FALSE)
		{
			if ($thumbnail === TRUE)
			{
				if (isset($post->op) && $post->op == 1)
				{
					$image = $post->preview_op ? : $post->preview_reply;
				}
				else
				{
					$image = $post->preview_reply ? : $post->preview_op;
				}

				if(is_null($image) || $image == '')
				{
					$image = $post->media;
					$thumbnail = FALSE;
				}
			}
			else
			{
				$image = $post->media;
			}
		}
		else if($thumbnail === TRUE && file_exists($this->get_media_dir($board, $post, FALSE)))
		{
			$image = $post->media;
			$thumbnail = FALSE;
		}

		if(isset($image))
		{
			$media_cdn = array();
			if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' && get_setting('fs_fuuka_boards_media_balancers_https'))
			{
				$balancers = get_setting('fs_fuuka_boards_media_balancers_https');
			}

			if (!isset($balancers) && get_setting('fs_fuuka_boards_media_balancers'))
			{
				$balancers = get_setting('fs_fuuka_boards_media_balancers');
			}

			if(isset($balancers))
			{
				$media_cdn = array_filter(preg_split('/\r\n|\r|\n/', $balancers));
			}

			if(!empty($media_cdn) && $post->media_id > 0)
			{
				return $media_cdn[($post->media_id % 2)] . '/' . $board->shortname . '/'
					. ($thumbnail ? 'thumb' : 'image') . '/' . substr($image, 0, 4) . '/' . substr($image, 4, 2) . '/' . $image;
			}

			return get_setting('fs_fuuka_boards_url', site_url()) . '/' . $board->shortname . '/'
				. ($thumbnail ? 'thumb' : 'image') . '/' . substr($image, 0, 4) . '/' . substr($image, 4, 2) . '/' . $image;
		}

		if ($thumbnail === TRUE)
		{
			$post->preview_h = 150;
			$post->preview_w = 150;
			return site_url() . 'content/themes/default/images/missing-image.jpg';
		}

		return FALSE;
	}


	/**
	 * @param object $board
	 * @param object $post
	 * @return bool|string
	 */
	function get_remote_media_link($board, $post)
	{
		if (!$post->media_hash)
		{
			return FALSE;
		}

		if ($board->archive && $board->images_url != "")
		{
			// ignore webkit and opera user agents
			if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(opera|webkit)/i', $_SERVER['HTTP_USER_AGENT']))
			{
				return $board->images_url . $post->media_orig;
			}

			return site_url(array($board->shortname, 'redirect')) . $post->media_orig;
		}
		else
		{
			if (file_exists($this->get_media_dir($board, $post)) !== FALSE)
			{
				return $this->get_media_link($board, $post);
			}
			else
			{
				return FALSE;
			}
		}
	}


	/**
	 * @param mixed $media
	 * @param bool $urlsafe
	 * @return bool|string
	 */
	function get_media_hash($media, $urlsafe = FALSE)
	{
		if (is_object($media) || is_array($media))
		{
			if (!$media->media_hash)
			{
				return FALSE;
			}

			$media = $media->media_hash;
		}
		else
		{
			if (strlen(trim($media)) == 0)
			{
				return FALSE;
			}
		}

		// return a safely escaped media hash for urls or un-altered media hash
		if ($urlsafe === TRUE)
		{
			return urlsafe_b64encode(urlsafe_b64decode($media));
		}
		else
		{
			return base64_encode(urlsafe_b64decode($media));
		}
	}


	/**
	 * @param string $name
	 * @return array
	 */
	function process_name($name)
	{
		// define variables
		$matches = array();
		$normal_trip = '';
		$secure_trip = '';

		if (preg_match("'^(.*?)(#)(.*)$'", $name, $matches))
		{
			$matches_trip = array();
			$name = trim($matches[1]);

			preg_match("'^(.*?)(?:#+(.*))?$'", $matches[3], $matches_trip);

			if (count($matches_trip) > 1)
			{
				$normal_trip = $this->process_tripcode($matches_trip[1]);
				$normal_trip = $normal_trip ? '!' . $normal_trip : '';
			}

			if (count($matches_trip) > 2)
			{
				$secure_trip = '!!' . $this->process_secure_tripcode($matches_trip[2]);
			}
		}

		return array($name, $normal_trip . $secure_trip);
	}


	/**
	 * @param string $plain
	 * @return string
	 */
	function process_tripcode($plain)
	{
		if (trim($plain) == '')
		{
			return '';
		}

		$trip = mb_convert_encoding($plain, 'SJIS', 'UTF-8');
		$trip = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&#39;', '&lt;', '&gt;'), $trip);

		$salt = substr($trip . 'H.', 1, 2);
		$salt = preg_replace('/[^.-z]/', '.', $salt);
		$salt = strtr($salt, ':;<=>?@[\]^_`', 'ABCDEFGabcdef');

		return substr(crypt($trip, $salt), -10);
	}


	/**
	 * @param string $plain
	 * @return string
	 */
	function process_secure_tripcode($plain)
	{
		return substr(base64_encode(sha1($plain . base64_decode(FOOLFUUKA_SECURE_TRIPCODE_SALT), TRUE)), 0, 11);
	}


	/**
	 * @param object $board
	 * @param object $post
	 * @param bool $clean
	 * @param bool $build
	 */
	function process_post($board, $post, $clean = TRUE, $build = FALSE)
	{
		$this->load->helper('text');
		$this->current_p = $post;

		$post->safe_media_hash = $this->get_media_hash($post, TRUE);
		$post->remote_media_link = $this->get_remote_media_link($board, $post);
		$post->media_link = $this->get_media_link($board, $post);
		$post->thumb_link = $this->get_media_link($board, $post, TRUE);
		$post->comment_processed = @iconv('UTF-8', 'UTF-8//IGNORE', $this->process_comment($board, $post));
		$post->comment = @iconv('UTF-8', 'UTF-8//IGNORE', $post->comment);

		// gotta change the timestamp of the archives to GMT
		if($board->archive)
		{
			$post->original_timestamp = $post->timestamp;
			$newyork = new DateTime(date('Y-m-d H:i:s', $post->timestamp), new DateTimeZone('America/New_York'));
			$utc = new DateTime(date('Y-m-d H:i:s', $post->timestamp), new DateTimeZone('UTC'));
			$diff = $newyork->diff($utc)->h;
			$post->timestamp = $post->timestamp + ($diff * 60 * 60);
		}
		else
		{
			$post->original_timestamp = $post->timestamp;
		}

		$elements = array('title', 'name', 'email', 'trip', 'media_orig',
			'preview_orig', 'media_filename', 'media_hash', 'poster_hash');

		if ($this->tank_auth->is_allowed())
		{
			array_push($elements, 'report_reason');
		}

		foreach($elements as $element)
		{
			$element_processed = $element . '_processed';

			$post->$element_processed = @iconv('UTF-8', 'UTF-8//IGNORE', fuuka_htmlescape($post->$element));
			$post->$element = @iconv('UTF-8', 'UTF-8//IGNORE', $post->$element);
		}

		// remove both ip and delpass from public view
		if ($clean === TRUE)
		{
			if (!$this->tank_auth->is_allowed())
			{
				unset($post->poster_ip);
			}

			unset($post->delpass);
		}

		if ($build === TRUE)
		{
			$post->formatted = $this->build_board_comment($board, $post);
		}
	}


	/**
	 * @param object $board
	 * @param object $post
	 * @param array $media
	 * @param string $media_hash
	 * @return array|bool
	 */
	function process_media($board, $post_id, $file, $media_hash, $duplicate = NULL)
	{
		// only allow media on internal boards
		if ($board->archive)
		{
			return FALSE;
		}

		$preliminary_check = @getimagesize($file['full_path']);

		if(!$preliminary_check)
		{
			return array('error' => __('The file you submitted doesn\'t seem to be an image.'));
		}

		// if width and height are lower than 25 reject the image
		if($preliminary_check[0] < 25 || $preliminary_check[1] < 25)
		{
			return array('error' => __('The image you submitted is too small.'));
		}


		// default variables
		$media_exists = FALSE;
		$thumb_exists = FALSE;

		// only run the check when iterated with duplicate
		if ($duplicate === NULL)
		{
			// check *_images table for media hash
			$check = $this->db->query('
				SELECT *
				FROM ' . $this->radix->get_table($board, '_images') . '
				WHERE media_hash = ?
				LIMIT 0, 1
			',
				array($media_hash)
			);

			// if exists, re-run process with duplicate set
			if ($check->num_rows() > 0)
			{
				$check_row = $check->row();

				// do we have some image reposting constraint?
				if($board->min_image_repost_hours == 0 || $this->tank_auth->is_allowed())
				{
					// do nothing, 0 means that there's no time constraint
					// also admins and mods can repost however mich they want
				}
				else if($board->min_image_repost_hours == -1)
				{
					// don't allow reposting, ever
					return array('error' =>
						__('This image has already been posted once. This board doesn\'t allow image reposting'));
				}
				else
				{
					// check if there's a recent image with the same media_id
					$constraint = $this->db->query('
						SELECT *
						FROM ' . $this->radix->get_table($board) . '
						WHERE media_id = ? AND timestamp > ?
					', array($check_row->media_id, time() - $board->min_image_repost_hours * 60 * 60));

					if($constraint->num_rows() > 0)
					{
						return array('error' => sprintf(
							__('You must wait up to %s hours to repost this image.'),
							$board->min_image_repost_hours)
						);
					}
				}

				return $this->process_media($board, $post_id, $file, $media_hash, $check_row);
			}
		}

		// generate unique filename with timestamp, this will be stored with the post
		$media_unixtime = time() . rand(1000, 9999);
		$media_filename = $media_unixtime . strtolower($file['file_ext']);
		$thumb_filename = $media_unixtime . 's' . strtolower($file['file_ext']);

		// set default locations of media directories and image directory structure
		$board_directory = get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $board->shortname . '/';
		$thumb_filepath = $board_directory . 'thumb/' . substr($media_unixtime, 0, 4) . '/' . substr($media_unixtime, 4, 2) . '/';
		$media_filepath = $board_directory . 'image/' . substr($media_unixtime, 0, 4) . '/' . substr($media_unixtime, 4, 2) . '/';

		// PHP must be compiled with --enable-exif
		// exif can be grabbed only from jpg and tiff
		if(function_exists('exif_read_data')
			&& in_array(strtolower(trim($file['file_ext'], '.')), array('jpg', 'jpeg', 'tiff')))
		{
			$exif = exif_read_data($file['full_path']);

			if($exif === FALSE)
			{
				$exif = NULL;
			}
		}
		else
		{
			$exif = NULL;
		}

		// check for any type of duplicate records or information and override default locations
		if ($duplicate !== NULL)
		{
			// handle full media
			if ($duplicate->media !== NULL)
			{
				$media_exists = TRUE;

				$media_existing = $duplicate->media;
				$media_filepath = $board_directory . 'image/'
					. substr($duplicate->media, 0, 4) . '/' . substr($duplicate->media, 4, 2) . '/';
			}

			// generate full file paths for missing files only
			if ($duplicate->media === NULL || file_exists($media_filepath . $duplicate->media) === FALSE)
			{
				if(!file_exists($media_filepath))
					mkdir($media_filepath, FOOL_FILES_DIR_MODE, TRUE);
			}

			// handle thumbs
			if ($post_id == 0)
			{
				// thumb op
				if ($duplicate->preview_op !== NULL)
				{
					$thumb_exists = TRUE;

					$thumb_existing = $duplicate->preview_op;
					$thumb_filepath = $board_directory . 'thumb/'
						. substr($duplicate->preview_op, 0, 4) . '/' . substr($duplicate->preview_op, 4, 2) . '/';
				}

				// generate full file paths for missing files only
				if ($duplicate->preview_op === NULL || file_exists($media_filepath . $duplicate->preview_op) === FALSE)
				{
					if(!file_exists($thumb_filepath))
						mkdir($thumb_filepath, FOOL_FILES_DIR_MODE, TRUE);
				}
			}
			else
			{
				// thumb re
				if ($duplicate->preview_reply !== NULL)
				{
					$thumb_exists = TRUE;

					$thumb_existing = $duplicate->preview_reply;
					$thumb_filepath = $board_directory . 'thumb/'
						. substr($duplicate->preview_reply, 0, 4) . '/' . substr($duplicate->preview_reply, 4, 2) . '/';
				}

				// generate full file paths for missing files only
				if ($duplicate->preview_reply === NULL || file_exists($media_filepath . $duplicate->preview_reply) === FALSE)
				{
					if(!file_exists($thumb_filepath))
						mkdir($thumb_filepath, FOOL_FILES_DIR_MODE, TRUE);
				}
			}
		}
		else
		{
			// generate full file paths for everything
			if(!file_exists($media_filepath))
				mkdir($media_filepath, FOOL_FILES_DIR_MODE, TRUE);
			if(!file_exists($thumb_filepath))
				mkdir($thumb_filepath, FOOL_FILES_DIR_MODE, TRUE);
		}

		// relocate the media file to proper location
		if (!copy($file['full_path'], $media_filepath . (($media_exists) ? $media_existing : $media_filename)))
		{
			log_message('error', 'post.php/process_media: failed to move media file');
			return FALSE;
		}

		// remove the media file
		if (!unlink($file['full_path']))
		{
			log_message('error', 'post.php/process_media: failed to remove media file from cache directory');
		}

		// determine the correct thumbnail dimensions
		if ($post_id == 0)
		{
			$thumb_width = $board->thumbnail_op_width;
			$thumb_height = $board->thumbnail_op_height;
		}
		else
		{
			$thumb_width = $board->thumbnail_reply_width;
			$thumb_height = $board->thumbnail_reply_height;
		}

		// generate thumbnail
		$imagemagick = locate_imagemagick();
		$media_config = array(
			'image_library' => ($imagemagick) ? 'ImageMagick' : 'GD2',
			'library_path'  => ($imagemagick) ? $this->ff_imagemagick->path : '',
			'source_image'  => $media_filepath . (($media_exists) ? $media_existing : $media_filename),
			'new_image'     => $thumb_filepath . (($thumb_exists) ? $thumb_existing : $thumb_filename),
			'width'         => ($file['image_width'] > $thumb_width) ? $thumb_width : $file['image_width'],
			'height'        => ($file['image_height'] > $thumb_height) ? $thumb_height : $file['image_height'],
		);

		// leave this NULL so it processes normally
		$switch = $this->plugins->run_hook('fu_post_model_process_media_switch_resize', array($media_config));

		// if plugin returns false, error
		if(isset($switch['return']) && $switch['return'] === FALSE)
		{
			log_message('error', 'post.php/process_media: failed to generate thumbnail');
			return FALSE;
		}

		if(is_null($switch) || is_null($switch['return']))
		{
			$this->load->library('image_lib');

			$this->image_lib->initialize($media_config);
			if (!$this->image_lib->resize())
			{
				log_message('error', 'post.php/process_media: failed to generate thumbnail');
				return FALSE;
			}

			$this->image_lib->clear();
		}

		$thumb_dimensions = @getimagesize($thumb_filepath . (($thumb_exists) ? $thumb_existing : $thumb_filename));

		return array(
			'preview_orig' => $thumb_filename,
			'thumb_width' => $thumb_dimensions[0],
			'thumb_height'=> $thumb_dimensions[1],
			'media_filename' => $file['file_name'],
			'width' => $file['image_width'],
			'height'=> $file['image_height'],
			'size' => floor($file['file_size'] * 1024),
			'media_hash' => $media_hash,
			'media_orig' => $media_filename,
			'exif' => !is_null($exif)?json_encode($exif):NULL,
			'unixtime' => $media_unixtime,
		);
	}


	/**
	 * @param object $board
	 * @param object $row
	 * @return string
	 */
	function p_process_comment($board, $post)
	{
		$CI = & get_instance();

		// default variables
		$find = "'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i";
		$html = '\\1<span class="greentext">\\2</span>\\3';

		$html = $this->plugins->run_hook('fu_post_model_process_comment_greentext_result', array($html), 'simple');

		$comment = $post->comment;

		// this stores an array of moot's formatting that must be removed
		$special = array(
			'<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">',
			'<span style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">'
		);

		// remove moot's special formatting
		if ($post->capcode == 'A' && mb_strpos($comment, $special[0]) == 0)
		{
			$comment = str_replace($special[0], '', $comment);

			if (mb_substr($comment, -6, 6) == '</div>')
			{
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 6);
			}
		}

		if ($post->capcode == 'A' && mb_strpos($comment, $special[1]) == 0)
		{
			$comment = str_replace($special[1], '', $comment);

			if (mb_substr($comment, -10, 10) == '[/spoiler]')
			{
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 10);
			}
		}

		$comment = htmlentities($comment, ENT_COMPAT | ENT_IGNORE, 'UTF-8', FALSE);

		// preg_replace_callback handle
		$this->current_board_for_prc = $board;

		// format entire comment
		$comment = preg_replace_callback("'(&gt;&gt;(\d+(?:,\d+)?))'i",
			array(get_class($this), 'process_internal_links'), $comment);

		$comment = preg_replace_callback("'(&gt;&gt;&gt;(\/(\w+)\/(\d+(?:,\d+)?)?(\/?)))'i",
			array(get_class($this), 'process_crossboard_links'), $comment);

		$comment = auto_linkify($comment, 'url', TRUE);
		$comment = preg_replace($find, $html, $comment);
		$comment = parse_bbcode($comment, ($board->archive && !$post->subnum) ? TRUE : FALSE);

		// additional formatting
		if ($board->archive && !$post->subnum)
		{
			// admin bbcode
			$admin_find = "'\[banned\](.*?)\[/banned\]'i";
			$admin_html = '<span class="banned">\\1</span>';

			$comment = preg_replace($admin_find, $admin_html, $comment);

			// literal bbcode
			$lit_find = array(
				"'\[banned:lit\]'i", "'\[/banned:lit\]'i",
				"'\[moot:lit\]'i", "'\[/moot:lit\]'i"
			);

			$lit_html = array(
				'[banned]', '[/banned]',
				'[moot]', '[/moot]'
			);

			$comment = preg_replace($lit_find, $lit_html, $comment);
		}

		return nl2br(trim($comment));
	}


	/**
	 * @param array $matches
	 * @return string
	 */
	function process_internal_links($matches)
	{
		$num = $matches[2];
		$num_id = str_replace(',', '_', $num);

		$html = array(
			'prefix' => '',
			'suffix' => '',
			'urltag' => '#',
			'option' => ' class="backlink" data-function="highlight" data-backlink="true" data-board="' . $this->current_board_for_prc->shortname . '" data-post="' . $num_id . '"',
			'option_op' => ' class="backlink op" data-function="highlight" data-backlink="true" data-board="' . $this->current_board_for_prc->shortname . '" data-post="' . $num_id . '"',
			'option_backlink' => ' class="backlink" data-function="highlight" data-backlink="true" data-board="' . $this->current_board_for_prc->shortname . '" data-post="'
				. $this->current_p->num . (($this->current_p->subnum == 0) ? '' : '_' . $this->current_p->subnum) . '"',
		);

		$html = $this->plugins->run_hook('fu_post_model_process_internal_links_html_result', array($num_id, $html), 'simple');

		$this->backlinks[$num_id][$this->current_p->num] = $html['prefix']
			. '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread',
			($this->current_p->thread_num == 0) ? $this->current_p->num : $this->current_p->thread_num)) . $html['urltag']
			. $this->current_p->num . (($this->current_p->subnum == 0) ? '' : '_' . $this->current_p->subnum)
			. '"' . $html['option_backlink'] . '>&gt;&gt;'
			. $this->current_p->num . (($this->current_p->subnum == 0) ? '' : ',' . $this->current_p->subnum)
			. '</a>' . $html['suffix'];

		if (array_key_exists($num, $this->posts_arr))
		{
			if ($this->backlinks_hash_only_url)
			{
				return $html['prefix'] . '<a href="' . $html['urltag'] . $num_id . '"' . $html['option_op']
					. '>&gt;&gt;' . $num . '</a>' . $html['suffix'];
			}

			return $html['prefix'] . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread', $num))
				. $html['urltag'] . $num_id . '"' . $html['option_op'] . '>&gt;&gt;' . $num . '</a>' . $html['suffix'];
		}

		foreach ($this->posts_arr as $key => $thread)
		{
			if (in_array($num, $thread))
			{
				if ($this->backlinks_hash_only_url)
				{
					return $html['prefix'] . '<a href="' . $html['urltag'] . $num_id . '"' . $html['option']
						. '>&gt;&gt;' . $num . '</a>' . $html['suffix'];
				}

				return $html['prefix'] . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread', $key))
					. $html['urltag'] . $num_id . '"' . $html['option'] . '>&gt;&gt;' . $num . '</a>' . $html['suffix'];
			}
		}

		if ($this->realtime === TRUE)
		{
			return $html['prefix'] . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread', $key))
				. $html['urltag'] . $num_id . '"' . $html['option'] . '>&gt;&gt;' . $num . '</a>' . $html['suffix'];
		}

		return $html['prefix'] . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'post', $num_id))
			. '"' . $html['option'] . '>&gt;&gt;' . $num . '</a>' . $html['suffix'];

		// return un-altered
		return $matches[0];
	}


	/**
	 * @param array $matches
	 * @return string
	 */
	function process_crossboard_links($matches)
	{
		$shortname = $matches[3];
		$url = $matches[2];
		$num = $matches[4];

		$html = array(
			'prefix' => '',
			'suffix' => '',
			'urltag' => '#'
		);

		if ($this->features === FALSE)
		{
			if ($this->theme->get_selected_theme() == 'fuuka')
			{
				$html = array(
					'prefix' => '<span class="unkfunc">',
					'suffix' => '</span>'
				);
			}

			if ($this->theme->get_selected_theme() == 'yotsuba')
			{
				$html = array(
					'prefix' => '<font class="unkfunc">',
					'suffix' => '</font>'
				);
			}
		}

		$board = $this->radix->get_by_shortname($shortname);
		if (!$board)
		{
			if ($num)
			{
				return $html['prefix'] . '<a href="//boards.4chan.org/' . $shortname . '/res/' . $num . '">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];
			}

			return $html['prefix'] . '<a href="//boards.4chan.org/' . $shortname . '/">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];
		}

		if ($num)
		{
			return $html['prefix'] . '<a href="' . site_url(array($board->shortname, 'post', $num))
				. '" class="backlink" data-function="highlight" data-backlink="true" data-board="' . $board->shortname . '" data-post="' . $num . '">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];
		}

		return $html['prefix'] . '<a href="' . site_url($board->shortname) . '">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];

		// return un-altered
		return $matches[0];
	}


	/**
	 * @param object $board
	 * @param object $p
	 * @return string
	 */
	function build_board_comment($board, $p)
	{
		return $this->theme->build('board_comment', array('p' => $p), TRUE, TRUE);
	}


	/**
	 * @param object $board
	 * @param mixed $num if you send a $query->result() of a thread it will avoid another query
	 * @return array
	 */
	function check_thread($board, $num)
	{
		if ($num == 0)
		{
			return array('invalid_thread' => TRUE);
		}

		// of $num is an array it means we've sent a $query->result()
		if (!is_array($num))
		{
			// grab the entire thread
			$query = $this->db->query('
				SELECT * FROM ' . $this->radix->get_table($board) . '
				WHERE thread_num = ?
			',
				array($num, $num)
			);

			// thread was not found
			if ($query->num_rows() == 0)
			{
				return array('invalid_thread' => TRUE);
			}

			$query_result = $query->result();

			// free up result
			$query->free_result();
		}
		else
		{
			$query_result = $num;
		}

		// define variables
		$thread_op_present = FALSE;
		$ghost_post_present = FALSE;
		$thread_last_bump = 0;
		$counter = array('posts' => 0, 'images' => 0);

		foreach ($query_result as $post)
		{
			// we need to find if there's the OP in the list
			// let's be strict, we want the $num to be the OP
			if ($post->op == 1)
			{
				$thread_op_present = TRUE;
			}

			if($post->subnum > 0)
			{
				$ghost_post_present = TRUE;
			}

			if($post->subnum == 0 && $thread_last_bump < $post->timestamp)
			{
				$thread_last_bump = $post->timestamp;
			}

			if ($post->media_filename)
			{
				$counter['images']++;
			}

			$counter['posts']++;
		}

		// we didn't point to the thread OP, this is not a thread
		if (!$thread_op_present)
		{
			return array('invalid_thread' => TRUE);
		}

		// time check
		if(time() - $thread_last_bump > 432000 || $ghost_post_present)
		{
			return array('thread_dead' => TRUE, 'disable_image_upload' => TRUE, 'ghost_disabled' => $board->disable_ghost);
		}

		if ($counter['posts'] > $board->max_posts_count)
		{
			if ($counter['images'] > $board->max_images_count)
			{
				return array('thread_dead' => TRUE, 'disable_image_upload' => TRUE, 'ghost_disabled' => $board->disable_ghost);
			}
			else
			{
				return array('thread_dead' => TRUE, 'ghost_disabled' => $board->disable_ghost);
			}
		}
		else if ($counter['images'] > $board->max_images_count)
		{
			return array('disable_image_upload' => TRUE);
		}

		return array('valid_thread' => TRUE);
	}


	/**
	 * @param object $board
	 * @param array $args
	 * @param array $options
	 * @return array
	 */
	function get_search($board, $args, $options = array())
	{
		// default variables
		$process = TRUE;
		$clean = TRUE;

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// set a valid value for $search['page']
		if ($args['page'])
		{
			if (!is_numeric($args['page']))
			{
				log_message('error', 'post.php/get_search: invalid page argument');
				show_404();
			}

			$args['page'] = intval($args['page']);
		}
		else
		{
			$args['page'] = 1;
		}

		// if image is set, get either media_hash or media_id
		if ($args['image'] && !is_natural($args['image']))
		{
			// this is urlsafe, let's convert it else decode it
			if (mb_strlen($args['image']) < 23)
			{
				$args['image'] = $this->get_media_hash($args['image']);
			}
			else
			{
				$args['image'] = rawurldecode($args['image']);
			}

			if(substr($args['image'], -2) != '==')
			{
				$args['image'] .= '==';
			}

			// if board set, grab media_id
			if ($board !== FALSE)
			{
				$image_query = $this->db->query('
					SELECT media_id
					FROM ' . $this->radix->get_table($board, '_images') . '
					WHERE media_hash = ?
				', array($args['image']));

				// if there's no images matching, the result is certainly empty
				if($image_query->num_rows() == 0)
				{
					return array('posts' => array(), 'total_found' => 0);
				}

				$args['image'] = $image_query->row()->media_id;
			}
		}

		// if global or board => use sphinx, else mysql for board only
		// global search requires sphinx
		if (($board === FALSE && get_setting('fs_sphinx_global', 0) == 0))
		{
			return array('error' => __('Sorry, global search requires SphinxSearch.'));
		}
		elseif (($board === FALSE && get_setting('fs_sphinx_global', 0)) || (is_object($board) && $board->sphinx))
		{
			$this->load->library('SphinxQL');

			// establish connection to sphinx
			$sphinx_server = explode(':', get_setting('fu_sphinx_listen', FOOL_PREF_SPHINX_LISTEN));

			if (!$this->sphinxql->set_server($sphinx_server[0], $sphinx_server[1]))
				return array('error' => __('The search backend is currently not online. Try later or contact us in case it\'s offline for too long.'));

			// determine if all boards will be used for search or not
			if ($board === FALSE)
			{
				$this->radix->preload(TRUE);
				$indexes = array();

				foreach ($this->radix->get_all() as $radix)
				{
					// ignore boards that don't have sphinx enabled
					if (!$radix->sphinx)
					{
						continue;
					}

					$indexes[] = $radix->shortname . '_ancient';
					$indexes[] = $radix->shortname . '_main';
					$indexes[] = $radix->shortname . '_delta';
				}
			}
			else
			{
				$indexes = array(
					$board->shortname . '_ancient',
					$board->shortname . '_main',
					$board->shortname . '_delta'
				);
			}

			// set db->from with indexes loaded
			$this->db->from($indexes, FALSE, FALSE);

			// begin filtering search params
			if ($args['text'])
			{
				if (mb_strlen($args['text']) < 1)
				{
					return array();
				}

				$this->db->sphinx_match('comment', $args['text'], 'half', TRUE);
			}
			if ($args['subject'])
			{
				$this->db->sphinx_match('title', $args['subject'], 'full', TRUE);
			}
			if ($args['username'])
			{
				$this->db->sphinx_match('name', $args['username'], 'full', TRUE);
			}
			if ($args['tripcode'])
			{
				$this->db->sphinx_match('trip', $args['tripcode'], 'full', TRUE, TRUE);
			}
			if ($args['email'])
			{
				$this->db->sphinx_match('email', $args['email'], 'full', TRUE);
			}
			if ($args['filename'])
			{
				$this->db->sphinx_match('media_filename', $args['filename'], 'full', TRUE);
			}
			if ($args['image'])
			{
				if($board !== FALSE)
				{
					$this->db->where('mid', (int) $args['image']);
				}
				else
				{
					$this->db->sphinx_match('media_hash', $args['image'], 'full', TRUE, TRUE);
				}
			}
			if ($args['capcode'] == 'admin')
			{
				$this->db->where('cap', 3);
			}
			if ($args['capcode'] == 'mod')
			{
				$this->db->where('cap', 2);
			}
			if ($args['capcode'] == 'user')
			{
				$this->db->where('cap', 1);
			}
			if ($args['deleted'] == 'deleted')
			{
				$this->db->where('is_deleted', 1);
			}
			if ($args['deleted'] == 'not-deleted')
			{
				$this->db->where('is_deleted', 0);
			}
			if ($args['ghost'] == 'only')
			{
				$this->db->where('is_internal', 1);
			}
			if ($args['ghost'] == 'none')
			{
				$this->db->where('is_internal', 0);
			}
			if ($args['type'] == 'op')
			{
				$this->db->where('is_op', 1);
			}
			if ($args['type'] == 'posts')
			{
				$this->db->where('is_op', 0);
			}
			if ($args['filter'] == 'image')
			{
				$this->db->where('has_image', 0);
			}
			if ($args['filter'] == 'text')
			{
				$this->db->where('has_image', 1);
			}
			if ($args['start'])
			{
				$this->db->where('timestamp >=', intval(strtotime($args['start'])));
			}
			if ($args['end'])
			{
				$this->db->where('timestamp <=', intval(strtotime($args['end'])));
			}
			if ($args['order'] == 'asc')
			{
				$this->db->order_by('timestamp', 'ASC');
			}
			else
			{
				$this->db->order_by('timestamp', 'DESC');
			}

			// set sphinx options
			$this->db->limit(25, ($args['page'] * 25) - 25)
				->sphinx_option('max_matches', 5000)
				->sphinx_option('reverse_scan', ($args['order'] == 'asc') ? 0 : 1);

			// send sphinxql to searchd
			$search = $this->sphinxql->query($this->db->statement());

			if (empty($search['matches']))
			{
				return array('posts' => array(), 'total_found' => 0);
			}

			// populate array to query for full records
			$sql = array();

			foreach ($search['matches'] as $post => $result)
			{
				$sql[] = '
					(
						SELECT *, ' . $result['board'] . ' AS board
						FROM ' . $this->radix->get_table($this->radix->get_by_id($result['board'])) . '
						' . $this->sql_media_join($this->radix->get_by_id($result['board'])) . '
						' . $this->sql_report_join($this->radix->get_by_id($result['board'])) . '
						WHERE num = ' . $result['num'] . ' AND subnum = ' . $result['subnum'] . '
					)
				';
			}

			// query mysql for full records
			$query = $this->db->query(implode('UNION', $sql) . ' ORDER BY timestamp ' . (($args['order'] == 'asc') ? 'ASC' : 'DESC'));
			$total = $search['total_found'];
		}
		else /* use mysql as fallback for non-sphinx indexed boards */
		{
			// begin filtering search params
			if ($args['text'] || $args['filename'])
			{
				if (mb_strlen($args['text']) < 1)
				{
					return array();
				}

				// we're using fulltext fields, we better start from this
				$this->db->from($this->radix->get_table($board, '_search'), FALSE, FALSE);

				// select that we'll use for the final statement
				$select = 'SELECT ' . $this->radix->get_table($board, '_search') . '.`doc_id`';

				if($args['text'])
				{
					$this->db->where(
						'MATCH (' . $this->radix->get_table($board, '_search') . '.`comment`) AGAINST (' . $this->db->escape(rawurldecode($args['text'])) . ' IN BOOLEAN MODE)',
						NULL,
						FALSE
					);
				}

				if($args['filename'])
				{
					$this->db->where(
						'MATCH (' . $this->radix->get_table($board, '_search') . '.`media_filename`) AGAINST (' . $this->db->escape(rawurldecode($args['filename'])) . ' IN BOOLEAN MODE)',
						NULL,
						FALSE
					);
				}

				$query = $this->db->query($this->db->statement('', NULL, NULL, 'SELECT doc_id'));
				if ($query->num_rows == 0)
				{
					return array('posts' => array(), 'total_found' => 0);
				}

				$docs = array();
				foreach ($query->result() as $rec)
				{
					$docs[] = $rec->doc_id;
				}
			}

			$this->db->start_cache();

			// no need for the fulltext fields
			$this->db->from($this->radix->get_table($board), FALSE, FALSE);

			// select that we'll use for the final statement
			$select = 'SELECT ' . $this->radix->get_table($board) . '.`doc_id`';

			if (isset($docs))
			{
				$this->db->where_in('doc_id', $docs);
			}

			if ($args['subject'])
			{
				$this->db->like('title', rawurldecode($args['subject']));
			}
			if ($args['username'])
			{
				$this->db->like('name', rawurldecode($args['username']));
				$this->db->use_index('name_trip_index');
			}
			if ($args['tripcode'])
			{
				$this->db->like('trip', rawurldecode($args['tripcode']));
				$this->db->use_index('trip_index');
			}
			if ($args['email'])
			{
				$this->db->like('email', rawurldecode($args['email']));
				$this->db->use_index('email_index');
			}
			if ($args['image'])
			{
				$this->db->where('media_id', $args['image']);
				$this->db->use_index('media_id_index');
			}
			if ($args['capcode'] == 'admin')
			{
				$this->db->where('capcode', 'A');
			}
			if ($args['capcode'] == 'mod')
			{
				$this->db->where('capcode', 'M');
			}
			if ($args['capcode'] == 'user')
			{
				$this->db->where('capcode !=', 'A');
				$this->db->where('capcode !=', 'M');
			}
			if ($args['deleted'] == 'deleted')
			{
				$this->db->where('deleted', 1);
			}
			if ($args['deleted'] == 'not-deleted')
			{
				$this->db->where('deleted', 0);
			}
			if ($args['ghost'] == 'only')
			{
				$this->db->where('subnum <>', 0);
				$this->db->use_index('subnum_index');
			}
			if ($args['ghost'] == 'none')
			{
				$this->db->where('subnum', 0);
				$this->db->use_index('subnum_index');
			}
			if ($args['type'] == 'op')
			{
				$this->db->where('op', 1);
				$this->db->use_index('op_index');
			}
			if ($args['type'] == 'posts')
			{
				$this->db->where('op', 0);
				$this->db->use_index('op_index');
			}
			if ($args['filter'] == 'image')
			{
				$this->db->where('media_id', 0);
				$this->db->use_index('media_id_index');
			}
			if ($args['filter'] == 'text')
			{
				$this->db->where('media_id <>', 0);
				$this->db->use_index('media_id_index');
			}
			if ($args['start'])
			{
				$this->db->where('timestamp >=', intval(strtotime($args['start'])));
				$this->db->use_index('timestamp_index');
			}
			if ($args['end'])
			{
				$this->db->where('timestamp <=', intval(strtotime($args['end'])));
				$this->db->use_index('timestamp_index');
			}

			$this->db->stop_cache();

			// fetch initial total first...
			$this->db->limit(5000);

			// get directly the count for speed
			$count_res = $this->db->query($this->db->statement('', NULL, NULL, 'SELECT COUNT(*) AS count'));
			$total = $count_res->row()->count;

			if (!$total)
			{
				return array('posts' => array(), 'total_found' => 0);
			}

			// now grab those results in order
			$this->db->limit(25, ($args['page'] * 25) - 25);

			$this->db->order_by('timestamp', ($args['order'] == 'asc'?'ASC':'DESC'));

			// get doc_ids, last parameter is the select
			$doc_ids_res = $this->db->query($this->db->statement('', NULL, NULL, $select));

			$doc_ids = array();
			$doc_ids_res_arr = $doc_ids_res->result();
			foreach($doc_ids_res_arr as $doc_id)
			{
				// juuust to be extra sure, make force it to be an int
				$doc_ids[] = intval($doc_id->doc_id);
			}

			$this->db->flush_cache();

			$query = $this->db->query('
				SELECT *
				FROM ' . $this->radix->get_table($board) . '
				' . $this->sql_media_join($board) . '
				' . $this->sql_report_join($board) . '
				WHERE doc_id IN (' . implode(', ', $doc_ids) . ')
				ORDER BY timestamp ' . ($args['order'] == 'asc'?'ASC':'DESC') . '
				LIMIT ?, ?
			', array(($args['page'] * 25) - 25, 25));

			// query mysql for full records
			//$query = $this->db->query($this->db->statement());
			$total = $doc_ids_res->num_rows();
		}

		// process all results to be displayed
		$results = array();

		$this->populate_posts_arr($query->result());

		foreach ($query->result() as $post)
		{
			// override board with full board information
			if (isset($post->board))
			{
				$post->board = $this->radix->get_by_id($post->board);
				$board = $post->board;
			}

			// populate posts_arr array
			$this->populate_posts_arr($post);

			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}

			$results[0]['posts'][] = $post;
		}

		return array('posts' => $results, 'total_found' => $total);
	}


	/**
	 * @param object $board
	 * @param int $page
	 * @param array $options
	 * @return array|bool
	 */
	function get_latest($board, $page = 1, $options = array())
	{
		// default variables
		$per_page = 20;
		$process = TRUE;
		$clean = TRUE;
		$type = 'by_post';

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// determine type
		switch ($type)
		{
			case 'by_post':

				$query = $this->db->query('
					SELECT *, thread_num as unq_thread_num
					FROM ' . $this->radix->get_table($board, '_threads') . '
					ORDER BY time_bump DESC LIMIT ?, ?
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);

				break;

			case 'by_thread':

				$query = $this->db->query('
					SELECT *, thread_num as unq_thread_num
					FROM ' . $this->radix->get_table($board, '_threads') . '
					ORDER BY thread_num DESC LIMIT ?, ?
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);

				break;

			case 'ghost':

				$query = $this->db->query('
					SELECT *
					FROM
					(
						SELECT *, thread_num as unq_thread_num
						FROM ' . $this->radix->get_table($board, '_threads') . '
						WHERE time_ghost_bump IS NOT NULL
						ORDER BY time_ghost_bump DESC LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_thread_num AND g.subnum = 0
					' . $this->sql_media_join($board, 'g') . '
					' . $this->sql_report_join($board, 'g') . '
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);

				break;

			default:
				log_message('error', 'post.php/get_latest: invalid or missing type argument');
				return FALSE;
		}

		// cache the count or get the cached count
		$this->load->driver('cache', array('adapter' => 'apc', 'backup' => 'dummy'));
		if($type == 'ghost')
		{
			$type_cache = 'ghost_num';
		}
		else
		{
			$type_cache = 'thread_num';
		}

		if(!$threads = $this->cache->get('foolfuuka_' . config_item('encryption_key') . '_board_' . $board->id . '_get_latest_threads_count_' . $type_cache))
		{
			switch ($type)
			{
				// these two are the same
				case 'by_post':
				case 'by_thread':
					$query_threads = $this->db->query('
						SELECT COUNT(thread_num) AS threads
						FROM ' . $this->radix->get_table($board, '_threads') . '
					');
					break;

				case 'ghost':
					$query_threads = $this->db->query('
						SELECT COUNT(thread_num) AS threads
						FROM ' . $this->radix->get_table($board, '_threads') . '
						WHERE time_ghost_bump IS NOT NULL;
					');
					break;
			}

			$threads = $query_threads->row()->threads;
			$query_threads->free_result();

			// start caching only over 300 threads so we can keep boards with little number of threads dynamic
			if($threads > 300)
			{
				$this->cache->save(
					'foolfuuka_' . config_item('encryption_key') . '_board_' . $board->id . '_get_latest_threads_count_' . $type_cache,
					$threads,
					180
				);
			}
		}

		if ($query->num_rows() == 0)
		{
			return array(
				'result' => array('op' => array(), 'posts' => array()),
				'pages' => NULL
			);
		}


		// set total pages found
		if ($threads <= $per_page)
		{
			$pages = NULL;
		}
		else
		{
			$pages = floor($threads/$per_page)+1;
		}

		// populate arrays with posts
		$threads = array();
		$results = array();
		$sql_arr = array();

		foreach ($query->result() as $thread)
		{
			$threads[$thread->unq_thread_num] = array('replies' => $thread->nreplies, 'images' => $thread->nimages);

			$sql_arr[] = '
				(
					SELECT *
					FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE thread_num = ' . $thread->unq_thread_num . '
					ORDER BY op DESC, num DESC, subnum DESC
					LIMIT 0, 6
				)
			';
		}

		$query_posts = $this->db->query(implode('UNION', $sql_arr));

		// populate posts_arr array
		$this->populate_posts_arr($query_posts->result());

		// populate results array and order posts
		foreach ($query_posts->result() as $post)
		{
			$post_num = ($post->op == 0) ? $post->thread_num : $post->num;

			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}

			if (!isset($results[$post_num]['omitted']))
			{
				foreach ($threads as $thread_num => $counter)
				{
					if ($thread_num == $post_num)
					{
						$results[$post_num] = array(
							'omitted' => ($counter['replies'] - 6),
							'images_omitted' => ($counter['images'] - 1)
						);
					}
				}
			}

			if ($post->op == 0)
			{
				if ($post->preview_orig)
				{
					$results[$post->thread_num]['images_omitted']--;
				}

				if(!isset($results[$post->thread_num]['posts']))
					$results[$post->thread_num]['posts'] = array();

				array_unshift($results[$post->thread_num]['posts'], $post);
			}
			else
			{
				$results[$post->num]['op'] = $post;
			}
		}

		return array('result' => $results, 'pages' => $pages);
	}


	/**
	 * @param object $board
	 * @param int $num
	 * @param array $options
	 * @return array|bool
	 */
	function get_thread($board, $num, $options = array())
	{
		// default variables
		$process = TRUE;
		$clean = TRUE;
		$type = 'thread';
		$type_extra = array();
		$realtime = FALSE;

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// determine type
		switch ($type)
		{
			case 'from_doc_id':

				if (!isset($type_extra['latest_doc_id']) || !is_natural($type_extra['latest_doc_id']))
				{
					log_message('error', 'post.php/get_thread: invalid last_doc_id argument');
					return FALSE;
				}

				$query = $this->db->query('
					SELECT *
					FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE thread_num = ? AND doc_id > ?
					ORDER BY num, subnum ASC
				',
					array($num, $type_extra['latest_doc_id'])
				);

				break;

			case 'ghosts':

				$query = $this->db->query('
					SELECT *
					FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE thread_num = ? AND subnum <> 0
					ORDER BY num, subnum ASC
				',
					array($num)
				);

				break;

			case 'last_x':

				if (!isset($type_extra['last_limit']) || !is_natural($type_extra['last_limit']))
				{
					log_message('error', 'post.php/get_thread: invalid last_limit argument');
					return FALSE;
				}

				/* @todo reduce this query since thread_num catches all */
				$query = $this->db->query('
					SELECT *
					FROM
					(
						(
							SELECT * FROM ' . $this->radix->get_table($board) . '
							WHERE num = ? LIMIT 0, 1
						)
						UNION
						(
							SELECT * FROM ' . $this->radix->get_table($board) . '
							WHERE thread_num = ?
							ORDER BY num DESC, subnum DESC
							LIMIT ?
						)
					) AS x
					' . $this->sql_media_join($board, 'x') . '
					' . $this->sql_report_join($board, 'x') . '
					ORDER BY num, subnum ASC
				',
					array(
						$num, $num, intval($type_extra['last_limit'])
					)
				);

				break;

			case 'thread':

				$query = $this->db->query('
					SELECT * FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE thread_num = ?
					ORDER BY num, subnum ASC
				',
					array($num, $num)
				);

				break;

			default:
				log_message('error', 'post.php/show_thread: invalid or missing type argument');
				return FALSE;
		}

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		// set global variables for special usage
		if ($realtime === TRUE)
		{
			$this->realtime = TRUE;
		}

		$this->backlinks_hash_only_url = TRUE;

		// populate posts_arr array
		$this->populate_posts_arr($query->result());
		$thread_check = $this->check_thread($board, $query->result());

		// process entire thread and store in $result array
		$result = array();

		foreach ($query->result() as $post)
		{
			if ($process === TRUE)
			{
				if ($post->op == 0)
				{
					$this->process_post($board, $post, $clean, $realtime);
				}
				else
				{
					$this->process_post($board, $post, TRUE, TRUE);
				}
			}

			if ($post->op == 0)
			{
				$result[$post->thread_num]['posts'][$post->num . (($post->subnum == 0) ? '' : '_' . $post->subnum)] = $post;
			}
			else
			{
				$result[$post->num]['op'] = $post;
			}
		}

		// free up memory
		$query->free_result();

		// populate results with backlinks
		foreach ($this->backlinks as $key => $backlinks)
		{
			if (isset($result[$num]['op']) && $result[$num]['op']->num == $key)
			{
				$result[$num]['op']->backlinks = array_unique($backlinks);
			}
			else if (isset($result[$num]['posts'][$key]))
			{
				$result[$num]['posts'][$key]->backlinks = array_unique($backlinks);
			}
		}

		// reset module settings
		$this->backlinks_hash_only_url = FALSE;
		$this->realtime = FALSE;

		return array('result' => $result, 'thread_check' => $thread_check);
	}


	/**
	 * @param object $board
	 * @param int $page
	 * @param array $options
	 * @return array|bool
	 */
	function get_gallery($board, $page = 1, $options = array())
	{
		// default variables
		$per_page = 200;
		$process = TRUE;
		$clean = TRUE;
		$type = 'by_thread';

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// determine type
		switch ($type)
		{
			case 'by_image':

				$query = $this->db->query('
					SELECT * FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE ' . $this->radix->get_table($board) . '.`media_id` <> 0
					ORDER BY timestamp DESC LIMIT ?, ?
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);
				break;

			case 'by_thread':

				$query = $this->db->query('
					SELECT *
					FROM
					(
						SELECT *, thread_num as unq_thread_num
						FROM ' . $this->radix->get_table($board, '_threads') . '
						ORDER BY time_op DESC LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_thread_num AND g.subnum = 0
					' . $this->sql_media_join($board, 'g') . '
					' . $this->sql_report_join($board, 'g') . '
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);
				break;

			default:
				log_message('error', 'post.php/get_gallery: invalid or missing type argument');
				return FALSE;
		}



		// cache the count or get the cached count
		$this->load->driver('cache', array('adapter' => 'apc', 'backup' => 'dummy'));
		if(!$threads = $this->cache->get('foolfuuka_' . config_item('encryption_key') . '_board_' . $board->id . '_get_gallery_threads_count_' . $type))
		{
			switch ($type)
			{
				case 'by_image':
					$query_threads = $this->db->query('
						SELECT SUM(total) AS threads
						FROM ' . $this->radix->get_table($board, '_images') . '
					');
					break;

				case 'by_thread':
					$query_threads = $this->db->query('
						SELECT COUNT(thread_num) AS threads
						FROM ' . $this->radix->get_table($board, '_threads') . '
					');
					break;
			}

			$threads = $query_threads->row()->threads;
			$query_threads->free_result();

			// start caching only over 300 threads so we can keep boards with little number of threads dynamic
			if($threads > 300)
			{
				$this->cache->save(
					'foolfuuka_' . config_item('encryption_key') . '_board_' . $board->id . '_get_gallery_threads_count_' . $type,
					$threads,
					180
				);
			}
		}

		// populate result array
		$results = array();

		foreach ($query->result() as $key => $post)
		{
			if ($post->preview_orig)
			{
				$this->process_post($board, $post, $clean, $process);
				$results[$post->num] = $post;
			}
		}

		return array('threads' => $results, 'total_found' => $threads);
	}


	/**
	 * @param int $page
	 * @return array|bool
	 */
	function get_reports($page = 1)
	{
		$this->load->model('report_model', 'report');

		// populate multi_posts array to fetch
		$multi_posts = array();

		foreach ($this->report->get_reports($page) as $post)
		{
			$multi_posts[] = array(
				'board_id' => $post->board_id,
				'doc_id'   => array($post->doc_id)
			);
		}

		return array('posts' => $this->get_multi_posts($multi_posts), 'total_found' => $this->report->get_count());
	}


	/**
	 * @param array $multi_posts
	 * @param null|string $order_by
	 * @return array|bool
	 */
	function get_multi_posts($multi_posts = array(), $order_by = NULL)
	{
		// populate sql array
		$sql = array();

		foreach ($multi_posts as $posts)
		{
			// posts => [board_id, doc_id => [1, 2, 3]]
			if (isset($posts['board_id']) && isset($posts['doc_id']))
			{
				$board = $this->radix->get_by_id($posts['board_id']);
				$sql[] = '
					(
						SELECT *, CONCAT(' . $this->db->escape($posts['board_id']) . ') AS board_id
						FROM ' . $this->radix->get_table($board) . ' AS g
						' . $this->sql_media_join($board, 'g') . '
						' . $this->sql_report_join($board, 'g') . '
						WHERE g.`doc_id` = ' . implode(' OR g.`doc_id` = ', $posts['doc_id']) . '
					)
				';
			}
		}

		if (empty($sql))
		{
			return array();
		}

		// order results properly with string argument
		$query = $this->db->query(implode('UNION', $sql) . ($order_by ? $order_by : ''));

		if ($query->num_rows() == 0)
		{
			return array();
		}

		// populate results array
		$results = array();

		foreach ($query->result() as $post)
		{
			$board = $this->radix->get_by_id($post->board_id);
			$post->board = $board;

			$this->process_post($board, $post);

			array_push($results, $post);
		}

		return $results;
	}


	/**
	 * @param object $board
	 * @param int $num
	 * @param int $subnum
	 * @return bool|object
	 */
	function get_post_thread($board, $num, $subnum = 0)
	{
		$query = $this->db->query('
			SELECT num, thread_num, subnum
			FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE num = ? AND subnum = ? LIMIT 0, 1
		',
			array($num, $subnum)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		return $query->row();
	}


	/**
	 * @param object $board
	 * @param int|string $num
	 * @param int $subnum
	 * @return bool|object
	 */
	function get_post_by_num($board, $num, $subnum = 0, $build = FALSE)
	{
		if (strpos($num, '_') !== FALSE && $subnum == 0)
		{
			$num_array = explode('_', $num);

			if (count($num_array) != 2)
			{
				return FALSE;
			}

			$num = $num_array[0];
			$subnum = $num_array[1];
		}

		$num = intval($num);
		$subnum = intval($subnum);

		$query = $this->db->query('
			SELECT *
			FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE num = ? AND subnum = ? LIMIT 0, 1
		',
			array($num, $subnum)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		// process results
		$post = $query->row();
		$this->process_post($board, $post, TRUE, $build);

		return $post;
	}


	/**
	 * @param object $board
	 * @param int $doc_id
	 * @return bool|object
	 */
	function get_post_by_doc_id($board, $doc_id)
	{
		$query = $this->db->query('
			SELECT * ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE doc_id = ? LIMIT 0, 1
		',
			array($doc_id)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		return $query->row();
	}


	/**
	 * @param object $board
	 * @param int $doc_id
	 * @return bool|object
	 */
	function get_by_doc_id($board, $doc_id)
	{
		$query = $this->db->query('
			SELECT * FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			' . $this->sql_report_join($board) . '
			WHERE doc_id = ? LIMIT 0, 1;
		',
			array($doc_id)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		return $query->row();
	}


	/**
	 * @param object $board
	 * @param string $media_filename
	 * @return array
	 */
	function get_full_media($board, $media_filename)
	{
		$query = $this->db->query('
			SELECT * FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE media_orig = ?
			ORDER BY num DESC LIMIT 0, 1
		',
			array($media_filename)
		);

		if ($query->num_rows() == 0)
		{
			return array('error_type' => 'no_record', 'error_code' => 404);
		}

		$result = $query->row();
		$media_link = $this->get_media_link($board, $result);

		if ($media_link === FALSE)
		{
			$this->process_post($board, $result, TRUE);
			return array('error_type' => 'not_on_server', 'error_code' => 404, 'result' => $result);
		}

		return array('media_link' => $media_link);
	}


	/**
	 * @param object $board
	 * @param array $data
	 * @param array $options
	 * @return array
	 */
	function p_comment($board, $data, $options = array())
	{
		// default variables
		$media_allowed = TRUE;

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// check: stopforumspam databae for banned ip
		if (check_stopforumspam_ip($this->input->ip_address()))
		{
			if ($data['media'] !== FALSE || $data['media'] != '')
			{
				if (!unlink($data['media']['full_path']))
				{
					log_message('error', 'post.php/comment: failed to remove media file from cache');
				}
			}

			return array('error' => __('Your IP has been identified as a spam proxy. Please try a different IP or remove the proxy to post.'));
		}

		// check: if passed stopforumspam, check if banned internally
		$check = $this->db->query('
			SELECT *
			FROM ' . $this->db->protect_identifiers('posters', TRUE) . '
			WHERE ip = ?
			LIMIT 0, 1
		',
			array($this->input->ip_address())
		);

		if ($check->num_rows() > 0)
		{
			$row = $check->row();

			if ($row->banned && !$this->tank_auth->is_allowed())
			{
				if ($data['media'] !== FALSE || $data['media'] != '')
				{
					if (!unlink($data['media']['full_path']))
					{
						log_message('error', 'post.php/comment: failed to remove media file from cache');
					}
				}

				return array('error' => __('You are banned from posting'));
			}
		}
		
		if($data['num'] == 0 && !$this->tank_auth->is_allowed())
		{
			// check: validate some information
			$check_op = $this->db->query('
				SELECT 1
				FROM ' . $this->radix->get_table($board) . '
				WHERE poster_ip = ?
				AND timestamp > ?
				AND op = 1
				LIMIT 0,1
			',
				array($this->input->ip_address(), time() - 300)
			);
			
			if($check_op->num_rows() > 0)
			{
				return array('error' => __('You must wait more time to make new threads!'));
			}
		}

		// check: validate some information
		$check = $this->db->query('
			SELECT *
			FROM ' . $this->radix->get_table($board) . '
			WHERE poster_ip = ?
			ORDER BY timestamp DESC
			LIMIT 0,1
		',
			array($this->input->ip_address())
		);
		
		
		if ($check->num_rows() > 0)
		{
			$row = $check->row();

			if ($data['comment'] != '' && $row->comment == $data['comment'] && !$this->tank_auth->is_allowed())
			{
				return array('error' => __('You\'re posting again the same comment as the last time!'));
			}

			if (time() - $row->timestamp < 10 && time() - $row->timestamp > 0 && !$this->tank_auth->is_allowed())
			{
				return array('error' => 'You must wait at least 10 seconds before posting again.');
			}

		}

		// hook entire comment data to alter in plugin
		$data = $this->plugins->run_hook('fu_post_model_comment_alter_input', array($board, $data), 'simple');

		// process comment name+trip
		if ($data['name'] === FALSE || $data['name'] == '')
		{
			$this->input->set_cookie('foolfuuka_reply_name', '', 0);
			$name = 'Anonymous';
			$trip = '';
		}
		else
		{
			// store name in cookie to repopulate forms
			$this->input->set_cookie('foolfuuka_reply_name', $data['name'], 60 * 60 * 24 * 30);

			$name_trip = $this->process_name($data['name']);
			$name = $name_trip[0];
			$trip = (isset($name_trip[1])) ? $name_trip[1] : '';
		}

		// process comment email
		if ($data['email'] === FALSE || $data['email'] == '')
		{
			$this->input->set_cookie('foolfuuka_reply_email', '', 0);
			$email = '';
		}
		else
		{
			// store email in cookie to repopulate forms
			if ($data['email'] != 'sage')
			{
				$this->input->set_cookie('foolfuuka_reply_email', $data['email'], 60 * 60 * 24 * 30);
			}

			$email = $data['email'];
		}

		// process comment subject
		if ($data['subject'] === FALSE || $data['subject'] == '')
		{
			$subject = '';
		}
		else
		{
			$subject = $data['subject'];
		}

		// process comment password
		if ($data['password'] === FALSE || $data['password'] == '')
		{
			$password = '';
		}
		else
		{
			// store password in cookie to repopulate forms
			$this->input->set_cookie('foolfuuka_reply_password', $data['password'], 60 * 60 * 24 * 30);

			$password = $data['password'];
		}

		// process comment
		if ($data['comment'] === FALSE || $data['comment'] == '')
		{
			$comment = '';
		}
		else
		{
			$comment = $data['comment'];
		}

		// load the spam list and check comment, name, subject and email
		$spam = array_filter(preg_split('/\r\n|\r|\n/', file_get_contents('assets/anti-spam/databases')));
		foreach($spam as $s)
		{
			if(strpos($comment, $s) !== FALSE || strpos($name, $s) !== FALSE
				|| strpos($subject, $s) !== FALSE || strpos($email, $s) !== FALSE)
			{
				return array('error' => __('Your comment has contains words that aren\'t allowed.'));
			}
		}

		// process comment ghost+spoiler
		if (isset($data['ghost']) && $data['ghost'] === TRUE)
		{
			$ghost = TRUE;
		}
		else
		{
			$ghost = FALSE;
		}


		if ($data['spoiler'] === FALSE || $data['spoiler'] == '')
		{
			$spoiler = 0;
		}
		else
		{
			$spoiler = $data['spoiler'];
		}


		// process comment media
		if ($data['media'] === FALSE || $data['media'] == '')
		{
			// if no media is present, remove spoiler setting
			if ($spoiler == 1)
			{
				$spoiler = 0;
			}

			// if no media is present and post is op, stop processing
			if ($data['num'] == 0)
			{
				return array('error' => __('An image is required for creating threads.'));
			}

			// check other media errors
			if (isset($data['media_error']))
			{
				// invalid file type
				if (strlen($data['media_error']) == 64)
				{
					return array('error' => __('The filetype you are attempting to upload is not allowed.'));
				}

				// media file is too large
				if (strlen($data['media_error']) == 79)
				{
					return array('error' =>  __('The image you are attempting to upload is larger than the permitted size.'));
				}
			}
		}
		else
		{
			$media = $data['media'];

			// check if media is allowed
			if ($media_allowed === FALSE)
			{
				if (!unlink($media['full_path']))
				{
					log_message('error', 'post.php/comment: failed to remove media file from cache');
				}

				return array('error' => __('Sorry, this thread has reached its maximum amount of image replies.'));
			}

			// check for valid media dimensions
			if ($media['image_width'] == 0 || $media['image_height'] == 0)
			{
				if (!unlink($media['full_path']))
				{
					log_message('error', 'post.php/comment: failed to remove media file from cache');
				}

				return array('error' => __('Your image upload is not a valid image file.'));
			}

			// generate media hash
			$media_hash = base64_encode(pack("H*", md5(file_get_contents($media['full_path']))));


			// check if media is banned
			$check = $this->db->get_where('banned_md5', array('md5' => $media_hash));

			if ($check->num_rows() > 0)
			{
				if (!unlink($media['full_path']))
				{
					log_message('error', 'post.php/comment: failed to remove media file from cache');
				}

				return array('error' => __('Your image upload has been flagged as inappropriate.'));
			}
		}

		// check comment data for spam regex
		if (check_commentdata($data))
		{
			return array('error' => __('Your post contains contents that is marked as spam.'));
		}

		// check entire length of comment
		if (mb_strlen($comment) > 4096)
		{
			return array('error' => __('Your post was too long.'));
		}

		// check total numbers of lines in comment
		if (count(explode("\n", $comment)) > 20)
		{
			return array('error' => __('Your post had too many lines.'));
		}

		// phpass password for extra security, using the same tank_auth setting since it's cool
		$phpass = new PasswordHash(
			$this->config->item('phpass_hash_strength', 'tank_auth'),
			$this->config->item('phpass_hash_portable', 'tank_auth')
		);
		$password = $phpass->HashPassword($password);

		// set missing variables
		$num = $data['num'];
		$lvl = $data['postas'];

		$timestamp = time();

		// 2ch-style codes, only if enabled
		if($board->enable_poster_hash)
		{
			$poster_hash = substr(substr(crypt(md5($this->input->ip_address().'id'.date("Ymd", $timestamp)),'id'),+3), 0, 8);
		}
		else
		{
			$poster_hash = NULL;
		}

		$check = $this->db->query('
				SELECT doc_id
				FROM ' . $this->radix->get_table($board) . '
				WHERE poster_ip = ? AND comment = ? AND timestamp >= ?
			',
			array(
				$this->input->ip_address(), ($comment)?$comment:NULL, ($timestamp - 10)
			)
		);

		if ($check->num_rows() > 0)
		{
			return array('error' => __('This post is already being processed...'));
		}

		$this->db->trans_begin();

		// being processing insert...
		if ($ghost === TRUE)
		{
			if($board->archive)
			{
				// archives are in new york time
				$newyork = new DateTime(date('Y-m-d H:i:s', time()), new DateTimeZone('America/New_York'));
				$utc = new DateTime(date('Y-m-d H:i:s', time()), new DateTimeZone('UTC'));
				$diff = $newyork->diff($utc)->h;
				$timestamp = time() - ($diff * 60 * 60);
			}

			// ghost reply to existing thread
			$this->db->query('
				INSERT INTO ' . $this->radix->get_table($board) . '
				(
					num, subnum, thread_num, timestamp, capcode,
					email, name, trip, title, comment, delpass, poster_ip,
					poster_hash
				)
				VALUES
				(
					(
						SELECT MAX(num)
						FROM
						(
							SELECT num
							FROM ' . $this->radix->get_table($board) . '
							WHERE num = ? OR thread_num = ?
						) AS x
					),
					(
						SELECT MAX(subnum)+1
						FROM
						(
							SELECT subnum
							FROM ' . $this->radix->get_table($board) . '
							WHERE
								num = (
									SELECT MAX(num)
									FROM ' . $this->radix->get_table($board) . '
									WHERE num = ? OR thread_num = ?
								)
						) AS x
					),
					?, ?, ?,
					?, ?, ?, ?, ?, ?, ?, ?
				)
			',
				array(
					$num, $num, $num, $num, $num, $timestamp, $lvl,
					($email)?$email:NULL, ($name)?$name:NULL, ($trip)?$trip:NULL,
					($subject)?$subject:NULL, ($comment)?$comment:NULL,
					$password, $this->input->ip_address(), $poster_hash
				)
			);

			// we can grab the ID only here
			$insert_id = $this->db->insert_id();

			// check that it wasn't posted multiple times
			$check_duplicate = $this->db->query('
				SELECT doc_id
				FROM ' . $this->radix->get_table($board) . '
				WHERE poster_ip = ? AND comment = ? AND  timestamp >= ?
			',
			array(
				$this->input->ip_address(), ($comment)?$comment:NULL, ($timestamp - 10)
			));

			if($check_duplicate->num_rows() > 1)
			{
				$this->db->trans_rollback();
				return array('error' => __('You already posted this.'));
			}
		}
		else
		{
			// define default values for post
			$default_post_arr = array(
				0, ($num)?0:1,
				$num, ($num)?0:1,
				$timestamp, $lvl,
				($email)?$email:NULL, ($name)?$name:NULL, ($trip)?$trip:NULL, ($subject)?$subject:NULL,
				($comment)?$comment:NULL, $password, $spoiler, $this->input->ip_address(),
				$poster_hash
			);

			// process media
			if (isset($media))
			{
				$media_file = $this->process_media($board, $num, $media, $media_hash);
				if ($media_file === FALSE)
				{
					return array('error' => __('Your image was invalid.'));
				}

				if (is_array($media_file) && isset($media_file['error']))
				{
					return $media_file;
				}

				// replace timestamp with timestamp generated by process_media
				// process_media sends a timestamp with milliseconds which we want to get rid of
				$default_post_arr[4] = substr($media_file['unixtime'],0,10);
				unset($media_file['unixtime']);
				$default_post_arr = array_merge($default_post_arr, array_values($media_file));
			}
			else
			{
				// populate with empty media values
				$media_file =  array(
					'preview_orig' => NULL,
					'thumb_width' => 0,
					'thumb_height'=> 0,
					'media_filename' => NULL,
					'width' => 0,
					'height'=> 0,
					'size' => 0,
					'media_hash' => NULL,
					'media_orig' => NULL,
					'exif' => NULL,
				);

				array(NULL, 0, 0, NULL, 0, 0, 0, NULL, NULL, NULL);
				$default_post_arr = array_merge($default_post_arr, array_values($media_file));
			}
			//print_r($default_post_arr); die();

			// insert post into board
			$this->db->query('
				INSERT INTO ' . $this->radix->get_table($board) . '
				(
					num, subnum, thread_num, op, timestamp, capcode,
					email, name, trip, title, comment, delpass, spoiler, poster_ip, poster_hash,
					preview_orig, preview_w, preview_h, media_filename, media_w, media_h, media_size, media_hash,
					media_orig, exif
				)
				VALUES
				(
					(
						SELECT COALESCE(MAX(num), 0)+1 AS num
						FROM
						(
							SELECT num
							FROM ' . $this->radix->get_table($board) . '
						) AS x
					),
					?,
					IF(?, (
						SELECT COALESCE(MAX(num), 0)+1 AS num
						FROM
						(
							SELECT num
							FROM ' . $this->radix->get_table($board) . '
						) AS x
					), ?),
					?, ?, ?,
					?, ?, ?, ?, ?, ?, ?, ?, ?,
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				)
			',
				$default_post_arr
			);

			// we can grab the ID only here
			$insert_id = $this->db->insert_id();

			// check that it wasn't posted multiple times
			$check_duplicate = $this->db->query('
				SELECT *
				FROM ' . $this->radix->get_table($board) . '
				' . $this->sql_media_join($board) . '
				WHERE poster_ip = ? AND comment = ? AND  timestamp >= ?
				ORDER BY doc_id DESC
			',
			array(
				$this->input->ip_address(), ($comment)?$comment:NULL, ($timestamp - 10)
			));

			if($check_duplicate->num_rows() > 1)
			{
				$this->db->trans_rollback();

				$duplicate = $check_duplicate->row();

				if($duplicate->total == 1)
				{
					$this->delete_media($board, $duplicate);
				}
				// get rid of the extra media
				return array('error' => __('You already posted this.'));
			}
		}

		$this->db->trans_commit();

		// success, now check if there's extra work to do

		// we might be using the local MyISAM search table which doesn't support transactions
		// so we must be really careful with the insertion
		if($board->myisam_search)
		{
			// this is still fully MySQL so let's use a MySQL function for now
			$word_length = $this->radix->mysql_get_min_word_length();

			$this->db->query("
				INSERT IGNORE INTO " . $this->radix->get_table($board, '_search') . "
				SELECT doc_id, num, subnum, thread_num, media_filename, comment
				FROM " . $this->radix->get_table($board) . "
				WHERE doc_id = ? AND
					(CHAR_LENGTH(media_filename) >= ? OR CHAR_LENGTH(comment) >= ?)
			", array($insert_id, $word_length, $word_length));
		}

		// retreive num, subnum, thread_num for redirection
		$post = $this->db->query('
			SELECT num, subnum, thread_num
			FROM ' . $this->radix->get_table($board) . '
			WHERE doc_id = ? LIMIT 0, 1
		',
			array($insert_id)
		);

		return array('success' => TRUE, 'posted' => $post->row());
	}


	/**
	 * @param object $board
	 * @param array $post
	 * @return array|bool
	 */
	function delete($board, $post)
	{
		// $post => [doc_id, password, type]
		$query = $this->db->query('
			SELECT * FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE doc_id = ? LIMIT 0, 1
		',
			array($post['doc_id'])
		);

		if ($query->num_rows() == 0)
		{
			log_message('debug', 'post.php/delete: invalid doc_id for post or thread');
			return array('error' => __('There\'s no such a post to be deleted.'));
		}

		// store query results
		$row = $query->row();

		$phpass = new PasswordHash(
			$this->config->item('phpass_hash_strength', 'tank_auth'),
			$this->config->item('phpass_hash_portable', 'tank_auth')
		);

		// validate password
		if ($phpass->CheckPassword($post['password'], $row->delpass) !== TRUE && !$this->tank_auth->is_allowed())
		{
			log_message('debug', 'post.php/delete: invalid password');
			return array('error' => __('The password you inserted did not match the post\'s deletion password.'));
		}

		// delete media file for post
		if ($row->total == 1 && !$this->delete_media($board, $row))
		{
			log_message('error', 'post.php/delete: unable to delete media from post');
			return array('error' => __('Unable to delete thumbnail for post.'));
		}

		// remove the thread
		$this->db->query('
				DELETE
				FROM ' . $this->radix->get_table($board) . '
				WHERE doc_id = ?
			',
			array($row->doc_id)
		);

		// get rid of the entry from the myisam _search table
		if($board->myisam_search)
		{
			$this->db->query("
				DELETE
				FROM " . $this->radix->get_table($board, '_search') . "
				WHERE doc_id = ?
			", array($row->doc_id));
		}

		// an error was encountered
		if ($this->db->affected_rows() != 1)
		{
			log_message('error', 'post.php/delete: unable to delete thread op');
			return array('error', __('Unable to delete post.'));
		}

		// purge existing reports for post
		$this->db->delete('reports', array('board_id' => $board->id, 'doc_id' => $row->doc_id));

		// purge thread replies if thread_num
		if ($row->op == 1) // delete: thread
		{
			$thread = $this->db->query('
				SELECT * FROM ' . $this->radix->get_table($board) . '
				' . $this->sql_media_join($board) . '
				WHERE thread_num = ?
			',array($row->num));

			// thread replies found
			if ($thread->num_rows() > 0)
			{
				// remove all media files
				foreach ($thread->result() as $p)
				{
					if (!$this->delete_media($board, $p))
					{
						log_message('error', 'post.php/delete: unable to delete media from thread op');
						return array('error' => __('Unable to delete thumbnail for thread replies.'));
					}

					// purge associated reports
					$this->db->delete('reports', array('board_id' => $board->id, 'doc_id' => $p->doc_id));
				}

				// remove all replies
				$this->db->query('
					DELETE FROM ' . $this->radix->get_table($board) . '
					WHERE thread_num = ?
				', array($row->num));

				// get rid of the replies from the myisam _search table
				if($board->myisam_search)
				{
					$this->db->query("
						DELETE
						FROM " . $this->radix->get_table($board, '_search') . "
						WHERE thread_num = ?
					", array($row->num));
				}
			}
		}

		return TRUE;
	}


	/**
	 * @param object $board
	 * @param object $post
	 * @param bool $media
	 * @param bool $thumb
	 * @return bool
	 */
	function p_delete_media($board, $post, $media = TRUE, $thumb = TRUE)
	{
		if (!$post->media_hash)
		{
			// if there's no media, it's all OK
			return TRUE;
		}

		// delete media file only if there is only one image OR the image is banned
		if ($post->total == 1 || $post->banned == 1)
		{
			if ($media === TRUE)
			{
				$media_file = $this->get_media_dir($board, $post);
				if (file_exists($media_file))
				{
					if (!unlink($media_file))
					{
						log_message('error', 'post.php/delete_media: unable to remove ' . $media_file);
						return FALSE;
					}
				}
			}

			if ($thumb === TRUE)
			{
				$thumb_file = $this->get_media_dir($board, $post, TRUE);
				if (file_exists($thumb_file))
				{
					if (!unlink($thumb_file))
					{
						log_message('error', 'post.php/delete_media: unable to remove ' . $thumb_file);
						return FALSE;
					}
				}
			}
		}

		return TRUE;
	}


	/**
	 * @param string $hash
	 * @param bool $delete
	 * @return bool
	 */
	function ban_media($media_hash, $delete = FALSE)
	{
		// insert into global banned media hash
		$this->db->query('
			INSERT IGNORE INTO ' . $this->db->protect_identifiers('banned_md5', TRUE) . '
			(
				md5
			)
			VALUES
			(
				?
			)
		',
			array($media_hash)
		);

		// update all local _images table
		foreach ($this->radix->get_all() as $board)
		{
			$this->db->query('
				INSERT INTO ' . $this->radix->get_table($board, '_images') . '
				(
					media_hash, media, preview_op, preview_reply, total, banned
				)
				VALUES
				(
					?, ?, ?, ?, ?, ?
				)
				ON DUPLICATE KEY UPDATE banned = 1
			',
				array($media_hash, NULL, NULL, NULL, 0, 1)
			);
		}

		// delete media files if TRUE
		if ($delete === TRUE)
		{
			$posts = array();

			foreach ($this->radix->get_all() as $board)
			{
				$posts[] = '
					(
						SELECT *, CONCAT(' . $this->db->escape($board->id) . ') AS board_id
						FROM ' . $this->radix->get_table($board) . '
						WHERE media_hash = ' . $this->db->escape($media_hash) . '
					)
				';
			}

			$query = $this->db->query(implode('UNION', $posts));
			if ($query->num_rows() == 0)
			{
				log_message('error', 'post.php/ban_media: unable to locate posts containing media_hash');
				return FALSE;
			}

			foreach ($query->result() as $post)
			{
				$this->delete_media($this->radix->get_by_id($post->board_id), $post);
			}
		}

		return TRUE;
	}


	/**
	 * Recheck all banned images and remove eventual leftover images
	 *
	 * @param object $board
	 */
	function recheck_banned($board = FALSE)
	{
		if($board === FALSE)
		{
			$boards = $this->radix->get_all();
		}
		else
		{
			$boards = array($board);
			unset($board);
		}

		foreach($boards as $board)
		{
			$query = $this->db->query('
				SELECT *
				FROM ' . $this->radix->get_table($board, '_images') . '
				WHERE banned = 1
			');

			foreach($query->result() as $i)
			{
				if(!is_null($i->preview_op))
				{
					$op = get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' .
						$board->shortname . '/thumb/' .
						substr($i->preview_op, 0, 4) . '/' . substr($i->preview_op, 4, 2) . '/' .
						$i->preview_op;

					if(file_exists($op))
					{
						unlink($op);
					}
				}

				if(!is_null($i->preview_reply))
				{
					$reply = get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' .
						$board->shortname . '/thumb/' .
						substr($i->preview_reply, 0, 4) . '/' . substr($i->preview_reply, 4, 2) . '/' .
						$i->preview_reply;

					if(file_exists($reply))
					{
						unlink($reply);
					}
				}

				if(!is_null($i->media))
				{
					$media = get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' .
						$board->shortname . '/image/' .
						substr($i->media, 0, 4) . '/' . substr($i->media, 4, 2) . '/' .
						$i->media;

					if(file_exists($media))
					{
						unlink($media);
					}
				}

			}

		}
	}

	/**
	 * @param object $board
	 * @param int $doc_id
	 * @return bool
	 */
	function mark_spam($board, $doc_id)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->radix->get_table($board) . '
			WHERE doc_id = ?
			LIMIT 0, 1
		',
			array($doc_id)
		);

		if ($query->num_rows() == 0)
		{
			log_message('error', 'post.php/mark_spam: invalid doc_id argument');
			return FALSE;
		}

		// store post information
		$post = $query->row();

		// mark post as spam


		return TRUE;
	}


}
