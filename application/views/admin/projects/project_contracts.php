<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="p_buttons">
	<?php $this->load->view('admin/contracts/filters'); ?>
</div>
<div class="clearfix"></div>
<div class="project_contracts mtop20">
	<?php $this->load->view('admin/contracts/table_html'); ?>
</div>
