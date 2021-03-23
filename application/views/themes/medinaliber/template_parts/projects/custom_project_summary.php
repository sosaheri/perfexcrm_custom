<?php defined('BASEPATH') or exit('No direct script access allowed');?>




        <div class='rg-container'>
            <table id="custom_summary"  class='rg-table'>
                        <thead>
                            <tr>
                                <th class='title_custom_summary'>Title</th>
                                <th class='title_custom_summary'>Status</th>
                                <th class='title_custom_summary'>Distruibuiton POD</th>
                                <th class='title_custom_summary'>PVP</th>
                            </tr>
                        </thead>
                    <tbody>               
                        
                    
                    <?php 
                        foreach ($projects as $project) {
                    ?>

                            
                        <tr class=''>

                            <td class='text'  data-title='Title'>
                                <a href="<?php echo site_url('clients/project/' . $project['id']); ?>">
                                    <?php echo $project['name']; ?>
                                </a>
                            </td>

                            <td class='text ' data-title='Status'>
                                <?php
                                        $status = get_project_status_by_id($project['status']);
                                        echo $status['name'];
                                ?>

                            </td>
                            <td class='text ' data-title='Distruibuiton POD'>
                                 <?php 
                                    // echo var_dump ( get_custom_fields( 'projects' , $where = [], $exclude_only_admin = false) );
                                    // echo get_custom_field_value( 1 , 'projects_distribuition' , 'projects', false )  ;
                                    echo get_custom_field_value( $project['id'] , 1, 'projects' , $format = true)  ;
                                    // echo var_dump (render_custom_fields( 'projects' , 1 , $where = [], $items_cf_params = []) )

                                ?>
                            </td>
                            <td class='text ' data-title='PVP'>
                                 <?php 
                                    echo  $project['project_cost']; 
                                ?>
                            </td>
                        </tr>
              
                    <?php }?>






                     
                    </tbody>
            </table>
        </div>

		   


