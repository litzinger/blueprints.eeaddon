/*
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
// (function($){var toFloat=function(val){return parseFloat(val)||0};var arraySlice=Array.prototype.slice;var jCarousel={};jCarousel.version='@VERSION';var rRelativeTarget=/^([+\-]=)?(.+)$/;jCarousel.parseTarget=function(target){var relative=false,parts=typeof target!=='object'?rRelativeTarget.exec(target):null;if(parts){target=parseInt(parts[2],10)||0;if(parts[1]){relative=true;if(parts[1]==='-='){target*=-1}}}else if(typeof target!=='object'){target=parseInt(target,10)||0}return{target:target,relative:relative}};jCarousel.detectCarousel=function(element){var carousel=element.data('jcarousel'),find=function(element){var carousel;element.find('.jcarousel').each(function(){carousel=$(this).data('jcarousel');if(carousel){return false}});return carousel};if(!carousel){while(element.size()>0){carousel=find(element);if(carousel){break}element=element.parent()}}return carousel};jCarousel.Plugin={version:jCarousel.version,options:{},pluginName:null,pluginClass:null,pluginFn:null,_element:null,_carousel:null,_options:$.noop,_init:$.noop,_destroy:$.noop,_create:function(){this.carousel()._bind('destroy.'+this.pluginName,$.proxy(this.destroy,this))},destroy:function(){this._destroy();this.carousel()._unbind('.'+this.pluginName).element().unbind('.'+this.pluginName);this.element().unbind('.'+this.pluginName).removeData(this.pluginName);return this},element:function(){return this._element},option:function(key,value){if(arguments.length===0){return $.extend({},this.options)}if(typeof key==='string'){if(typeof value==='undefined'){return typeof this.options[key]==='undefined'?null:this.options[key]}this.options[key]=value}else{if($.isFunction(key)){key=key.call(this)}this.options=$.extend({},this.options,key)}return this},carousel:function(){if(!!this.options.carousel){return this.options.carousel.jquery?this.options.carousel.data('jcarousel'):this.options.carousel}if(!this._carousel){this._carousel=jCarousel.detectCarousel(this.element());if(!this._carousel){$.error('Could not detect carousel for plugin "'+this.pluginName+'"')}}return this._carousel},_bind:function(event,handler,element){(element||this.element()).bind(this.pluginName+event,handler);return this},_unbind:function(event,handler,element){(element||this.element()).unbind(this.pluginName+event,handler);return this},_trigger:function(type,element,data){var event=$.Event((this.pluginName+type).toLowerCase());data=[this].concat(data||[]);(element||this.element()).trigger(event,data);return!($.isFunction(event.isDefaultPrevented)?event.isDefaultPrevented():event.defaultPrevented)}};var plugins={};jCarousel.plugin=function(name,callback){if(typeof callback==='undefined'){if(typeof plugins[name]==='undefined'){return $.error('No such plugin "'+name+'" registered')}return plugins[name].call(jCarousel,$)}plugins[name]=callback;var pluginName,pluginClass,pluginFn;if(name!=='jcarousel'){pluginName='jcarousel'+name.toLowerCase();pluginClass='jcarousel-'+name.toLowerCase();pluginFn='jcarousel'+name.charAt(0).toUpperCase()+name.slice(1)}else{pluginName=pluginClass=pluginFn=name}var plugin=function(element,options){if(!this._init){return new plugin(element,options)}this._element=$(element).data(pluginName,this).addClass(pluginClass);this.options=$.extend({},this.options,this._options(),options);this._create();this._init()};plugin.prototype=$.extend({},jCarousel.Plugin,{pluginName:pluginName,pluginClass:pluginClass,pluginFn:pluginFn},callback.call(jCarousel,$));$.fn[pluginFn]=function(options){var args=arraySlice.call(arguments,1),returnValue=this;if(typeof options==='string'){this.each(function(){var instance=$(this).data(pluginName);if(!instance){return $.error('Cannot call methods on '+pluginFn+' prior to initialization; '+'attempted to call method "'+options+'"')}if(!$.isFunction(instance[options])||options.charAt(0)==='_'){return $.error('No such method "'+options+'" for '+pluginFn+' instance')}var methodValue=instance[options].apply(instance,args);if(methodValue!==instance&&typeof methodValue!=='undefined'){returnValue=methodValue;return false}})}else{this.each(function(){var instance=$(this).data(pluginName);if(instance){if(options){instance.option(options)}}else{plugin(this,options)}})}return returnValue};return plugin};var _jCarousel=window.jCarousel;jCarousel.noConflict=function(){window.jCarousel=_jCarousel;return jCarousel};window.jCarousel=jCarousel;jCarousel.plugin('jcarousel',function($){var jCarousel=this;return{options:{list:'ul',items:'li',animation:400,wrap:null,vertical:null,rtl:null,center:false},animating:false,tail:0,inTail:false,resizeTimer:null,lt:null,vertical:false,rtl:false,circular:false,_list:null,_items:null,_target:null,_first:null,_last:null,_visible:null,_fullyvisible:null,_create:function(){},_init:function(){if(false===this._trigger('init')){return this}this._reload();var self=this;this.onWindowResize=function(){if(self.resizeTimer){clearTimeout(self.resizeTimer)}self.resizeTimer=setTimeout(function(){self.reload()},100)};$(window).bind('resize.jcarousel',this.onWindowResize);this.onAnimationComplete=function(callback){self.animating=false;var c=self.list().find('.jcarousel-clone');if(c.size()>0){c.remove();self._reload()}self._trigger('animateEnd');if($.isFunction(callback)){callback.call(self,true)}};this._trigger('initEnd');return this},destroy:function(){if(false===this._trigger('destroy')){return this}this.element().unbind('.'+this.pluginName).removeData(this.pluginName);this.items().unbind('.jcarousel');$(window).unbind('resize.jcarousel',this.onWindowResize);this._trigger('destroyend');return this},reload:function(){if(false===this._trigger('reload')){return this}this._reload();this._trigger('reloadEnd');return this},list:function(){if(this._list===null){var option=this.option('list');this._list=$.isFunction(option)?option.call(this):this.element().find(option)}return this._list},items:function(){if(this._items===null){var option=this.option('items');this._items=($.isFunction(option)?option.call(this):this.list().find(option)).not('.jcarousel-clone')}return this._items},target:function(){return this._target},first:function(){return this._first},last:function(){return this._last},visible:function(){return this._visible},fullyvisible:function(){return this._fullyvisible},hasNext:function(){if(false===this._trigger('hasnext')){return true}var wrap=this.option('wrap'),end=this.items().size()-1;return end>=0&&((wrap&&wrap!=='first')||(this._last.index()<end)||(this.tail&&!this.inTail))?true:false},hasPrev:function(){if(false===this._trigger('hasprev')){return true}var wrap=this.option('wrap');return this.items().size()>0&&((wrap&&wrap!=='last')||(this._first.index()>0)||(this.tail&&this.inTail))?true:false},scroll:function(target,animate,callback){if(this.animating){return this}if(false===this._trigger('scroll',null,[target,animate])){return this}if($.isFunction(animate)){callback=animate;animate=true}var parsed=jCarousel.parseTarget(target);if(parsed.relative){var end=this.items().size()-1,scroll=Math.abs(parsed.target),first,index,curr,i;if(parsed.target>0){var last=this._last.index();if(last>=end&&this.tail){if(!this.inTail){this._scrollTail(animate,callback)}else{if(this.options.wrap=='both'||this.options.wrap=='last'){this._scroll(0,animate,callback)}else{this._scroll(Math.min(this._target.index()+scroll,end),animate,callback)}}}else{if(last===end&&(this.options.wrap=='both'||this.options.wrap=='last')){this._scroll(0,animate,callback)}else{first=this._target.index();index=first+scroll;if(this.circular&&index>end){i=end;curr=this.items().get(-1);while(i++<index){curr=this.items().eq(0);curr.after(curr.clone(true).addClass('jcarousel-clone'));this.list().append(curr);this._items=null}this._scroll(curr,animate,callback)}else{this._scroll(Math.min(index,end),animate,callback)}}}}else{if(this.inTail){this._scroll(Math.max((this._first.index()-scroll)+1,0),animate,callback)}else{first=this._first.index();index=first-scroll;if(first===0&&(this.options.wrap=='both'||this.options.wrap=='first')){this._scroll(end,animate,callback)}else{if(this.circular&&index<0){i=index;curr=this.items().get(0);while(i++<0){curr=this.items().eq(-1);curr.after(curr.clone(true).addClass('jcarousel-clone'));this.list().prepend(curr);this._items=null;var lt=toFloat(this.list().css(this.lt)),dim=this._dimension(curr);this.rtl?lt+=dim:lt-=dim;this.list().css(this.lt,lt+'px')}this._scroll(curr,animate,callback)}else{this._scroll(Math.max(first-scroll,0),animate,callback)}}}}}else{this._scroll(parsed.target,animate,callback)}this._trigger('scrollend');return this},_reload:function(){var element=this.element(),checkRTL=function(){if((''+element.attr('dir')).toLowerCase()==='rtl'){return true}var found=false;element.parents('[dir]').each(function(){if((/rtl/i).test($(this).attr('dir'))){found=true;return false}});return found};this.vertical=this.options.vertical==null?(''+element.attr('class')).toLowerCase().indexOf('jcarousel-vertical')>-1:this.options.vertical;this.rtl=this.options.rtl==null?checkRTL():this.options.rtl;this.lt=this.vertical?'top':'left';this._items=null;var item=this._target||this.items().eq(0);this.circular=this.options.wrap=='circular';this.list().css({'left':0,'top':0});if(item.size()>0){this._prepare(item);this.list().find('.jcarousel-clone').remove();this._items=null;this.circular=this.options.wrap=='circular'&&this._fullyvisible.size()<this.items().size();this.list().css(this.lt,this._position(item)+'px')}return this},_scroll:function(item,animate,callback){if(this.animating){return this}if(typeof item!=='object'){item=this.items().eq(item)}else if(typeof item.jquery==='undefined'){item=$(item)}if(item.size()===0){if($.isFunction(callback)){callback.call(this,false)}return this}this.inTail=false;this._prepare(item);var pos=this._position(item),currPos=toFloat(this.list().css(this.lt));if(pos===currPos){if($.isFunction(callback)){callback.call(this,false)}return this}var properties={};properties[this.lt]=pos+'px';this._animate(properties,animate,callback);return this},_scrollTail:function(animate,callback){if(this.animating||!this.tail){return this}var pos=this.list().position()[this.lt];this.rtl?pos+=this.tail:pos-=this.tail;this.inTail=true;var properties={};properties[this.lt]=pos+'px';this._update({target:this._target.next(),fullyvisible:this._fullyvisible.slice(1).add(this._visible.last())});this._animate(properties,animate,callback);return this},_animate:function(properties,animate,callback){if(this.animating){return this}if(false===this._trigger('animate')){return this}this.animating=true;if(!this.options.animation||animate===false){this.list().css(properties);this.onAnimationComplete(callback)}else{var self=this;if($.isFunction(this.options.animation)){this.options.animation.call(this,properties,function(){self.onAnimationComplete(callback)})}else{var opts=typeof this.options.animation==='object'?this.options.animation:{duration:this.options.animation},oldComplete=opts.complete;opts.complete=function(){self.onAnimationComplete(callback);if($.isFunction(oldComplete)){oldComplete.call(this)}};this.list().animate(properties,opts)}}return this},_prepare:function(item){var index=item.index(),idx=index,wh=this._dimension(item),clip=this._clipping(),update={target:item,first:item,last:item,visible:item,fullyvisible:wh<=clip?item:$()},lrb=this.vertical?'bottom':(this.rtl?'left':'right'),curr,margin;if(this.options.center){wh/=2;clip/=2}if(wh<clip){while(true){curr=this.items().eq(++idx);if(curr.size()===0){if(this.circular){curr=this.items().eq(0);if(item.get(0)===curr.get(0)){break}curr.after(curr.clone(true).addClass('jcarousel-clone'));this.list().append(curr);this._items=null}else{break}}wh+=this._dimension(curr);update.last=curr;update.visible=update.visible.add(curr);margin=toFloat(curr.css('margin-'+lrb));if((wh-margin)<=clip){update.fullyvisible=update.fullyvisible.add(curr)}if(wh>=clip){break}}}if(!this.circular&&wh<clip){idx=index;while(true){if(--idx<0){break}curr=this.items().eq(idx);if(curr.size()===0){break}wh+=this._dimension(curr);update.first=curr;update.visible=update.visible.add(curr);margin=toFloat(curr.css('margin-'+lrb));if((wh-margin)<=clip){update.fullyvisible=update.fullyvisible.add(curr)}if(wh>=clip){break}}}this._update(update);this.tail=0;if(this.options.wrap!=='circular'&&this.options.wrap!=='custom'&&update.last.index()===(this.items().size()-1)){wh-=toFloat(update.last.css('margin-'+lrb));if(wh>clip){this.tail=wh-clip}}return this},_position:function(item){var first=this._first,pos=first.position()[this.lt];if(this.rtl&&!this.vertical){pos-=this._clipping()-this._dimension(first)}if(this.options.center){pos-=(this._clipping()/2)-(this._dimension(first)/2)}if((item.index()>first.index()||this.inTail)&&this.tail){pos=this.rtl?pos-this.tail:pos+this.tail;this.inTail=true}else{this.inTail=false}return-pos},_update:function(update){var self=this,current={target:this._target||$(),first:this._first||$(),last:this._last||$(),visible:this._visible||$(),fullyvisible:this._fullyvisible||$()},back=(update.first||current.first).index()<current.first.index(),key,doUpdate=function(key){var elIn=[],elOut=[];update[key].each(function(){if(current[key].index(this)<0){elIn.push(this)}});current[key].each(function(){if(update[key].index(this)<0){elOut.push(this)}});if(back){elIn=elIn.reverse()}else{elOut=elOut.reverse()}self._trigger('item'+key+'in',$(elIn));self._trigger('item'+key+'out',$(elOut));current[key].removeClass('jcarousel-item-'+key);update[key].addClass('jcarousel-item-'+key);self['_'+key]=update[key]};for(key in update){doUpdate(key)}return this},_clipping:function(){return this.element()['inner'+(this.vertical?'Height':'Width')]()},_dimension:function(element){return element['outer'+(this.vertical?'Height':'Width')](true)}}})})(jQuery);
(function(i){var q={vertical:false,rtl:false,start:1,offset:1,size:null,scroll:3,visible:null,animation:"normal",easing:"swing",auto:0,wrap:null,initCallback:null,reloadCallback:null,itemLoadCallback:null,itemFirstInCallback:null,itemFirstOutCallback:null,itemLastInCallback:null,itemLastOutCallback:null,itemVisibleInCallback:null,itemVisibleOutCallback:null,buttonNextHTML:"<div></div>",buttonPrevHTML:"<div></div>",buttonNextEvent:"click",buttonPrevEvent:"click",buttonNextCallback:null,buttonPrevCallback:null, itemFallbackDimension:null},r=false;i(window).bind("load.jcarousel",function(){r=true});i.jcarousel=function(a,c){this.options=i.extend({},q,c||{});this.autoStopped=this.locked=false;this.buttonPrevState=this.buttonNextState=this.buttonPrev=this.buttonNext=this.list=this.clip=this.container=null;if(!c||c.rtl===undefined)this.options.rtl=(i(a).attr("dir")||i("html").attr("dir")||"").toLowerCase()=="rtl";this.wh=!this.options.vertical?"width":"height";this.lt=!this.options.vertical?this.options.rtl? "right":"left":"top";for(var b="",d=a.className.split(" "),f=0;f<d.length;f++)if(d[f].indexOf("jcarousel-skin")!=-1){i(a).removeClass(d[f]);b=d[f];break}if(a.nodeName.toUpperCase()=="UL"||a.nodeName.toUpperCase()=="OL"){this.list=i(a);this.container=this.list.parent();if(this.container.hasClass("jcarousel-clip")){if(!this.container.parent().hasClass("jcarousel-container"))this.container=this.container.wrap("<div></div>");this.container=this.container.parent()}else if(!this.container.hasClass("jcarousel-container"))this.container= this.list.wrap("<div></div>").parent()}else{this.container=i(a);this.list=this.container.find("ul,ol").eq(0)}b!==""&&this.container.parent()[0].className.indexOf("jcarousel-skin")==-1&&this.container.wrap('<div class=" '+b+'"></div>');this.clip=this.list.parent();if(!this.clip.length||!this.clip.hasClass("jcarousel-clip"))this.clip=this.list.wrap("<div></div>").parent();this.buttonNext=i(".jcarousel-next",this.container);if(this.buttonNext.size()===0&&this.options.buttonNextHTML!==null)this.buttonNext= this.clip.after(this.options.buttonNextHTML).next();this.buttonNext.addClass(this.className("jcarousel-next"));this.buttonPrev=i(".jcarousel-prev",this.container);if(this.buttonPrev.size()===0&&this.options.buttonPrevHTML!==null)this.buttonPrev=this.clip.after(this.options.buttonPrevHTML).next();this.buttonPrev.addClass(this.className("jcarousel-prev"));this.clip.addClass(this.className("jcarousel-clip")).css({overflow:"hidden",position:"relative"});this.list.addClass(this.className("jcarousel-list")).css({overflow:"hidden", position:"relative",top:0,margin:0,padding:0}).css(this.options.rtl?"right":"left",0);this.container.addClass(this.className("jcarousel-container")).css({position:"relative"});!this.options.vertical&&this.options.rtl&&this.container.addClass("jcarousel-direction-rtl").attr("dir","rtl");var j=this.options.visible!==null?Math.ceil(this.clipping()/this.options.visible):null;b=this.list.children("li");var e=this;if(b.size()>0){var g=0,k=this.options.offset;b.each(function(){e.format(this,k++);g+=e.dimension(this, j)});this.list.css(this.wh,g+100+"px");if(!c||c.size===undefined)this.options.size=b.size()}this.container.css("display","block");this.buttonNext.css("display","block");this.buttonPrev.css("display","block");this.funcNext=function(){e.next()};this.funcPrev=function(){e.prev()};this.funcResize=function(){e.reload()};this.options.initCallback!==null&&this.options.initCallback(this,"init");if(!r&&i.browser.safari){this.buttons(false,false);i(window).bind("load.jcarousel",function(){e.setup()})}else this.setup()}; var h=i.jcarousel;h.fn=h.prototype={jcarousel:"0.2.7"};h.fn.extend=h.extend=i.extend;h.fn.extend({setup:function(){this.prevLast=this.prevFirst=this.last=this.first=null;this.animating=false;this.tail=this.timer=null;this.inTail=false;if(!this.locked){this.list.css(this.lt,this.pos(this.options.offset)+"px");var a=this.pos(this.options.start,true);this.prevFirst=this.prevLast=null;this.animate(a,false);i(window).unbind("resize.jcarousel",this.funcResize).bind("resize.jcarousel",this.funcResize)}}, reset:function(){this.list.empty();this.list.css(this.lt,"0px");this.list.css(this.wh,"10px");this.options.initCallback!==null&&this.options.initCallback(this,"reset");this.setup()},reload:function(){this.tail!==null&&this.inTail&&this.list.css(this.lt,h.intval(this.list.css(this.lt))+this.tail);this.tail=null;this.inTail=false;this.options.reloadCallback!==null&&this.options.reloadCallback(this);if(this.options.visible!==null){var a=this,c=Math.ceil(this.clipping()/this.options.visible),b=0,d=0; this.list.children("li").each(function(f){b+=a.dimension(this,c);if(f+1<a.first)d=b});this.list.css(this.wh,b+"px");this.list.css(this.lt,-d+"px")}this.scroll(this.first,false)},lock:function(){this.locked=true;this.buttons()},unlock:function(){this.locked=false;this.buttons()},size:function(a){if(a!==undefined){this.options.size=a;this.locked||this.buttons()}return this.options.size},has:function(a,c){if(c===undefined||!c)c=a;if(this.options.size!==null&&c>this.options.size)c=this.options.size;for(var b= a;b<=c;b++){var d=this.get(b);if(!d.length||d.hasClass("jcarousel-item-placeholder"))return false}return true},get:function(a){return i(".jcarousel-item-"+a,this.list)},add:function(a,c){var b=this.get(a),d=0,f=i(c);if(b.length===0){var j,e=h.intval(a);for(b=this.create(a);;){j=this.get(--e);if(e<=0||j.length){e<=0?this.list.prepend(b):j.after(b);break}}}else d=this.dimension(b);if(f.get(0).nodeName.toUpperCase()=="LI"){b.replaceWith(f);b=f}else b.empty().append(c);this.format(b.removeClass(this.className("jcarousel-item-placeholder")), a);f=this.options.visible!==null?Math.ceil(this.clipping()/this.options.visible):null;d=this.dimension(b,f)-d;a>0&&a<this.first&&this.list.css(this.lt,h.intval(this.list.css(this.lt))-d+"px");this.list.css(this.wh,h.intval(this.list.css(this.wh))+d+"px");return b},remove:function(a){var c=this.get(a);if(!(!c.length||a>=this.first&&a<=this.last)){var b=this.dimension(c);a<this.first&&this.list.css(this.lt,h.intval(this.list.css(this.lt))+b+"px");c.remove();this.list.css(this.wh,h.intval(this.list.css(this.wh))- b+"px")}},next:function(){this.tail!==null&&!this.inTail?this.scrollTail(false):this.scroll((this.options.wrap=="both"||this.options.wrap=="last")&&this.options.size!==null&&this.last==this.options.size?1:this.first+this.options.scroll)},prev:function(){this.tail!==null&&this.inTail?this.scrollTail(true):this.scroll((this.options.wrap=="both"||this.options.wrap=="first")&&this.options.size!==null&&this.first==1?this.options.size:this.first-this.options.scroll)},scrollTail:function(a){if(!(this.locked|| this.animating||!this.tail)){this.pauseAuto();var c=h.intval(this.list.css(this.lt));c=!a?c-this.tail:c+this.tail;this.inTail=!a;this.prevFirst=this.first;this.prevLast=this.last;this.animate(c)}},scroll:function(a,c){if(!(this.locked||this.animating)){this.pauseAuto();this.animate(this.pos(a),c)}},pos:function(a,c){var b=h.intval(this.list.css(this.lt));if(this.locked||this.animating)return b;if(this.options.wrap!="circular")a=a<1?1:this.options.size&&a>this.options.size?this.options.size:a;for(var d= this.first>a,f=this.options.wrap!="circular"&&this.first<=1?1:this.first,j=d?this.get(f):this.get(this.last),e=d?f:f-1,g=null,k=0,l=false,m=0;d?--e>=a:++e<a;){g=this.get(e);l=!g.length;if(g.length===0){g=this.create(e).addClass(this.className("jcarousel-item-placeholder"));j[d?"before":"after"](g);if(this.first!==null&&this.options.wrap=="circular"&&this.options.size!==null&&(e<=0||e>this.options.size)){j=this.get(this.index(e));if(j.length)g=this.add(e,j.clone(true))}}j=g;m=this.dimension(g);if(l)k+= m;if(this.first!==null&&(this.options.wrap=="circular"||e>=1&&(this.options.size===null||e<=this.options.size)))b=d?b+m:b-m}f=this.clipping();var p=[],o=0,n=0;j=this.get(a-1);for(e=a;++o;){g=this.get(e);l=!g.length;if(g.length===0){g=this.create(e).addClass(this.className("jcarousel-item-placeholder"));j.length===0?this.list.prepend(g):j[d?"before":"after"](g);if(this.first!==null&&this.options.wrap=="circular"&&this.options.size!==null&&(e<=0||e>this.options.size)){j=this.get(this.index(e));if(j.length)g= this.add(e,j.clone(true))}}j=g;m=this.dimension(g);if(m===0)throw Error("jCarousel: No width/height set for items. This will cause an infinite loop. Aborting...");if(this.options.wrap!="circular"&&this.options.size!==null&&e>this.options.size)p.push(g);else if(l)k+=m;n+=m;if(n>=f)break;e++}for(g=0;g<p.length;g++)p[g].remove();if(k>0){this.list.css(this.wh,this.dimension(this.list)+k+"px");if(d){b-=k;this.list.css(this.lt,h.intval(this.list.css(this.lt))-k+"px")}}k=a+o-1;if(this.options.wrap!="circular"&& this.options.size&&k>this.options.size)k=this.options.size;if(e>k){o=0;e=k;for(n=0;++o;){g=this.get(e--);if(!g.length)break;n+=this.dimension(g);if(n>=f)break}}e=k-o+1;if(this.options.wrap!="circular"&&e<1)e=1;if(this.inTail&&d){b+=this.tail;this.inTail=false}this.tail=null;if(this.options.wrap!="circular"&&k==this.options.size&&k-o+1>=1){d=h.margin(this.get(k),!this.options.vertical?"marginRight":"marginBottom");if(n-d>f)this.tail=n-f-d}if(c&&a===this.options.size&&this.tail){b-=this.tail;this.inTail= true}for(;a-- >e;)b+=this.dimension(this.get(a));this.prevFirst=this.first;this.prevLast=this.last;this.first=e;this.last=k;return b},animate:function(a,c){if(!(this.locked||this.animating)){this.animating=true;var b=this,d=function(){b.animating=false;a===0&&b.list.css(b.lt,0);if(!b.autoStopped&&(b.options.wrap=="circular"||b.options.wrap=="both"||b.options.wrap=="last"||b.options.size===null||b.last<b.options.size||b.last==b.options.size&&b.tail!==null&&!b.inTail))b.startAuto();b.buttons();b.notify("onAfterAnimation"); if(b.options.wrap=="circular"&&b.options.size!==null)for(var f=b.prevFirst;f<=b.prevLast;f++)if(f!==null&&!(f>=b.first&&f<=b.last)&&(f<1||f>b.options.size))b.remove(f)};this.notify("onBeforeAnimation");if(!this.options.animation||c===false){this.list.css(this.lt,a+"px");d()}else this.list.animate(!this.options.vertical?this.options.rtl?{right:a}:{left:a}:{top:a},this.options.animation,this.options.easing,d)}},startAuto:function(a){if(a!==undefined)this.options.auto=a;if(this.options.auto===0)return this.stopAuto(); if(this.timer===null){this.autoStopped=false;var c=this;this.timer=window.setTimeout(function(){c.next()},this.options.auto*1E3)}},stopAuto:function(){this.pauseAuto();this.autoStopped=true},pauseAuto:function(){if(this.timer!==null){window.clearTimeout(this.timer);this.timer=null}},buttons:function(a,c){if(a==null){a=!this.locked&&this.options.size!==0&&(this.options.wrap&&this.options.wrap!="first"||this.options.size===null||this.last<this.options.size);if(!this.locked&&(!this.options.wrap||this.options.wrap== "first")&&this.options.size!==null&&this.last>=this.options.size)a=this.tail!==null&&!this.inTail}if(c==null){c=!this.locked&&this.options.size!==0&&(this.options.wrap&&this.options.wrap!="last"||this.first>1);if(!this.locked&&(!this.options.wrap||this.options.wrap=="last")&&this.options.size!==null&&this.first==1)c=this.tail!==null&&this.inTail}var b=this;if(this.buttonNext.size()>0){this.buttonNext.unbind(this.options.buttonNextEvent+".jcarousel",this.funcNext);a&&this.buttonNext.bind(this.options.buttonNextEvent+ ".jcarousel",this.funcNext);this.buttonNext[a?"removeClass":"addClass"](this.className("jcarousel-next-disabled")).attr("disabled",a?false:true);this.options.buttonNextCallback!==null&&this.buttonNext.data("jcarouselstate")!=a&&this.buttonNext.each(function(){b.options.buttonNextCallback(b,this,a)}).data("jcarouselstate",a)}else this.options.buttonNextCallback!==null&&this.buttonNextState!=a&&this.options.buttonNextCallback(b,null,a);if(this.buttonPrev.size()>0){this.buttonPrev.unbind(this.options.buttonPrevEvent+ ".jcarousel",this.funcPrev);c&&this.buttonPrev.bind(this.options.buttonPrevEvent+".jcarousel",this.funcPrev);this.buttonPrev[c?"removeClass":"addClass"](this.className("jcarousel-prev-disabled")).attr("disabled",c?false:true);this.options.buttonPrevCallback!==null&&this.buttonPrev.data("jcarouselstate")!=c&&this.buttonPrev.each(function(){b.options.buttonPrevCallback(b,this,c)}).data("jcarouselstate",c)}else this.options.buttonPrevCallback!==null&&this.buttonPrevState!=c&&this.options.buttonPrevCallback(b, null,c);this.buttonNextState=a;this.buttonPrevState=c},notify:function(a){var c=this.prevFirst===null?"init":this.prevFirst<this.first?"next":"prev";this.callback("itemLoadCallback",a,c);if(this.prevFirst!==this.first){this.callback("itemFirstInCallback",a,c,this.first);this.callback("itemFirstOutCallback",a,c,this.prevFirst)}if(this.prevLast!==this.last){this.callback("itemLastInCallback",a,c,this.last);this.callback("itemLastOutCallback",a,c,this.prevLast)}this.callback("itemVisibleInCallback", a,c,this.first,this.last,this.prevFirst,this.prevLast);this.callback("itemVisibleOutCallback",a,c,this.prevFirst,this.prevLast,this.first,this.last)},callback:function(a,c,b,d,f,j,e){if(!(this.options[a]==null||typeof this.options[a]!="object"&&c!="onAfterAnimation")){var g=typeof this.options[a]=="object"?this.options[a][c]:this.options[a];if(i.isFunction(g)){var k=this;if(d===undefined)g(k,b,c);else if(f===undefined)this.get(d).each(function(){g(k,this,d,b,c)});else{a=function(m){k.get(m).each(function(){g(k, this,m,b,c)})};for(var l=d;l<=f;l++)l!==null&&!(l>=j&&l<=e)&&a(l)}}}},create:function(a){return this.format("<li></li>",a)},format:function(a,c){a=i(a);for(var b=a.get(0).className.split(" "),d=0;d<b.length;d++)b[d].indexOf("jcarousel-")!=-1&&a.removeClass(b[d]);a.addClass(this.className("jcarousel-item")).addClass(this.className("jcarousel-item-"+c)).css({"float":this.options.rtl?"right":"left","list-style":"none"}).attr("jcarouselindex",c);return a},className:function(a){return a+" "+a+(!this.options.vertical? "-horizontal":"-vertical")},dimension:function(a,c){var b=a.jquery!==undefined?a[0]:a,d=!this.options.vertical?(b.offsetWidth||h.intval(this.options.itemFallbackDimension))+h.margin(b,"marginLeft")+h.margin(b,"marginRight"):(b.offsetHeight||h.intval(this.options.itemFallbackDimension))+h.margin(b,"marginTop")+h.margin(b,"marginBottom");if(c==null||d==c)return d;d=!this.options.vertical?c-h.margin(b,"marginLeft")-h.margin(b,"marginRight"):c-h.margin(b,"marginTop")-h.margin(b,"marginBottom");i(b).css(this.wh, d+"px");return this.dimension(b)},clipping:function(){return!this.options.vertical?this.clip[0].offsetWidth-h.intval(this.clip.css("borderLeftWidth"))-h.intval(this.clip.css("borderRightWidth")):this.clip[0].offsetHeight-h.intval(this.clip.css("borderTopWidth"))-h.intval(this.clip.css("borderBottomWidth"))},index:function(a,c){if(c==null)c=this.options.size;return Math.round(((a-1)/c-Math.floor((a-1)/c))*c)+1}});h.extend({defaults:function(a){return i.extend(q,a||{})},margin:function(a,c){if(!a)return 0; var b=a.jquery!==undefined?a[0]:a;if(c=="marginRight"&&i.browser.safari){var d={display:"block","float":"none",width:"auto"},f,j;i.swap(b,d,function(){f=b.offsetWidth});d.marginRight=0;i.swap(b,d,function(){j=b.offsetWidth});return j-f}return h.intval(i.css(b,c))},intval:function(a){a=parseInt(a,10);return isNaN(a)?0:a}});i.fn.jcarousel=function(a){if(typeof a=="string"){var c=i(this).data("jcarousel"),b=Array.prototype.slice.call(arguments,1);return c[a].apply(c,b)}else return this.each(function(){i(this).data("jcarousel", new h(this,a))})}})(jQuery);



if (typeof window.Blueprints == 'undefined') window.Blueprints = {};

Blueprints.filter_select_menu_options_called = false;

Blueprints.carousel = function(template_id)
{
    var start_template;
    var old_template_id = template_id;
    var select_name = Blueprints.template_select.attr('name');

    // Make sure either of these divs are visible first
    if(Blueprints.tab_is_visible())
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

        // Simple version, no carousel b/c there are no thumbnail previews :(
        if( ! Blueprints.config.has_thumbnails)
        {
            $('#blueprints_carousel').find('li').addClass('jcarousel-item');
            $('#blueprints_carousel').addClass('simple').show();

            var submit_button = $('<input type="submit" class="submit simple" name="submit" value="Load Layout" />');

            if( $('input.submit.simple').length == 0 && Blueprints.config.publish_layout_takeover) {
                submit_button.insertAfter('#blueprints_carousel');
            }

            // Sets the layout_change input value so the correct layout group is saved
            Blueprints.carousel_init(old_template_id);

            // On load set the active template
            start_template.addClass('current');

            // On click set active template
            $('.jcarousel-item').click(function(){
                var item = $(this);

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
                var layout_preview = Blueprints.carousel_init(id);

                // Make it visually active
                item.siblings().removeClass('active');
                item.addClass('active');

                // Add zee button, but only if the choosen template is assigned to a Publish Layout
                if(old_template_id != id && id in Blueprints.config.layouts)
                {
                    submit_button.unbind('click')
                        .css({'opacity': '1', 'cursor': 'pointer'})
                        .click(function(e){
                            e.preventDefault();
                            submit_button.after('<img class="ajax_loader simple" src="'+ Blueprints.config.theme_url +'blueprints/images/ajax_loader.gif" />');
                            submit_button.remove();
                            Blueprints.autosave(layout_preview);
                        });
                }
                else
                {
                    submit_button.unbind('click')
                        .css({'opacity': '0.5', 'cursor': 'default'})
                        .click(function(e){ e.preventDefault(); });
                }
            });
        }
        else
        {
            // Start it up
            jQuery("#blueprints_carousel").show().jcarousel({
                size: Blueprints.config.carousel_options.length,
                start: start_template.index()
            });

            // Add divs for the edge fades
            $('.jcarousel-container').prepend('<div class="jcarousel-left-fade"></div><div class="jcarousel-right-fade"></div>');

            // Sets the layout_change input value so the correct layout group is saved
            Blueprints.carousel_init(old_template_id);

            // On load set the active template
            start_template.addClass('current');

            // On click set active template
            $('.jcarousel-item').click(function(){
                var item = $(this);

                // Set all siblings as not clicked
                item.siblings().each(function(){
                    $(this).data('clicked', false);
                });
                // Make sure the item can only be clicked once, otherwise it will
                // execute this code each time it's clicked. bad.
                if(item.data('clicked')) return;
                item.data('clicked', true);

                var id = $(this).attr('data-id');
                $('input[name='+ select_name +']').val(id);

                // Set layout_change on item click
                var layout_preview = Blueprints.carousel_init(id);

                // Make it visually active
                item.siblings().removeClass('active');
                item.addClass('active');

                item.siblings().find('.submit').remove();
                item.siblings().find('.overlay').remove();
                item.siblings().find('.ajax_loader').remove();

                // Add zee button, but only if the choosen template is assigned to a Publish Layout
                if(old_template_id != id && id in Blueprints.config.layouts && Blueprints.config.publish_layout_takeover)
                {
                    item.find('.carousel_thumbnail').prepend('<div class="overlay"></div>');
                    item.find('.carousel_thumbnail').prepend('<input type="submit" class="submit" name="submit" value="Load Layout" />');
                    item.find('.carousel_thumbnail .overlay').height( item.find('img').height() );
                    submit_button = item.find('.submit');

                    var item_width = $('.carousel_thumbnail').width();
                    var submit_width = submit_button.width();
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
}

Blueprints.autosave = function(layout_preview)
{
    var post_data = $("#publishForm").serialize();
    var entry_id = $("#publishForm input[name=entry_id]").val();
    var channel_id = $("#publishForm input[name=channel_id]").val();

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
    var href = window.location.href;

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
Blueprints.select_init = function(ele)
{
    var template = $(ele).find("option:selected").val();
    var thumbnail = Blueprints.config.thumbnails[template] != '' ? '<img src="'+ Blueprints.config.thumbnails[template] +'" />' : '';

    if(Blueprints.config.thumbnails[template] != "" && Blueprints.config.thumbnails[template] != undefined) {
        $("#template_thumbnail").show().html(thumbnail);
    } else {
        $("#template_thumbnail").hide().html('');
    }

    if(Blueprints.config.publish_layout_takeover)
    {
        var layout_preview = Blueprints.config.layouts[template];

        if(layout_preview != undefined && layout_preview != "") {
            $("#layout_change").html('<input type="hidden" name="old_layout_preview" value="'+ layout_preview +'" /><input type="hidden" name="new_layout_preview" value="'+ layout_preview +'" />');
        } else {
            $("#layout_change").html('<input type="hidden" name="old_layout_preview" value="NULL" /><input type="hidden" name="new_layout_preview" value="NULL" />');
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
Blueprints.carousel_init = function(template)
{
    if(Blueprints.config.publish_layout_takeover)
    {
        var layout_preview = Blueprints.config.layouts[template];

        if(layout_preview != undefined && layout_preview != "") {
            $("#layout_change").html('<input type="hidden" name="old_layout_preview" value="'+ layout_preview +'" /><input type="hidden" name="new_layout_preview" value="'+ layout_preview +'" />');
            return layout_preview;
        } else {
            $("#layout_change").html('<input type="hidden" name="old_layout_preview" value="NULL" /><input type="hidden" name="old_layout_preview" value="NULL" />');
            return false;
        }
    }
};

/*
    See if either the Structure tab or Pages tab is visible
*/
Blueprints.tab_is_visible = function()
{
    if(
        (Blueprints.structure_field.length > 0 && Blueprints.structure_field.is(':visible')) ||
        (Blueprints.pages_field.length > 0 && Blueprints.pages_field.is(':visible'))
    ){
        return true;
    }

    return false;
}

