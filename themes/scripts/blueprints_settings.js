/* Not nearly as clean as blueprints.js, but it's legacy, and works. If it ain't broke, don't fix it */

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
            if($('.publish_layouts tbody tr').length <= Blueprints.config.blueprints_total_templates){
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
        if($('.publish_layouts tbody tr').length <= Blueprints.config.blueprints_total_templates){
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
        if($('.publish_layouts tbody tr').length <= Blueprints.config.blueprints_total_templates){
            $('.publish_layouts + .blueprint_add_row').show();
        }
    }
    else if(rel == 'channel_template_selection')
    {
        $(this).closest('tr').remove();
        
        /* Add the Add link back ;) */
        if($('.channel_template_selection tbody tr').length <= Blueprints.config.blueprints_total_channels){
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
        if($('.channel_template_selection tbody tr').length >= Blueprints.config.blueprints_total_channels){
            $('.channel_template_selection + .blueprint_add_row').hide();
        } 
    } else if(rel == 'publish_layouts') {
        if($('.publish_layouts tbody tr').length >= Blueprints.config.blueprints_total_templates){
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





// var insert_file = function(file, return_to, field_name, return_path_only)
// {
//     // Yeah, thats right, eval!
//     // I could/should redo this as JSON, but this works...
//     upload_paths = eval(wyvern_config.upload_paths);
// 
//     // EE 2.2 changed the file object property names
//     if(wyvern_config.ee_version > 220)
//     {
//         file.directory = file.upload_location_id;
//         file.name = file.file_name;
//     }
// 
//     for(i = 0; i < upload_paths.length; i++)
//     {
//         path = upload_paths[i];
// 
//         if(file.directory == path.directory)
//         {
//             break;
//         }
//     }
//     
//     // 2.2+ removed the dimensions? Add them if present.
//     dimensions = file.dimensions ? file.dimensions : '';
//     
//     // Place the image in the editing window
//     if(file.is_image && ! return_path_only)
//     {
//         var image = '<img src="'+ path.url + file.name +'" '+ dimensions +' />';
//         // return_to being the editor object
//         return_to.insertHtml(image);
//     }
//     // File isn't an image, and return_path isn't true, then embed it as a link
//     else if( ! file.is_image && ! return_path_only)
//     {
//         var link = '<a href="{filedir_'+ path.directory +'}'+ file.name +'" title="">'+ file.name +'</a>';
//         // return_to being the editor object
//         return_to.insertHtml(link);
//     }
//     // Otherwise we want to only return the path value to a form field
//     else if(return_path_only && typeof return_path_only != 'function')
//     {
//         // return_to being a jQuery element
//         $(return_to).val(path.url + file.name);
// 
//         // If the return_path_only var happens to be a valid jQuery object, insert the image.
//         // Right now this is expecting it to be an <img> tag
//         if(return_path_only.jquery)
//         {
//             return_path_only.show().attr('src', path.url + file.name);
//         }
//     }
//     // We have a callback
//     else if(typeof return_path_only == 'function')
//     {
//         return_path_only(file);
//     }
// }
// 
// /*
//     Assets returns a slightly different object needed for insert_file above
// */
// var get_file_object = function(file)
// {
//     // See if it's an image
//     var file_types = ['png', 'jpg', 'gif', 'svg'];
// 
//     extension = file.url.substr(-3);
//     is_image = ($.inArray(extension, file_types) != -1) ? true : false;
//     
//     directory = file.path.match(/\{filedir_(\d+)\}/);
//     file_name = file.path.replace(/\{filedir_(\d+)\}/, '');
//     
//     updated = {
//         path: file.path, 
//         url: file.url,
//         is_image: is_image,
//         file_name: file_name,
//         upload_location_id: directory[1],
//         dimensions: file.dimensions ? file.dimensions : ''
//     };
//     
//     return updated;
// }
// 
// /*
//     field_name and ee_field_id are usually going to be the same, except when field_name
//     is a jQuery element, not the field_name string. ee_field_id is used for the upload_prefs.
// */
// 
// var bind_file_manager = function(button, return_to, field_name, ee_field_id, return_path_only)
// {
//     var field_settings = wyvern_config[ee_field_id].upload_prefs;
//     
//     if(wyvern_config.file_manager == 'assets')
//     {
//         $(button).click(function(){
//             var sheet = new Assets.Sheet({
//                 filedirs: field_settings.directory,
//                 kinds: field_settings.content_type,
//                 onSelect: function(files) 
//                 {
//                     for(i = 0; i < files.length; i++)
//                     {
//                         file = get_file_object(files[i]);
//                         insert_file(file, return_to, field_name, return_path_only);
//                     }
//                 }
//             });
// 
//             sheet.show();
//         });
//     }
//     else
//     {
//         // TODO: Remove this fallback message if the FB ever gets global abilities, not just the Publish page.
//         if(!wyvern_config.valid_filemanager && !wyvern_config.error_displayed)
//         {
//             $.ee_notice('Sorry, but the File Manager is not available on this page. Wyvern can not load the File Manager.', 'notice');
//             wyvern_config.error_displayed = true;
//         } 
//         else if(wyvern_config.valid_filemanager)
//         {
//             $(button).each(function()
//             {
//                 if(wyvern_config.ee_version < 220)
//                 {
//                     $.ee_filebrowser.add_trigger($(this), field_name, function(file){
//                         insert_file(file, return_to, field_name, return_path_only);
//                     });
//                 }
//                 else
//                 {
//                     var settings = {
//                         directory: field_settings.directory,
//                         content_type: field_settings.content_type
//                     };
// 
//                     $.ee_filebrowser.add_trigger($(this), field_name, settings, function(file){
//                         insert_file(file, return_to, field_name, return_path_only);
//                     });
//                 }
//             });
//         }
//     }
// }