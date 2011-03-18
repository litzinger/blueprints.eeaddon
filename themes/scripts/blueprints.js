var init_carousel = function(template_id)
{
    structure_field = $('#hold_field_structure__template_id');
    pages_field = $('#hold_field_pages__pages_template_id');
    
    // Make sure either of these divs are visible first
    if((structure_field.length > 0 && structure_field.is(':visible')) || (pages_field.length > 0 && pages_field.is(':visible'))){
        
        // Find the template to select/start on
        start_template = $("#blueprints_carousel").find("[data-id='" + template_id +"']");
        
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
            id = $(this).attr('data-id');
            $('input[name='+ select_name +']').val(id);
            
            // Set layout_change on item click
            blueprint_template_carousel_change(id);
            
            $(this).siblings().removeClass('active');
            $(this).addClass('active'); 
            
            init_autosave();
        });
    }
}

var init_autosave = function()
{
    data = $("#publishForm").serialize();
    
    $.ajax({
        type: "POST",
        dataType: "json",
        url: EE.BASE + "&C=content_publish&M=autosave",
        data: data,
        success: function (rdata) {
            // console.log(rdata);
        }
    })
}

jQuery(function(){
    
    var template_select = $("select[name=structure__template_id], select[name=pages__pages_template_id]");

    // Only create the carousel if turned on
    if(blueprints_options.enable_carousel == 'y')
    {
        carousel = blueprints_options.carousel_options;
        out = '<ul id="blueprints_carousel" class="jcarousel-skin-blueprints" style="display: none">';

        for(i = 0; i < carousel.length; i++)
        {
            template_thumb = carousel[i].template_thumb;
            template_name = carousel[i].template_name;
            template_id = carousel[i].template_id;
        
            thumbnail = template_thumb ? '<div class="carousel_thumbnail" style="background-image: url('+ blueprints_options.thumbnail_path + template_thumb +')"; />' : '<div class="carousel_thumbnail"></div>';
        
            out = out + '<li data-id="'+ template_id +'"><span>'+ template_name +'</span>'+ thumbnail +'</li>';
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
    
        // On page load...
        init_carousel(select_value);
        
        // When a tab is clicked...
        $('.content_tab a').click(function(){
            init_carousel(select_value);
        });
    }
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
    
        $("body")
            .ajaxStart(function () {
                $(this).addClass("loading");
            })
            .ajaxStop(function () {
                $(this).removeClass("loading");
            });
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
            // $("#layout_change").html('<div class="instruction_text"><p style="margin-left: 0;">A Revision must be saved to apply the selected Template\'s Publish Layout.</p></div>');
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
        } else {
            $("#layout_change").html('<input type="hidden" name="layout_preview" value="NULL" />');
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