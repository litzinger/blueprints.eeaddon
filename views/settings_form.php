<style type="text/css">
.blueprint_add_row { float: right; font-weight: bold; display: inline-block; padding: 0 12px 12px 12px; margin-top: -5px }
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
    
    <?php if($app_version > 231): ?>
        
        <?php echo form_hidden('enable_edit_menu_tweaks', 'n'); ?>
        
    <?php else: ?>
        
        <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <th colspan="2">
                    <?php echo lang('enable_edit_menu_tweaks'); ?>
                </th>
            </tr>
            <tr>
                <td width="80%">
                    <?php echo lang('enable_edit_menu_tweaks_detail'); ?>
                </td>
                <td width="20%">
                    <?php echo form_dropdown('enable_edit_menu_tweaks', array('n' => 'No', 'y' => 'Yes'), $enable_edit_menu_tweaks, 'id="enable_edit_menu_tweaks"'); ?>
                </td>
            </tr>
        </table>
    
    <?php endif; ?>
    
    <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <th colspan="2">
                <?php echo lang('enable_publish_layout_takeover'); ?>
            </th>
        </tr>
        <tr>
            <td width="80%">
                <?php echo lang('enable_publish_layout_takeover_detail'); ?>
            </td>
            <td width="20%">
                <?php echo form_dropdown('enable_publish_layout_takeover', array('n' => 'No', 'y' => 'Yes'), $enable_publish_layout_takeover, 'id="enable_publish_layout_takeover"'); ?>
            </td>
        </tr>
    </table>
    
    <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <th colspan="2">
                <?php echo lang('enable_carousel'); ?>
            </th>
        </tr>
        <tr>
            <td width="80%">
                <?php echo lang('enable_carousel_detail'); ?>
            </td>
            <td width="20%">
                <?php echo form_dropdown('enable_carousel', array('n' => 'No', 'y' => 'Yes'), $enable_carousel, 'id="enable_carousel"'); ?>
            </td>
        </tr>
    </table>
    
    <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <th colspan="2">
                <?php echo lang('thumbnail_path'); ?>
            </th>
        </tr>
        <tr>
            <td width="50%">
                <?php echo lang('thumbnail_path_detail'); ?>
            </td>
            <td width="50%">
                <?php echo form_input('thumbnail_path', $thumbnail_path, 'class="thumbnail_path"'); ?>
            </td>
        </tr>
    </table>
    
    <input type="hidden" value="<?php echo $max_group_id ?>" id="max_group_id" />
    
    <div class="publish_layouts settings_sortable">
        <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <th>
                    <?php echo lang('blueprint_layout_heading'); ?>
                </th>
                <th>
                    <?php echo lang('blueprint_template_heading'); ?>
                </th>
                <th>
                    <?php echo lang('blueprint_thumbnail_heading'); ?>
                </th>
            </tr>
            <?php foreach($fields as $field): ?>
                <tr id="order_<?php echo $field['row_id'] ?>">
                    <td width="25%">
                        <div class="handle"><img src="<?php echo $theme_folder_url ?>boldminded_themes/images/icon_handle.gif" /></div>
                        <?php echo form_hidden($field['layout_group_id'], $field['layout_group_id_value']); ?>
                        <?php echo form_input($field['layout_group_name'], $field['layout_group_name_value'], 'class="layout_group_name"'); ?>
                    </td>
                    <td width="30%">
                        <?php echo form_dropdown($field['tmpl_name'], $field['tmpl_options'], $field['tmpl_options_selected'], 'id="'.$field['tmpl_name'].'" class="template_name"'); ?>
                    </td>
                    <td width="45%">
                        <?php echo form_dropdown($field['thb_name'], $field['thb_options'], $field['thb_options_selected'], 'id="'.$field['thb_name'].'"'); ?>
                        <a href="#" class="blueprint_remove_row" rel="publish_layouts" data="<?php echo $field['layout_group_id_value'] ?>">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <a href="#" class="blueprint_add_row" rel="publish_layouts">+ Add</a>

    <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <th colspan="2">
                <?php echo lang('enable_detailed_template'); ?>
            </th>
        </tr>
        <tr>
            <td width="80%">
                <?php echo lang('enable_detailed_template_detail'); ?>
            </td>
            <td width="20%">
                <?php echo form_dropdown('enable_detailed_template', array('n' => 'No', 'y' => 'Yes'), $enable_detailed_template, 'id="enable_detailed_template"'); ?>
            </td>
        </tr>
    </table>
        
    <div class="channel_template_selection"<?php echo (($enable_detailed_template != 'y') ? 'style="display: none;"' : "") ?>>
        <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>
                        <?php echo lang('blueprint_channel_heading'); ?>
                    </th>
                    <th>
                        <?php echo lang('blueprint_template_heading'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($channels as $channel): ?>
                    <tr>
                        <td width="25%">
                            <p style="margin-bottom: 12px" class="template_display_detail"><?php echo lang('template_display_detail') ?></p>
                        </td>
                        <td width="30%">
                            <?php echo form_dropdown($channel['channel_name'], $channel['channel_options'], $channel['channel_options_selected'], 'id="'.$channel['channel_name'].'"'); ?>
                        </td>
                        <td width="45%">
                            <div class="checkboxes">
                                <?php echo $channel['channel_checkbox_options'] ?>
                                <?php echo form_multiselect($channel['channel_templates_name'], $channel['channel_templates_options'], $channel['channel_templates_options_selected'], 'id="'.$channel['channel_templates_name'].'" class="show_select" size="10" style="display: none; width: 99%; margin-top: 5px;"') ?>
                                <a href="#" class="blueprint_remove_row" rel="channel_template_selection">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <a href="#" class="blueprint_add_row" rel="channel_template_selection"<?php echo (($enable_detailed_template != 'y') ? 'style="display: none;"' : "") ?>>+ Add</a>
    
    <script type="text/javascript">
    jQuery(function($){
        
        var fixHelper = function(e, ui) {
            ui.children().each(function() {
                $(this).width($(this).width());
                $(this).height($(this).height());
            });
            return ui;
        };

        $("div.settings_sortable table").sortable({
            axis: "y",
            placeholder: "ui-state-highlight",
            distance: 5,
            forcePlaceholderSize: true,
            items: "tr",
            helper: fixHelper,
            handle: ".handle",
            start: function (event, ui) {
                ui.placeholder.html("<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>");
            }
        });
        
        var blueprints_total_templates = <?php echo count($channel['channel_templates_options']) - 1; // minus 1 b/c of the 'None' option ?>;
        var blueprints_total_channels = <?php echo count($channel['channel_options']) - 1; // minus 1 b/c of the 'None' option ?>;
        var blueprints_selected_templates = [];
        
        // Hide delete if there is only 1 row
        $('#blueprint_settings tbody').each(function(){
            if($(this).children('tr').length == 1) {
                $(this).find('.blueprint_remove_row').hide();
            }
        });
    
        $('.blueprint_add_row').live('click', function(e){
            // regex = /(\[\d+\])/g; 
            var regex = /(\[\d+\]|\[new_\d+\])/g;
            var rel = $(this).attr('rel');
            var table = $('.'+ rel +' .mainTable tbody');
            var tr = table.find('tr:last-child').clone(true);
            var row = tr.html();
            var index = table.find('tr').length;
        
            if(tr.hasClass('even')){
                var cssclass = 'odd';
            } else {
                var cssclass = 'even';
            }
            
            // Remove the index from the cloned row so it gets saved with a new index
            row = row.replace(regex, '[new_'+ index +']');
            // row = row.replace(regex, '[]');
            table.append('<tr id="'+ rel + index +'" class="'+ cssclass +'">'+ row +'</tr>');
        
            /* Remove all selections from the duplicated select */
            $('#'+ rel + index).find('select').val('');

            /* Remove values from text fields */
            $('#'+ rel + index).find('input').attr('value', '');

            /* Set the new group ID */
            var max_group_id = parseInt($('#max_group_id').val()) + 1;
            $('#max_group_id').val(max_group_id);
            $('#'+ rel + index).find('input[type="hidden"]').val(max_group_id);
            
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
                
                /* Add the Add link back ;) */
                if($('.publish_layouts tbody tr').length <= blueprints_total_templates){
                    $('.publish_layouts + .blueprint_add_row').show();
                }
            }
            else if(rel == 'publish_layouts')
            {
                // Set the ID and retrieve it when Yes is clicked
                id = $(this).attr('data');
                $.data(document.body, 'delete_id', id);
                $.data(document.body, 'delete_link', $(this));
            
                blueprints_dialog.dialog('open');

                /* Add the Add link back ;) */
                if($('.publish_layouts tbody tr').length <= blueprints_total_templates){
                    $('.publish_layouts + .blueprint_add_row').show();
                }
            }
            else if(rel == 'channel_template_selection')
            {
                $(this).closest('tr').remove();
                
                /* Add the Add link back ;) */
                if($('.channel_template_selection tbody tr').length <= blueprints_total_channels){
                    $('.channel_template_selection + .blueprint_add_row').show();
                }
            }
            
            // Hide delete link if 1 row is present
            if( $('.'+ rel +' tbody tr').length == 1 ) {
                $('.'+ rel +' tbody tr').find('.blueprint_remove_row').hide();
            }
            
            e.preventDefault();
        });

        $('#enable_publish_layout_takeover').change(function(){
            blueprints_enable_publish_layout_takeover($(this));
        });
        
        $('#enable_publish_layout_takeover').next('.pt-switch').click(function(){
            blueprints_enable_publish_layout_takeover($(this).prev('select'));
        });
        
        // blueprints_disable_template_options($('.template_name'));
        blueprints_enable_publish_layout_takeover($('#enable_publish_layout_takeover'));
        blueprints_set_row_events('channel_template_selection');
        blueprints_set_row_events('publish_layouts');
        
        function blueprints_set_row_events(rel)
        {
            // Show delete link if more than 1 row is present
            if( $('.'+ rel +' tbody tr').length > 1 ) {
                $('.'+ rel +' tbody tr').find('.blueprint_remove_row').show();
            }
            
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
                // $('#blueprint_settings .layout_group_name').val('').attr('disabled', true).addClass('disabled');
                $('#blueprint_settings .layout_group_name:text[value=""]').attr('disabled', true).addClass('disabled');
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
        
        // Turn off detailed template display
        $('#enable_detailed_template').next('.pt-switch').click(function(){
            val = $('#enable_detailed_template').val();
            
            if(val == 'y') {
                $('.channel_template_selection').slideDown();
                $('.channel_template_selection').next('.blueprint_add_row').show();
            } else {
                $('.channel_template_selection').slideUp();
                $('.channel_template_selection').find('.show_group, .show_selected').attr("checked", false);
                $('.channel_template_selection').find('select option').attr("selected", false);
                $('.channel_template_selection').next('.blueprint_add_row').hide();
            }
        });
    
    });
    </script>

    <p class="centerSubmit"><?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?></p>

    <?php echo form_close(); ?>
    
<?php else: ?>
    
    <p>The Structure or Pages module must be installed first.</p>

<?php endif; ?>