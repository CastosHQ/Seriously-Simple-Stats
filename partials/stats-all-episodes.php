<div class="postbox" id="last-three-months-container">
	<h2 class="hndle ui-sortable-handle">
		<span><?php echo __( 'All Episodes for the Last Three Months', 'seriously-simple-stats' ); ?></span>
	</h2>
	<div class="inside">
		<table class='form-table striped'>
			<thead>
			<tr>
				<th style="text-align: center;" class="<?php echo $sort_order['date'][0]; ?>">
					<a href="<?php echo $sort_order['date'][1] ?>">
						<span><?php echo __( 'Publish Date', 'seriously-simple-stats' ); ?></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th style="text-align: center; width: 50%;" class="<?php echo $sort_order['episode_name'][0]; ?>">
					<a href="<?php echo $sort_order['episode_name'][1] ?>">
						<span><?php echo __( 'Episode Name', 'seriously-simple-stats' ); ?></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<?php foreach ( $this->dates as $date ) { ?>
					<th style="text-align: center;" class="<?php echo $sort_order[ $date ][0]; ?>">
						<a style="margin-left: 40%" href="<?php echo $sort_order[ $date ][1] ?>">
							<span><?php echo __( $date, 'seriously-simple-stats' ); ?></span>
							<span class="sorting-indicator"></span>
						</a>
					</th>
				<?php } ?>
				<th style="text-align: center;" class="ssp_stats_3m_total <?php echo $sort_order['listens'][0]; ?>">
					<a style="margin-left: 20%" href="<?php echo $sort_order['listens'][1] ?>">
						<span><?php echo __( 'Lifetime', 'seriously-simple-stats' ); ?></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
			</tr>
			</thead>
			<?php foreach ( $all_episodes_stats as $episode ) { ?>
				<tr>
					<td><?php echo $episode['formatted_date']; ?></td>
					<td style="width: 50%;"><a href='<?php echo $episode['slug']; ?>'><?php echo $episode['episode_name']; ?></a></td>
					<?php foreach ( $this->dates as $date ) { ?>
							<td style='text-align: center;'><?php echo $episode[$date]; ?></td>
					<?php } ?>
					<td style='text-align: center;' class='ssp_stats_3m_total'><?php echo $episode['listens']; ?></td>
				</tr>
			<?php } ?>
		</table>
	</div>
</div>
