<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<a name="<?php echo ($p->subnum > 0) ? $p->num . '_' . $p->subnum : $p->num ?>"></a>
<table>
	<tbody>
		<tr>
			<td class="doubledash" nowrap="">&gt;&gt;</td>
			<td id="<?php echo ($p->subnum > 0) ? $p->num . '_' . $p->subnum : $p->num ?>" class="<?php echo ($p->subnum > 0) ? 'subreply' : 'reply' ?>">
				<input type="checkbox" name="delete[]" value="<?php echo $p->doc_id ?>"/>
				<span class="replytitle"><?php echo $p->title_processed ?></span>
				<span class="commentpostername"><?php echo (($p->email_processed && $p->email_processed != 'noko') ? '<a href="mailto:' . form_prep($p->email_processed) . '">' . $p->name_processed . '</a>' : $p->name_processed) ?></span>
				<?php if ($p->trip_processed) : ?>
					<span class="postertrip"><?php echo $p->trip_processed ?></span>
				<?php endif; ?>
				<?php if ($p->capcode == 'M') : ?>
					<span class="post_level_moderator">## Mod</span>
				<?php endif ?>
				<?php if ($p->capcode == 'G') : ?>
					<span class="post_level_global_moderator">## Global Mod</span>
				<?php endif ?>
				<?php if ($p->capcode == 'A') : ?>
					<span class="post_level_administrator">## Admin</span>
				<?php endif ?>
				<span class="posttime"><?php echo date('m/d/y(D)H:i', $p->original_timestamp) ?></span>

				<?php if ($p->subnum > 0) : ?>
					<span id="norep<?php echo $p->num . '_' . $p->subnum ?>">
						<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $p->thread_num) . '#' . $p->num . '_' . $p->subnum ?>" class="quotejs">No.</a><a href="<?php echo (!isset($thread_id)) ? site_url(get_selected_radix()->shortname . '/thread/' . $p->thread_num) . '#q' . $p->num . '_' . $p->subnum : 'javascript:quote(\'' . $p->num . ',' . $p->subnum . '\')' ?>" class="quotejs"><?php echo $p->num . ',' . $p->subnum ?></a>
					</span>
				<?php else : ?>
					<span id="norep<?php echo $p->num ?>">
						<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $p->thread_num) . '#' . $p->num ?>" class="quotejs">No.</a><a href="<?php echo (!isset($thread_id)) ? site_url(get_selected_radix()->shortname . '/thread/' . $p->thread_num) . '#q' . $p->num : 'javascript:quote(\'' . $p->num . '\')' ?>" class="quotejs"><?php echo $p->num ?></a>
					</span>
				<?php endif; ?>

				<?php if ($p->preview) : ?>
					<br>
					<span class="filesize">
						File: <a href="<?php echo ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" target="_blank"><?php echo ($p->media_filename) ? $p->media_filename : $p->media ?></a><?php echo '-(' . byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ')' ?>
					</span>
					<br>
					<a href="<?php echo ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" target="_blank">
						<img src="<?php echo $p->thumb_link ?>" border="0" align="left" <?php if ($p->preview_w > 0 && $p->preview_h > 0) : ?>width="<?php echo $p->preview_w ?>" height="<?php echo $p->preview_h ?>" <?php endif; ?> hspace="20" alt="<?php echo byte_format($p->media_size, 0) ?>" md5="<?php echo $p->media_hash ?>"/>
					</a>
				<?php endif; ?>

				<blockquote>
					<?php echo $p->comment_processed ?>
				</blockquote>
			</td>
		</tr>
	</tbody>
</table>
