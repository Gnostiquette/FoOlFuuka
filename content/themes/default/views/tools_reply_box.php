<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if ((isset($enabled_tools_reply_box) && $enabled_tools_reply_box && !get_selected_radix()->archive) ||
	(isset($thread_id))) :
?>

<div class="thread_form_wrap clearfix">
<section class="thread_form clearfix">
	<?php
	echo form_open_multipart(get_selected_radix()->shortname . '/submit');
	echo form_hidden('reply_numero', isset($thread_id)?$thread_id:0);
	?>
<fieldset>

	<div class="input-prepend">
		<label class="add-on" for="reply_talkingde"><?php echo __('Subject') ?></label><?php
		echo form_input(array(
			'name' => 'reply_talkingde',
			'id' => 'reply_talkingde',
			'class' => 'span6'
		));
		?>
	</div>

	<label class="comment_label" for="reply_chennodiscursus"><?php echo __('Comment') ?></label>

	<div class="pull-left">

		<div class="input-prepend">
			<label class="add-on" for="reply_bokunonome"><?php echo __('Name') ?></label><?php
			echo form_input(array(
				'name' => 'name',
				'id' => 'reply_name_yep',
				'style' => 'display:none'
			));

			echo form_input(array(
				'name' => 'reply_bokunonome',
				'id' => 'reply_bokunonome',
				'value' => ((isset($this->fu_reply_name)) ? $this->fu_reply_name : '')
			));
			?>
		</div>

		<div class="input-prepend">
			<label class="add-on" for="reply_elitterae"><?php echo __('E-mail') ?></label><?php
			echo form_input(array(
				'name' => 'email',
				'id' => 'reply_email_yep',
				'style' => 'display:none'
			));
			echo form_input(array(
				'name' => 'reply_elitterae',
				'id' => 'reply_elitterae',
				'value' => ((isset($this->fu_reply_email)) ? $this->fu_reply_email : '')
			));
			?>
		</div>


		<?php if(!isset($disable_image_upload) || !$disable_image_upload) : ?>
				<div class="input-prepend">
					<label class="add-on" for="file_image"><?php echo __('File') ?></label><input type="file" name="file_image" id="file_image"  /><?php
		echo form_hidden('MAX_FILE_SIZE', get_selected_radix()->max_image_size_kilobytes);
		?>
					</div>
		<?php endif; ?>

		<div class="input-prepend">
			<label class="add-on" for="reply_nymphassword"><?php echo __('Password') ?></label><?php
			echo form_password(array(
				'name' => 'reply_nymphassword',
				'id' => 'reply_nymphassword',
				'value' => $this->fu_reply_password,
				'required' => 'required'
			));
			?>
		</div>
		<?php if ($this->tank_auth->is_allowed()) : ?>
		<div class="input-prepend">
			<label class="add-on" for="reply_postas"><?php echo __('Post as') ?></label><?php
			$postas = array('user' => __('User'), 'mod' => __('Moderator'));
			if ($this->tank_auth->is_admin())
			{
				$postas['admin'] = __('Administrator');
			}
			echo form_dropdown('reply_postas', $postas, 'User', 'id="reply_postas"');
			?>
		</div>
		<?php endif; ?>

	</div>


	<div class="input-append pull-left">
		<?php
		echo form_textarea(array(
			'name' => 'reply',
			'id' => 'reply_comment_yep',
			'style' => 'display:none'
		));
		?><?php
		echo form_textarea(array(
			'name' => 'reply_chennodiscursus',
			'id' => 'reply_chennodiscursus',
			'rows' => 3,
			'style' => 'height:132px; width:320px;'
		));
		?>
	</div>


	<div class="rules pull-left">
		<div class="btn-group" style="margin-bottom:5px">

			<?php

			$submit_array = array(
				'name' => 'reply_gattai',
				'value' => __('Submit'),
				'class' => 'btn btn-primary',
			);

			if (isset($thread_id) && $thread_id > 0)
			{
				$submit_array['data-function'] = 'comment';
				$submit_array['data-post'] = $thread_id;
			}
			echo form_submit($submit_array);
			
			$submit_array = array(
				'name' => 'reply_gattai_spoilered',
				'value' => __('Submit spoilered'),
				'class' => 'btn',
			);

			if (isset($thread_id) && $thread_id > 0)
			{
				$submit_array['data-function'] = 'comment';
				$submit_array['data-post'] = $thread_id;
			}
			echo form_submit($submit_array);
			
			echo form_reset(array('class' => 'btn', 'name' => 'reset', 'value' => __('Reset')));

			?>

		</div>
		
		<?php
			if(get_selected_radix()->posting_rules)
			{
				$this->load->library('Markdown_Parser');
				echo Markdown(get_selected_radix()->posting_rules);
			}
		?>
		
	</div>

	<div id="reply_ajax_notices"></div>
	<?php if(isset($reply_errors)) : ?>
	<span style="color:red"><?php echo $reply_errors ?></span>
	<?php endif; ?>

</fieldset>
	<?php echo form_close() ?>
</section>
</div>

<?php endif; ?>
