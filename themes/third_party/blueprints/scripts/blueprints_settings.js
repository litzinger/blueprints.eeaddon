/* Not nearly as clean as blueprints.js, but it's legacy, and works. If it ain't broke, don't fix it */

var fixHelper = function(e, ui) {
    ui.children().each(function() {
        $(this).width($(this).width());
        $(this).height($(this).height());
    });
    return ui;
};

$(function(){

$("div.settings_sortable table").sortable({
    axis: "y",
    placeholder: "ui-state-highlight",
    distance: 5,
    forcePlaceholderSize: true,
    items: "tr",
    helper: fixHelper,
    handle: ".blueprints-handle",
    start: function (event, ui) {
        ui.placeholder.html("<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>");
    }
});

// Hide delete if there is only 1 row
$('#blueprint_settings tbody').each(function(){
    if($(this).children('tr').length == 1) {
        $(this).find('.blueprint_remove_row').hide();
    }
});

$('.blueprint_add_row').live('click', function(e){

    var regex = /(\[\d+\]|\[new_\d+\])/g;
    var regex_thumbnails = /((thumbnail_preview_)\d+|(thumbnail_trigger_)\d+|(thumbnail_value_)\d+|(thumbnail_remove_)\d+)/g;

    var rel = $(this).attr('rel');
    var table = $('.'+ rel +' .mainTable tbody:eq(0)');
    var tr = table.find('tr.row:last-child').clone(true);
    var row = tr.html();
    var index = table.find('tr.row').length;

    // Remove the index from the cloned row so it gets saved with a new index
    row = row.replace(regex_thumbnails, function(match, g1, g2, g3, g4, g5){
        var str = '';
        if(g2) return g2 + 'new_'+ index;
        if(g3) return g3 + 'new_'+ index;
        if(g4) return g4 + 'new_'+ index;
        if(g5) return g5 + 'new_'+ index;
    });

    row = row.replace(regex, '[new_'+ index +']');

    table.append('<tr id="'+ rel + index +'" class="row">'+ row +'</tr>');

    /* Remove all selections from the duplicated select */
    $('#'+ rel + index).find('select').val('');

    /* Remove values from text fields */
    $('#'+ rel + index).find('input[type="text"]').attr('value', '');

    /* Set the new group ID */
    var max_group_id = parseInt($('#max_group_id').val()) + 1;
    $('#max_group_id').val(max_group_id);
    $('#'+ rel + index).find('input[type="hidden"]').val(max_group_id);
    
    /* Reset all checkboxes */
    $('#'+ rel + index).find('.show_group, .show_selected').attr('disabled', false).attr('checked', false);
    
    /* Hide the select */
    $('#'+ rel + index).find('.show_select').hide();

    /* Remove thumbnail images */
    $('#thumbnail_preview_new_'+ index).html('');
    $('#thumbnail_trigger_new_'+ index).text('Select Image');
    $('#thumbnail_remove_new_'+ index).hide();

    /* bind the file manager to the new rows we're adding */
    if(Blueprints.config.is_assets_installed == "yes")
    {
        $('#thumbnail_trigger_new_'+ index).click(function(e){
            var sheet = new Assets.Sheet({
                filedirs: "all",
                kinds: "any",
                onSelect: function(file) { 
                    blueprints_set_thumbnail(file[0], 'new_'+ index);
                }
            });

            e.preventDefault();
            sheet.show();
        });
    }
    else
    {
        $.ee_filebrowser.add_trigger('#thumbnail_trigger_new_'+ index, '#thumbnail_preview_new_'+ index, {
            content_type: 'img',
            directory:    'all'
        }, function(file, field){
            blueprints_set_thumbnail(file, 'new_'+ index);
        });
    }

    blueprints_set_row_events(rel);
    
    e.preventDefault();
});

$('.thumbnail_remove').live('click', function(e){
    parent = $(this).closest('td');
    parent.find('.thumbnail_preview').html('');
    parent.find('input').val('');
    parent.find('.thumbnail_trigger').text("Select Image");
    $(this).hide();
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
            link.closest('tr.row').remove();
        
            /* Add the Add link back ;) */
            if($('.publish_layouts tbody tr.row').length <= Blueprints.config.blueprints_total_templates){
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
    field = $(this).closest('tr.row').find('.layout_group_name');

    if(field.val() == "")
    {
        $(this).closest('tr.row').remove();
        
        /* Add the Add link back ;) */
        if($('.publish_layouts tbody tr.row').length <= Blueprints.config.blueprints_total_templates){
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
        if($('.publish_layouts tbody tr.row').length <= Blueprints.config.blueprints_total_templates){
            $('.publish_layouts + .blueprint_add_row').show();
        }
    }
    else if(rel == 'channel_template_selection')
    {
        $(this).closest('tr.row').remove();
        
        /* Add the Add link back ;) */
        if($('.channel_template_selection tbody tr.row').length <= Blueprints.config.blueprints_total_channels){
            $('.channel_template_selection + .blueprint_add_row').show();
        }
    }
    
    // Hide delete link if 1 row is present
    if( $('.'+ rel +' tbody tr.row').length == 1 ) {
        $('.'+ rel +' tbody tr.row').find('.blueprint_remove_row').hide();
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

    if(rel == 'channel_template_selection'){
        if($('.channel_template_selection tbody tr').length >= Blueprints.config.blueprints_total_channels){
            $('.channel_template_selection + .blueprint_add_row').hide();
        } 
    } else if(rel == 'publish_layouts') {
        if($('.publish_layouts tbody tr.row').length >= Blueprints.config.blueprints_total_templates){
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

$('#enable_detailed_template').change(function(){
    blueprints_toggle_enabled_detailed_template();
});

// Turn off detailed template display
$('#enable_detailed_template').next('.pt-switch').click(function(){
    blueprints_toggle_enabled_detailed_template();
});

function blueprints_toggle_enabled_detailed_template()
{
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
}

});

function blueprints_set_thumbnail(file, field)
{
    upload_paths = Blueprints.config.upload_prefs;

    // We have an Assets file...
    if (file.id && file.url)
    {
        var url = null,
            thumbnail = null,
            file_parts = [],
            file_name = null,
            image_url = null,
            directory = null;

        // Add the path to the input.
        if (file.directory && file.name) {
            url = file.url;
            file_parts = url.split('/');
            file_name = file_parts.slice(-1)[0];
            thumbnail = url.replace(file_name, '_thumbs/'+file_name);

        // It's an Assets file from S3 or another external source.
        } else if (file.url) {
            url = file.url;
            thumbnail = url;

        }

        for(dir_id in upload_paths)
        {
            path = upload_paths[dir_id];
            if(file.url.indexOf(path.url) !== -1)
            {
                directory = dir_id;
                image_url = url.replace(path.url, '{filedir_'+ dir_id +'}', url);
                break;
            }
        }

        if ( !image_url) {
            image_url = url;
        }

        $("#thumbnail_preview_"+ field).html('<img src="'+ thumbnail +'" width="75" />');
        $("#thumbnail_value_"+ field).val(image_url);
    }
    else
    {
        // EE 2.2 changed the file object property names
        if(Blueprints.config.ee_version > 220)
        {
            file.directory = file.upload_location_id;
            file.name = file.file_name;
        }

        if(file.is_image)
        {
            $("#thumbnail_preview_"+ field).html('<img src="'+ file.thumb +'" width="75" />');
            $("#thumbnail_value_"+ field).val('{filedir_'+ file.directory +'}' + file.name);
        }
    }

    var trigger_link = $("#thumbnail_trigger_"+ field);
    var remove_link = $("#thumbnail_remove_"+ field);

    trigger_link.text("Change Image");

    if(remove_link.length == 1) {
        remove_link.show();        
    } else {
        trigger_link.after('<br /><a class="thumbnail_remove" href="#" id="thumbnail_remove_'+ field +'">Remove Image</a>');
    }
}