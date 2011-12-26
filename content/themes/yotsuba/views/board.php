<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (!isset($modifiers))
		$modifiers = array();
?>

<form name="threads">

<?php
foreach ($posts as $key => $post) : ?>
	<?php if(isset($post['op'])) :
		$op = $post['op'];
	?>
		<?php if ($op->media_filename) : ?>
		<br>
		<span class="filesize">
			File : <a href="<?php echo ($op->image_href) ? $op->image_href : $op->remote_image_href ?>" rel="noreferrer" target="_blank"><?php echo $op->media_filename ?></a><?php echo '-(' . byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ')' ?>
		</span>
		<br>
		<a href="<?php echo ($op->image_href) ? $op->image_href : $op->remote_image_href ?>" rel="noreferrer" target="_blank">
			<img src="<?php echo $op->thumbnail_href ?>" border="0" align="left" width="<?php echo $op->preview_w ?>" height="<?php echo $op->preview_h ?>" hspace="20" alt="<?php echo byte_format($op->media_size, 0) ?>" md5="<?php echo $op->media_hash ?>"/>
		</a>
		<?php endif; ?>

		<a name="0"></a>

		<span class="filetitle"></span>
		<span class="postername"><?php echo $op->name ?></span>
		<span class="postertrip"><?php echo $op->trip ?></span>
		<span class="posttime"><?php echo date('M/d/y(D)H:i', $op->timestamp) ?></span>
		<span id="nothread<?php echo $op->num ?>">
			<a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#' . $op->num ?>" class="quotejs">No.</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#q' . $op->num ?>" class="quotejs"><?php echo $op->num ?></a> [<a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) ?>" class="quotejs">Reply</a>]
		</span>
		<blockquote>
			<?php echo $op->comment_processed ?>
		</blockquote>
		<?php echo ((isset($post['omitted']) && $post['omitted'] > 0) ? '<span class="omitted">' . $post['omitted'] . ' posts '.((isset($post['images_omitted']) && $post['images_omitted'] > 0)?'and '.$post['images_omitted'].' images':'').' omitted. Click Reply to view.</span>' : '') ?>
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

		for ($i = $limit; $i < count($post['posts']); $i++)
		{
			$p = $post['posts'][$i];

			if ($p->parent == 0)
				$p->parent = $p->num;

			echo build_board_comment($p, $modifiers);
		}
	}
	?>
	<br clear="left">
	<hr>
<?php endforeach; ?>
</form>

<script type="text/javascript">
	site_url = '<?php echo site_url() ?>';
	board_shortname = '<?php echo get_selected_board()->shortname ?>';
	<?php if (isset($thread_id)) : ?>
	thread_id = <?php echo $thread_id ?>;
	thread_json = <?php echo json_encode($posts) ?>;
	thread_latest_timestamp = thread_json[thread_id].posts[(thread_json[thread_id].posts.length - 1)].timestamp;
	<?php endif; ?>
</script>
