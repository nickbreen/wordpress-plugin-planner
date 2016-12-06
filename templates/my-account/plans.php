<p><?php printf(__('%1$d plans are available for %2$d drivers registered for this account.', $text_domain), $plan->total(), $driver->total()); ?>
<?php echo $plan->pagination(); ?>
<?php echo $plan->template('plan'); ?>
<?php echo $plan->pagination(); ?>
