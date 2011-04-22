var init_carousel = function(template_id)
{
    structure_field = $('#hold_field_structure__template_id');
    pages_field = $('#hold_field_pages__pages_template_id');
    old_template_id = template_id;
    
    autosave_entry_id_field = $("#publishForm input[name=autosave_entry_id]");
    if(blueprints_options.autosave_entry_id && autosave_entry_id_field.val() == 0)
    {
        autosave_entry_id_field.val(blueprints_options.autosave_entry_id);
    }
    
    // Make sure either of these divs are visible first
    if((structure_field.length > 0 && structure_field.is(':visible')) || (pages_field.length > 0 && pages_field.is(':visible'))){
        
        // Find the template to select/start on
        if(blueprints_options.layout_preview) {
            start_template = $("#blueprints_carousel").find("[data-layout='" + blueprints_options.layout_preview +"']");
            old_template_id = start_template.attr("data-id");
        } else {
            start_template = $("#blueprints_carousel").find("[data-id='" + template_id +"']");
        }
        
        jQuery("#blueprints_carousel").show().jcarousel({
            size: carousel.length,
            start: start_template.index()
        });
        
        // sets the layout_change input value so the correct layout group is saved
        blueprint_template_carousel_change(template_id);
        
        // On load set the active template
        start_template.addClass('active');
        
        // On click set active template
        $('.jcarousel-item').click(function(){
            item = $(this);
            
            // Set all siblings as not clicked
            item.siblings().each(function(){
                $(this).data('clicked', false);
            });
            // Make sure the item can only be clicked once, otherwise it will
            // execute this code each time it's clicked. bad.
            if(item.data('clicked')) return;
            item.data('clicked', true);
            
            id = $(this).attr('data-id');
            $('input[name='+ select_name +']').val(id);
            
            // Set layout_change on item click
            layout_preview = blueprint_template_carousel_change(id);
            
            // Make it visually active
            // item.siblings().removeClass('active');
            // item.addClass('active');
            
            item.siblings().find('.submit').remove();
            item.siblings().find('.overlay').remove();
            item.siblings().find('.ajax_loader').remove();
            
            // Add zee button
            if(old_template_id != id)
            {
                item.find('.carousel_thumbnail').append('<input type="submit" class="submit" name="submit" value="Load Layout" />');
                item.find('.carousel_thumbnail').append('<div class="overlay"></div>');
                submit_button = item.find('.submit');
                
                item_width = $('.carousel_thumbnail').width();
                submit_width = submit_button.width();
                submit_button.css('left', ((item_width / 2) - (submit_width / 2)) - 8);
                
                submit_button.click(function(e){
                    e.preventDefault();
                    item.find('.overlay').addClass('loading');
                    submit_button.after('<img class="ajax_loader" src="'+ blueprints_options.theme_url +'blueprints/images/ajax_loader.gif" />');
                    submit_button.remove();
                    init_autosave(layout_preview); 
                });
            }
        });
    }
}

/*
    Save the Entries data, then reload the page with the new layout and saved data
*/
var init_autosave = function(layout_preview)
{
    post_data = $("#publishForm").serialize();
    
    $.ajax({
        type: "POST",
        dataType: "json",
        url: EE.BASE + "&C=content_publish&M=autosave",
        data: post_data,
        success: function (data, status, xhr) {
            setTimeout({
                run: function() {
                    
                    entry_id = $("#publishForm input[name=entry_id]").val();
                    channel_id = $("#publishForm input[name=channel_id]").val();
                    autosave_entry_id = data.autosave_entry_id;
                    new_autosave_entry_id = false;

                    // If no autosave_entry_id exists, go to the table and find it
                    // Or if EE reports the autosave_entry_id incorrectly... I swear this is a bug.
                    if(autosave_entry_id == 0 || autosave_entry_id == entry_id)
                    {
                        $.ajax({
                            type: "POST",
                            dataType: "text",
                            url: blueprints_options.get_autosave_entry_url,
                            data: "entry_id="+ entry_id +"&channel_id="+ channel_id,
                            success: function (autosave_entry_id, status, xhr) {
                                autosave_redirect(autosave_entry_id, layout_preview);
                            }
                        });
                    }
                    else
                    {
                        autosave_redirect(autosave_entry_id, layout_preview);
                    }
                }
            }.run, 500);
        }
    });
}

var autosave_redirect = function(autosave_entry_id, layout_preview)
{
    href = window.location.href;
    
    // Clean up the current URL to make sure the params are set correctly,
    // and we don't have more than 1 of each.
    if(href.indexOf('layout_preview') != -1) {
        href = href.replace(/&layout_preview=(\d+)/, '&layout_preview='+ layout_preview);
    } else {
        href = href +'&layout_preview='+ layout_preview;
    }
    
    if(href.indexOf('use_autosave') != -1) {
        href = href.replace(/&use_autosave=(\w+)/, '&use_autosave=y');
    } else {
        href = href +'&use_autosave=y';
    }
    
    if(href.indexOf('entry_id') != -1) {
        href = href.replace(/&entry_id=(\d+)/, '&entry_id='+ autosave_entry_id);
    } else {
        href = href +'&entry_id='+ autosave_entry_id;
    }
    
    window.location.href = href;
}

