YUI.add("moodle-core-notification-exception",function(e,t){var n,r,i,s,o,u,a;n="moodle-dialogue",r="notificationBase",i="yesLabel",s="noLabel",o="title",u="question",a={BASE:"moodle-dialogue-base",WRAP:"moodle-dialogue-wrap",HEADER:"moodle-dialogue-hd",BODY:"moodle-dialogue-bd",CONTENT:"moodle-dialogue-content",FOOTER:"moodle-dialogue-ft",HIDDEN:"hidden",LIGHTBOX:"moodle-dialogue-lightbox"},M.core=M.core||{};var f="Moodle exception",l;l=function(t){var n=e.mix({},t);n.width=n.width||M.cfg.developerdebug?Math.floor(e.one(document.body).get("winWidth")/3)+"px":null,n.closeButton=!0;var r=["message","name","fileName","lineNumber","stack"];e.Array.each(r,function(e){n[e]=t[e]}),l.superclass.constructor.apply(this,[n])},e.extend(l,M.core.notification.info,{_hideTimeout:null,_keypress:null,initializer:function(t){var n,i=this,s=this.get("hideTimeoutDelay"),o=M.util.get_string("labelsep","langconfig");this.get(r).addClass("moodle-dialogue-exception"),this.setStdModContent(e.WidgetStdMod.HEADER,'<h1 id="moodle-dialogue-'+t.COUNT+'-header-text">'+e.Escape.html(t.name)+"</h1>",e.WidgetStdMod.REPLACE),n=e.Node.create('<div class="moodle-exception" data-rel="fatalerror"></div>').append(e.Node.create('<div class="moodle-exception-message">'+e.Escape.html(this.get("message"))+"</div>")).append(e.Node.create('<div class="moodle-exception-param hidden param-filename"><label>'+M.util.get_string("file","moodle")+o+"</label> "+e.Escape.html(this.get("fileName"))+"</div>")).append(e.Node.create('<div class="moodle-exception-param hidden param-linenumber"><label>'+M.util.get_string("line","debug")+o+"</label> "+e.Escape.html(this.get("lineNumber"))+"</div>")).append(e.Node.create('<div class="moodle-exception-param hidden param-stacktrace"><label>'+M.util.get_string("stacktrace","debug")+o+"</label> <pre>"+this.get("stack")+"</pre></div>")),M.cfg.developerdebug&&n.all(".moodle-exception-param").removeClass("hidden"),this.setStdModContent(e.WidgetStdMod.BODY,n,e.WidgetStdMod.REPLACE),s&&(this._hideTimeout=setTimeout(function(){i.hide()},s)),this.after("visibleChange",this.visibilityChanged,this),this._keypress=e.on("key",this.hide,window,"down:13,27",this),this.centerDialogue()},visibilityChanged:function(e){if(e.attrName==="visible"&&e.prevVal&&!e.newVal){this._keypress&&this._keypress.detach();var t=this;setTimeout(function(){t.destroy()},1e3)}}},{NAME:f,CSS_PREFIX:n,ATTRS:{message:{value:""},name:{value:""},fileName:{value:""},lineNumber:{value:""},stack:{setter:function(t){var n=e.Escape.html(t).split("\n"),r=new RegExp("^(.+)@("+M.cfg.wwwroot+")?(.{0,75}).*:(\\d+)$"),i;for(i in n)n[i]=n[i].replace(r,"<div class='stacktrace-line'>ln: $4</div><div class='stacktrace-file'>$3</div><div class='stacktrace-call'>$1</div>");return n.join("\n")},value:""},hideTimeoutDelay:{validator:e.Lang.isNumber,value:null}}}),M.core.exception=l},"@VERSION@",{requires:["moodle-core-notification-dialogue"]});