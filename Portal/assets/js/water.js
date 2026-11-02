function notify(msg,type){
	if(type == 'success'){
		$('#notify').addClass('alert-success');
		$('#notify').html('<i class="fa fa-fw fa-1x py-1 fa-check-circle"></i>'+msg);
	}	
	else if (type=='error'){
		$('#notify').addClass('alert-danger');
		$('#notify').html('<i class="fa fa-fw fa-1x py-1 fa-exclamation-circle"></i>'+msg);
	}	
    else if (type=='info'){
		$('#notify').addClass('alert-dark');
		$('#notify').html('<i class="fa fa-fw fa-1x py-1 fa-info-circle"></i>'+msg);
	}
	$('#notify').append('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');
	$('#notify').addClass('show');
	$('#notify').alert();
	
	setTimeout(function(){
		$('#notify').removeClass('show');
		$('#notify').removeClass('alert-success');
		$('#notify').removeClass('alert-danger');
		$('#notify').removeClass('alert-dark');
		$('#notify').html('');
	},2500); 
}

function tank_graphs(type){
	$("canvas[id^='tank-']").each(function(item){
		var tank = $(this).attr('id').substring(5);
		$.ajax({
			url: "dashboard.php",
			method: "POST",
			data:{xa:1,id:tank},
			success: function(data) {
				var result=JSON.parse(data);
				$('#last_level-'+tank).html('%'+result.last_level);
				$('#last_update-'+tank).html(result.last_update);
				if(type==1){
					result.motors.forEach(function(item){
						if(item.status==1){
							var color='bg-success';
							var icon='fa fa-fw fa-1x py-1 fa-pause';
							var status='stop';
							var last='çalışıyor';
						}
						else{
							var color='bg-danger';
							var icon='fa fa-fw fa-1x py-1 fa-play';
							var status='start';
							var last='çalışmıyor';
						}
						//$('#motor-'+tank+'-'+item.id).html('<a class="btn text-white" href="#" data-toggle="tooltip"  data-html="true" data-placement="bottom" title="<b>'+item.name+'</b> '+last+'"><i class="'+txtcolor+' fa fa-fw fa-1x py-1 fa-circle"></i></a>');
						$('#motor-'+tank+'-'+item.id).html('<div class="p-0" ><a role="button" class="btn '+color+' text-white" href="#" id="pump-'+item.id+'" data-target="'+status+'" data-toggle="tooltip" data-html="true" data-placement="bottom" title="<b>'+item.name+'</b><br>'+item.last+last+'"><i class="'+icon+'"></i></a></div>');
					});
				}
				if( result.levels != null){	
					var labels = [];
					var levels = [];
					result.labels.forEach(function(item){
						labels.push(item);
					});
					result.levels.forEach(function(item){
						levels.push(item);
					});
					var config = createConfig(levels, labels);
					var ctx = document.getElementById('tank-'+tank).getContext('2d');
					var Graph = new Chart(ctx,config);
				}
				$('[data-toggle="tooltip"]').tooltip();
			},
		});
	});

}

function tank_graphs_backup(type){
	$("canvas[id^='tank-']").each(function(item){
		var tank = $(this).attr('id').substring(5);
		$.ajax({
			url: "dashboard_x.php",
			method: "POST",
			data:{xa:1,id:tank},
			success: function(data) {

				var result=JSON.parse(data);
				if(result.last_level !=null)
					$('#last_level-'+tank).html('%'+result.last_level);
				if(result.last_update !=null)
					$('#last_update-'+tank).html(result.last_update);
				if(type==1){
// 					if(result.motors !=null){
						result.motors.forEach(function(item){
							console.log('motor = '+item.name+' Status = '+item.status);
							if(item.status==1){
								var color='bg-success';
								var txtcolor='text-success';
								var icon='fa fa-fw fa-1x py-1 fa-pause';
								var status='stop';
								var last='çalışıyor';
							}
							else{
								var color='bg-danger';
								var txtcolor='text-danger';
								var icon='fa fa-fw fa-1x py-1 fa-play';
								var status='start';
								var last='çalışmıyor';
							}
							$('#motor-'+tank+'-'+item.id).html('<a class="btn text-white" href="#" data-toggle="tooltip"  data-html="true" data-placement="bottom" title="<b>'+item.name+'</b> '+last+'"><i class="'+txtcolor+' fa fa-fw fa-1x py-1 fa-circle"></i></a>');
						});
// 					}
				}
				if( result.levels != null){	
					var labels = [];
					var levels = [];
					result.labels.forEach(function(item){
						labels.push(item);
					});
					result.levels.forEach(function(item){
						levels.push(item);
					});
					var config = createConfig(levels, labels);
					var ctx = document.getElementById('tank-'+tank).getContext('2d');
					var Graph = new Chart(ctx,config);
				}
				$('[data-toggle="tooltip"]').tooltip();
			},
		});
	});

}

