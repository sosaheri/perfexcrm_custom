<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">
	<div class="col-md-12 section-client-dashboard">

			<div id="ExternalUrl" class="panel_s">
				<div id="ExternalUrl" class="panel-body">
					<h3 class="text-success projects-summary-heading no-mtop mbot15"></h3>
					<div class="row">
					
					<div class="divTable ExternalUrl">
						<div class="divTableBody">

						
							<div class="divTableRow">
							<div class="divTableCell" style="width:150px;"></div>
							<div class="divTableCell"><a href="#">Request A New Book</a></div>
							<div class="divTableCell"><a href="#">My Publishing Proposals</a></div>
							<div class="divTableCell"><a href="#">Contact with Us</a></div>
							<div class="divTableCell"></div>
							</div>

							<div class="divTableRow">
							<div class="divTableCell"></div>
							<div class="divTableCell"><a href="#">Printing Order</a></div>
							<div class="divTableCell"><a href="#">My Services Budgets</a></div>
							<div class="divTableCell"><a href="#">My Tickets</a></div>
							<div class="divTableCell"></div>
							</div>

							
							<div class="divTableRow">
							<div class="divTableCell"></div>
							<div class="divTableCell"><a href="#">Promote My Book</a></div>
							<div class="divTableCell"><a href="#">My Invoices</a></div>
							<div class="divTableCell"><a href="#">All Royalties</a></div>
							<div class="divTableCell"></div>
							</div>
							
							<div class="divTableRow">
							<div class="divTableCell"></div>								
							<div class="divTableCell"><a href="#">Sale Channels</a></div>
							<div class="divTableCell"><a href="#">My Author's Profile</a></div>
							<div class="divTableCell"><a href="#">Request Payment</a></div>
							<div class="divTableCell"></div>
						</div>
						</div>
					</div>

					</div>
				</div>
			</div>

			<h3 id="customers" class="no-mtop"><?php echo _l('projects_summary'); ?></h3>
	
		<?php if(has_contact_permission('projects')) { ?>
			<div class="panel_s">
				<div class="panel-body">
					<div class="row">
						<?php get_template_part('projects/project_summary'); ?>
					</div>
				</div>
			</div>
		<?php } ?>

			<h3 id="customers" class="no-mtop"><?php echo _l('my_royalties'); ?></h3>



		<?php hooks()->do_action('client_area_after_project_overview'); ?>
		<div class="panel_s">
			<?php
			if(has_contact_permission('invoices')){ ?>
				<div class="panel-body">
					<p class="bold"><?php echo _l('clients_quick_invoice_info'); ?></p>
					<?php if(has_contact_permission('invoices')){ ?>
						<a href="<?php echo site_url('clients/statement'); ?>"><?php echo _l('view_account_statement'); ?></a>
					<?php } ?>
					<hr />
					<?php get_template_part('invoices_stats'); ?>
					<hr />
					<div class="row">
						<div class="col-md-3">
							<?php if(count($payments_years) > 0){ ?>
								<div class="form-group">
									<select data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" class="form-control" id="payments_year" name="payments_years" data-width="100%" onchange="total_income_bar_report();" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
										<?php foreach($payments_years as $year) { ?>
											<option value="<?php echo $year['year']; ?>"<?php if($year['year'] == date('Y')){echo 'selected';} ?>>
												<?php echo $year['year']; ?>
											</option>
										<?php } ?>
									</select>
								</div>
							<?php } ?>
							<?php if(is_client_using_multiple_currencies()){ ?>
								<div id="currency" class="form-group mtop15" data-toggle="tooltip" title="<?php echo _l('clients_home_currency_select_tooltip'); ?>">
									<select data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" class="form-control" name="currency">
										<?php foreach($currencies as $currency){
											$selected = '';
											if($currency['isdefault'] == 1){
												$selected = 'selected';
											}
											?>
											<option value="<?php echo $currency['id']; ?>" <?php echo $selected; ?>><?php echo $currency['symbol']; ?> - <?php echo $currency['name']; ?></option>
										<?php } ?>
									</select>
								</div>
							<?php } ?>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="relative" style="max-height:400px;">
								<canvas id="client-home-chart" height="400" class="animated fadeIn"></canvas>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>

