<?php if ($player === 'embed') { ?>
	<embed src="<?php echo $url; ?>"
		type="application/x-shockwave-flash"
		allowscriptaccess="always"
		allowfullscreen="true"
		width="<?php echo $width; ?>"
		height="<?php echo $height; ?>"></embed>

<?php } else { ?>
	<iframe src="<?php echo $url; ?>"
		width="<?php echo $width; ?>"
		height="<?php echo $height; ?>"
		frameborder="0"></iframe>

<?php } ?>