var colors = ['#DB6946', '#C14543', '#445060', '#395953', '#6C8C80', '#829AB5', '#BF807A', '#BF0000', '#006BB7', '#EC732C', '#BF3D27', '#A6375F',
			'#8C6D46', '#326149', '#802B35', '#8A3842', '#366D73', '#4D6173', '#4A4659', '#C9D65B', '#F45552', '#F3CC5E', '#F29B88', '#D96941',
			'#484F73', '#C9AB81', '#F5655C', '#F0C480'];

jQuery(document).ready(function ()
{
	
	

	
	if (typeof google === 'object' && typeof google.maps === 'object') {
	    return;
	}else{
	   
	}
	
	
	
});			
//------------------------------------------------------------------------------				
function convertToNumeric(data){
	if(data instanceof Array){
		for(var index in data){
			data[index] = Number(data[index]);
		}
	} else{
		data = Number(data);
	}
	return data;
}
//------------------------------------------------------------------------------
function getRandomElementFromArray(array){
	var ranIndex = Math.floor(Math.random() * array.length);
	return array[ranIndex];
}
//------------------------------------------------------------------------------
function drawVisitsLineChart(visitsData){
	var randomScalingFactor = function(){ return Math.round(Math.random()*100)};

	var barChartData = {
		labels : visitsData.data.dates,
		datasets : [
			{
				label: "Visitors",
				barShowStroke: false,
				fillColor : "rgba(75,178,1970,.5)",
				strokeColor : "rgba(75,178,1970,.5)",
				highlightFill: "rgba(220,220,220,0.75)",
				highlightStroke: "rgba(220,220,220,1)",
				data : visitsData.data.visitors
			},
			{
				label: "Visits",
				barShowStroke: false,
				fillColor : "rgba(234,162,40,0.5)",
				strokeColor : "rgba(234,162,40,0.5)",
				highlightFill : "rgba(151,187,205,0.75)",
				highlightStroke : "rgba(151,187,205,1)",
				data : visitsData.data.visits
			}
		]

	}
	var ctx = document.getElementById("visitorsVisitsChart").getContext("2d");
	window.myBar = new Chart(ctx).Bar(barChartData, {
		responsive : true
	});
}
//------------------------------------------------------------------------------


function drawBrowsersBieChart(browsersData){
    var brsBieChartData = [];
    var container = jQuery('#browsersLegContainer');
    var html = '';
	
	if(browsersData.length == 0)
	{
	document.getElementById('browsersLegContainer').style.display = 'none';	
	}else{
	document.getElementById('browsersLegContainer').style.display = 'block';		
	}
	
    for(var i = 0; i < browsersData.length; i++){
        var color = getRandomElementFromArray(colors);
        var value = Number(browsersData[i].hits);
        brsBieChartData[i] = {label: browsersData[i].bsr_name, value: value, color: color};
        html += (isEmpty(value))? '' : '<div class="legend">' +
                    '<span class="color" style="background-color: ' + color + ';">&nbsp;&nbsp;</span>' +
                    '<span class="name">' + browsersData[i].bsr_name + '</span>' +
                    '<span class="value">' + value + '</span>' +
                '</div>';
    }
    html += '<div class="cleaner"></div>';
    container.html(html);
    var ctx = document.getElementById("brsBiechartContainer").getContext("2d");
    /*var myPie = new Chart(ctx,{
		type: 'pie',
		data: brsBieChartData,
		options: {responsive : true}
	});*/
    window.myPie = new Chart(ctx).Pie(brsBieChartData, {responsive : true});
}

function drawCountriesPieChart(countriesData){
    var countriesPieChartData = [];
    
    var container = jQuery('#countriesLegContainer');
    var html = '';
	
    if(countriesData.length == 0)
    {
        document.getElementById('countriesLegContainer').style.display = 'none';	
    }else{
        document.getElementById('countriesLegContainer').style.display = 'block';		
    }
	
    for(var i = 0; i < countriesData.length; i++){
        var color = getRandomElementFromArray(colors);
        var value = Number(countriesData[i].visits);
        countriesPieChartData[i] = {label: countriesData[i].ctr_name, value: value, color: color};
        html += (isEmpty(value))? '' : '<div class="legend">' +
                    '<span class="color" style="background-color: ' + color + ';">&nbsp;&nbsp;</span>' +
                    '<span class="name">' + countriesData[i].ctr_name + '</span>' +
                    '<span class="value">' + value + '</span>' +
                '</div>';
    }
    html += '<div class="cleaner"></div>';
    container.html(html);
    var ctx = document.getElementById("countriesPiechartContainer").getContext("2d");
    /*var myPie = new Chart(ctx,{
		type: 'pie',
		data: countriesPieChartData,
		options: {responsive : true}
	});*/
    window.myPie = new Chart(ctx).Pie(countriesPieChartData, {responsive : true});
}

