
if (typeof window.Blueprints == 'undefined') window.Blueprints = {};

Blueprints.carousel = function(template_id)
{
    var structure_field = $('#hold_field_structure__template_id');
    var pages_field = $('#hold_field_pages__pages_template_id');
    var old_template_id = template_id;

    // Make sure either of these divs are visible first
    if((structure_field.length > 0 && structure_field.is(':visible')) || (pages_field.length > 0 && pages_field.is(':visible')))
    {
        // Find the template to select/start on
        if(Blueprints.config.layout_preview != "NULL" && Blueprints.config.layout_preview != "") {
            start_template = $("#blueprints_carousel").find("li[data-layout='" + Blueprints.config.layout_preview +"']");
            old_template_id = start_template.attr("data-id");
        } else {
            start_template = $("#blueprints_carousel").find("li[data-id='" + template_id +"']");
        }

        // Make sure our Pages and Structure tab field is visible, otherwise the carousel will not display correctly.
        $('#hold_field_pages__pages_template_id, #hold_field_structure__template_id').each(function(){
            var field = $(this);
            var img = field.find('img.field_collapse');
            var src = img.attr('src');
            
            field.find('.js_hide').removeClass('js_hide');
            img.attr('src', src.replace('field_collapse.png', 'field_expand.png'));
        });

        // Start it up
        jQuery("#blueprints_carousel").show().jcarousel({
            size: carousel.length,
            start: start_template.index()
        });
        
        // Sets the layout_change input value so the correct layout group is saved
        Blueprints.carousel_change(old_template_id);

        // On load set the active template
        start_template.addClass('current');
        
        // Add divs for the edge fades
        $('.jcarousel-container').prepend('<div class="jcarousel-left-fade"></div><div class="jcarousel-right-fade"></div>');

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
                item.find('.carousel_thumbnail').prepend('<div class="overlay"></div>');
                item.find('.carousel_thumbnail').prepend('<input type="submit" class="submit" name="submit" value="Load Layout" />');
                item.find('.carousel_thumbnail .overlay').height( item.find('img').height() );
                submit_button = item.find('.submit');

                item_width = $('.carousel_thumbnail').width();
                submit_width = submit_button.width();
                submit_button.css('left', ((item_width / 2) - (submit_width / 2)) - 8);
                
                submit_button.show(function () {
                    // alert('visible!');
                      // $(this).hide("scale", {}, 1000);
                });

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
    entry_id = $("#publishForm input[name=entry_id]").val();
    channel_id = $("#publishForm input[name=channel_id]").val();

    $.ajax({
        type: "POST",
        url: Blueprints.config.action_url_update_field_settings,
        data: "action=unset&hash="+ Blueprints.config.hash +'&'+ Blueprints.config.ajax_params +'&entry_id='+ entry_id,
        success: function (data, status, xhr) 
        {
            $.ajax({
                type: "POST",
                dataType: "json",
                url: EE.BASE + "&C=content_publish&M=autosave",
                data: post_data,
                success: function (data, status, xhr) 
                {
                    setTimeout({
                        run: function() 
                        {
                            Blueprints.autosave_redirect(data.autosave_entry_id, layout_preview);
                        }
                    }.run, 500);
                }
            });
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

    window.location.href = href;
}

Blueprints.is_array = function(input){ return typeof(input)=="object"&&(input instanceof Array); }

/*
    Normal Structure or Pages select menu, pre 2.0 version or when Carousel is turned off.
*/
Blueprints.select_change = function(ele)
{
    var template = $(ele).find("option:selected").val();
    thumbnail = Blueprints.config.thumbnails[template];
    
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
        } else {
            $("#layout_change").html('<input type="hidden" name="old_layout_preview" value="NULL" /><input type="hidden" name="layout_preview" value="NULL" />');
        }
        
        $("#template_thumbnail").show().append('<input type="submit" class="submit" name="submit" value="Load Layout" />');
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
};


/*!
 * jCarousel - Riding carousels with jQuery
 *   http://sorgalla.com/jcarousel/
 *
 * Copyright (c) 2006 Jan Sorgalla (http://sorgalla.com)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * Built on top of the jQuery library
 *   http://jquery.com
 *
 * Inspired by the "Carousel Component" by Bill Scott
 *   http://billwscott.com/carousel/
 *
 * Version - 0.2.8
 */
(function($){var toFloat=function(val){return parseFloat(val)||0};var arraySlice=Array.prototype.slice;var jCarousel={};jCarousel.version='@VERSION';var rRelativeTarget=/^([+\-]=)?(.+)$/;jCarousel.parseTarget=function(target){var relative=false,parts=typeof target!=='object'?rRelativeTarget.exec(target):null;if(parts){target=parseInt(parts[2],10)||0;if(parts[1]){relative=true;if(parts[1]==='-='){target*=-1}}}else if(typeof target!=='object'){target=parseInt(target,10)||0}return{target:target,relative:relative}};jCarousel.detectCarousel=function(element){var carousel=element.data('jcarousel'),find=function(element){var carousel;element.find('.jcarousel').each(function(){carousel=$(this).data('jcarousel');if(carousel){return false}});return carousel};if(!carousel){while(element.size()>0){carousel=find(element);if(carousel){break}element=element.parent()}}return carousel};jCarousel.Plugin={version:jCarousel.version,options:{},pluginName:null,pluginClass:null,pluginFn:null,_element:null,_carousel:null,_options:$.noop,_init:$.noop,_destroy:$.noop,_create:function(){this.carousel()._bind('destroy.'+this.pluginName,$.proxy(this.destroy,this))},destroy:function(){this._destroy();this.carousel()._unbind('.'+this.pluginName).element().unbind('.'+this.pluginName);this.element().unbind('.'+this.pluginName).removeData(this.pluginName);return this},element:function(){return this._element},option:function(key,value){if(arguments.length===0){return $.extend({},this.options)}if(typeof key==='string'){if(typeof value==='undefined'){return typeof this.options[key]==='undefined'?null:this.options[key]}this.options[key]=value}else{if($.isFunction(key)){key=key.call(this)}this.options=$.extend({},this.options,key)}return this},carousel:function(){if(!!this.options.carousel){return this.options.carousel.jquery?this.options.carousel.data('jcarousel'):this.options.carousel}if(!this._carousel){this._carousel=jCarousel.detectCarousel(this.element());if(!this._carousel){$.error('Could not detect carousel for plugin "'+this.pluginName+'"')}}return this._carousel},_bind:function(event,handler,element){(element||this.element()).bind(this.pluginName+event,handler);return this},_unbind:function(event,handler,element){(element||this.element()).unbind(this.pluginName+event,handler);return this},_trigger:function(type,element,data){var event=$.Event((this.pluginName+type).toLowerCase());data=[this].concat(data||[]);(element||this.element()).trigger(event,data);return!($.isFunction(event.isDefaultPrevented)?event.isDefaultPrevented():event.defaultPrevented)}};var plugins={};jCarousel.plugin=function(name,callback){if(typeof callback==='undefined'){if(typeof plugins[name]==='undefined'){return $.error('No such plugin "'+name+'" registered')}return plugins[name].call(jCarousel,$)}plugins[name]=callback;var pluginName,pluginClass,pluginFn;if(name!=='jcarousel'){pluginName='jcarousel'+name.toLowerCase();pluginClass='jcarousel-'+name.toLowerCase();pluginFn='jcarousel'+name.charAt(0).toUpperCase()+name.slice(1)}else{pluginName=pluginClass=pluginFn=name}var plugin=function(element,options){if(!this._init){return new plugin(element,options)}this._element=$(element).data(pluginName,this).addClass(pluginClass);this.options=$.extend({},this.options,this._options(),options);this._create();this._init()};plugin.prototype=$.extend({},jCarousel.Plugin,{pluginName:pluginName,pluginClass:pluginClass,pluginFn:pluginFn},callback.call(jCarousel,$));$.fn[pluginFn]=function(options){var args=arraySlice.call(arguments,1),returnValue=this;if(typeof options==='string'){this.each(function(){var instance=$(this).data(pluginName);if(!instance){return $.error('Cannot call methods on '+pluginFn+' prior to initialization; '+'attempted to call method "'+options+'"')}if(!$.isFunction(instance[options])||options.charAt(0)==='_'){return $.error('No such method "'+options+'" for '+pluginFn+' instance')}var methodValue=instance[options].apply(instance,args);if(methodValue!==instance&&typeof methodValue!=='undefined'){returnValue=methodValue;return false}})}else{this.each(function(){var instance=$(this).data(pluginName);if(instance){if(options){instance.option(options)}}else{plugin(this,options)}})}return returnValue};return plugin};var _jCarousel=window.jCarousel;jCarousel.noConflict=function(){window.jCarousel=_jCarousel;return jCarousel};window.jCarousel=jCarousel;jCarousel.plugin('jcarousel',function($){var jCarousel=this;return{options:{list:'ul',items:'li',animation:400,wrap:null,vertical:null,rtl:null,center:false},animating:false,tail:0,inTail:false,resizeTimer:null,lt:null,vertical:false,rtl:false,circular:false,_list:null,_items:null,_target:null,_first:null,_last:null,_visible:null,_fullyvisible:null,_create:function(){},_init:function(){if(false===this._trigger('init')){return this}this._reload();var self=this;this.onWindowResize=function(){if(self.resizeTimer){clearTimeout(self.resizeTimer)}self.resizeTimer=setTimeout(function(){self.reload()},100)};$(window).bind('resize.jcarousel',this.onWindowResize);this.onAnimationComplete=function(callback){self.animating=false;var c=self.list().find('.jcarousel-clone');if(c.size()>0){c.remove();self._reload()}self._trigger('animateEnd');if($.isFunction(callback)){callback.call(self,true)}};this._trigger('initEnd');return this},destroy:function(){if(false===this._trigger('destroy')){return this}this.element().unbind('.'+this.pluginName).removeData(this.pluginName);this.items().unbind('.jcarousel');$(window).unbind('resize.jcarousel',this.onWindowResize);this._trigger('destroyend');return this},reload:function(){if(false===this._trigger('reload')){return this}this._reload();this._trigger('reloadEnd');return this},list:function(){if(this._list===null){var option=this.option('list');this._list=$.isFunction(option)?option.call(this):this.element().find(option)}return this._list},items:function(){if(this._items===null){var option=this.option('items');this._items=($.isFunction(option)?option.call(this):this.list().find(option)).not('.jcarousel-clone')}return this._items},target:function(){return this._target},first:function(){return this._first},last:function(){return this._last},visible:function(){return this._visible},fullyvisible:function(){return this._fullyvisible},hasNext:function(){if(false===this._trigger('hasnext')){return true}var wrap=this.option('wrap'),end=this.items().size()-1;return end>=0&&((wrap&&wrap!=='first')||(this._last.index()<end)||(this.tail&&!this.inTail))?true:false},hasPrev:function(){if(false===this._trigger('hasprev')){return true}var wrap=this.option('wrap');return this.items().size()>0&&((wrap&&wrap!=='last')||(this._first.index()>0)||(this.tail&&this.inTail))?true:false},scroll:function(target,animate,callback){if(this.animating){return this}if(false===this._trigger('scroll',null,[target,animate])){return this}if($.isFunction(animate)){callback=animate;animate=true}var parsed=jCarousel.parseTarget(target);if(parsed.relative){var end=this.items().size()-1,scroll=Math.abs(parsed.target),first,index,curr,i;if(parsed.target>0){var last=this._last.index();if(last>=end&&this.tail){if(!this.inTail){this._scrollTail(animate,callback)}else{if(this.options.wrap=='both'||this.options.wrap=='last'){this._scroll(0,animate,callback)}else{this._scroll(Math.min(this._target.index()+scroll,end),animate,callback)}}}else{if(last===end&&(this.options.wrap=='both'||this.options.wrap=='last')){this._scroll(0,animate,callback)}else{first=this._target.index();index=first+scroll;if(this.circular&&index>end){i=end;curr=this.items().get(-1);while(i++<index){curr=this.items().eq(0);curr.after(curr.clone(true).addClass('jcarousel-clone'));this.list().append(curr);this._items=null}this._scroll(curr,animate,callback)}else{this._scroll(Math.min(index,end),animate,callback)}}}}else{if(this.inTail){this._scroll(Math.max((this._first.index()-scroll)+1,0),animate,callback)}else{first=this._first.index();index=first-scroll;if(first===0&&(this.options.wrap=='both'||this.options.wrap=='first')){this._scroll(end,animate,callback)}else{if(this.circular&&index<0){i=index;curr=this.items().get(0);while(i++<0){curr=this.items().eq(-1);curr.after(curr.clone(true).addClass('jcarousel-clone'));this.list().prepend(curr);this._items=null;var lt=toFloat(this.list().css(this.lt)),dim=this._dimension(curr);this.rtl?lt+=dim:lt-=dim;this.list().css(this.lt,lt+'px')}this._scroll(curr,animate,callback)}else{this._scroll(Math.max(first-scroll,0),animate,callback)}}}}}else{this._scroll(parsed.target,animate,callback)}this._trigger('scrollend');return this},_reload:function(){var element=this.element(),checkRTL=function(){if((''+element.attr('dir')).toLowerCase()==='rtl'){return true}var found=false;element.parents('[dir]').each(function(){if((/rtl/i).test($(this).attr('dir'))){found=true;return false}});return found};this.vertical=this.options.vertical==null?(''+element.attr('class')).toLowerCase().indexOf('jcarousel-vertical')>-1:this.options.vertical;this.rtl=this.options.rtl==null?checkRTL():this.options.rtl;this.lt=this.vertical?'top':'left';this._items=null;var item=this._target||this.items().eq(0);this.circular=this.options.wrap=='circular';this.list().css({'left':0,'top':0});if(item.size()>0){this._prepare(item);this.list().find('.jcarousel-clone').remove();this._items=null;this.circular=this.options.wrap=='circular'&&this._fullyvisible.size()<this.items().size();this.list().css(this.lt,this._position(item)+'px')}return this},_scroll:function(item,animate,callback){if(this.animating){return this}if(typeof item!=='object'){item=this.items().eq(item)}else if(typeof item.jquery==='undefined'){item=$(item)}if(item.size()===0){if($.isFunction(callback)){callback.call(this,false)}return this}this.inTail=false;this._prepare(item);var pos=this._position(item),currPos=toFloat(this.list().css(this.lt));if(pos===currPos){if($.isFunction(callback)){callback.call(this,false)}return this}var properties={};properties[this.lt]=pos+'px';this._animate(properties,animate,callback);return this},_scrollTail:function(animate,callback){if(this.animating||!this.tail){return this}var pos=this.list().position()[this.lt];this.rtl?pos+=this.tail:pos-=this.tail;this.inTail=true;var properties={};properties[this.lt]=pos+'px';this._update({target:this._target.next(),fullyvisible:this._fullyvisible.slice(1).add(this._visible.last())});this._animate(properties,animate,callback);return this},_animate:function(properties,animate,callback){if(this.animating){return this}if(false===this._trigger('animate')){return this}this.animating=true;if(!this.options.animation||animate===false){this.list().css(properties);this.onAnimationComplete(callback)}else{var self=this;if($.isFunction(this.options.animation)){this.options.animation.call(this,properties,function(){self.onAnimationComplete(callback)})}else{var opts=typeof this.options.animation==='object'?this.options.animation:{duration:this.options.animation},oldComplete=opts.complete;opts.complete=function(){self.onAnimationComplete(callback);if($.isFunction(oldComplete)){oldComplete.call(this)}};this.list().animate(properties,opts)}}return this},_prepare:function(item){var index=item.index(),idx=index,wh=this._dimension(item),clip=this._clipping(),update={target:item,first:item,last:item,visible:item,fullyvisible:wh<=clip?item:$()},lrb=this.vertical?'bottom':(this.rtl?'left':'right'),curr,margin;if(this.options.center){wh/=2;clip/=2}if(wh<clip){while(true){curr=this.items().eq(++idx);if(curr.size()===0){if(this.circular){curr=this.items().eq(0);if(item.get(0)===curr.get(0)){break}curr.after(curr.clone(true).addClass('jcarousel-clone'));this.list().append(curr);this._items=null}else{break}}wh+=this._dimension(curr);update.last=curr;update.visible=update.visible.add(curr);margin=toFloat(curr.css('margin-'+lrb));if((wh-margin)<=clip){update.fullyvisible=update.fullyvisible.add(curr)}if(wh>=clip){break}}}if(!this.circular&&wh<clip){idx=index;while(true){if(--idx<0){break}curr=this.items().eq(idx);if(curr.size()===0){break}wh+=this._dimension(curr);update.first=curr;update.visible=update.visible.add(curr);margin=toFloat(curr.css('margin-'+lrb));if((wh-margin)<=clip){update.fullyvisible=update.fullyvisible.add(curr)}if(wh>=clip){break}}}this._update(update);this.tail=0;if(this.options.wrap!=='circular'&&this.options.wrap!=='custom'&&update.last.index()===(this.items().size()-1)){wh-=toFloat(update.last.css('margin-'+lrb));if(wh>clip){this.tail=wh-clip}}return this},_position:function(item){var first=this._first,pos=first.position()[this.lt];if(this.rtl&&!this.vertical){pos-=this._clipping()-this._dimension(first)}if(this.options.center){pos-=(this._clipping()/2)-(this._dimension(first)/2)}if((item.index()>first.index()||this.inTail)&&this.tail){pos=this.rtl?pos-this.tail:pos+this.tail;this.inTail=true}else{this.inTail=false}return-pos},_update:function(update){var self=this,current={target:this._target||$(),first:this._first||$(),last:this._last||$(),visible:this._visible||$(),fullyvisible:this._fullyvisible||$()},back=(update.first||current.first).index()<current.first.index(),key,doUpdate=function(key){var elIn=[],elOut=[];update[key].each(function(){if(current[key].index(this)<0){elIn.push(this)}});current[key].each(function(){if(update[key].index(this)<0){elOut.push(this)}});if(back){elIn=elIn.reverse()}else{elOut=elOut.reverse()}self._trigger('item'+key+'in',$(elIn));self._trigger('item'+key+'out',$(elOut));current[key].removeClass('jcarousel-item-'+key);update[key].addClass('jcarousel-item-'+key);self['_'+key]=update[key]};for(key in update){doUpdate(key)}return this},_clipping:function(){return this.element()['inner'+(this.vertical?'Height':'Width')]()},_dimension:function(element){return element['outer'+(this.vertical?'Height':'Width')](true)}}})})(jQuery);

/*
 * jQuery.appear
 * http://code.google.com/p/jquery-appear/
 *
 * Copyright (c) 2009 Michael Hixson
 * Licensed under the MIT license (http://www.opensource.org/licenses/mit-license.php)
*/
(function($){$.fn.appear=function(f,o){var s=$.extend({one:true},o);return this.each(function(){var t=$(this);t.appeared=false;if(!f){t.trigger('appear',s.data);return;}var w=$(window);var c=function(){if(!t.is(':visible')){t.appeared=false;return;}var a=w.scrollLeft();var b=w.scrollTop();var o=t.offset();var x=o.left;var y=o.top;if(y+t.height()>=b&&y<=b+w.height()&&x+t.width()>=a&&x<=a+w.width()){if(!t.appeared)t.trigger('appear',s.data);}else{t.appeared=false;}};var m=function(){t.appeared=true;if(s.one){w.unbind('scroll',c);var i=$.inArray(c,$.fn.appear.checks);if(i>=0)$.fn.appear.checks.splice(i,1);}f.apply(this,arguments);};if(s.one)t.one('appear',s.data,m);else t.bind('appear',s.data,m);w.scroll(c);$.fn.appear.checks.push(c);(c)();});};$.extend($.fn.appear,{checks:[],timeout:null,checkAll:function(){var l=$.fn.appear.checks.length;if(l>0)while(l--)($.fn.appear.checks[l])();},run:function(){if($.fn.appear.timeout)clearTimeout($.fn.appear.timeout);$.fn.appear.timeout=setTimeout($.fn.appear.checkAll,20);}});$.each(['append','prepend','after','before','attr','removeAttr','addClass','removeClass','toggleClass','remove','css','show','hide'],function(i,n){var u=$.fn[n];if(u){$.fn[n]=function(){var r=u.apply(this,arguments);$.fn.appear.run();return r;}}});})(jQuery);


/*
    Save the Entries data, then reload the page with the new layout and saved data
*/
(function($){

    // console.log(Blueprints.config);
    
    var template_select = $("select[name=structure__template_id], select[name=pages__pages_template_id]");

    if(Blueprints.config.publish_layout_takeover)
    {
        jQuery(function(){
            $("#showToolbarLink a").toggle(function() {
                if($(".blueprints_layout_groups_holder").length == 0) {
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

            if(Blueprints.config.layout_group == "" && Blueprints.config.member_group_id == 1)
            {
                $('#showToolbarLink a span').text('No Publish Layout defined for the current template and channel combination. Create one now.');
            }
            
            $('#layout_groups_holder input[name="member_group[]"]').live('click', function(){
                var b_checkboxes = $('.blueprints_layout_groups_holder input[name="member_group[]"]');
                var d_checkboxes = $('#layout_groups_holder input[name="member_group[]"]').not('.blueprints_member_groups');
                
                if(b_checkboxes.filter(':checked').length > 0){
                    d_checkboxes.attr('disabled', true);
                } else {
                    d_checkboxes.attr('disabled', false);
                }
                
                if(d_checkboxes.filter(':checked').length > 0){
                    b_checkboxes.attr('disabled', true);
                } else {
                    b_checkboxes.attr('disabled', false);
                }
            });
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
            
            thumbnail = template_thumb.indexOf('http://') != -1 ? '<div class="carousel_thumbnail"><img src="'+ template_thumb +'" /></div>' : '<div class="carousel_thumbnail"><img src="'+ Blueprints.config.theme_url +'blueprints/images/no_template.png' +'" /></div>';
        
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
                // Make sure our hidden fields are added to the page, otherwise
                // only clicking the Pages/Structure tab will add them.
                Blueprints.carousel_change(select_value);
                Blueprints.carousel(select_value);
            }
        }.run, 350);
        
        // When a tab is clicked...
        $('#structure, #pages').appear(function(){
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
})(jQuery);