$j = jQuery.noConflict();
$j(document).ready(function(){
	$j(window).on("resize",function(){
		$j(".onepager .carousel,.full-panel").setCarHeight();
	}).trigger("resize");
	
	$j("#history-timeline").setupTimeline();
	$j("body").trackExternalClicks();
	$j('[data-toggle="tooltip"]').tooltip();
	$j('[data-layout="grid"]').effects();
	$j('[data-layout="map"]').campusMap();	
	$j('[data-layout="configurator"]').configurator();
	$j("[data-src]").loadSVG();
	$j(".post-type-archive-bios .flex-child").on("mouseleave",function(){
		return $j(this).scrollTop(0);
	});
});
$j.fn.effects = function(){
	if(this.length){
		var didScroll = true;
		
		$j(window).scroll(function() {
			didScroll = true;
		});
		
		setInterval(function() {
			if ( didScroll ) {
				didScroll = false;
				$j(".flex-child").each(function(){
					var th = $j(this),
					win = $j(window),
					top_of_object = th.position().top,
					bottom_of_window = win.scrollTop() + win.height();
					if( bottom_of_window > top_of_object ){
						th.css("opacity",1);
					}
				});
			}
		}, 250);
		
		$j(window).on("hashchange",function(e){
			if(window.location.hash.length>2){
				$j(window.location.hash).flip();
				$j('html, body').animate({
					scrollTop: $j(window.location.hash).offset().top - $j(".navbar").outerHeight() - Number($j(".content-nav-padding").css("paddingTop").replace("px",""))
				}, 1000);
			}
		});
		
		$j(".flex-child").on("click",function(e){
			$j(this).flip();
		});
		$j("body").on("click","#content.backdrop",function(){
			$j(".flipped").unFlip();
		});
		
		if(window.location.hash.length>1){
			$j(window).trigger("hashchange");
		}
	}
	return this;
}
$j.fn.flip = function(){
	if(this.is(".flex-child")){
		this.addClass("flipped");
		$j("#content").addClass("backdrop");
		window.location.hash = this.attr("id");
	}
	return this;
}
$j.fn.unFlip = function(){
	this.removeClass("flipped");
	$j("#content").removeClass("backdrop");
	window.location.hash = "_";
	return this;
}
$j.fn.setupTimeline = function(){
	var el = this;
	if(el.length){
		var pad = $j(".leadertext").outerHeight() + $j(".navbar").outerHeight() + Number($j(".content-nav-padding").css("paddingTop").replace("px",""));
		createStoryJS({
			type:       'timeline',
			width:      '100%',
			height:     $j(window).height()-pad,
			source:     $j.parseJSON($j("#data").text()),
			css:		assets + '/css/timeline.css',
			js: 		assets + '/js/timeline.min.js',
			embed_id:   'history-timeline'
		});
	}
	return el;
}
$j.fn.setCarHeight = function(){
	if(this.length){
		this.height($j(window).height()-30);
	}
	return this;
}
$j.fn.trackExternalClicks = function(){
	var filetypes = /\.(zip|exe|dmg|pdf|doc.*|xls.*|ppt.*|mp3|txt|rar|wma|mov|avi|wmv|flv|wav)$/i;
	var baseHref = '';
	if (jQuery('base').attr('href') != undefined) baseHref = jQuery('base').attr('href');
	
	jQuery('a').on('click', function(event) {
		var el = jQuery(this);
		var track = true;
		var href = (typeof(el.attr('href')) != 'undefined' ) ? el.attr('href') :"";
		var isThisDomain = href.match(document.domain.split('.').reverse()[1] + '.' + document.domain.split('.').reverse()[0]);
		if (!href.match(/^javascript:/i)) {
			var elEv = []; elEv.value=0;
			if (href.match(/^mailto\:/i)) {
				elEv.category = "email";
				elEv.action = "click";
				elEv.label = href.replace(/^mailto\:/i, '');
				elEv.loc = href;
			}
			else if (href.match(filetypes)) {
				var extension = (/[.]/.exec(href)) ? /[^.]+$/.exec(href) : undefined;
				elEv.category = "download";
				elEv.action = "click-" + extension[0];
				elEv.label = href.replace(/ /g,"-");
				elEv.loc = baseHref + href;
			}
			else if (href.match(/^https?\:/i) && !isThisDomain) {
				elEv.category = "external";
				elEv.action = "click";
				elEv.label = href.replace(/^https?\:\/\//i, '');
				elEv.loc = href;
			}
			else if (href.match(/^tel\:/i)) {
				elEv.category = "telephone";
				elEv.action = "click";
				elEv.label = href.replace(/^tel\:/i, '');
				elEv.loc = href;
			}
			else track = false;
			
			if (track) {
				ga('send','event', elEv.category.toLowerCase(), elEv.action.toLowerCase(), elEv.label.toLowerCase(), elEv.value);
				if ( el.attr('target') == undefined || el.attr('target').toLowerCase() != '_blank') {
					setTimeout(function() { location.href = elEv.loc; }, 400);
					return false;
				}
			}
		}
	});
}
$j.fn.campusMap = function(){
	if($j(".campus-map").length){
		$j(".campus-map svg")
			.height($j(window).height()-$j(".navbar").outerHeight()-20)
			.width($j(".campus-map").width() - 20).closest(".campus-map").removeClass("invisible");
		$j("#buildings>g,#numbers>g").on("click",function(e){
			var el = $j(this);
			e.stopPropagation();			
			window.location.hash = el.attr("id").toLowerCase().replace("n","b");
		});
		$j(window).on("hashchange",function(){
			var hash = window.location.hash.replace("#","").toLowerCase(),
				svg = $j(".campus-map svg");
			if(!hash){window.location.hash = "#dh"}
			svg.clearActiveSVG().moveToVisible();
			$j("#buildings>g").popover("destroy");
			$j.each(hash.split(","),function(num,el){
				var jel = $j("#" + el);
				jel.makeActiveSVG();
				if(el.indexOf("b")===0){
					var text = $j(".poptext").clone().removeClass("poptext"),
						on = text.find("[data-building='" + el.replace("b","") + "']")
					
					on.each(function(){
						text.find("[data-attach='" + $j(this).data("type") + "']").removeClass("hidden");
					}).removeClass("hidden");
					
					if(on.data("main_door")){
						$j("#d"+on.data("main_door").toLowerCase()).makeActiveSVG();
					}
					jel.popover({
						content : text,
						container : "body",
						trigger : "manual",
						html : true,
						placement : "top",
						title : on.data("building_name")
					}).popover("show");
					$j("body").one("click",function(){
						$j("#buildings>g").popover("destroy");
						window.location.hash = "#dh";
					});
				}
			});
		}).trigger("hashchange");
	}
}
$j.fn.makeActiveSVG = function(){
	var cls = this.attr("class") ? this.attr("class") : "";
	return this.attr("class",cls + " active");
}
$j.fn.clearActiveSVG = function(){
	this.find(".active").each(function(){
		var el = $j(this),
		pre = el.attr("class");
		el.attr("class",pre.replace(" active",""));
	});
	$j("#buildings>g").popover("destroy");
	return this;
}
$j.fn.moveToVisible = function(){
	var win = $j("html,body"),
		topy = this.offset().top;
		
	win.animate({
		scrollTop: topy
	}, 250);
}
$j.fn.configurator = function(){
	if(this.length){
		var control = this.find("#chooser");
		
		control.on("change",function(){
			window.location.hash = $j(this).find("option:selected").val();
		});
		
		$j(window).on("hashchange",function(){
			var hash = window.location.hash.replace("#","");
			control.val(hash);
			$j("[data-type='property']").addClass("hidden");
			$j("[data-variant='" + hash + "']" ).removeClass("hidden");
		}).trigger("hashchange");
	}
	return this;
}
$j.fn.loadSVG = function(){
	this.each(function(){
		var v = $j(this);
		v.load(v.data("src"),function(){
			$j(".svg-wrap svg")
				.height($j(window).height()-$j(".navbar").outerHeight()-60)
				.width($j(".svg-wrap").width() - 60);
		});
	});
	return this;
}