<?php
if (!isset($page))
	$page = 1;
?>
<div style="overflow:hidden;">
<!--- Page Input -->
	<?php echo form_open('#') . form_close(); ?>

	<!--- Search Input -->
	<?php echo form_open(get_selected_radix()->shortname . '/search'); ?>
	<div id="simple-search" class="postspan" style="float:left">
		Text Search [<a class="tooltip" href="#">?<span>Place a <tt>|</tt> in between expressions to get one of them in results, e.g. <tt>tripcode|email</tt> to locate posts that contain either the word tripcode or email in them.<br />Place a <tt>-</tt> before a word to exclude posts containing the word: <tt>-tripcode</tt><br />Place quotes around phrases to find pages containing the phrase: <tt>"I am a filthy tripcode user"</tt></span></a>]

		<?php
		echo form_input(array(
			'name' => 'text',
			'id' => 'text',
			'size' => '24',
			'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : ''
		));
		?>

		<?php
		echo form_submit(array(
			'value' => 'Go'
		));
		?>
		<a href="<?php echo site_url(get_selected_radix()->shortname . '/advanced-search') ?>" onclick="javascript:toggle('advanced-search');toggle('simple-search');return false;">[ Advanced ]</a>
	</div>
	<?php echo form_close(); ?>

	<!--- Advanced Search Input -->
	<?php echo form_open(get_selected_radix()->shortname . '/search'); ?>
	<div id="advanced-search" class="postspan" style="float:left;display:none">
		<table style="float:left">
			<tbody>
			<tr>
				<td colspan="2" class="theader">Advanced Search</td>
			</tr>
			<tr>
				<td class="postblock">Text to find</td>
				<td>
					<?php echo form_input(array('name' => 'text', 'size' => '32', 'id' => 'text2', 'value' => (isset($search["text"])) ? rawurldecode($search["text"]) : '')); ?>
				</td>
			</tr>
			<tr>
				<td class="postblock">Username <a class="tooltip" href="#">[?]<span>Search for <b>exact</b> username. Leave empty for any username.</span></a></td>
				<td>
					<?php echo form_input(array('name' => 'username', 'size' => '32', 'id' => 'username', 'value' => (isset($search["username"])) ? rawurldecode($search["username"]) : '')); ?>
				</td>
			</tr>
			<tr>
				<td class="postblock">Tripcode <a class="tooltip" href="#">[?]<span>Search for <b>exact</b> tripcode. Leave empty for any tripcode.</span></a></td>
				<td>
					<?php echo form_input(array('name' => 'tripcode', 'size' => '32', 'id' => 'tripcode', 'value' => (isset($search["tripcode"])) ? rawurldecode($search["tripcode"]) : '')); ?>
				</td>
			</tr>
			<tr>
				<td class="postblock">From Date <a class="tooltip" href="#">[?]<span>Enter what date to start searching from.<br/>Format is YYYY-MM-DD</span></a></td>
				<td>
					<?php
					echo form_input(
						array('type' => 'date',
							  'name' => 'start',
							  'placeholder' => 'yyyy-mm-dd',
							  'id' => 'date_start',
							  'value' => (isset($search["date_start"])) ?
								  rawurldecode($search["date_start"]) : ''));
					?>
				</td>
			</tr>
			<tr>
				<td class="postblock">To Date <a class="tooltip" href="#">[?]<span>Enter what date to start searching until.<br/>Format is YYYY-MM-DD</span></a></td>
				<td>
					<?php
					echo form_input(
						array(
							'type' => 'date',
							'name' => 'end',
							'id' => 'date_end',
							'placeholder' => 'yyyy-mm-dd',
							'value' => (isset($search["date_start"])) ?
								rawurldecode($search["date_start"]) : ''));
					?>
				</td>
			</tr>
			<tr>
				<td class="postblock">Deleted posts</td>
				<td>
					<label>
						<?php echo form_radio(array('name' => 'deleted', 'value' => '', 'checked' => (empty($search["deleted"])) ? TRUE : FALSE)); ?>
						<span>Show all posts</span>
					</label><br />
					<label>
						<?php echo form_radio(array('name' => 'deleted', 'value' => 'deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'deleted') ? TRUE : FALSE)); ?>
						<span>Show only deleted posts</span>
					</label><br />
					<label>
						<?php echo form_radio(array('name' => 'deleted', 'value' => 'not-deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'not-deleted') ? TRUE : FALSE)); ?>
						<span>Only show non-deleted posts</span>
					</label>
				</td>
			</tr>
			<tr>
				<td class="postblock">Internal posts</td>
				<td>
					<label>
						<?php echo form_radio(array('name' => 'ghost', 'value' => '', 'checked' => (empty($search["ghost"])) ? TRUE : FALSE)); ?>
						<span>Show all posts</span>
					</label><br />
					<label>
						<?php echo form_radio(array('name' => 'ghost', 'value' => 'only', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'only') ? TRUE : FALSE)); ?>
						<span>Show only internal posts</span>
					</label><br />
					<label>
						<?php echo form_radio(array('name' => 'ghost', 'value' => 'none', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'none') ? TRUE : FALSE)); ?>
						<span>Show old archived posts</span>
					</label>
				</td>
			</tr>
			<tr>
				<td class="postblock">Order</td>
				<td>
					<label>
						<?php echo form_radio(array('name' => 'order', 'value' => 'desc', 'checked' => (empty($search["order"]) || (!empty($search["order"]) && $search["order"] == 'desc')) ? TRUE : FALSE)); ?>
						<span>New posts first</span>
					</label><br />
					<label>
						<?php echo form_radio(array('name' => 'order', 'value' => 'asc', 'checked' => (!empty($search["order"]) && $search["order"] == 'asc') ? TRUE : FALSE)); ?>
						<span>Old posts first</span>
					</label>
				</td>
			</tr>
			<tr>
				<td class="postblock">Results</td>
				<td>
					<label>
						<?php echo form_radio(array('name' => 'type', 'value' => 'posts', 'checked' => (!empty($search["type"]) && $search["type"] == 'posts'))); ?>
						<span>Post</span>
					</label><br />
					<label>
						<?php echo form_radio(array('name' => 'type', 'value' => 'op', 'checked' => (!empty($search["type"]) && $search["type"] == 'op'))); ?>
						<span>Threads</span>
					</label>
				</td>
			</tr>
			<tr>
				<td class="postblock">Action</td>
				<td>
					<?php
					echo form_submit(array(
						'value' => 'Search'
					));
					?>
					<a href="#" onclick="javascript:toggle('advanced-search');toggle('simple-search');return false;">[ Simple ]</a>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<?php echo form_close(); ?>

	<!--- Post Input -->
	<?php echo form_open(get_selected_radix()->shortname . '/post'); ?>
	<div class="postspan" style="float:left">
		View Post

		<?php
		echo form_input(array(
			'name' => 'post',
			'id' => 'post',
			'size' => '9'
		));
		?>

		<?php
		echo form_submit(array(
			'value' => 'View',
			'onclick' => 'location.href=\'' . site_url(get_selected_radix()->shortname . '/post/') . '\' + this.form.post.value + \'/\'; return false;'
		));
		?>
	</div>
	<?php echo form_close(); ?>

	<!--- Page Input -->
	<?php echo form_open(get_selected_radix()->shortname . '/page'); ?>
	<div class="postspan" style="float:left">
		View Page

		<?php
		echo form_input(array(
			'name' => 'page',
			'id' => 'page',
			'size' => '6',
			'value' => $page
		));
		?>

		<?php
		echo form_submit(array(
			'value' => 'View',
			'onclick' => 'location.href=\'' . site_url(get_selected_radix()->shortname . '/page/') . '\' + this.form.page.value + \'/\'; return false;'
		));
		?>

		<a class="tooltip" href="#">[?]<span>In ghost mode, only threads with non-archived posts will be shown.</span></a>

		<input type="button" value="View in Ghost Mode" onclick="location.href='<?php echo site_url(get_selected_radix()->shortname . '/ghost') ?>' + this.form.page.value + '/'; return false;" />
	</div>
	<?php echo form_close(); ?>
</div>
