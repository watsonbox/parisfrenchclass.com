<ul>
	<?php 	/* Widgetized sidebar, if you have the plugin installed. */
			if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) : ?>
			<p>Please add widgets to your sidebar!</p>
	<?php endif; ?>
</ul>