/*
    Remove options from the Pages/Structure template dropdown menu, or rename them...
*/
Blueprints.filter_select_menu_options = function()
{
    // Make sure this only happens once, otherwise it reverses what we just reversed
    // b/c this gets called twice, once on init and once on click.
    if (Blueprints.filter_select_menu_options_called != false)
        return;

    Blueprints.select_init(Blueprints.template_select);
    var template_select_options = Blueprints.template_select.find("option");
    var template_select_options_selected = template_select_options.filter(':selected');

    // Grab the selected value prior to manipulating the options because Firefox is stupid
    var selected_val = template_select_options_selected.val();

    var templates = [];
    var extra_templates = [];

    template_select_options.each(function(i){
        var value = parseInt($(this).val());

        // Remove templates that should not be displayed in the dropdown.
        if( ! Blueprints.is_array(Blueprints.config.channel_templates) ) {
            if(value != Blueprints.config.channel_templates) {
                $(this).remove();
            }
        } else {
            if($.inArray(value, Blueprints.config.channel_templates) == -1 && Blueprints.config.channel_templates.length > 0) {
                $(this).remove();
            }
        }

        // Update the label for the drop down if a custom layout name is defined.
        if(Blueprints.config.carousel_options.length > 0) {
            for(i = 0; i < Blueprints.config.carousel_options.length; i++) {
                if(Blueprints.config.carousel_options[i].template_id == value) {
                    $(this).text(Blueprints.config.carousel_options[i].layout_name);
                }
            }
        }
    });

    template_select_options = Blueprints.template_select.find("option").get();

    var sorted = Blueprints.sort_options(template_select_options, Blueprints.config.channel_templates);

    Blueprints.template_select.find("option").remove();
    Blueprints.template_select.append(sorted);

    // Because Firefox when appending options back to the list doesn't
    // set the selected value properly, thus causing the last template/option
    // in the list to be POSTed and breaking layouts.
    Blueprints.template_select.find("option").each(function(){
        if ($(this).val() == selected_val) {
            $(this).attr('selected', true);
        } else {
            $(this).attr('selected', false);
        }
    });

    var template_select_optgroups = Blueprints.template_select.find("optgroup");
    template_select_optgroups.each(function(i){
        if( $(this).children().length == 0 ){
            $(this).remove();
        }
    });

    Blueprints.filter_select_menu_options_called = true;
}

