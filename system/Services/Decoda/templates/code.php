<?php // Place $content directly within the tags to not leave any whitespace for <pre> ?>

<pre class="decoda-code <?php if (!empty($default)) { echo 'code-' . $default; } ?>"<?php if (isset($hl)) { ?> data-highlight="<?php echo $hl; ?>"<?php } ?>><?php echo $content; ?></pre>