//------------------------------------------------------------------------------
function drawSrhEngVstLineChart(f){
    var srh_series = [];
    var container = jQuery('#srchEngLegContainer');
    var html = '';
	document.getElementById('srchEngLegContainer').style.display = 'none';
	
    for(var index in srhEngVisitsData.data.search_engines){
        var color = getRandomElementFromArray(colors);
        var value = countVisits(srhEngVisitsData.data.search_engines[index]);
		
		if(parseFloat(value) !=0)
		{
		document.getElementById('srchEngLegContainer').style.display = 'block';	
		}
		
        srh_series[srh_series.length] = {
                                        "label": index,
                                        "value": value,
                                        "color": color
                                        }
                                        
        html += (isEmpty(value))? '' : '<div class="legend">' +
                    '<span class="color" style="background-color: ' + color + ';">&nbsp;&nbsp;</span>' +
                    '<span class="name">' + index + '</span>' +
                    '<span class="value">' + value + '</span>' +
                '</div>';
    }
    html += '<div class="cleaner"></div>';
    container.html(html);
    var ctx = document.getElementById("srhEngBieChartContainer").getContext("2d");
    /*var myPie = new Chart(ctx,{
		type: 'pie',
		data: srh_series,
		options: {responsive : true}
	});*/
    window.myPie = new Chart(ctx).Pie(srh_series, {responsive : true});
}


function isEmpty(val){
    return (val == null || val == 0 || val == '' || val == '0');
}

//------------------------------------------------------------------------------
function countVisits(arr){
	var count = 0;
	for(var i = 0; i < arr.length; i++){
		count += Number(arr[i]);
	}
	return count;
}
//------------------------------------------------------------------------------

