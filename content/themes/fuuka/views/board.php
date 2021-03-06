<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (isset($thread_id))
	echo form_open_multipart(get_selected_radix()->shortname .'/sending', array('id' => 'postform'));
?>

<div class="content">
<?php
foreach ($posts as $key => $post) : ?>
	<?php if (isset($post['op'])) :
		$op = $post['op'];
		$selected_radix = isset($op->board)?$op->board:get_selected_radix();
	?>
	<div id="p<?php echo $op->num ?>">
		<?php if ($op->preview_orig) : ?>
		<span>File: <?php echo byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ', ' . $op->media_filename; ?> <?php echo '<!-- ' . substr($op->media_hash, 0, -2) . '-->' ?></span>
		<?php if (!get_selected_radix()->hide_thumbnails || $this->tank_auth->is_allowed()) : ?>[<a href="<?php echo site_url(get_selected_radix()->shortname . '/search/image/' . $op->safe_media_hash) ?>">View Same</a>] [<a href="http://iqdb.org/?url=<?php echo $op->thumb_link ?>">iqdb</a>] [<a href="http://google.com/searchbyimage?image_url=<?php echo $op->thumb_link ?>">Google</a>] [<a href="http://saucenao.com/search.php?url=<?php echo $op->thumb_link ?>">SauceNAO</a>]<?php endif; ?>
		<br />
		<a href="<?php echo ($op->media_link)?$op->media_link:$op->remote_media_link ?>" rel="noreferrer"><img class="thumb" src="<?php echo $op->thumb_link ?>" alt="<?php echo $op->num ?>" <?php if ($op->preview_w > 0 && $op->preview_h > 0) : ?> width="<?php echo $op->preview_w ?>" height="<?php echo $op->preview_h ?>"<?php endif; ?>/></a>
		<?php endif; ?>

		<label id="<?php echo $op->num ?>">
			<input type="checkbox" name="delete[]" value="<?php echo $op->doc_id ?>"/>
			<span class="filetitle"><?php echo $op->title_processed ?></span>
			<span class="postername<?php echo ($op->capcode == 'M' || $op->capcode == 'G') ? ' mod' : '' ?><?php echo ($op->capcode == 'A') ? ' admin' : '' ?>"><?php echo (($op->email_processed && $op->email_processed != 'noko') ? '<a href="mailto:' . form_prep($op->email_processed) . '">' . $op->name_processed . '</a>' : $op->name_processed) ?></span>
			<span class="postertrip<?php echo ($op->capcode == 'M' || $op->capcode == 'G') ? ' mod' : '' ?><?php echo ($op->capcode == 'A') ? ' admin' : '' ?>"><?php echo $op->trip_processed ?></span>
			<span class="poster_hash"><?php if ($op->poster_hash_processed) : ?>ID:<?php echo $op->poster_hash_processed ?><?php endif; ?></span>
			<?php if ($op->capcode == 'M') : ?>
				<span class="postername mod">## Mod</span>
			<?php endif ?>
			<?php if ($op->capcode == 'G') : ?>
				<span class="postername mod">## Global Mod</span>
			<?php endif ?>
			<?php if ($op->capcode == 'A') : ?>
				<span class="postername admin">## Admin</span>
			<?php endif ?>
			<?php echo date('D M d H:i:s Y', $op->original_timestamp) ?>
		</label>

		<?php if(!isset($thread_id)) : ?>
		<a class="js" href="<?php echo site_url($selected_radix->shortname . '/thread/' . $op->num) ?>">No.<?php echo $op->num ?></a>
		<?php else : ?>
		<a class="js" href="<?php echo site_url($selected_radix->shortname . '/thread/' . $op->num) ?>">No.</a><a class="js" href="javascript:insert('>><?php echo $op->num ?>\n')"><?php echo $op->num ?></a>
		<?php endif; ?>

		<?php if ($op->deleted == 1) : ?><img class="inline" src="<?php echo site_url() . 'content/themes/' . (($this->theme->get_selected_theme()) ? $this->theme->get_selected_theme() : 'default') . '/images/icons/file-delete-icon.png'; ?>" alt="[DELETED]" title="This post was deleted before its lifetime has expired."/><?php endif ?>
		<?php if ($op->spoiler == 1) : ?><img class="inline" src="<?php echo site_url() . 'content/themes/' . (($this->theme->get_selected_theme()) ? $this->theme->get_selected_theme() : 'default') . '/images/icons/spoiler-icon.png'; ?>" alt="[SPOILER]" title="The image in this post is marked as spoiler."/><?php endif ?>

		[<a href="<?php echo site_url($selected_radix->shortname . '/thread/' . $op->num) ?>">Reply</a>]<?php echo (isset($post['omitted']) && $post['omitted'] > 50) ? ' [<a href="' . site_url($selected_radix->shortname . '/last50/' . $op->num) . '">Last 50</a>]' : '' ?><?php if ($selected_radix->archive) : ?> [<a href="http://boards.4chan.org/<?php echo $selected_radix->shortname . '/res/' . $op->num ?>">Original</a>]<?php endif; ?>

		<blockquote><p><?php echo $op->comment_processed ?></p></blockquote>
		<?php echo ((isset($post['omitted']) && $post['omitted'] > 0) ? '<span class="omittedposts">' . $post['omitted'] . ' posts omitted. Click Reply to view.</span>' : '') ?>
	</div>
	<?php endif; ?>

	<?php
	if (isset($post['posts']))
	{
		if (isset($posts_per_thread))
		{
			$limit = count($post['posts']) - $posts_per_thread;
			if ($limit < 0)
				$limit = 0;
		}
		else
		{
			$limit = 0;
		}

		foreach ($post['posts'] as $p)
		{

			if ($p->thread_num == 0)
				$p->thread_num = $p->num;

			if(!isset($thread_id))
				$thread_id = NULL;

			if(file_exists('content/themes/' . $this->theme->get_selected_theme() . '/views/board_comment.php'))
				include('content/themes/' . $this->theme->get_selected_theme() . '/views/board_comment.php');
			else
				include('content/themes/' . $this->theme->get_config('extends') . '/views/board_comment.php');
		}
	}
	?>
	<?php
	if (isset($thread_id)) :
		echo $template['partials']['tools_reply_box'];
	endif;
	?>
	<br class="newthr" />
	<hr />
<?php endforeach; ?>
</div>

<?php
if (isset($thread_id))
	echo form_close();
?>
