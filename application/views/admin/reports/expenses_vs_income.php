<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (count($years) > 1 || (count($years) == 1 && $years[0] != date('Y'))) {
    ?>
                        <select class="selectpicker" name="expense_year" onchange="change_expense_report_year(this.value);" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                            <?php foreach ($years as $year) {
        ?>
                            <option value="<?php echo $year; ?>"<?php if ($year == $report_year) {
            echo ' selected';
        } ?>>
                                <?php echo $year; ?>
                            </option>
                            <?php
    } ?>
                        </select>
                        <hr />
                        <?php
} ?>
                        <p class="text-danger bold">
                            <?php echo _l('amount_display_in_base_currency'); ?>
                        </p>
                        <div class="relative" style="max-height:600px;">
                            <canvas class="chart" height="600" id="report-expense-vs-income"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function(){
        init_currency(<?php echo $base_currency->id; ?>, function(){
          chartExpenseVsIncome = new Chart($('#report-expense-vs-income'), {
            type: 'bar',
            data: <?php echo $chart_expenses_vs_income_values; ?>,
            options:{
                maintainAspectRatio:false,
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return format_money(tooltipItem.yLabel)
                        }
                    }
                },
                scales: {
                    yAxes: [{
                      ticks: {
                        callback: function (value) {
                            return format_money(value)
                        },
                        beginAtZero: true,
                    }
                }]
            },}
        });
      });
    });
    function change_expense_report_year(year){
        window.location.href = admin_url+'reports/expenses_vs_income/'+year;
    }
</script>
</body>
</html>
