<div class="planner wrap">

	<h1><?php _e('Planner', 'wordpress-plugin-planner'); ?>
		<?php foreach (['plan' => 'Plan', 'driver' => 'Driver', 'vehicle' => 'Vehicle'] as $p => $l): ?>
			<a class="page-title-action" href="<?php echo admin_url("post-new.php?post_type=$p"); ?>"><?php _e("Add $l", 'wordpress-plugin-planner'); ?></a>
		<?php endforeach; ?>
	</h1>

	<form method="get" enctype="multipart/form-data">
		<input type="hidden" name="page" value="<?php echo $page; ?>" />
		<input type="hidden" name="week" id="week" value="<?php echo date('Y-m-d', $time); ?>" />

		<table>
			<caption>
				<a class="prev" title="Week <?php echo date('W', strtotime('last week', $time)); ?>"
					href="<?php echo add_query_arg('week', date('Y-m-d', strtotime('last week', $time))); ?>">&#x21e6;</a>
				<label for="week-picker" data-label="<?php _e('Week ', 'wordpress-plugin-planner'); ?>">
					<?php echo date('W', $time); ?>
					<input id="week-picker"
						data-datepicker.first-day="<?php echo get_option('start_of_week', 1); ?>"
						data-datepicker.date-format="D, d M yy"
						data-datepicker.alt-field="#week"
						data-datepicker.alt-format="yy-mm-dd"
						data-datepicker.show-other-months="true"
						data-datepicker.select-other-months="true"
						data-datepicker.show-week="true"
						size="16"
						value="<?php echo date('D, j M Y', $time); /* e.g. Mon, 21 Nov 2016 */ ?>"/>
				</label>
				<a class="next" title="Week <?php echo date('W', strtotime('next week', $time)); ?>"
					href="<?php echo add_query_arg('week', date('Y-m-d', strtotime('next week', $time))); ?>">&#x21e8;</a>
			</caption>
			<thead>
				<tr>
					<th><?php _e('Plan', 'wordpress-plugin-planner'); ?></th>
					<?php for ($i = get_option('start_of_week', 1); $i < get_option('start_of_week', 1) + 7; $i ++) : ?>
						<th><?php echo date("l\nj/n", strtotime("last sunday +{$i} day", $time)); ?></th>
					<?php endfor; ?>
				</tr>
			</thead>
			<tbody>
				<?php if (!$plans): ?>
					<tr>
						<td colspan="8">
							No plans for this week!
						</td>
					</tr>
				<?php endif; ?>
				<?php foreach ($plans as $planName => $planWeek): ?>
					<tr data-plan-name="<?php echo $planName; ?>">
						<td><?php echo $planName; ?></td>
						<?php foreach ($planWeek as $i => $planDay): ?>
							<td data-day-of-week="<?php echo $i; ?>"
								data-date="<?php echo date('Y-m-d', strtotime("last sunday +{$i} day", $time)); ?>">
								<?php foreach ($planDay as $j => $template): ?>
									<?php echo pods('plan', $j)->template($template); ?>
								<?php endforeach; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php echo $driver->template('driver'); ?>
	</form>
</div>
