<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('home_my_todo_items'); ?>">
   <div class="panel_s todo-panel">
      <div class="panel-body padding-10">
         <div class="widget-dragger"></div>
         <p class="pull-left padding-5">
            <?php echo _l('home_my_todo_items'); ?>
         </p>
         <a href="<?php echo admin_url('todo'); ?>" class="pull-right padding-5">&nbsp;|&nbsp;<?php echo _l('home_widget_view_all'); ?></a>
         <a href="#__todo" data-toggle="modal" class="pull-right padding-5" style="padding-right:0px;">
            <?php echo _l('new_todo'); ?>
         </a>
         <div class="clearfix"></div>
         <hr class="hr-panel-heading-dashboard">
         <?php $total_todos = count($todos); ?>
         <h4 class="todo-title text-warning"><i class="fa fa-warning"></i> <?php echo _l('home_latest_todos'); ?></h4>
         <ul class="list-unstyled todo unfinished-todos todos-sortable sortable">
            <?php foreach($todos as $todo) { ?>
               <li>
                <?php echo form_hidden('todo_order',$todo['item_order']); ?>
                <?php echo form_hidden('finished',0); ?>
                <div class="media">
                 <div class="media-left no-padding-right">
                  <div class="dragger todo-dragger"></div>
                  <div class="checkbox checkbox-default todo-checkbox">
                     <input type="checkbox" name="todo_id" value="<?php echo $todo['todoid']; ?>">
                     <label></label>
                  </div>
               </div>
               <div class="media-body">
                  <p class="todo-description read-more no-padding-left" data-todo-description="<?php echo $todo['todoid']; ?>">
                        <?php echo $todo['description']; ?>
                     </p>
                        <a href="#" onclick="delete_todo_item(this,<?php echo $todo['todoid']; ?>); return false;" class="pull-right text-muted">
                           <i class="fa fa-remove"></i>
                        </a>
                        <a href="#" onclick="edit_todo_item(<?php echo $todo['todoid']; ?>); return false;" class="pull-right text-muted mright5">
                           <i class="fa fa-pencil"></i>
                        </a>
                        <small class="todo-date"><?php echo $todo['dateadded']; ?></small>
               </div>
            </div>
         </li>
      <?php } ?>
      <li class="padding no-todos ui-state-disabled <?php if($total_todos > 0){echo 'hide';} ?>"><?php echo _l('home_no_latest_todos'); ?></li>
   </ul>
   <?php $total_finished_todos = count($todos_finished); ?>
   <h4 class="todo-title text-success"><i class="fa fa-check"></i> <?php echo _l('home_latest_finished_todos'); ?></h4>
   <ul class="list-unstyled todo finished-todos todos-sortable sortable" >
      <?php foreach($todos_finished as $todo_finished){ ?>
         <li>
            <?php echo form_hidden('todo_order',$todo_finished['item_order']); ?>
            <?php echo form_hidden('finished',1); ?>
            <div class="media">
              <div class="media-left no-padding-right">
               <div class="dragger todo-dragger"></div>
               <div class="checkbox checkbox-default todo-checkbox">
                  <input type="checkbox" value="<?php echo $todo_finished['todoid']; ?>" name="todo_id" checked>
                  <label></label>
               </div>
            </div>
            <div class="media-body">
               <p class="todo-description read-more line-throught no-padding-left">
                  <?php echo $todo_finished['description']; ?>
               </p>
                 <a href="#" onclick="delete_todo_item(this,<?php echo $todo_finished['todoid']; ?>); return false;" class="pull-right text-muted"><i class="fa fa-remove"></i></a>
               <a href="#" onclick="edit_todo_item(<?php echo $todo_finished['todoid']; ?>); return false;" class="pull-right text-muted mright5">
                  <i class="fa fa-pencil"></i>
               </a>
               <small class="todo-date todo-date-finished"><?php echo $todo_finished['datefinished']; ?></small>
            </div>
         </div>
      </li>
   <?php } ?>
   <li class="padding no-todos ui-state-disabled <?php if($total_finished_todos > 0){echo 'hide';} ?>"><?php echo _l('home_no_finished_todos_found'); ?></li>
</ul>
</div>
</div>
<?php $this->load->view('admin/todos/_todo.php'); ?>
</div>
