<div class="postbox" id="last-three-months-container">
	<h2 class="hndle ui-sortable-handle">
		<span><?php echo __( 'All Episodes for the Last Three Months', 'seriously-simple-stats' ); ?></span>
	</h2>
	<div class="inside">
		<table class='form-table striped'>
			<thead>
			<tr>
				<th style="text-align: center;" class="<?php echo $sort_order['publish']; ?>">
					<a href="#">
						<span><?php echo __( 'Publish Date', 'seriously-simple-stats' ); ?></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th style="text-align: center;" class="<?php echo $sort_order['name']; ?>">
					<a href="#">
						<span><?php echo __( 'Episode Name', 'seriously-simple-stats' ); ?></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>

				<?php foreach ( $this->dates as $date ) { ?>
					<th style="text-align: center;" class="<?php echo $sort_order[ $date ]; ?>">
						<a href="#">
							<span><?php echo __( $date, 'seriously-simple-stats' ); ?></span>
							<span class="sorting-indicator"></span>
						</a>
					</th>
				<?php } ?>

				<th style="text-align: center;" class="ssp_stats_3m_total <?php echo $sort_order['lifetime']; ?>">
					<a href="#">
						<span><?php echo __( 'Lifetime', 'seriously-simple-stats' ); ?></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>

			</tr>
			</thead>
			<?php foreach ( $all_episodes_stats as $episode ) { ?>
				<tr>
					<td><?php echo $episode['date']; ?></td>
					<td><a href='<?php echo $episode['slug']; ?>'><?php echo $episode['episode_name']; ?></a></td>
					<?php
					if ( isset( $episode['listens_array'] ) ) {
						foreach ( $episode['listens_array'] as $listen ) {
							?>
							<td style='text-align: center;'><?php echo $listen; ?></td>
							<?php
						}
					}
					?>
					<td style='text-align: center;' class='ssp_stats_3m_total'><?php echo $episode['listens']; ?></td>
				</tr>
			<?php } ?>
		</table>
	</div>
</div>
