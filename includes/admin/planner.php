<h2><?php _e('Planner', 'wordpress-plugin-planner'); ?></h2>

<form method="get" enctype="multipart/form-data">
	<input type="hidden" name="page" value="<?php echo $page; ?>" />
	<input type="hidden" name="week" id="week" value="<?php echo date('Y-m-d', $time); ?>" />

    <nav class="planner">
        <label>Week <b><?php echo date('W', $time); ?></b> starting:&nbsp;
            <input class="week-picker"
                data-datepicker.first-day="<?php echo get_option('start_of_week', 1); ?>"
                data-datepicker.date-format="D, d M yy"
                data-datepicker.alt-field="#week"
                data-datepicker.alt-format="yy-mm-dd"
                value="<?php echo date('D, j M Y', $time); ?> "/>
        </label>

		<a class="new-thing" href="<?php echo admin_url('edit.php?post_type=thing&page=create_thing'); ?>"><?php _e('New Thing', 'wordpress-plugin-planner'); ?></a>
		<a class="new-thing" href="<?php echo admin_url('edit.php?post_type=thing&page=create_thing'); ?>"><?php _e('New Thing', 'wordpress-plugin-planner'); ?></a>
		<a class="new-thing" href="<?php echo admin_url('edit.php?post_type=thing&page=create_thing'); ?>"><?php _e('New Thing', 'wordpress-plugin-planner'); ?></a>
	</nav>

	<table class="planner">
		<caption>
			<a class="prev"
				href="<?php echo add_query_arg('week', date('Y-m-d', strtotime('-7 days', $time))); ?>">&#x21e6;</a>
			Week <b><?php echo date('W', $time); ?></b>
			<a class="next"
				href="<?php echo add_query_arg('week', date('Y-m-d', strtotime('+7 days', $time))); ?>">&#x21e8;</a>
		</caption>
		<thead>
			<tr>
				<th><?php _e('Plan', 'wordpress-plugin-planner'); ?></th>
				<?php for ($i = get_option('start_of_week', 1); $i < get_option('start_of_week', 1) + 7; $i ++) : ?>
					<th><?php echo date('l j/n', strtotime("last sunday +{$i} day", $time)); ?></th>
				<?php endfor; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($plans as $planName => $plan): ?>
				<tr data-plan-name="<?php echo $planName; ?>">
					<td><?php echo $planName; ?></td>
					<?php foreach ($plan as $i => $ps): ?>
						<td data-day-of-week="<?php echo $i; ?>" data-date="<?php echo date('Y-m-d', strtotime("last sunday +{$i} day", $time)); ?>">
							<?php foreach ($ps as $j => $p): ?>
								<dl data-plan-id="<?php echo $j; ?>">
									<dt><?php echo $fields['passengers']['label']; ?> <dd><?php echo $p['passengers']; ?>
									<dt><?php echo $fields['driver']['label']; ?> <dd><?php echo $p['driver']['post_title']; ?>
									<dt><?php echo $fields['vehicle']['label']; ?> <dd><?php echo $p['vehicle']['post_title']; ?>
								</dl>
							<?php endforeach; ?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</form>
