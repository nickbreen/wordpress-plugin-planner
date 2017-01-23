<div class="planner wrap">

	<h1><?php _e('Planner', $text_domain); ?>
		<?php foreach (['plan', 'driver', 'vehicle'] as $p): ?>
			<a class="page-title-action" href="<?php echo admin_url("post-new.php?post_type=$p"); ?>">
				<?php printf(__("Add %s", $text_domain), pods_data($p)->pod_data['options']['label_singular']); ?>
			</a>
		<?php endforeach; ?>
	</h1>

	<div id="calendar"></div>

	<?php echo $booking->template('booking'); ?>
	<?php echo $driver->template('driver'); ?>
	<?php echo $vehicle->template('vehicle'); ?>
</div>
