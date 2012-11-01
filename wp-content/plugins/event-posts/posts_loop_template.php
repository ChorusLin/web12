<!-- Note: if you make changes to this file, move it to your current theme's
	directory so this file won't be overwritten when the plugin is upgraded. -->

<!-- This is the output of the post title -->
<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

<!-- This is the output of the excerpt -->
<div class="entry-summary">
	<?php the_excerpt(); ?>
</div>
