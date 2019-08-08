<div class='tablenav bottom'>
	<div class="tablenav-pages">
		<span class="displaying-num"><?php echo $this->total_posts . ' ' . __( 'items', 'seriously-simple-stats' ) ?></span>
		<span class='pagination-links'>
		<?php if ( $pagenum != 1 ) { ?>
			<a class="next-page" href="<?php echo $prev_page_url ?>">
				<span class="screen-reader-text"><?php echo __( 'Previous page', 'seriously-simple-stats' ); ?></span>
				<span aria-hidden="true">«</span>
			</a>
		<?php } ?>
		<span id="table-paging" class="paging-input"><span class="tablenav-paging-text"><?php echo $pagenum . ' of <span class="total-pages">' . $total_pages . '</span> </span>'; ?>
			<?php if ( $next_page <= $total_pages ) { ?>
				<a class="next-page table-paging" href="<?php echo $next_page_url ?>">
			 	<span class="screen-reader-text"><?php echo __( 'Next page', 'seriously-simple-stats' ) ?></span>
				<span aria-hidden="true">»</span>
				</a>
			<?php } ?>
		</span>&nbsp;
	</div>
</div>
