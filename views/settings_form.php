<style type="text/css">
#blueprint_settings tbody tr:first-child .blueprint_remove_row { display: none; }
.blueprint_add_row { float: right; font-weight: bold; display: inline-block; padding: 5px 12px }
.blueprint_remove_row { float: right; }
#remove_dialog { display: none; }
.radios { margin: 1em; padding-left: 100px }
.radios label { margin: 0.5em 0; display: block;}
.layout_group_name.disabled { background-color: #e2e2e2; }
.channel_template_selection tbody tr .template_display_detail { display: none; }
.channel_template_selection tbody tr:first-child .template_display_detail { display: block; }
option.disabled { color: #999; }
</style>

<?php if ($structure_installed OR $pages_installed): ?> 
    
    <div id="remove_dialog">
        <p>You are about to delete this Publish Layout. Would you like to remove it from all existing entries as well?</p>
        <p class="radios">
            <label><input type="radio" class="burn_baby_burn" name="burn_baby_burn" value="n" checked="checked" /> No</label>
            <label><input type="radio" class="burn_baby_burn" name="burn_baby_burn" value="y" /> Yes</label>
        </p>
    </div>

    <?php echo form_open('C=addons_extensions'.AMP.'M=save_extension_settings', 'id="blueprint_settings"', $hidden)?>
    
    <?php
    // Enable Edit menu tweak in Accessory?
    $this->table->set_template($cp_table_template);
    $this->table->set_heading(
        array('data' => lang('enable_edit_menu_tweaks'), 'style' => 'width: 80%;', 'colspan' => '2')
    );
    $this->table->add_row(
        array('data' => '<p>'. lang('enable_edit_menu_tweaks_detail') .'</p>', 'style' => 'width: 80%'),
        array('data' => form_dropdown('enable_edit_menu_tweaks', array('n' => 'No', 'y' => 'Yes'), $enable_edit_menu_tweaks, 'id=enable_edit_menu_tweaks'), 'style' => 'width: 20%')
    );

    echo $this->table->generate();
    $this->table->clear();
    
    
    // Enable hi-jacking
    $this->table->set_template($cp_table_template);
    $this->table->set_heading(
        array('data' => lang('enable_publish_layout_takeover'), 'colspan' => '2')
    );
    $this->table->add_row(
        array('data' => '<p>'. lang('enable_publish_layout_takeover_detail') .'</p>', 'style' => 'width: 80%'),
        array('data' => form_dropdown('enable_publish_layout_takeover', array('n' => 'No', 'y' => 'Yes'), $enable_publish_layout_takeover, 'id=enable_publish_layout_takeover'), 'style' => 'width: 20%')
    );

    echo $this->table->generate();
    $this->table->clear();
    
    // Thumbnail Path
    $this->table->set_template($cp_table_template);
    $this->table->set_heading(
        array('data' => lang('thumbnail_path'), 'colspan' => '2')
    );
    $this->table->add_row(
        array('data' => '<p>'. lang('thumbnail_path_detail') .'</p>', 'style' => 'width: 50%'),
        array('data' => form_input('thumbnail_path', $thumbnail_path, 'class="thumbnail_path"') . '<p><small>Current path: '. $site_path.$thumbnail_path .'</small></p>', 'style' => 'width: 50%')
    );

    echo $this->table->generate();
    $this->table->clear();
    
    // Layouts
    echo '<div class="publish_layouts">';
    $this->table->set_template($cp_table_template);
    $this->table->set_heading(
        array('data' => lang('blueprint_layout_heading'), 'style' => 'width:25%;'),
        array('data' => lang('blueprint_template_heading'), 'style' => 'width:30%;'),
        array('data' => lang('blueprint_thumbnail_heading'),'style' => 'width:45%;')
    );
    
    foreach($fields as $field)
    {
        $this->table->add_row(
            form_hidden($field['layout_group_id'], $field['layout_group_id_value']) .
            form_input($field['layout_group_name'], $field['layout_group_name_value'], 'class="layout_group_name"'),
            form_dropdown($field['tmpl_name'], $field['tmpl_options'], $field['tmpl_options_selected'], 'id="'.$field['tmpl_name'].'" class="template_name"'),
            form_dropdown($field['thb_name'], $field['thb_options'], $field['thb_options_selected'], 'id="'.$field['thb_name'].'"') . '<a href="#" class="blueprint_remove_row" rel="publish_layouts" data="'. $field['layout_group_id_value'] .'">Delete</a>'
        );
    }

    echo $this->table->generate();
    echo '</div>';
    echo '<a href="#" class="blueprint_add_row" rel="publish_layouts">+ Add</a>';
    $this->table->clear();

    // Template selection
    echo '<div class="channel_template_selection">';
    $this->table->set_template($cp_table_template);
    $this->table->set_heading(
        array('style' => '30%'),
        array('data' => lang('blueprint_channel_heading'), 'style' => 'width:30%;'),
        array('data' => lang('blueprint_template_heading'),'style' => 'width:40%;')
    );

    foreach($channels as $channel)
    {
        $this->table->add_row(
            '<p style="margin-bottom: 12px" class="template_display_detail">'. lang('template_display_detail') .'</p>',
            form_dropdown($channel['channel_name'], $channel['channel_options'], $channel['channel_options_selected'], 'id="'.$channel['channel_name'].'"'),
            '<div class="checkboxes">'. 
                $channel['channel_checkbox_options'] .
                form_multiselect($channel['channel_templates_name'], $channel['channel_templates_options'], $channel['channel_templates_options_selected'], 'id="'.$channel['channel_templates_name'].'" class="show_select" size="10" style="display: none; width: 99%; margin-top: 5px;"') . '<a href="#" class="blueprint_remove_row" rel="channel_template_selection">Delete</a>'. 
            '</div>'    
        );
    }

    echo $this->table->generate();
    echo '</div>';
    echo '<a href="#" class="blueprint_add_row" rel="channel_template_selection">+ Add</a>';
    $this->table->clear();
    ?>

    <script type="text/javascript">
    jQuery(function($){
        
        var blueprints_total_templates = <?php echo count($channel['channel_templates_options']) - 1; // minus 1 b/c of the 'None' option ?>;
        var blueprints_total_channels = <?php echo count($channel['channel_options']) - 1; // minus 1 b/c of the 'None' option ?>;
        var blueprints_selected_templates = [];
    
        $('.blueprint_add_row').live('click', function(e){
            regex = /(\[\d+\])/g; 
            rel = $(this).attr('rel');
            table = $('.'+ rel +' .mainTable tbody');
            tr = table.find('tr:last-child').clone(true);
            row = tr.html();
            index = table.find('tr').length;
        
            if(tr.hasClass('even')){
                cssclass = 'odd';
            } else {
                cssclass = 'even';
            }
            
            // Old way, way over thinking this, caused some errors.
            // row = row.replace(regex, '['+ index +']');
            // Remove the index from the cloned row so it gets saved with a new index
            row = row.replace(regex, '[]');
            table.append('<tr id="'+ rel + index +'" class="'+ cssclass +'">'+ row +'</tr>');
        
            /* Remove all selections from the duplicated select */
            $('#'+ rel + index).find('select').val('');

            /* Remove values from text fields */
            $('#'+ rel + index).find('input').val('');

            /* Set ID value */
            if(parseInt(index - 1) == 0) {
                var prev_id = parseInt($('.'+ rel).find('table tbody tr:eq(0) input[type="hidden"]').val());
            } else {
                var prev_id = parseInt($('.'+ rel).find('table tbody tr:eq('+ parseInt(index - 1) +')').find('input[type="hidden"]').val());
            }
            $('#'+ rel + index).find('input[type="hidden"]').val(parseInt(prev_id + 1));
            
            /* Reset all checkboxes */
            $('#'+ rel + index).find('.show_group, .show_selected').attr('disabled', false).attr('checked', false);
            
            /* Hide the select */
            $('#'+ rel + index).find('.show_select').hide();
            
            blueprints_set_row_events(rel);
            
            e.preventDefault();
        });
        
        blueprints_dialog = $('#remove_dialog').dialog({
            width: 300,
            resizable: false,
            modal: true,
            autoOpen: false,
            title: 'Confirm Delete?',
            position: ['center', 100],
            buttons: {
                Cancel: function() {
                    blueprints_dialog.dialog('close');
                },
                "Delete Layout": function() {
                    id = $.data(document.body, 'delete_id');
                    link = $.data(document.body, 'delete_link');

                    if( $('#remove_dialog input[name=burn_baby_burn]:checked').val() == 'y')
                    {
                        // Add ID to delete
                        $('#blueprint_settings').append('<input type="hidden" name="delete[]" value="'+ id +'" />');
                    }
                    
                    // Remove row and close dialog
                    blueprints_dialog.dialog('close');
                    link.closest('tr').remove();
                
                    /* Add the Add link back ;) */
                    if($('.publish_layouts tbody tr').length <= blueprints_total_templates){
                        $('.publish_layouts + .blueprint_add_row').show();
                    }
                    
                    // Unset our data
                    $.data(document.body, 'delete_id', false);
                    $.data(document.body, 'delete_link', false);
                }
            }
        });
        

        $('.blueprint_remove_row').live('click', function(e){
            
            rel = $(this).attr('rel');
            field = $(this).closest('tr').find('.layout_group_name');

            if(field.val() == "")
            {
                $(this).closest('tr').remove();
            }
            else if(rel == 'publish_layouts')
            {
                // Set the ID and retrieve it when Yes is clicked
                id = $(this).attr('data');
                $.data(document.body, 'delete_id', id);
                $.data(document.body, 'delete_link', $(this));
            
                blueprints_dialog.dialog('open');
            }
            else if(rel == 'channel_template_selection')
            {
                $(this).closest('tr').remove();
                
                /* Add the Add link back ;) */
                if($('.channel_template_selection tbody tr').length <= blueprints_total_channels){
                    $('.channel_template_selection + .blueprint_add_row').show();
                }
            }
            
            e.preventDefault();
        });

        $('#enable_publish_layout_takeover').change(function(){
            blueprints_enable_publish_layout_takeover($(this));
        });
        
        // blueprints_disable_template_options($('.template_name'));
        blueprints_enable_publish_layout_takeover($('#enable_publish_layout_takeover'));
        blueprints_set_row_events('channel_template_selection');
        blueprints_set_row_events('publish_layouts');
        
        // function blueprints_disable_template_options(ele)
        // {
        //     $(ele).find('option').attr('disabled', false);
        //     
        //     var selected = Array();
        //     $(ele).find('option:selected').each(function(){
        //         selected.push($(this).val());
        //     });
        // 
        //     $(ele).find('option').each(function(){
        //         if($.inArray($(this).val(), selected) != -1)
        //         {
        //             $(this).addClass('disabled');
        //         }
        //     });
        //     console.log(selected);
        // }
        
        function blueprints_set_row_events(rel)
        {
            /* Remove Add link if we have no more channels to add settings for */
            if(rel == 'channel_template_selection'){
                if($('.channel_template_selection tbody tr').length >= blueprints_total_channels){
                    $('.channel_template_selection + .blueprint_add_row').hide();
                } 
            } else if(rel == 'publish_layouts') {
                if($('.publish_layouts tbody tr').length >= blueprints_total_templates){
                    $('.publish_layouts + .blueprint_add_row').hide();
                }
            }
            
        }
    
        function blueprints_enable_publish_layout_takeover(ele)
        {
            var val = ele.val();
            if(val == 'n'){
                $('#blueprint_settings .layout_group_name').val('').attr('disabled', true).addClass('disabled');
            } else {
                $('#blueprint_settings .layout_group_name').attr('disabled', false).removeClass('disabled');
            }
        }
        
        function blueprints_show_group(parent, on_load)
        {
            if($(parent).find("input[name*=\'channel_show_group\']").is(":checked")){
                $(parent).find(".show_selected").attr("checked", false).attr("disabled", true);
            } else if(!on_load) {
                $(parent).find(".show_selected").attr("disabled", false);
            }
        }
        
        function blueprints_show_selected(parent, on_load)
        {
            if($(parent).find(".show_selected").is(":checked")){
                $(parent).find(".show_group").attr("checked", false).attr("disabled", true);
                $(parent).find(".show_select").show();
            } else if(!on_load) {
                $(parent).find(".show_group").attr("disabled", false);
                $(parent).find(".show_select").hide();
                $(parent).find(".show_select option").attr("selected", false);
            }
        }
        
        // Click events for checkboxes
        $(".show_group").live('click', function(){
            blueprints_show_group( $(this).closest(".checkboxes"), false );
        });
        $(".show_selected").live('click', function(){
            blueprints_show_selected( $(this).closest(".checkboxes"), false );
        });
        
        // On load, set checkboxes
        $('.channel_template_selection tr').each(function(){
            blueprints_show_group( $(this), true );
            blueprints_show_selected( $(this), true );
        });
    
    });
    </script>

    <p class="centerSubmit"><?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?></p>

    <?php echo form_close(); ?>
    
<?php else: ?>
    
    <p>The Structure or Pages module must be installed first.</p>

<?php endif; ?>