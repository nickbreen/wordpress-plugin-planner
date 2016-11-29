<h2><?php _e('Planner', 'wordpress-plugin-planner'); ?></h2>

<form method="get" enctype="multipart/form-data" class="planner">
	<input type="hidden" name="page" value="<?php echo $page; ?>" />
	<input type="hidden" name="week" id="week" value="<?php echo date('Y-m-d', $time); ?>" />

    <nav>
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

	<table class="">
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
				<?php for ($ii = get_option('start_of_week', 1); $ii < get_option('start_of_week', 1) + 7; $ii ++) : ?>
					<th><?php echo date('l j/n', strtotime("next sunday +{$ii} day", $time)); ?></th>
				<?php endfor; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($plans as $planName => $plan): ?>
			<tr>
				<td><?php echo $planName; ?></td>
				<?php foreach ($plan as $i => $p): ?>
					<td><pre><?php var_dump($i, $p); ?></pre></td>
				<?php endforeach; ?>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</form>
