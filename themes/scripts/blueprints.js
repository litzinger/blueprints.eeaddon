// console.log(blueprints_options);

/*
var blueprints_options = {
    thumbnails: {'. implode(',', $thumbnails) .'},
    layout_groups: {'. implode(',', $layout_groups) .'},
    layout_group_options: "'. $layout_group_options .'",
    active_publish_layouts: '. $active_publish_layouts .',
    channel_templates: '. $channel_templates .',
    edit_templates_link: "'. $edit_templates_link .'",
    publish_layout_takeover: '. $this->_enable_publish_layout_takeover() .',
    thumbnail_path: "'. $this->EE->config->slash_item('site_url') . $thumbnail_path .'"
};
*/

var init_carousel = function(template_id)
{
    structure_field = $('#hold_field_structure__template_id');
    pages_field = $('#hold_field_pages__pages_template_id');
    
    // Make sure either of these divs are visible first
    if((structure_field.length > 0 && structure_field.is(':visible')) || (pages_field.length > 0 && pages_field.is(':visible'))){
        jQuery("#blueprints_carousel").jcarousel({
            size: carousel.length
        });
        
        $('.jcarousel-item')
        
        $("#blueprints_carousel").find("[data-id='" + template_id +"']").addClass('active');
    }
}

jQuery(function(){
    
    var template_select = $("select[name=structure__template_id], select[name=pages__pages_template_id]");
    
    // Only create the carousel if turned on
    if(true)
    {
        carousel = blueprints_options.carousel_options;
        out = '<ul id="blueprints_carousel" class="jcarousel-skin-blueprints">';

        for(i = 0; i < carousel.length; i++)
        {
            template_thumb = carousel[i].template_thumb;
            template_name = carousel[i].template_name;
            template_id = carousel[i].template_id;
        
            thumbnail = template_thumb ? '<img src="'+ blueprints_options.thumbnail_path + template_thumb +'" />' : '';
        
            out = out + '<li data-id="'+ template_id +'"><span>'+ template_name +'</span>'+ thumbnail +'</li>';
        }
    
        out = out + '</ul>';
    
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
            init_carousel();
        });
        
        $('.jcarousel-item').click(function(){
            id = $(this).attr('data-id');
            $('input[name='+ select_name +']').val(id);
            
            $(this).siblings().removeClass('active');
            $(this).addClass('active'); 
        });
    }
    else
    {
        template_select.after(blueprints_options.edit_templates_link + '<div class="clear"></div><div id="template_thumbnail"></div><div id="layout_change"></div><div class="clear"></div>');
        template_select.change(function(){
            blueprint_structure_tab($(this));
        });

        blueprint_structure_tab(template_select);
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

function blueprint_structure_tab(ele)
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
            // $("#layout_change").html('<div class="instruction_text"><p style="margin-left: 0;">A Revision must be saved to apply the selected Template\'s Publish Layout.</p></div><input type="hidden" name="layout_preview" value="'+ blueprints_options.layout_groups[template] +'" />');
            $("#revision_button").clone(true).appendTo( jQuery("#layout_change") );
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