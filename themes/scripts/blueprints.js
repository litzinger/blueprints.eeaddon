if (typeof window.Blueprints == 'undefined') window.Blueprints = {};

Blueprints.carousel = function(template_id)
{
    var structure_field = $('#hold_field_structure__template_id');
    var pages_field = $('#hold_field_pages__pages_template_id');
    var old_template_id = template_id;
    
    // Make sure either of these divs are visible first
    if((structure_field.length > 0 && structure_field.is(':visible')) || (pages_field.length > 0 && pages_field.is(':visible'))){

        // Find the template to select/start on
        if(Blueprints.config.layout_preview != "NULL" && Blueprints.config.layout_preview != "") {
            start_template = $("#blueprints_carousel").find("li[data-layout='" + Blueprints.config.layout_preview +"']");
            old_template_id = start_template.attr("data-id");
        } else {
            start_template = $("#blueprints_carousel").find("li[data-id='" + template_id +"']");
        }

        jQuery("#blueprints_carousel").show().jcarousel({
            size: carousel.length,
            start: start_template.index()
        });

        // sets the layout_change input value so the correct layout group is saved
        Blueprints.carousel_change(old_template_id);

        // On load set the active template
        start_template.addClass('current');

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
            layout_preview = Blueprints.carousel_change(id);

            // Make it visually active
            item.siblings().removeClass('active');
            item.addClass('active');

            item.siblings().find('.submit').remove();
            item.siblings().find('.overlay').remove();
            item.siblings().find('.ajax_loader').remove();

            // Add zee button, but only if the choosen template is assigned to a Publish Layout
            if(old_template_id != id && id in Blueprints.config.layouts)
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
                    submit_button.after('<img class="ajax_loader" src="'+ Blueprints.config.theme_url +'blueprints/images/ajax_loader.gif" />');
                    submit_button.remove();
                    Blueprints.autosave(layout_preview); 
                });
            }
        });
    }
}
    
Blueprints.autosave = function(layout_preview)
{
    post_data = $("#publishForm").serialize();

    $.ajax({
        type: "POST",
        dataType: "json",
        url: EE.BASE + "&C=content_publish&M=autosave",
        data: post_data,
        success: function (data, status, xhr) {
            setTimeout({
                run: function() 
                {
                    entry_id = $("#publishForm input[name=entry_id]").val();
                    channel_id = $("#publishForm input[name=channel_id]").val();

                    Blueprints.autosave_redirect(data.autosave_entry_id, layout_preview);
                }
            }.run, 500);
        }
    });
}

Blueprints.autosave_redirect = function(autosave_entry_id, layout_preview)
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
    // console.log(href);
    window.location.href = href;
}

Blueprints.is_array = function(input){ return typeof(input)=="object"&&(input instanceof Array); }

/*
    Normal Structure or Pages select menu, pre 1.4 version or when Carousel is turned off.
*/
Blueprints.select_change = function(ele)
{
    var template = $(ele).find("option:selected").val();
    thumbnail = Blueprints.config.thumbnail_path + Blueprints.config.thumbnails[template];
    if(Blueprints.config.thumbnails[template] != "" && Blueprints.config.thumbnails[template] != undefined) {
        $("#template_thumbnail").show().html('<img src="'+ thumbnail +'" width="155" />');
    } else {
        $("#template_thumbnail").hide().html('');
    }

    if(Blueprints.config.publish_layout_takeover)
    {
        layout_preview = Blueprints.config.layouts[template];
        
        if(layout_preview != undefined && layout_preview != "") {
            $("#layout_change").html('<input type="hidden" name="old_layout_preview" value="'+ layout_preview +'" /><input type="hidden" name="layout_preview" value="'+ layout_preview +'" />');
            $("#revision_button").clone(true).appendTo( jQuery("#layout_change") );
        } else {
            $("#layout_change").html('<input type="hidden" name="old_layout_preview" value="NULL" /><input type="hidden" name="layout_preview" value="NULL" />');
        }
        
        $("#template_thumbnail").append('<input type="submit" class="submit" name="submit" value="Load Layout" />');
        $("#template_thumbnail .submit").click(function(e){
            e.preventDefault();
            $(this).after('<img class="ajax_loader" src="'+ Blueprints.config.theme_url +'blueprints/images/ajax_loader.gif" />');
            $(this).remove();
            Blueprints.autosave(layout_preview); 
        });
    }
}

