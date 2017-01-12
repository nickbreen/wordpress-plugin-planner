<div class="planner wrap">

	<h1><?php _e('Planner', $text_domain); ?>
		<?php foreach (['plan', 'driver', 'vehicle'] as $p): ?>
			<a class="page-title-action" href="<?php echo admin_url("post-new.php?post_type=$p"); ?>">
				<?php printf(__("Add %s", $text_domain), pods_data($p)->pod_data['options']['label_singular']); ?>
			</a>
		<?php endforeach; ?>
	</h1>

	<table>
		<caption>
			<form method="get" enctype="multipart/form-data">
				<input type="hidden" name="page" value="<?php echo $page; ?>" />
				<input type="hidden" name="week" id="week" value="<?php echo date('Y-m-d', $time); ?>" />

				<a class="prev" title="Week <?php echo date('W', strtotime('last week', $time)); ?>"
					href="<?php echo add_query_arg('week', date('Y-m-d', strtotime('last week', $time))); ?>">&#x21e6;</a>
				<label>
					<?php _e('Week ', $text_domain); ?>
					<?php echo date('W', $time); ?>
					<input class="date-picker"
						data-datepicker.first-day="<?php echo get_option('start_of_week', 1); ?>"
						data-datepicker.date-format="D, d M yy"
						data-datepicker.alt-field="#week"
						data-datepicker.alt-format="yy-mm-dd"
						data-datepicker.show-other-months="true"
						data-datepicker.select-other-months="true"
						data-datepicker.show-week="true"
						size="16"
						value="<?php echo date(get_option('date_format'), $time); ?>"/>
				</label>
				<a class="next" title="Week <?php echo date('W', strtotime('next week', $time)); ?>"
					href="<?php echo add_query_arg('week', date('Y-m-d', strtotime('next week', $time))); ?>">&#x21e8;</a>
			</form>
		</caption>
		<thead>
			<tr>
				<th><?php _e('Plan', $text_domain); ?></th>
				<?php for ($i = get_option('start_of_week', 1); $i < get_option('start_of_week', 1) + 7; $i ++) : ?>
					<th><?php echo date("l\nj/n", strtotime("last sunday +{$i} day", $time)); ?></th>
				<?php endfor; ?>
			</tr>
		</thead>
		<tbody>
			<?php if (!$plans): ?>
				<tr>
					<td colspan="8">
						<?php _e('No plans for this week!', $text_domain); ?>
					</td>
				</tr>
			<?php endif; ?>
			<?php foreach ($plans as $planName => $planWeek): ?>
				<tr>
					<td><?php echo $planName; ?></td>
					<?php foreach ($planWeek as $i => $planDay): ?>
						<td>
							<?php foreach ($planDay as $j => $template) echo pods('plan', $j)->template($template); ?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php echo $booking->template('booking'); ?>
	<?php echo $driver->template('driver'); ?>
	<?php echo $vehicle->template('vehicle'); ?>
</div>
