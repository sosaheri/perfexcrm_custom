<?php defined('BASEPATH') or exit('No direct script access allowed');?>




        <div class='rg-container'>
            <table id="custom_summary"  class='rg-table'>
                        <thead>
                            <tr>
                                <th class='title_custom_summary'><?php echo _l('Title'); ?></th>
                                <th class='title_custom_summary'><?php echo _l('Status'); ?></th>
                                <th class='title_custom_summary'><?php echo _l('Distruibuiton_POD'); ?></th>
                                <th class='title_custom_summary'><?php echo _l('PVP'); ?></th>
                            </tr>
                        </thead>
                    <tbody>               
                        
                    
                    <?php 
                        foreach ($projects as $project) {
                    ?>

                            
                        <tr class=''>

                            <td class='text'  data-title='<?php echo _l('Title'); ?>'>
                                <a href="<?php echo site_url('clients/project/' . $project['id']); ?>">
                                    <?php echo $project['name']; ?>
                                </a>
                            </td>

                            <td class='text ' data-title='<?php echo _l('Status'); ?>'>
                                <?php
                                        $status = get_project_status_by_id($project['status']);
                                        echo $status['name'];
                                ?>

                            </td>
                            <td class='text ' data-title='<?php echo _l('Distruibuiton_POD'); ?>'>
                                 <?php 
                                    echo get_custom_field_value( $project['id'] , 44, 'projects' , $format = true)  ;

                                ?>
                            </td>
                            <td class='text ' data-title='<?php echo _l('PVP'); ?>'>
                                 <?php 
                                    echo get_custom_field_value($project['id'], 19, 'projects', $format = true);

                                ?>
                            </td>
                        </tr>
              
                    <?php }?>






                     
                    </tbody>
            </table>
        </div>

		   