function createConfig(datas,labels){
	return {
		type: 'line',
		//maintainAspectRatio: false,
		data: {
			datasets: [{
				label:'Seviye',
				backgroundColor: 'rgb(54, 162, 235)', //color(window.chartColors[colorName]).alpha(0.5).rgbString(),
				pointStyle:'circle',
				pointBackgroundColor:'#FFFFFF',
				data: datas,
			}],
			labels:labels
		},
		options: {
			legend: {
		        display: false
    		},
 			responsive:false,
			scales: {
				yAxes: [{
					type:'linear',
					position:'right',

					ticks: {
						min:0,
						max:100,
						stepSize:20,
						beginAtZero: true,
						fontColor:'#000000',
						fontSize:10,
						/*callback: function(value, index, values) {
                        	return '%' + value;
                   		}*/
					},
					gridLines:{
						color:'#000000',
						drawOnChartArea:true,
						zeroLineColor:'#000000',
					},
				}],
				xAxes: [{

					display: false,

				}],
			}
		},
		plugins: {
			zoom: {
				pan: {
					enabled: true,
					mode: 'xy',
				},
				zoom: {
					enabled: true,
					drag: true,
					mode: 'xy',
				}
			}
		},
	};
}

$(document).ready(function() {
	
	$('[data-toggle="tooltip"]').tooltip();
	
	 $('[id$="_level"]').on("input",  function(e) { 

		var name='#'+$(this).attr('id')+'_label';
		$(name).html('% '+$(this).val());
	});
	
	
	$('#newrecord').on("submit", function(e) {
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: $('form#newrecord').serialize(),
            cache: false,
            success: function(data) {
	            var result=JSON.parse(data);
	            if(result.status == 'success'){
					notify(result.messages,'success');
					$('#modal-newrecord').modal('hide');
					$('#dyntable').DataTable().ajax.reload();
				}
				else if(result.status =='error'){
					result.messages.forEach(function(item){
						notify(item,'error');
					});
				}
				else if(result.status =='info'){
					notify(result.messages,result.status);
					$('#modal-editrecord').modal('hide');
				}
            },
        });
        e.preventDefault();
    });

    $('#editrecord').on("submit", function(e) {
	    $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: $('form#editrecord').serialize(),
            cache: false,
            success: function(data) {
	            var result=JSON.parse(data);
	            if(result.status == 'success'){
					notify(result.messages,'success');
					$('#modal-editrecord').modal('hide');
					$('#dyntable').DataTable().ajax.reload();
				}
				else if(result.status =='error'){
					result.messages.forEach(function(item){
						notify(item,'error');
					});
				}
				else if(result.status =='info'){
					notify(result.messages,result.status);
					$('#modal-editrecord').modal('hide');
				}
            },
        });
        e.preventDefault();
	});
    
    $(document).on('click', '.delete-record', function(){
		var id = $(this).attr("id").substring(7);
		var target=$(this).attr("data-target");
		if(confirm("Kayıt silinecek, Emin misin?"))
		{
			$.ajax({
				url:"water_"+target+"_x.php",
				method:"POST",
				data:{xa:'av',id:id},
				success:function(data){
					var result=JSON.parse(data);
					if(result.status == 'success'){
						notify(result.messages,'success');
						$('#dyntable').DataTable().ajax.reload();
					}
					else if(result.status =='error'){
						result.messages.forEach(function(item){
							notify(item,'error');
						});
					}
				}
	    	});
	    }
	});
	
	$(document).on('click', '.disconnect', function(){
		var id = $('#editrecordId').val();
		var target=$(this).attr("data-target");
		if(confirm("Kontrol kutusu ile  Depo/Kuyu bağlantısı kesilecektir, Emin misin?"))
		{
			$.ajax({
				url:"water_"+target+"_x.php",
				method:"POST",
				data:{xa:'ac3',id:id},
				success:function(data){
					var result=JSON.parse(data);
					if(result.status == 'success'){
						notify(result.messages,'success');
						$('#dyntable').DataTable().ajax.reload();
					}
					else if(result.status =='error'){
						result.messages.forEach(function(item){
							notify(item,'error');
						});
					}
					else if(result.status =='info'){
						notify(result.messages,result.status);
						$('#modal-editrecord').modal('hide');
					}
				}
	    	});
	    }
	});
		
	$("#set").on("submit",  function(e) {
		console.log('Settings Submit');
		console.log($(this).serialize());
		$.ajax({
            url: "dashboard.php",
            type: "POST",
            data: $(this).serialize(),
            cache: false,
            success: function(data) {
				console.log(data);
				var result=JSON.parse(data);
				if(result.status=='success'){
					notify(result.messages,'success');
					$('#modal-settings').modal('hide');
				}
				else if(result.status =='error'){
					result.messages.forEach(function(item){
						notify(item,'error');
					});
				}
				else if(result.status =='info'){
					notify(result.messages,result.status);
					$('#modal-settings').modal('hide');
				}
			},
		});
		e.preventDefault();
	});
	
	$("body").on("click", "a[id^='pump-']" , function(e) {
	    var mid=$(this).attr('id').substring(5);
	    var action =$(this).attr('data-target');
 	    $.ajax({
            url: "water_pumps.php",
            type: "POST",
            data: {xa:'ab',id:mid,ac:action},
            cache: false,
            success: function(data) {
	            var result=JSON.parse(data);
	            if(result.status == 'success'){
					notify(result.messages,'success');
				}
				else if(result.status =='error'){
					result.messages.forEach(function(item){
						notify(item,'error');
					});
				}
				tank_graphs();
            },
        });
        e.preventDefault();
	});

	$("body").on("change","input[id^='state-']", function(){
		var id = $(this).attr('id').substring(6);
		var target=$(this).attr("data-target");
		var stat=$(this).prop('checked');
		$.ajax({
			url:"water_"+target+"_x.php",
			method:"POST",
			data:{xa:'ac3',id:id,status:stat},
			success:function(data){
				var result=JSON.parse(data);
				if(result.status == 'success'){
					notify(result.messages,'success');
					$('#dyntable').DataTable().ajax.reload();
				}
				else if(result.status =='error'){
					result.messages.forEach(function(item){
						notify(item,'error');
					});
				}
				else if(result.status =='info'){
					notify(result.messages,result.status);
					$('#modal-editrecord').modal('hide');
				}
			}
	    });
	});
	
