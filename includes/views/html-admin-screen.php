<div class="wrap woocommerce">
    <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=wc4jp-zeus-output') ?>" class="nav-tab <?php echo ($tab == 'setting') ? 'nav-tab-active' : ''; ?>"><?php echo __( 'Setting', 'woo-zeus' )?></a><a href="<?php echo admin_url('admin.php?page=wc4jp-zeus-output&tab=info') ?>" class="nav-tab <?php echo ($tab == 'info') ? 'nav-tab-active' : ''; ?>"><?php echo __( 'Infomations', 'woo-zeus' )?></a>
    </h2>
	<?php
		switch ($tab) {
			case "setting" :
				$this->admin_zeus_setting_page();
			break;
			default :
				$this->admin_zeus_info_page();
			break;
		}
	?>
</div>
