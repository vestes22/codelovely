var pageid = ahc_ajax_front.page_id;
var page_id = (pageid.length > 0) ? pageid : ''; 
var pagetitle = ahc_ajax_front.page_title;
var page_title = (pagetitle.length > 0) ? pagetitle : ''; 
var posttype = ahc_ajax_front.post_type;
var post_type = (posttype.length > 0) ? posttype : ''; 
var referer = document.referrer;
var useragent = window.navigator.userAgent;
var servername = location.hostname;
var hostname = location.host;
var request_uri = location.pathname.substring(1);

var xhttp = new XMLHttpRequest();

xhttp.open("POST", ahc_ajax_front.ajax_url, true);
xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
xhttp.send("action=ahcpro_track_visitor&page_id="+ page_id +"&page_title="+ page_title + "&post_type="+ post_type + "&referer="+ referer +"&useragent="+ useragent +"&servername="+ servername +"&hostname="+ hostname +"&request_uri="+request_uri);
/*

jQuery(document).ready(function ()
{			
	var pageid = ahc_ajax_front.page_id;
	var page_id = (pageid.length > 0) ? pageid : ''; 
	var pagetitle = ahc_ajax_front.page_title;
	var page_title = (pagetitle.length > 0) ? pagetitle : ''; 
	var posttype = ahc_ajax_front.post_type;
	var post_type = (posttype.length > 0) ? posttype : ''; 
	var referer = document.referrer;
	var useragent = window.navigator.userAgent;
	var servername = location.hostname;
	var hostname = location.host;
	var request_uri = location.pathname.substring(1);
	
	jQuery.ajax({
		type: 'POST',
		url : ahc_ajax_front.ajax_url,
		data: {
			'action': 'ahcpro_track_visitor',
			'page_id': page_id,
			'page_title': page_title,
			'post_type': post_type,
			'referer': referer,
			'useragent':useragent,
			'servername':servername,
			'hostname':hostname,
			'request_uri':request_uri
		},
		success: function(data){
			console.log(data);
		},
		error: function(data)
		{	
			console.log(data);
		}
	});	
});
*/