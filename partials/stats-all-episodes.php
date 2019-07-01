<div class="postbox" id="last-three-months-container">
	<h2 class="hndle ui-sortable-handle">
		<span><?php echo __( 'All Episodes for the Last Three Months', 'seriously-simple-stats' ); ?></span>
	</h2>
	<div class="inside">
		<table class='form-table striped'>
			<thead>
			<tr>
				<th class="sortable desc">
					<a href="#">
						<span><?php echo __( 'Publish Date', 'seriously-simple-stats' ); ?></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th><?php echo __( 'Episode Name', 'seriously-simple-stats' ); ?></th>
				<th style='text-align: center;'><?php echo current_time( 'F' ); ?></a></th>
				<th style='text-align: center;'><?php echo date( 'F', strtotime( current_time( "Y-m-d" ) . '-1 MONTH' ) ); ?></th>
				<th style='text-align: center;'><?php echo date( 'F', strtotime( current_time( "Y-m-d" ) . '-2 MONTH' ) ); ?></th>
				<th style='text-align: center;' class='ssp_stats_3m_total'><?php echo __( 'Lifetime', 'seriously-simple-stats' ); ?></th>
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
