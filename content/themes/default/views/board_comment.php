<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/*
 * This file appears also in the admin panel, so use @radix to specify that it's a board link
 */
 
$selected_radix = isset($p->board)?$p->board:get_selected_radix();
?>

<article class="post doc_id_<?php echo $p->doc_id ?>
	<?php if ($p->subnum > 0)   : ?> post_ghost<?php endif; ?>
	<?php if ($p->thread_num == $p->num) : ?> post_is_op<?php endif; ?>
	<?php echo ((isset($p->report_status) && !is_null($p->report_status)) ? ' reported' : '') ?><?php echo ($p->media ? ' has_image' : '') ?><?php if ($p->media) : ?> clearfix<?php endif; ?><?php if (false && $p->spam == 1) : ?> is_spam<?php endif; ?>" id="<?php echo $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">
	<?php if ($p->preview_orig) : ?>
	<div class="post_file">
			<span class="post_file_controls">
				<?php if (!$selected_radix->hide_thumbnails || $this->tank_auth->is_allowed()) : ?>
				<a href="<?php echo site_url('@radix/' . $selected_radix->shortname . '/search/image/' . $p->safe_media_hash) ?>" class="btnr parent"><?php echo __('View Same') ?></a><a href="http://google.com/searchbyimage?image_url=<?php echo $p->thumb_link ?>" target="_blank" class="btnr parent">Google</a><a href="http://iqdb.org/?url=<?php echo $p->thumb_link ?>" target="_blank" class="btnr parent">iqdb</a><a href="http://saucenao.com/search.php?url=<?php echo $p->thumb_link ?>" target="_blank" class="btnr parent">SauceNAO</a>
				<?php endif; ?>
			</span>

		<?php
		if (mb_strlen($p->media_filename) > 38) : ?>
			<span class="post_file_filename" rel="tooltip" title="<?php echo form_prep($p->media_filename) ?>">
					<?php echo mb_substr($p->media_filename, 0, 32) . ' (...)' . mb_substr($p->media_filename, mb_strrpos($p->media_filename, '.')) . ', '; ?>
					</span>
			<?php else :
			echo $p->media_filename . ', ';
		endif;
		?>
		<span class="post_file_metadata">
			<?php echo byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h ?>
		</span>
	</div>

	<div class="thread_image_box">
		<a href="<?php echo ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" target="_blank" rel="noreferrer" class="thread_image_link">
			<img <?php echo (isset($modifiers['lazyload']) && $modifiers['lazyload'] == TRUE) ? 'src="' . site_url('content/themes/default/images/transparent_pixel.png') . '" data-original="' . $p->thumb_link . '"' : 'src="' . $p->thumb_link . '"' ?> <?php echo ($p->preview_w > 0 && $p->preview_h > 0) ? 'width="' . $p->preview_w . '" height="' . $p->preview_h . '" ' : '' ?>class="lazyload post_image<?php echo ($p->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?php echo $p->media_hash ?>" />
			<?php if (isset($modifiers['lazyload']) && $modifiers['lazyload'] == TRUE) : ?>
			<noscript>
				<a href="<?php echo ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" target="_blank" rel="noreferrer" class="thread_image_link">
					<img src="<?php echo $p->thumb_link ?>" style="margin-left: -<?php echo $p->preview_w ?>px" <?php echo ($p->preview_w > 0 && $p->preview_h > 0) ? 'width="' . $p->preview_w . '" height="' . $p->preview_h . '" ' : '' ?>class="lazyload post_image<?php echo ($p->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?php echo $p->media_hash ?>" />
				</a>
			</noscript>
			<?php endif; ?>
		</a>
	</div>
	<?php endif; ?>

	<header>
		<div class="post_data">
			<?php if(isset($modifiers['post_show_board_name']) &&  $modifiers['post_show_board_name']): ?><span class="post_show_board">/<?php echo $selected_radix->shortname ?>/</span><?php endif; ?>
			<?php echo ($p->title_processed) ? '<h2 class="post_title">' . $p->title_processed . '</h2>' : '' ?>

			<span class="post_author"><?php echo ($p->email_processed && $p->email_processed != 'noko') ? '<a href="mailto:' . form_prep($p->email_processed) . '">' . $p->name_processed . '</a>' : $p->name_processed ?></span>
			<?php echo ($p->trip_processed) ? '<span class="post_trip">'. $p->trip_processed . '</span>' : '' ?>
			<?php if ($p->poster_hash_processed) : ?><span class="poster_hash">ID:<?php echo $p->poster_hash_processed ?></span><?php endif; ?>
			<?php if ($p->capcode != 'N') : ?>
			<?php if ($p->capcode == 'M') : ?>
				<span class="post_level post_level_moderator">## <?php echo __('Mod') ?></span>
				<?php endif ?>
			<?php if ($p->capcode == 'G') : ?>
				<span class="post_level post_level_global_moderator">## <?php echo __('Global Mod') ?></span>
				<?php endif ?>
			<?php if ($p->capcode == 'A') : ?>
				<span class="post_level post_level_administrator">## <?php echo __('Admin') ?></span>
				<?php endif ?>
			<?php endif; ?>

			<span class="time_wrap">
				<time datetime="<?php echo date(DATE_W3C, $p->timestamp) ?>" <?php if($selected_radix->archive) : ?> title="<?php echo __('4chan time') . ': ' . date('D M d H:i:s Y', $p->original_timestamp) ?>"<?php endif; ?>><?php echo date('D M d H:i:s Y', $p->timestamp) ?></time>
			</span>

			<a href="<?php echo site_url('@radix/' . $selected_radix->shortname . '/thread/' . $p->thread_num) . '#'  . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>" data-post="<?php echo $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>" data-function="highlight">No.</a><a href="<?php echo site_url('@radix/' . $selected_radix->shortname . '/thread/' . $p->thread_num) . '#q' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>" data-post="<?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?>" data-function="quote"><?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a>

			<span class="post_controls">
				<?php if (isset($modifiers['post_show_view_button'])) : ?><a href="<?php echo site_url('@radix/' . $selected_radix->shortname . '/thread/' . $p->thread_num) . '#' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>" class="btnr parent"><?php echo __('View') ?></a><?php endif; ?><a href="<?php echo site_url('@radix/' . $selected_radix->shortname . '/report/' . $p->doc_id) ?>" class="btnr parent" data-post="<?php echo $p->doc_id ?>" data-post-id="<?php echo $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="report"><?php echo __('Report') ?></a><?php if ($p->subnum > 0 || $this->tank_auth->is_allowed() || !$selected_radix->archive) : ?><a href="<?php echo site_url('@radix/' . $selected_radix->shortname . '/delete/' . $p->doc_id) ?>" class="btnr parent" data-post="<?php echo $p->doc_id ?>" data-post-id="<?php echo $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="delete"><?php echo __('Delete') ?></a><?php endif; ?>
			</span>

			<?php if ($p->deleted == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.($this->theme->get_selected_theme() ? $this->theme->get_selected_theme() : 'default').'/images/icons/file-delete-icon.png'; ?>" width="16" height="16" title="<?php echo form_prep(__('This post was deleted from 4chan')) ?>"/></span><?php endif ?>
			<?php if ($p->spoiler == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.($this->theme->get_selected_theme() ? $this->theme->get_selected_theme() : 'default').'/images/icons/spoiler-icon.png'; ?>" width="16" height="16" title="<?php echo form_prep(__('This post contains a spoiler image')) ?>"/></span><?php endif ?>
			<?php if ($p->subnum > 0)   : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.($this->theme->get_selected_theme() ? $this->theme->get_selected_theme() : 'default').'/images/icons/communicate-icon.png'; ?>" width="16" height="16" title="<?php echo form_prep(__('This post was made in the archive')) ?>"/></span><?php endif ?>

		</div>
	</header>
	<div class="backlink_list"<?php echo (isset($p->backlinks)) ? ' style="display:block"' : '' ?>>
		<?php echo __('Quoted by:') ?> <span class="post_backlink" data-post="<?php echo $p->num ?>"><?php echo (isset($p->backlinks)) ? implode(' ', $p->backlinks) : '' ?></span>
	</div>

	<div class="text<?php if (preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $p->comment_processed)) echo ' shift-jis'; ?>">
		
		<?php echo $p->comment_processed ?>
	</div>
	<?php if($this->tank_auth->is_allowed()) : ?>
	<div class="btn-group" style="clear:both; padding:5px 0 0 0;">
		<button class="btn btn-mini" data-function="activateModeration"><?php echo __('Mod') ?><?php if($p->poster_ip) echo ' ' .inet_dtop($p->poster_ip) ?></button>
	</div>
	<div class="btn-group post_mod_controls" style="clear:both; padding:5px 0 0 5px;">
		<button class="btn btn-mini" data-function="mod" data-board="<?php echo $selected_radix->shortname ?>" data-board-url="<?php echo site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?php echo $p->doc_id ?>" data-action="remove_post"><?php echo __('Remove') ?></button>
		<?php if($p->preview_orig) : ?>
		<button class="btn btn-mini" data-function="mod" data-board="<?php echo $selected_radix->shortname ?>" data-board-url="<?php echo site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?php echo $p->doc_id ?>" data-action="remove_image"><?php echo __('Remove image') ?></button>
		<button class="btn btn-mini" data-function="mod" data-board="<?php echo $selected_radix->shortname ?>" data-board-url="<?php echo site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?php echo $p->doc_id ?>" data-action="ban_md5"><?php echo __('Ban image') ?></button>
		<?php endif; ?>
		<?php if($p->poster_ip) : ?>
		<button class="btn btn-mini" data-function="mod" data-board="<?php echo $selected_radix->shortname ?>" data-board-url="<?php echo site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?php echo $p->doc_id ?>" data-action="ban_user"><?php echo __('Ban user:') . ' ' . inet_dtop($p->poster_ip) ?></button>
		<?php endif; ?>
		<?php if(isset($p->report_status) && !is_null($p->report_status)) : ?>
		<button class="btn btn-mini" data-function="mod" data-board="<?php echo $selected_radix->shortname ?>" data-board-url="<?php echo site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?php echo $p->doc_id ?>" data-action="remove_report"><?php echo __('Remove report') ?></button>
		<?php endif; ?>
		</ul>
	</div>
	
	<?php if(isset($p->report_status) && !is_null($p->report_status)) : ?>
	<div class="report_reason"><?php echo '<strong>' . __('Report reason:') . '</strong> ' . $p->report_reason_processed ?>
		<br/>
		<div class="ip_reporter"><?php echo inet_dtop($p->report_ip_reporter) ?></div>
	</div>
	<?php endif; ?>
	
	<?php endif; ?>
</article>
<br/>