jQuery(function(){
    
    var template_select = $("select[name=structure__template_id], select[name=pages__pages_template_id]");

    // Only create the carousel if turned on
    if(blueprints_options.enable_carousel == 'y' && blueprints_options.carousel_options.length > 0)
    {
        carousel = blueprints_options.carousel_options;
        out = '<ul id="blueprints_carousel" class="jcarousel-skin-blueprints" style="display: none">';

        for(i = 0; i < carousel.length; i++)
        {
            template_thumb = carousel[i].template_thumb;
            template_name = carousel[i].template_name;
            template_id = carousel[i].template_id;
            layout_preview = carousel[i].layout_preview;
            layout_name = carousel[i].layout_name;
        
            thumbnail = template_thumb ? '<div class="carousel_thumbnail" style="background-image: url('+ blueprints_options.thumbnail_path + template_thumb +')"; />' : '<div class="carousel_thumbnail"></div>';
        
            out = out + '<li data-id="'+ template_id +'" data-layout="'+ layout_preview +'"><span>'+ layout_name +'</span>'+ thumbnail +'</li>';
        }
    
        out = out + '</ul><div id="layout_change"></div><div class="clear"></div>';
    
        // Insert our carousel
        template_select.after(out);
        
        // Create our hidden field to send the ID to on click
        select_name = template_select.attr('name');
        select_value = template_select.val();
        template_select.after('<input type="hidden" name="'+ select_name +'" value="'+ select_value +'" />');
        
        // Remove the original dropdown
        template_select.remove();
    
        // Added short timeouts to ensure it initiates correctly.
        
        // On page load...
        setTimeout({
            run: function() {
                init_carousel(select_value);
            }
        }.run, 250);
        
        
        // When a tab is clicked...
        $('.content_tab a').click(function(){
            setTimeout({
                run: function() {
                    init_carousel(select_value);
                }
            }.run, 50);
        });
    }
    // Old school template select menu
    else
    {
        template_select.after(blueprints_options.edit_templates_link + '<div class="clear"></div><div id="template_thumbnail"></div><div id="layout_change"></div><div class="clear"></div>');
        template_select.change(function(){
            blueprint_template_select_change($(this));
        });

        blueprint_template_select_change(template_select);
        var template_select_options = template_select.find("option");
        template_select_options.each(function(i){
            var value = parseInt($(this).val());

            if(!is_array(blueprints_options.channel_templates)) {
                if(value != blueprints_options.channel_templates) {
                    $(this).remove();
                }
            } else {
                if($.inArray(value, blueprints_options.channel_templates) == -1 && blueprints_options.channel_templates.length > 0) {
                    $(this).remove();
                }
            }
        });
    
        var template_select_optgroups = template_select.find("optgroup");
        template_select_optgroups.each(function(i){
            if( $(this).children().length == 0 ){
                $(this).remove();
            }
        });
        
        // $("body")
        //     .ajaxStart(function () {
        //         $(this).addClass("loading");
        //     })
        //     .ajaxStop(function () {
        //         $(this).removeClass("loading");
        //     });
    }
});

function is_array(input){ return typeof(input)=="object"&&(input instanceof Array); }

function blueprint_template_select_change(ele)
{
    var template = $(ele).find("option:selected").val();
    thumbnail = blueprints_options.thumbnail_path + blueprints_options.thumbnails[template];
    if(blueprints_options.thumbnails[template] != "" && blueprints_options.thumbnails[template] != undefined) {
        $("#template_thumbnail").show().html('<img src="'+ thumbnail +'" width="125" />');
    } else {
        $("#template_thumbnail").hide().html('');
    }

    if(blueprints_options.publish_layout_takeover)
    {
        if(blueprints_options.layout_groups[template] != undefined && blueprints_options.layout_groups[template] != "") {
            $("#layout_change").html('<input type="hidden" name="layout_preview" value="'+ blueprints_options.layout_groups[template] +'" />');
            $("#revision_button").clone(true).appendTo( jQuery("#layout_change") );
        } else {
            $("#layout_change").html('<input type="hidden" name="layout_preview" value="NULL" />');
        }
    }
    
}

function blueprint_template_carousel_change(template)
{
    if(blueprints_options.publish_layout_takeover)
    {
        if(blueprints_options.layout_groups[template] != undefined && blueprints_options.layout_groups[template] != "") {
            $("#layout_change").html('<input type="hidden" name="layout_preview" value="'+ blueprints_options.layout_groups[template] +'" />');
            return blueprints_options.layout_groups[template];
        } else {
            $("#layout_change").html('<input type="hidden" name="layout_preview" value="NULL" />');
            return false;
        }
    }
}

if(blueprints_options.publish_layout_takeover)
{
    jQuery(function(){
        $("#showToolbarLink a").toggle(function() {
            if($(".blueprints_layout_groups_holder").length == 0){
                $("#layout_groups_holder").prepend('<div class="blueprints_layout_groups_holder">'+ blueprints_options.layout_group_options +'</div>');
            }
            active_layouts = blueprints_options.active_publish_layouts;
            $("#layout_groups_holder input").each(function(){
                value = $(this).val();
                if($.inArray(value, active_layouts) != -1 && active_layouts.length > 0) {
                    $(this).attr("checked", "checked");
                }
            });
        }, function() {
            $(".active_publish_layout").remove();
        });
    });
}