<div class="planner wrap">

	<div id="calendar"></div>

	<?php echo $booking->template('booking'); ?>

	<div id="drivers" title="Choose a Driver">
		<p>Drag a Driver onto a plan.</p>
		<?php echo $driver->template('driver'); ?>
	</div>
	<div id="vehicles" title="Choose a Vehicle">
		<p>Drag a Vehicle onto a plan.</p>
		<?php echo $vehicle->template('vehicle'); ?>
	</div>
</div>
