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
            <thead>
                <tr class="odd">
                    <th colspan="2">
                        <?php echo lang('enable_edit_menu_tweaks'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr class="even">
                    <td width="80%">
                        <?php echo lang('enable_edit_menu_tweaks_detail'); ?>
                    </td>
                    <td width="20%">
                        <?php echo form_dropdown('enable_edit_menu_tweaks', array('n' => 'No', 'y' => 'Yes'), $enable_edit_menu_tweaks, 'id="enable_edit_menu_tweaks"'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    
    <?php endif; ?>
    
    <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
        <thead>
            <tr class="odd">
                <th colspan="2">
                    <?php echo lang('enable_publish_layout_takeover'); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr class="even">
                <td width="80%">
                    <?php echo lang('enable_publish_layout_takeover_detail'); ?>
                </td>
                <td width="20%">
                    <?php echo form_dropdown('enable_publish_layout_takeover', array('n' => 'Simple', 'y' => 'Advanced'), $enable_publish_layout_takeover, 'id="enable_publish_layout_takeover"'); ?>
                </td>
            </tr>
        </tbody>
    </table>
    
    <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
        <thead>
            <tr class="odd">
                <th colspan="2">
                    <?php echo lang('enable_carousel'); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr class="even">
                <td width="80%">
                    <?php echo lang('enable_carousel_detail'); ?>
                </td>
                <td width="20%">
                    <?php echo form_dropdown('enable_carousel', array('n' => 'No', 'y' => 'Yes'), $enable_carousel, 'id="enable_carousel"'); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <input type="hidden" value="<?php echo $max_group_id ?>" id="max_group_id" />
    
    <div class="publish_layouts settings_sortable">
        <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
            <thead>
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
            </thead>
            <tbody>
            <?php foreach($fields as $k => $field): ?>
                <tr id="order_<?php echo $field['row_id'] ?>" class="row">
                    <td width="33%">
                        <div class="blueprints-handle"><img src="<?php echo $theme_folder_url ?>boldminded_themes/images/icon_handle.gif" /></div>
                        <?php echo form_hidden($field['layout_group_id'], $field['layout_group_id_value']); ?>
                        <?php echo form_input($field['layout_group_name'], $field['layout_group_name_value'], 'class="layout_group_name"'); ?>
                    </td>
                    <td width="33%">
                        <?php echo form_dropdown($field['tmpl_name'], $field['tmpl_options'], $field['tmpl_options_selected'], 'id="'.$field['tmpl_name'].'" class="template_name"'); ?>
                    </td>
                    <td width="33%">
                        <table width="100%" class="thumbnail_table">
                            <tr>
                                <td width="70%">
                                    <?php 
                                    $thumbnail = (isset($field['thb_options_selected']) AND $field['thb_options_selected'] != '' AND ! is_numeric($field['thb_options_selected'])) ? '<img width="75" src="'. $this->blueprints_helper->swap_upload_pref_token($field['thb_options_selected'], true) .'" />' : '';

                                    $text = $thumbnail != '' ? 'Change Image' : 'Select Image';

                                    echo '<div class="thumbnail_preview" id="thumbnail_preview_'. $k .'">'. $thumbnail .'</div>';
                                    echo '<a class="thumbnail_trigger" href="#" id="thumbnail_trigger_'. $k .'">'. $text .'</a>';
                                    if($thumbnail != '') echo '<br /><a class="thumbnail_remove" href="#" id="thumbnail_remove_'. $k .'">Remove Image</a>';
                                    echo '<input type="hidden" name="'. $field['thb_name'] .'" value="'. $field['thb_options_selected'] .'" id="thumbnail_value_'. $k .'" />';
                                    ?>
                                </td>
                                <td width="30%" valign="middle">
                                    <a href="#" class="blueprint_remove_row" rel="publish_layouts" data="<?php echo $field['layout_group_id_value'] ?>">Delete</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <a href="#" class="blueprint_add_row" rel="publish_layouts">+ Add</a>

    <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th colspan="2">
                    <?php echo lang('enable_detailed_template'); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="80%">
                    <?php echo lang('enable_detailed_template_detail'); ?>
                </td>
                <td width="20%">
                    <?php echo form_dropdown('enable_detailed_template', array('n' => 'No', 'y' => 'Yes'), $enable_detailed_template, 'id="enable_detailed_template"'); ?>
                </td>
            </tr>
        </tbody>
    </table>
        
    <div class="channel_template_selection"<?php echo (($enable_detailed_template != 'y') ? 'style="display: none;"' : "") ?>>
        <table class="mainTable" border="0" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th>
                        <?php echo lang('blueprint_channel_heading'); ?>
                    </th>
                    <th>
                        <?php echo lang('blueprint_template_detailed_heading'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($channels as $channel): ?>
                    <tr class="row">
                        <td width="33%">
                            <?php echo form_dropdown($channel['channel_name'], $channel['channel_options'], $channel['channel_options_selected'], 'id="'.$channel['channel_name'].'"'); ?>
                        </td>
                        <td width="66%">
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

    <p class="centerSubmit"><?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?></p>

    <?php echo form_close(); ?>
    
<?php else: ?>
    
    <p>The Structure or Pages module must be installed first.</p>

<?php endif; ?>