/*
    2.0+ version if Carousel option is turned on.
*/
Blueprints.carousel_change = function(template)
{
    if(Blueprints.config.publish_layout_takeover)
    {
        layout_preview = Blueprints.config.layouts[template];
        
        if(layout_preview != undefined && layout_preview != "") {
            $("#layout_change").html('<input type="hidden" name="old_layout_preview" value="'+ layout_preview +'" /><input type="hidden" name="layout_preview" value="'+ layout_preview +'" />');
            $("#blueprints_template_id").val(template);
            return layout_preview;
        } else {
            $("#layout_change").html('<input type="hidden" name="old_layout_preview" value="NULL" /><input type="hidden" name="layout_preview" value="NULL" />');
            return false;
        }
    }
}


/*
    Save the Entries data, then reload the page with the new layout and saved data
*/
jQuery(function(){
    
    var template_select = $("select[name=structure__template_id], select[name=pages__pages_template_id]");
    
    if(Blueprints.config.publish_layout_takeover)
    {
        jQuery(function(){
            $("#showToolbarLink a").toggle(function() {
                if($(".blueprints_layout_groups_holder").length == 0){
                    $("#layout_groups_holder").prepend('<div class="blueprints_layout_groups_holder">'+ Blueprints.config.layout_checkbox_options +'</div>');
                }
                active_layouts = Blueprints.config.active_publish_layouts;
                $("#layout_groups_holder input").each(function(){
                    value = $(this).val();
                    if($.inArray(value, active_layouts) != -1 && active_layouts.length > 0) {
                        $(this).attr("checked", "checked");
                    }
                });
            }, function() {
                $(".active_publish_layout").remove();
            });

            if(Blueprints.config.layout_group == "")
            {
                // $('#showToolbarLink').prepend('<span class="blueprints_no_layout">No Publish Layout defined. Create one now &rarr;</span>');
                $('#showToolbarLink a span').text('No Publish Layout defined. Create one now.');
            }
        });
    }

    // Only create the carousel if turned on
    if(Blueprints.config.enable_carousel == 'y' && Blueprints.config.carousel_options.length > 0)
    {
        carousel = Blueprints.config.carousel_options;
        out = '<ul id="blueprints_carousel" class="jcarousel-skin-blueprints" style="display: none">';

        for(i = 0; i < carousel.length; i++)
        {
            template_thumb = carousel[i].template_thumb;
            template_name = carousel[i].template_name;
            template_id = carousel[i].template_id;
            layout_preview = carousel[i].layout_preview;
            layout_name = carousel[i].layout_name;
        
            thumbnail = template_thumb ? '<div class="carousel_thumbnail" style="background-image: url('+ Blueprints.config.thumbnail_path + template_thumb +')"; />' : '<div class="carousel_thumbnail"></div>';
        
            out = out + '<li data-id="'+ template_id +'" data-layout="'+ layout_preview +'"><span class="carousel_template_name">'+ layout_name +'</span><div class="carousel_thumbnail_wrapper">'+ thumbnail +'</div></li>';
        }
    
        out = out + '</ul><div id="layout_change"></div><div class="clear"></div>';
    
        // Insert our carousel HTML
        template_select.after(out);
        
        // Create our hidden field to send the ID to on click
        select_name = template_select.attr('name');
        select_value = template_select.val();
        template_select.after('<input type="hidden" id="blueprints_template_id" name="'+ select_name +'" value="'+ select_value +'" />');
        
        // Remove the original Structure or Pages template dropdown, we just replaced it.
        template_select.remove();
    
        // Added short timeouts to ensure it initiates correctly.
        
        // On page load...
        setTimeout({
            run: function() {
                Blueprints.carousel(select_value);
            }
        }.run, 250);
        
        
        // When a tab is clicked...
        $('.content_tab a').click(function(){
            setTimeout({
                run: function() {
                    Blueprints.carousel(select_value);
                }
            }.run, 100);
        });
    }
    
    // Old school template select menu
    else
    {
        template_select.after(Blueprints.config.edit_templates_link + '<div class="clear"></div><div id="template_thumbnail"></div><div id="layout_change"></div><div class="clear"></div>');
        template_select.change(function(){
            Blueprints.select_change($(this));
        });

        Blueprints.select_change(template_select);
        var template_select_options = template_select.find("option");
        template_select_options.each(function(i){
            var value = parseInt($(this).val());

            if(!Blueprints.is_array(Blueprints.config.channel_templates)) {
                if(value != Blueprints.config.channel_templates) {
                    $(this).remove();
                }
            } else {
                if($.inArray(value, Blueprints.config.channel_templates) == -1 && Blueprints.config.channel_templates.length > 0) {
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
    }
});