jQuery(document).ready(function () {
	if( !jQuery('#countriesPiechartContainer').length ){
		return;
	}
    //------------------------------------------
    //if(visitsData.success && typeof visitsData.data != 'undefined'){
	var duration = jQuery('#hits-duration').val();
    drawVisitsLineChart( mystart_date, myend_date, '1 day', visitors_data, visits_data, duration );
    //}
    //------------------------------------------
    if (browsersData.success && typeof browsersData.data != 'undefined' && typeof drawBrowsersBieChart === "function") {
        drawBrowsersBieChart(browsersData.data);
    }
    //------------------------------------------
    if (srhEngVisitsData.success && typeof srhEngVisitsData.data != 'undefined' && typeof drawSrhEngVstLineChart === "function") {
        drawSrhEngVstLineChart(srhEngVisitsData);
    }
    //------------------------------------------
    if (countriesData.success && typeof countriesData.data != 'undefined' && typeof drawCountriesPieChart === "function" ) {
        drawCountriesPieChart(countriesData.data);
    }
    //------------------------------------------
    jQuery.fn.dataTable.ext.errMode = 'none';
    /* pagination, export and search feature related jquery */
	if(jQuery('#traffic_by_title').length)
	{
		jQuery('#traffic_by_title').DataTable({
			"pageLength":10,
			"searching": true,
			"ordering": false,
			"bLengthChange": false,
			"bFilter": true,
			"bInfo": false,
			"bAutoWidth": false,
			"bJQueryUI": true,
			"processing": true,
			"serverSide": true,
			ajax: ahc_ajax.ajax_url+'?action=traffic_by_title',
			dataSrc: 'data',
			columns: [
				{ data: 'rank' },
				{ data: 'til_page_title' },
				{ data: 'til_hits' },
				{ data: 'percent' }
			],
			language: {
				searchPlaceholder: "Title",
				processing: "<span class='loader'>&nbsp;</span>",
				"zeroRecords": "No data available.",
				paginate: {
				  next: '<i class="dashicons dashicons-arrow-right-alt2"></i>',
				  previous: '<i class="dashicons dashicons-arrow-left-alt2"></i>'
				}
			 },
			"fnDrawCallback": function(oSettings) {
				if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
					jQuery(oSettings.nTableWrapper).find('.dataTables_paginate').hide();
				}
			},
			dom: 'Bfrtip',
			buttons: [{
				extend: 'excelHtml5',
				title:"",
				action: function (e, dt, node, config) {
					jQuery("#traffic_by_title").parents(".panelcontent").find(".dataTables_processing").show();
					jQuery.ajax({
					url:  ahc_ajax.ajax_url+'?action=traffic_by_title&page=all',
					data: dt.ajax.params(),
					success: function(res, status, xhr) {
						//console.log(res);
						
						var createXLSLFormatObj = [];

						/* XLS Head Columns */
						var xlsHeader = ["Rank", "Title","Hits","Percentage"];

						/* XLS Rows Data */
						var xlsRows = JSON.parse(res);
											
						createXLSLFormatObj.push(xlsHeader);
						jQuery.each(xlsRows, function(index, value) {
							var innerRowData = [];
							jQuery.each(value, function(ind, val) {
								innerRowData.push(val);
							});
							createXLSLFormatObj.push(innerRowData);
						});
						jQuery("#traffic_by_title").parents(".panelcontent").find(".dataTables_processing").hide();
						/* File Name */
						var filename = "traffic_by_title.xlsx";

						/* Sheet Name */
						var ws_name = "sheet1";

						if (typeof console !== 'undefined') console.log(new Date());
						var wb = XLSX.utils.book_new(),
							ws = XLSX.utils.aoa_to_sheet(createXLSLFormatObj);

						/* Add worksheet to workbook */
						XLSX.utils.book_append_sheet(wb, ws, ws_name);

						/* Write workbook and Download */
						if (typeof console !== 'undefined') console.log(new Date());
						XLSX.writeFile(wb, filename);
						if (typeof console !== 'undefined') console.log(new Date());

					}
				})
			}
			
			}]
		});
	}
	if(jQuery('#lasest_search_words').length)
	{
		latestSearchTable();
	}
	function latestSearchTable()
	{
		jQuery('#lasest_search_words').DataTable({
			"pageLength": 10,
			"searching": false,
			"ordering": false,
			"bLengthChange": false,
			"bFilter": true,
			"bInfo": false,
			"bJQueryUI": true,
			"processing": true,
			"serverSide": true,
			ajax: ahc_ajax.ajax_url+'?action=latest_search_words&fdt='+jQuery("#from_dt").val()+"&tdt="+jQuery("#to_dt").val(),
			dataSrc: 'data',
			columnDefs: [{
			  targets: 1,
			  className: 'hide'
			}],
			columns: [
				{ data: 'img' },
				{ data: 'csb' },
				{ data: 'keyword' },
				{ data: 'dt' },
			],
			language: {
				processing: "<span class='loader'>&nbsp;</span>",
				"zeroRecords": "No data available.",
				paginate: {
				  next: '<i class="dashicons dashicons-arrow-right-alt2"></i>',
				  previous: '<i class="dashicons dashicons-arrow-left-alt2"></i>'
				}
			 },
			"fnDrawCallback": function(oSettings) {
				if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
					jQuery(oSettings.nTableWrapper).find('.dataTables_paginate').hide();
				}
			},
			dom: 'Bfrtip',
			buttons: [{
				extend: 'excelHtml5',
				title:"",
				action: function (e, dt, node, config) {
					jQuery("#lasest_search_words").parents(".panelcontent").find(".dataTables_processing").show();
					jQuery.ajax({
					url:  ahc_ajax.ajax_url+'?action=latest_search_words&page=all&fdt='+jQuery("#from_dt").val()+"&tdt="+jQuery("#to_dt").val(),
					data: dt.ajax.params(),
					success: function(res, status, xhr) {
						//console.log(res);
		
						var createXLSLFormatObj = [];

						/* XLS Head Columns */
						var xlsHeader = ["Country/SE/Browser", "Keyword","Date"];

						/* XLS Rows Data */
						var xlsRows = JSON.parse(res);
											
						createXLSLFormatObj.push(xlsHeader);
						jQuery.each(xlsRows, function(index, value) {
							var innerRowData = [];
							jQuery.each(value, function(ind, val) {
								innerRowData.push(val);
							});
							createXLSLFormatObj.push(innerRowData);
						});
						jQuery("#lasest_search_words").parents(".panelcontent").find(".dataTables_processing").hide();
						/* File Name */
						var filename = "latest_search_words.xlsx";

						/* Sheet Name */
						var ws_name = "sheet1";

						if (typeof console !== 'undefined') console.log(new Date());
						var wb = XLSX.utils.book_new(),
							ws = XLSX.utils.aoa_to_sheet(createXLSLFormatObj);

						/* Add worksheet to workbook */
						XLSX.utils.book_append_sheet(wb, ws, ws_name);

						/* Write workbook and Download */
						if (typeof console !== 'undefined') console.log(new Date());
						XLSX.writeFile(wb, filename);
						if (typeof console !== 'undefined') console.log(new Date());

					}
				})
				},
				exportOptions: {
					columns: [1,2,3]
				},
			}],
			
		});
		
	}
	if(jQuery('#traffic_by_countries').length)
	{
		jQuery('#traffic_by_countries').DataTable({
			"pageLength": 10,
			"searching": false,
			"ordering": false,
			"bLengthChange": false,
			"bFilter": true,
			"bInfo": false,
			"bAutoWidth": false,
			"bJQueryUI": true,
			"processing": true,
			"serverSide": true,
			ajax: ahc_ajax.ajax_url+'?action=traffic_by_countries',
			dataSrc: 'data',
			columns: [
				{ data: 'rank' },
				{ data: 'flag' },
				{ data: 'ctr_name' },
				{ data: 'visitors' },
				{ data: 'visits' }
			],
			language: {
				processing: "<span class='loader'>&nbsp;</span>",
				"zeroRecords": "No data available.",
				paginate: {
				  next: '<i class="dashicons dashicons-arrow-right-alt2"></i>',
				  previous: '<i class="dashicons dashicons-arrow-left-alt2"></i>'
				}
			 },
			"fnDrawCallback": function(oSettings) {
				if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
					jQuery(oSettings.nTableWrapper).find('.dataTables_paginate').hide();
				}
			},
			dom: 'Bfrtip',
			buttons: [{
				extend: 'excelHtml5',
				title:"",
				action: function (e, dt, node, config) {
					jQuery("#traffic_by_countries").parents(".panelcontent").find(".dataTables_processing").show();
					jQuery.ajax({
						url:  ahc_ajax.ajax_url+'?action=traffic_by_countries&page=all',
						data: dt.ajax.params(),
						success: function(res, status, xhr) {
							//console.log(res);
			
							var createXLSLFormatObj = [];

							/* XLS Head Columns */
							var xlsHeader = ["Rank","Country", "Visitors","Visits"];

							/* XLS Rows Data */
							var xlsRows = JSON.parse(res);
												
							createXLSLFormatObj.push(xlsHeader);
							jQuery.each(xlsRows, function(index, value) {
								var innerRowData = [];
								jQuery.each(value, function(ind, val) {
									innerRowData.push(val);
								});
								createXLSLFormatObj.push(innerRowData);
							});
							jQuery("#traffic_by_countries").parents(".panelcontent").find(".dataTables_processing").hide();
							
							/* File Name */
							var filename = "traffic_by_countries.xlsx";

							/* Sheet Name */
							var ws_name = "sheet1";

							if (typeof console !== 'undefined') console.log(new Date());
							var wb = XLSX.utils.book_new(),
								ws = XLSX.utils.aoa_to_sheet(createXLSLFormatObj);

							/* Add worksheet to workbook */
							XLSX.utils.book_append_sheet(wb, ws, ws_name);

							/* Write workbook and Download */
							if (typeof console !== 'undefined') console.log(new Date());
							XLSX.writeFile(wb, filename);
							if (typeof console !== 'undefined') console.log(new Date());

						}
					})
				},
				exportOptions: {
					columns: [0,2,3,4]
				},
			}]
		});
	}
	if(jQuery('#top_refering_sites').find("tr").length > 1)
	{
		jQuery('#top_refering_sites').DataTable({
			"pageLength": 10,
			"searching": false,
			"ordering": false,
			"bLengthChange": false,
			"bFilter": true,
			"bInfo": false,
			"bAutoWidth": false,
			language: {
				paginate: {
				  next: '<i class="dashicons dashicons-arrow-right-alt2"></i>',
				  previous: '<i class="dashicons dashicons-arrow-left-alt2"></i>'
				}
			 },
			"fnDrawCallback": function(oSettings) {
				if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
					jQuery(oSettings.nTableWrapper).find('.dataTables_paginate').hide();
				}
			},
			dom: 'Bfrtip',
			buttons: [{
				extend: 'excelHtml5',
				title:""
			}]
		});
	}
	if(jQuery('#recent_visit_by_ip').length )
	{
		recentVisiroeByIPTable();
	}
	
	
	function recentVisiroeByIPTable()
	{
		jQuery('#recent_visit_by_ip').DataTable({
			"pageLength": 10,
			"searching": false,
			"ordering": false,
			"bLengthChange": false,
			"bFilter": true,
			"bInfo": false,
			"bAutoWidth": false,
			"bJQueryUI": true,
			"processing": true,
			"serverSide": true,
			"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
			ajax: ahc_ajax.ajax_url+'?action=recent_visitor_by_ip&fdt='+jQuery("#r_from_dt").val()+"&tdt="+jQuery("#r_to_dt").val()+"&ip="+jQuery("#ip_addr").val(),
			dataSrc: 'data',
			columns: [
				{ data: 'hit_ip_address' },
				{ data: 'ctr_name' },
				
				//{ data: 'ahc_city' },
				//{ data: 'ahc_region' },
				//{ data: 'bsr_name' },
				{ data: 'time' },
				{ data: 'day_hits' }
			],
			language: {
				processing: "<span class='loader'>&nbsp;</span>",
				"zeroRecords": "No data available.",
				paginate: {
				  next: '<i class="dashicons dashicons-arrow-right-alt2"></i>',
				  previous: '<i class="dashicons dashicons-arrow-left-alt2"></i>'
				}
			 },
			"fnDrawCallback": function(oSettings) {
				if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
					jQuery(oSettings.nTableWrapper).find('.dataTables_paginate').hide();
				}
			},
			dom: 'Bfrtip',
			buttons: [{
				extend: 'excelHtml5',
				title:"",
				action: function (e, dt, node, config) {
					jQuery("#recent_visit_by_ip").parents(".panelcontent").find(".dataTables_processing").show();
					jQuery.ajax({
					url:  ahc_ajax.ajax_url+'?action=recent_visitor_by_ip&page=all&fdt='+jQuery("#r_from_dt").val()+"&tdt="+jQuery("#r_to_dt").val()+"&ip="+jQuery("#ip_addr").val(),
					data: dt.ajax.params(),
					success: function(res, status, xhr) {
						//console.log(res);
		
						var createXLSLFormatObj = [];

						/* XLS Head Columns */
						var xlsHeader = ["IP Address", "Location","Time"];

						/* XLS Rows Data */
						var xlsRows = JSON.parse(res);
											
						createXLSLFormatObj.push(xlsHeader);
						jQuery.each(xlsRows, function(index, value) {
							var innerRowData = [];
							jQuery.each(value, function(ind, val) {
								innerRowData.push(val);
							});
							createXLSLFormatObj.push(innerRowData);
						});
						jQuery("#recent_visit_by_ip").parents(".panelcontent").find(".dataTables_processing").hide();
						/* File Name */
						var filename = "recent_visitor_by_ip.xlsx";

						/* Sheet Name */
						var ws_name = "sheet1";

						if (typeof console !== 'undefined') console.log(new Date());
						var wb = XLSX.utils.book_new(),
							ws = XLSX.utils.aoa_to_sheet(createXLSLFormatObj);

						/* Add worksheet to workbook */
						XLSX.utils.book_append_sheet(wb, ws, ws_name);

						/* Write workbook and Download */
						if (typeof console !== 'undefined') console.log(new Date());
						XLSX.writeFile(wb, filename);
						if (typeof console !== 'undefined') console.log(new Date());
					
					}
				})
			}
			}]
		});
		
		
		jQuery('#DayHitsModal').on('show.bs.modal', function (event) {
  var button = jQuery(event.relatedTarget) // Button that triggered the modal
  var hitipaddress = button.data('hitipaddress') // Extract info from data-* attributes
  var hitcountry =  button.data('hitcountry')
  var hitdate = button.data('hitdate')
   jQuery('.modal-body').html('<div class="ahc_loader" style="width:100px !important; height:50px !important;">&nbsp;Loading..</div>');
			
  // AJAX request
			   jQuery.ajax({
				url:  ahc_ajax.ajax_url+'?action=ip_history',
				type: 'post',
				data: {hitipaddress: hitipaddress, hitdate:hitdate, hitcountry:hitcountry},
				success: function(response){ 
				  // Add response in Modal body
				 jQuery('.modal-body').html(response);

				}
			  })
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = jQuery(this)
 modal.find('.modal-title').text('IP Traking')
// modal.find('.modal-body input').val(hitipaddress)
});
		
		jQuery('#DayHitsModal').on('hide.bs.modal', function (event) {
 jQuery('.modal-body').html('<div class="ahc_loader" style="width:100px !important; height:50px !important;">&nbsp;</div>');
});	
		
			
		
	}
	if(jQuery('#visit_time_graph_table').length)
	{
		visitTimeGraphTable();
	}
	function visitTimeGraphTable()
	{
		jQuery('#visit_time_graph_table').DataTable({
			"pageLength": 10,
			"searching": false,
			"ordering": false,
			"bLengthChange": false,
			"bFilter": true,
			"bInfo": false,
			"bAutoWidth": false,
			"bJQueryUI": true,
			"processing": true,
			"serverSide": true,
			ajax: ahc_ajax.ajax_url+'?action=visits_time_graph&fdt='+jQuery("#vfrom_dt").val()+"&tdt="+jQuery("#vto_dt").val(),
			dataSrc: 'data',
			columns: [
				{ data: 'time' },
				{ data: 'graph' },
				{ data: 'vtm_visitors' },
				{ data: 'vtm_visits' }
			],
			language: {
				processing: "<span class='loader'>&nbsp;</span>",
				"zeroRecords": "No data available.",
				paginate: {
				  next: '<i class="dashicons dashicons-arrow-right-alt2"></i>',
				  previous: '<i class="dashicons dashicons-arrow-left-alt2"></i>'
				}
			 },
			"fnDrawCallback": function(oSettings) {
				if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
					jQuery(oSettings.nTableWrapper).find('.dataTables_paginate').hide();
				}
			},
			dom: 'Bfrtip',
			buttons: [{
				extend: 'excelHtml5',
				title:"",
				action: function (e, dt, node, config) {
					jQuery("#visit_time_graph_table").parents(".panelcontent").find(".dataTables_processing").show();
					jQuery.ajax({
						url:  ahc_ajax.ajax_url+'?action=visits_time_graph&page=all&fdt='+jQuery("#vfrom_dt").val()+"&tdt="+jQuery("#vto_dt").val(),
						data: dt.ajax.params(),
						success: function(res, status, xhr) {
							//console.log(res);
			
							var createXLSLFormatObj = [];

							/* XLS Head Columns */
							var xlsHeader = ["Time","Visitors","Visits","Graph"];

							/* XLS Rows Data */
							var xlsRows = JSON.parse(res);
												
							createXLSLFormatObj.push(xlsHeader);
							jQuery.each(xlsRows, function(index, value) {
								var innerRowData = [];
								jQuery.each(value, function(ind, val) {
									innerRowData.push(val);
								});
								createXLSLFormatObj.push(innerRowData);
							});
							jQuery("#visit_time_graph_table").parents(".panelcontent").find(".dataTables_processing").hide();
							/* File Name */
							var filename = "visits_time_graph.xlsx";

							/* Sheet Name */
							var ws_name = "sheet1";

							if (typeof console !== 'undefined') console.log(new Date());
							var wb = XLSX.utils.book_new(),
								ws = XLSX.utils.aoa_to_sheet(createXLSLFormatObj);

							/* Add worksheet to workbook */
							XLSX.utils.book_append_sheet(wb, ws, ws_name);

							/* Write workbook and Download */
							if (typeof console !== 'undefined') console.log(new Date());
							XLSX.writeFile(wb, filename);
							if (typeof console !== 'undefined') console.log(new Date());

						}
					})
				},
			}]
		});
		
	}
	if(jQuery('#today_traffic_index_by_country').length )
	{
		trafficByIndexCountryTable();
	}
	function trafficByIndexCountryTable()
	{
		jQuery('#today_traffic_index_by_country').DataTable({
			"pageLength": 10,
			"searching": false,
			"ordering": false,
			"bLengthChange": false,
			"bFilter": true,
			"bInfo": false,
			"bAutoWidth": false,
			"bJQueryUI": true,
			"processing": true,
			"serverSide": true,
			ajax: ahc_ajax.ajax_url+'?action=today_traffic_index&fdt='+jQuery("#t_from_dt").val()+"&tdt="+jQuery("#t_to_dt").val(),
			dataSrc: 'data',
			columns: [
				{ data: 'no' },
				{ data: 'country' },
				{ data: 'ctr_name' },
				{ data: 'total' },
			],
			language: {
				processing: "<span class='loader'>&nbsp;</span>",
				"zeroRecords": "No data available.",
				paginate: {
				  next: '<i class="dashicons dashicons-arrow-right-alt2"></i>',
				  previous: '<i class="dashicons dashicons-arrow-left-alt2"></i>'
				}
			 },
			"fnDrawCallback": function(oSettings) {
				if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
					jQuery(oSettings.nTableWrapper).find('.dataTables_paginate').hide();
				}
			},
			dom: 'Bfrtip',
			buttons: [{
				extend: 'excelHtml5',
				title:"",
				action: function (e, dt, node, config) {
					jQuery("#today_traffic_index_by_country").parents(".panelcontent").find(".dataTables_processing").show();
					jQuery.ajax({
						url:  ahc_ajax.ajax_url+'?action=today_traffic_index&page=all&fdt='+jQuery("#t_from_dt").val()+"&tdt="+jQuery("#t_to_dt").val(),
						data: dt.ajax.params(),
						success: function(res, status, xhr) {
							//console.log(res);
			
							var createXLSLFormatObj = [];

							/* XLS Head Columns */
							var xlsHeader = ["No","Country", "Total"];

							/* XLS Rows Data */
							var xlsRows = JSON.parse(res);
												
							createXLSLFormatObj.push(xlsHeader);
							jQuery.each(xlsRows, function(index, value) {
								var innerRowData = [];
								jQuery.each(value, function(ind, val) {
									innerRowData.push(val);
								});
								createXLSLFormatObj.push(innerRowData);
							});
							jQuery("#today_traffic_index_by_country").parents(".panelcontent").find(".dataTables_processing").hide();
							
							/* File Name */
							var filename = "today_traffic_index.xlsx";

							/* Sheet Name */
							var ws_name = "sheet1";

							if (typeof console !== 'undefined') console.log(new Date());
							var wb = XLSX.utils.book_new(),
								ws = XLSX.utils.aoa_to_sheet(createXLSLFormatObj);

							/* Add worksheet to workbook */
							XLSX.utils.book_append_sheet(wb, ws, ws_name);

							/* Write workbook and Download */
							if (typeof console !== 'undefined') console.log(new Date());
							XLSX.writeFile(wb, filename);
							if (typeof console !== 'undefined') console.log(new Date());

						}
					})
				},
				exportOptions: {
					columns: [0,2,3]
				},
			}]
		});
	
	}
	if(jQuery('#summary_statistics').find("tr").length > 1)
	{
		jQuery('#summary_statistics').DataTable({
			"pageLength": 100,
			"searching": false,
			"ordering": false,
			"bPaginate": false,
			"bLengthChange": false,
			"bFilter": true,
			"bInfo": false,
			"bAutoWidth": false,
			"fnDrawCallback": function(oSettings) {
				if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
					jQuery(oSettings.nTableWrapper).find('.dataTables_paginate').hide();
				}
			},
			dom: 'Bfrtip',
			buttons: [{
				extend: 'excelHtml5',
				title:""
			}]
		});
	
	}
	if(jQuery('#search_engine').find("tr").length > 1)
	{
		jQuery('#search_engine').DataTable({
			"pageLength": 100,
			"searching": false,
			"ordering": false,
			"bPaginate": false,
			"bLengthChange": false,
			"bFilter": true,
			"bInfo": false,
			"bAutoWidth": false,
			"fnDrawCallback": function(oSettings) {
				if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
					jQuery(oSettings.nTableWrapper).find('.dataTables_paginate').hide();
				}
			},
			dom: 'Bfrtip',
			buttons: [{
				extend: 'excelHtml5',
				title:""
			}]
		});
	
	}
	
	jQuery(".export_data a").click(function(e) {
		e.preventDefault();
		jQuery(this).parents(".panel").find(".dt-buttons").find(".dt-button").trigger("click");
		
	})
	
	var dateFormat = "mm-dd-yy";
	if(jQuery("#from_dt").length && jQuery("#to_dt").length)
	{
      	var from = jQuery( "#from_dt" ).datepicker({
         	defaultDate: 0,
         	dateFormat:"mm-dd-yy",
          	numberOfMonths: 1
        });
        
        
      	var to = jQuery( "#to_dt" ).datepicker({
        	defaultDate: 0,
        	dateFormat:"mm-dd-yy",
        	numberOfMonths: 1
      	});
    }
    
	
	if(jQuery("#summary_from_dt").length && jQuery("#summary_to_dt").length)
	{
      	var from = jQuery( "#summary_from_dt" ).datepicker({
         	defaultDate: 0,
         	dateFormat:"yy-mm-dd",
          	numberOfMonths: 1
        });
        
        
      	var to = jQuery( "#summary_to_dt" ).datepicker({
        	defaultDate: 0,
        	dateFormat:"yy-mm-dd",
        	numberOfMonths: 1
      	});
    }
	
	
    jQuery( "#to_dt" ).on( "change", function() {
    	from.datepicker( "option", "maxDate", getDate( this ) );
  	});
  	
  	jQuery("#from_dt").on( "change", function() {
      	to.datepicker( "option", "minDate", getDate( this ) );
    });
    
    if(jQuery("#t_from_dt").length && jQuery("#t_to_dt").length)
	{
      	var t_from_dt = jQuery( "#t_from_dt" ).datepicker({
         	defaultDate: 0,
         	dateFormat:"mm-dd-yy",
          	numberOfMonths: 1
        });
        
        
      	var t_to_dt = jQuery( "#t_to_dt" ).datepicker({
        	defaultDate: 0,
        	dateFormat:"mm-dd-yy",
        	numberOfMonths: 1
      	});
    }
    
    jQuery( "#t_to_dt" ).on( "change", function() {
    	t_from_dt.datepicker( "option", "maxDate", getDate( this ) );
  	});
  	
  	jQuery("#t_from_dt").on( "change", function() {
      	t_to_dt.datepicker( "option", "minDate", getDate( this ) );
    });
    
    if(jQuery("#vfrom_dt").length && jQuery( "#vto_dt" ).length)
    {
		var vfrom = jQuery( "#vfrom_dt" ).datepicker({
			defaultDate: 0,
			dateFormat:"mm-dd-yy",
			numberOfMonths: 1
		});
		
		var vto = jQuery( "#vto_dt" ).datepicker({
        	defaultDate: 0,
        	dateFormat:"mm-dd-yy",
        	numberOfMonths: 1
      	});
	}
	
    jQuery( "#vto_dt" ).on( "change", function() {
    	vfrom.datepicker( "option", "maxDate", getDate( this ) );
  	});
  	
  	jQuery("#vfrom_dt").on( "change", function() {
      	vto.datepicker( "option", "minDate", getDate( this ) );
    });
    
    if(jQuery("#r_from_dt").length && jQuery( "#r_to_dt" ).length)
    {
		var vfrom = jQuery( "#r_from_dt" ).datepicker({
			defaultDate: 0,
			dateFormat:"mm-dd-yy",
			numberOfMonths: 1
		});
		
		var vto = jQuery( "#r_to_dt" ).datepicker({
        	defaultDate: 0,
        	dateFormat:"mm-dd-yy",
        	numberOfMonths: 1
      	});
	}
	
    jQuery( "#r_to_dt" ).on( "change", function() {
    	vfrom.datepicker( "option", "maxDate", getDate( this ) );
  	});
  	
  	jQuery("#r_from_dt").on( "change", function() {
      	vto.datepicker( "option", "minDate", getDate( this ) );
    });
  	
	
	function getDate( element ) {
		var date;
		try {
			date = jQuery.datepicker.parseDate( dateFormat, element.value );
		} catch( error ) {
			date = null;
		}
		return date;
 	}
 	jQuery(".search-panel .search_frm").submit(function(e){
		e.preventDefault();
		var tableID = jQuery(this).parents(".panel").find(".panelcontent").find("table").attr("id");
		
		
		if(tableID=="recent_visit_by_ip")
		{
			jQuery('#'+tableID).DataTable().destroy();
			recentVisiroeByIPTable();
			return false;
		}
		else if(tableID=="today_traffic_index_by_country")
		{
			jQuery('#'+tableID).DataTable().destroy();
			trafficByIndexCountryTable();
			return false;
		}
		else if(tableID=="lasest_search_words")
		{
			jQuery('#'+tableID).DataTable().destroy();
			latestSearchTable();
			return false;
		}
		else if(tableID=="visit_time_graph_table")
		{
			jQuery('#'+tableID).DataTable().destroy();
			visitTimeGraphTable();
			return false;
		}
		else
			return true;	
			
		
	});
 	jQuery(".clear_form").click(function(e){
		jQuery(this).parents("form").find(".ahc_clear").val("");
		jQuery(this).parents("form").submit();
	});
	
	jQuery(".search_data a").click(function(e){
		e.preventDefault();
		if(jQuery(this).parents(".panel").find(".search-panel").length)
			jQuery(this).parents(".panel").find(".search-panel").slideToggle();
		if(jQuery(this).parents(".panel").find(".dataTables_filter").length)
			jQuery(this).parents(".panel").find(".dataTables_filter").slideToggle();
	});
});