/*
	$('#modal-settings').on('shown.bs.modal', function (e) { 
		$.ajax({
			url:"dashboard.php",
			method:"POST",
			data:{xa:3},
			success:function(data){
				console.log(data);
				var result=JSON.parse(data);
				if(result.status=='success'){
					$('#editrecordSid').val(result.messages.sid);
					$('#hi_level').val(result.messages.hi_level);
					$('#lo_level').val(result.messages.lo_level);
					
					$('#hi_level_label').html('%'+result.messages.hi_level);
					$('#lo_level_label').html('%'+result.messages.lo_level);
				}
				else if(result.status =='error'){
					result.data.forEach(function(item){
						notify(item,'error');
					});
				}
			},
		});
	});
*/
	
	$('#modal-newrecord').on('shown.bs.modal', function (e) { 
		var target=$(this).attr('data-target')
		$.ajax({
			url:"water_"+target+"_x.php",
			method:"POST",
			data:{xa:'ax1'},
			success:function(data){
				var result=JSON.parse(data);
				if(result.status == 'success'){
					var option ='';
					var supply='';
					if(target=='pumps'){
						$('optgroup', '#newrecordControl').remove();
						result.options.forEach(function(item){
							option+='<optgroup label="'+item.name+'">';
							item.options.forEach(function(opitem){
								option+='<option value="'+opitem.id+'-'+opitem.port+'" '+opitem.ops+'>'+item.name+' Port '+opitem.port+'</option>';
							});
							option+='</optgroup>';
						});
						$('#newrecordControl').append(option);
					}
					else if(target=='tanks'){
						if(result.options != null){
							$('option', '#newrecordControl').remove();
							result.options.forEach(function(item){
								option +='<option value="'+item.id+'">'+item.name+' ('+item.unq+')</option>';
							});
							$('#newrecordControl').append(option);
						}
						if(result.supply != null){
							$('option', '#newrecordSupply').remove();
							result.supply.forEach(function(item){
								supply +='<option value="'+item.id+'">'+item.name+'</option>';
							});
							$('#newrecordSupply').append(supply);
						}
					}
					else if(target =='wells'){
						if(result.options != null){
							$('option', '#newrecordControl').remove();
							result.options.forEach(function(item){
								option +='<option value="'+item.id+'">'+item.name+' ('+item.unq+')</option>';
							});
							$('#newrecordControl').append(option);
						}
					}
					else if(target =='users'){
						$('option', '#newrecordPerm').remove();
						result.options.forEach(function(item){
							option +='<option value="'+item.id+'">'+item.name+'</option>';
						});
						$('#newrecordPerm').append(option);
					}
				}
				else if(result.status =='error'){
					result.messages.forEach(function(item){
						notify(item,'error');
					});
				}
			}
		});
	});
	
	$('#modal-editrecord').on('hide.bs.modal', function (e) {
		$("option[class='x']").remove();
		$('#editrecord').trigger("reset");
	});
	
	$('#modal-newrecord').on('hide.bs.modal', function (e) {
		$('#newrecord').trigger("reset");
	});
});