Blueprints.sort_options = function(options, order)
{
    if (options.length == 1) {
        return options;
    }

    var new_array = [];

    $.each(order, function(i){

    	$.each(options, function()
    	{
    		var opt = $(this);

            if (parseInt(opt.val()) === parseInt(order[i]))
            {
            	new_array.push(opt[0]);
            }
        });
    });

    return new_array;
}

/*
    Save the Entries data, then reload the page with the new layout and saved data
*/
$(function(){

    // console.log(Blueprints.config);
    var interval;

    Blueprints.template_select = $("select[name=structure__template_id], select[name=pages__pages_template_id]");
    Blueprints.structure_field = $('#hold_field_structure__template_id');
    Blueprints.pages_field = $('#hold_field_pages__pages_template_id');
    PublishForm = $('#publishForm');

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
        });
    }

    // Only create the carousel if turned on
    if(Blueprints.config.enable_carousel == 'y' && Blueprints.config.carousel_options.length > 0)
    {
        var carousel = Blueprints.config.carousel_options;
        var out = '<ul id="blueprints_carousel" class="jcarousel-skin-blueprints" style="display: none">';

        PublishForm.prepend('<div id="layout_change"></div>');

        for(i = 0; i < carousel.length; i++)
        {
            var template_thumb = carousel[i].template_thumb;
            var template_name = carousel[i].template_name;
            var template_id = carousel[i].template_id;
            var layout_preview = carousel[i].layout_preview;
            var layout_name = carousel[i].layout_name;

            var thumbnail = template_thumb != '' ? '<div class="carousel_thumbnail"><img src="'+ template_thumb +'" /></div>' : '<div class="carousel_thumbnail"><img src="'+ Blueprints.config.theme_url +'blueprints/images/no_template.png' +'" /></div>';

            out = out + '<li data-id="'+ template_id +'" data-layout="'+ layout_preview +'"> \
                            <span class="carousel_template_name">'+ layout_name +'</span> \
                            <div class="carousel_thumbnail_wrapper">'+ thumbnail +'</div> \
                        </li>';
        }

        // Get the values to re-create our hidden field to send the ID to on click before the original select is removed
        var select_name = Blueprints.template_select.attr('name');
        var select_value = Blueprints.template_select.val();

        out = out + '</ul><div class="clear"></div><input type="hidden" name="'+ select_name +'" value="'+ select_value +'" />';

        // Insert our carousel HTML
        Blueprints.template_select.after(out);

        // Remove the original Structure or Pages template dropdown, we just replaced it.
        Blueprints.template_select.remove();

        // On page load (if the user moved the Template field to another tab)
        setTimeout({
            run: function() {

                Blueprints.carousel_init(select_value);

                // Make sure our hidden fields are added to the page, otherwise
                // only clicking the Pages/Structure tab will add them.
                if (Blueprints.tab_is_visible()) {
                    Blueprints.carousel(select_value);
                }
            }
        }.run, 100);

        // When a tab is clicked
        $('.content_tab a').bind('click', function()
        {
            // Keep firing to make sure the carousel gets initiated
            var interval = setInterval(function(){
                if (Blueprints.tab_is_visible()) {
                    Blueprints.carousel(select_value);
                    clearInterval(interval);
                }
            });

            // Stop after 3 seconds just incase
            setTimeout(function(){
                clearInterval(interval);
            }, 3000);
        });
    }

    // Old school template select menu
    else
    {
        PublishForm.prepend('<div id="layout_change"></div>');

        Blueprints.template_select.after(Blueprints.config.edit_templates_link + '<div class="clear"></div><div id="template_thumbnail"></div><div class="clear"></div>');

        // On page load (if the user moved the Template field to another tab)
        setTimeout({
            run: function() {

                Blueprints.select_init(Blueprints.template_select);

                // Make sure our hidden fields are added to the page, otherwise
                // only clicking the Pages/Structure tab will add them.
                Blueprints.filter_select_menu_options();
            }
        }.run, 100);

        Blueprints.template_select.change(function(){
            Blueprints.select_init($(this));
        });

        $('.content_tab a').bind('click', function()
        {
            // Keep firing to make sure the carousel gets initiated
            interval = setInterval(function(){
                if (Blueprints.tab_is_visible()) {
                    Blueprints.filter_select_menu_options();
                    clearInterval(interval);
                }
            });

            // Stop after 3 seconds just incase
            setTimeout(function(){
                clearInterval(interval);
            }, 3000);
        });
    }
});