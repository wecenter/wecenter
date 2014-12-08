
<blockquote class="decoda-quote">
	<?php if (!empty($author) || !empty($date)) { ?>
		<div class="decoda-quoteHead">
			<?php if (!empty($date)) { ?>
				<span class="decoda-quoteDate">
					<?php echo date('M jS Y, H:i:s', is_numeric($date) ? $date : strtotime($date)); ?>
				</span>
			<?php }

			if (!empty($author)) { ?>
				<span class="decoda-quoteAuthor">
					<?php echo $this->getFilter()->message('quoteBy', array(
						'author' => htmlentities($author, ENT_NOQUOTES, 'UTF-8')
					)); ?>
				</span>
			<?php } ?>

			<span class="clear"></span>
		</div>
	<?php } ?>

	<div class="decoda-quoteBody">
		<?php echo $content; ?>
	</div>
</blockquote>
