<?php

// Initialize the session
session_start();
include("includes/config.php") ;

// Check if the user is logged in, if not then redirect him to login page



if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
$sql="SELECT `wu`.`user_permission` FROM `water_users` AS `wu` WHERE `wu`.`user_id`=".safe($_SESSION['uid']);
$user_query=$mysqli->query($sql);
$user_result=$user_query->fetch_assoc();
if($user_result['user_permission'] != 1){
	header("location: dashboard.php");
	exit();
}


/*
ax==new record
av=delete record
ac1=edit record (get details)
ac2=edit record (update details)

*/
//NewWell
if (isset($_POST['xa']) && $_POST['xa']=='ax') {
 	$well_name=trim($_POST['newrecordName']);
 	$well_capacity=$_POST['newrecordCapacity'];
 	$well_controlbox=$_POST['newrecordControl'];

 	$q="SELECT `wt`.`tank_id` FROM `water_tanks` AS `wt` WHERE `wt`.`tank_type`=1 AND `wt`.`tank_deleted`=0 AND `wt`.`tank_name`=".safe($well_name);
 	$result=$mysqli->query($q); 	
 	if($result->num_rows > 0){
	 	$err[]='Aynı isimde Su Kuyusu mevcut.';
 	}

 	$q="SELECT `wt`.`tank_id` FROM `water_tanks` AS `wt` WHERE `wt`.`tank_type`=1 AND `wt`.`tank_deleted`=0 AND `wt`.`tank_controlbox_id`=".safe($well_controlbox);
 	$result=$mysqli->query($q); 	
 	if($result->num_rows > 0){
	 	$err[]='Seçilen kontrol kutusu kullanımdadır.';
 	}
	
 	if(count($err)==0){
	 	$q="INSERT INTO `water_tanks` (`tank_name`, `tank_capacity`, `tank_controlbox_id`, `tank_type` ) VALUES (".safe($well_name).",".safe($well_capacity).",".safe($well_controlbox).",1)";
	 	if($mysqli->query($q)){
		 	$q="INSERT INTO `water_tanks_level_history` (`tank_id`, `tank_level`) VALUES (".$mysqli->insert_id.",0)";
		 	if($mysqli->query($q)){
				$status="success";
				$err[]='İşlem başarılı';
			}
			else{
				$status="error";
				$err[]=$mysqli->error;
			}
 	 	}
		else{
		 	$status="error";
	 		$err[]=$mysqli->error;
	 	}
 	}else{
	 	$status="error";
 	}
 	echo json_encode(array('status'=>$status, 'messages'=>$err));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='ax1'){
	$q="SELECT  `wc`.`controlbox_id`,  `wc`.`controlbox_name`, `wc`.`controlbox_uniqueid` FROM `water_controlbox` AS `wc` WHERE `wc`.`controlbox_deleted`=0 AND `wc`.`controlbox_id` NOT IN (SELECT `wt`.`tank_controlbox_id` FROM `water_tanks` AS `wt` WHERE `wt`.`tank_deleted`=0) ORDER BY `wc`.`controlbox_name` ASC";
	if($result=$mysqli->query($q)){
		$status="success";
		while($row=$result->fetch_assoc()){	
			$options[]=array('name'=>$row['controlbox_name'], 'id'=>$row['controlbox_id'], 'unq'=> $row['controlbox_uniqueid']);
		}
	}else{
		$status="error";
		$err[]=$mysqli->error;
	}
	echo json_encode(array('status'=>$status, 'messages'=>$err, 'options'=>$options));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='av' && !empty($_POST['id'])){
	$q="SELECT `wt`.* FROM `water_tanks` AS `wt` WHERE `wt`.`tank_type`=1 AND `wt`.`tank_deleted`=0 AND `wt`.`tank_id`=".safe($_POST['id']);
	$query=$mysqli->query($q);
	if($query->num_rows ==0){
		$err[]='Geçersiz Su Kuyusu.';
	}
	
	if(count($err)==0){
		$q="UPDATE `water_tanks` AS `wt` SET `wt`.`tank_deleted`=1, `wt`.`tank_controlbox_id`=0, `wt`.`tank_active`=0 WHERE `wt`.`tank_type`=1 AND `wt`.`tank_deleted`=0 AND `wt`.`tank_id`=".$_POST['id']." LIMIT 1";
		if($mysqli->query($q)){
	 		$status="success";
	 		$err[]='İşlem başarılı';
	 	}
		else{
		 	$status="error";
	 		$err[]=$mysqli->error;
	 	}	
	}else{
		$status="error";
	}	
	echo json_encode(array('status'=>$status, 'messages'=>$err));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='ac1' && !empty($_POST['id'])){
	$q="SELECT `wt`.`tank_id` AS `tid`, `wt`.`tank_name` AS `tname`, `wt`.`tank_capacity` AS `tcap`, `wt`.`tank_controlbox_id` AS `tcid`, `wc`.`controlbox_name` AS `cname`, `wc`.`controlbox_uniqueid` AS `cunq`
	FROM `water_tanks` AS `wt` LEFT JOIN `water_controlbox` AS `wc` ON (`wt`.`tank_controlbox_id` = `wc`.`controlbox_id`)
 WHERE `wt`.`tank_type`=1 AND `wt`.`tank_deleted`=0 AND `wt`.`tank_id`=".safe($_POST['id'])." LIMIT 1";
	$query=$mysqli->query($q);
	if($query->num_rows ==0){
		$err[]='Geçersiz Su Kuyusu.';
	}
	
	if(count($err)==0){
		$status="success";
		$err=$query->fetch_assoc();
		$q="SELECT  `wc`.`controlbox_id`,  `wc`.`controlbox_name`, `wc`.`controlbox_uniqueid` FROM `water_controlbox` AS `wc` WHERE `wc`.`controlbox_deleted`=0 AND `wc`.`controlbox_id` NOT IN (SELECT `wt`.`tank_controlbox_id` FROM `water_tanks` AS `wt` WHERE `wt`.`tank_deleted`=0) ORDER BY `wc`.`controlbox_name` ASC";
		$result=$mysqli->query($q);
		while($row=$result->fetch_assoc()){
			$options[]=array('name'=>$row['controlbox_name'], 'id'=>$row['controlbox_id'], 'unq'=> $row['controlbox_uniqueid']);
		}
	}else{
		$status="error";
	}
	echo json_encode(array('status'=>$status, 'data'=>$err, 'options'=>$options));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='ac2' && !empty($_POST['editrecordId'])){
	$q="SELECT `wt`.* FROM `water_tanks` AS `wt` WHERE `wt`.`tank_type`=1 AND `wt`.`tank_deleted`=0 AND `wt`.`tank_id`=".safe($_POST['editrecordId']);
	$query=$mysqli->query($q);
	if($query->num_rows ==0){
		$err[]='Geçersiz Su Kuyusu.';
	}
	
	$q="SELECT `wt`.* FROM `water_tanks` AS `wt`  WHERE `wt`.`tank_type`=1 AND `wt`.`tank_deleted`=0 AND `wt`.`tank_name`=".safe($_POST['editrecordName'])." AND `wt`.`tank_id`!=".safe($_POST['editrecordId']);
	$query=$mysqli->query($q);
	if($query->num_rows > 0){
		$err[]='Aynı isimde Su Kuyusu mevcut.';
	}
	
	if(count($err)==0){
		$q_add[]=" `wt`.`tank_name`=".safe($_POST['editrecordName']);
		$q_add[]=" `wt`.`tank_capacity`=".safe($_POST['editrecordCapacity']);
		$q_add[]=" `wt`.`tank_controlbox_id`=".safe($_POST['editrecordControl']);
		$q="UPDATE `water_tanks` AS `wt` SET ".join(",",$q_add)." WHERE `wt`.`tank_type`=1 AND `wt`.`tank_deleted`=0 AND `wt`.`tank_id`=".safe($_POST['editrecordId'])." LIMIT 1";
	 	if($mysqli->query($q)){
	 		$status="success";
	 		$err[]='İşlem başarılı.';
	 	}else{
		 	$status="error";
	 		$err[]=$mysqli->error;
	 	}
	}else{
		$status="error";
	}
	echo json_encode(array('status'=>$status, 'messages'=>$err));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='ac3' && isset($_POST['id']) && !empty($_POST['status']) ){
	$sql="SELECT `wt`.* FROM `water_tanks` AS `wt` WHERE `wt`.`tank_type`=1 AND `wt`.`tank_deleted`=0 AND `wt`.`tank_id`=".safe($_POST['id']);
	$query=$mysqli->query($sql);
	if($query->num_rows ==0){
		$err[]='Geçersiz Su Kuyusu.';
	}
	
	if(count($err)==0){
		$sql="UPDATE `water_tanks` AS `wt` SET `wt`.`tank_active`=".safe(($_POST['status']=='true' ? 1:0))." WHERE `wt`.`tank_type`=1 AND `wt`.`tank_deleted`=0 AND `wt`.`tank_id`=".safe($_POST['id'])." LIMIT 1";
		if($mysqli->query($sql)){
			$status="success";
			$err[]="İşlem başarılı.";
		}
		else{
			$status="error";
			$err[]=$mysqli->error;
		}
	}
	else{
		$status="error";
	}
	echo json_encode(array('status'=>$status, 'messages'=>$err));
}	
else{
	require_once("header.php");
?>
	<body>
		<nav class="navbar navbar-expand-lg navbar-light bg-light">
			<div class="container"> <a class="navbar-brand text-primary" href="#">
				<b> Turhal Belediyesi</b>
				</a> <button class="navbar-toggler navbar-toggler-right border-0" type="button" data-toggle="collapse" data-target="#navbar5">
				<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbar5">
					<ul class="navbar-nav mr-auto">
						<li class="nav-item"> <a class="nav-link" href="dashboard.php"><i class="fa fa-fw fa-1x py-1 fa-line-chart"></i> Dashboard</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_tanks.php"><i class="fa fa-fw fa-1x py-1 fa-database"></i> Su Depoları</a> </li>
						<li class="nav-item"> <a class="nav-link active" href="water_wells.php"><i class="fa fa-fw fa-1x py-1 fa-bitbucket"></i> Su Kuyuları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_pumps.php"><i class="fa fa-fw fa-1x py-1 fa-chrome"></i> Su Motorları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_controls.php"><i class="fa fa-fw fa-1x py-1 fa-microchip"></i> Kontrol Kartları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_users.php"><i class="fa fa-fw fa-1x py-1 fa-users"></i> Kullanıcılar</a> </li>
						<li class="nav-item"> <a class="nav-link" href="#" data-toggle="modal" data-target="#modal-settings"><i class="fa fa-fw fa-1x py-1 fa-cog"></i> Ayarlar</a> </li>
						<li class="nav-item"> <a class="nav-link" href="logout.php"><i class="fa fa-fw fa-1x py-1 fa-sign-out"></i> Çıkış</a> </li>
					</ul>
				</div>
			</div>
		</nav>
		<div class="pb-2">
			<div class="container d-flex border-bottom">
				<div class="row">
					<div class="col-md-12">
						<h3 class="" contenteditable="true">Su Kuyuları</h3>
					</div>
				</div>
			</div>
		</div>
		<div class="">
			<div class="container" >
				<div class="row">
					<div class="col-12 p-1">

						<div class="table-responsive ">
							<a href="#" data-toggle="modal" data-target="#modal-newrecord" >
								<i class="fa fa-fw fa-1x py-1 fa-plus-square-o"></i>
								<b>Yeni Kuyu Ekle</b>
							</a>
							<table id="dyntable" class="table text-nowrap p-1">
							    <thead>
							        <tr>
							            <th>Kuyu Adı</th>
							            <!-- <th>Yükseklik (cm)</th>
							            <th>Doluluk Oranı (%)</th>-->
							            <th>Kuyu Durumu</th>
										<th>Kontrol Kutusu Durumu</th>
							            <th>Seçenekler</th>
							        </tr>
							    </thead>
							</table>
						</div>

					</div>
				</div>
			</div>
		</div>
		<!-- Modals:new -->
		<div class="modal" id="modal-newrecord" data-target="wells" aria-labelledby="modal-newrecordLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Yeni Kuyu</h5> <button type="button" class="close" data-dismiss="modal"> <span>×</span> </button>
					</div>
					<form id="newrecord" action="water_wells.php" method="POST">
						<input type="hidden" name="xa" value="ax">
						<div class="modal-body">
							<div class="form-group row"> <label for="newrecordName" class="col-6 col-form-label">Kuyu Adı</label>
								<div class="col-6"><input type="text" class="form-control" id="newrecordName" name="newrecordName" placeholder="Kuyu Adı" required> </div>
							</div>
							<div class="form-group row"> <label for="newrecordCapacity" class="col-6 col-form-label">Kuyu Yüksekliği (cm)</label>
								<div class="col-6"><input type="numeric" class="form-control" id="newrecordCapacity" name="newrecordCapacity" placeholder="Kuyu Kapasitesi" required> </div>
							</div>
							<div class="form-group row"> <label for="newrecordControl" class="col-6 col-form-label">Kontrol Kutusu</label>
				            	<div class="col-6"><select name="newrecordControl" id="newrecordControl"  class="custom-select custom-select-sm" required></select></div>
				            </div>

						</div>
						<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button> <button type="submit" class="btn btn-primary">Kaydet</button> </div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Modals:new -->
		
		<!-- Modals:edit -->
		<div class="modal" id="modal-editrecord" data-target="wells" aria-labelledby="modal-editrecordLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Su Kuyusu Düzenle</h5> <button type="button" class="close" data-dismiss="modal"> <span>×</span> </button>
					</div>
					<form id="editrecord" action="water_wells.php" method="POST">
						<input type="hidden" name="xa" value="ac2">
						<input type="hidden" id="editrecordId" name="editrecordId" value="">
						<div class="modal-body">
							<div class="form-group row"> <label for="editrecordName" class="col-6 col-form-label">Tank Adı</label>
								<div class="col-6"><input type="text" class="form-control" id="editrecordName" name="editrecordName"  required> </div>
							</div>
							<div class="form-group row"> <label for="editrecordCapacity" class="col-6 col-form-label">Tank Yüksekliği (cm)</label>
								<div class="col-6"><input type="text" class="form-control" id="editrecordCapacity" name="editrecordCapacity" required> </div>
							</div>
							<div class="form-group row"> <label for="editrecordControl" class="col-6 col-form-label">Kontrol Kutusu</label>
				            	<div class="col-6"><select name="editrecordControl" id="editrecordControl"  class="custom-select custom-select-sm" required></select> </div>
				            </div>
						</div>
						<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button> <button type="submit" class="btn btn-primary">Kaydet</button> </div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Modals:edit -->

		<script>
			$(document).ready(function() {
				
				var table=$('#dyntable').DataTable( {
					language: {
						url:"//cdn.datatables.net/plug-ins/1.10.21/i18n/Turkish.json"
					},
					processing: true,
					serverSide: true,
					info: false,
					lengthMenu: [ 25, 50, 75, 100 ],
					ajax: { 
						url:"./lists.php",
						method:"POST",
						data:{xa:'ax'}
					},
					order: [[ 0, "asc" ],  [3, "desc"]],
					columnDefs: [
						{ searchable:false, targets:[1]},
						{ orderable: false, targets: 3 },
					],
					select: true,
					drawCallback: function( settings ) {
						$("input[id^='stat']").bootstrapToggle();
					},
					scrollY: "600px",
					scrollCollapse: true,
					paging: false
				} );
				
				setInterval($('#dyntable').DataTable().ajax.reload, 30000); 
				
				$(document).on('click', '.edit-record', function(){
					var id = $(this).attr("id").substring(5);
					var target=$(this).attr("data-target");
					$.ajax({
						url:"water_"+target+".php",
						method:"POST",
						data:{xa:'ac1',id:id},
						success:function(data){
							var result=JSON.parse(data);
							if(result.status=='success'){
								$('#editrecord').trigger("reset");
								$('#editrecordId').val(result.data.tid);
								$('#editrecordName').val(result.data.tname);
								$('#editrecordCapacity').val(result.data.tcap);
								$('option', '#editrecordControl').remove();
								if(result.options != null){
									result.options.forEach(function(item){
										$('#editrecordControl').append(new Option(item.name+' ('+item.unq+')', item.id, false));
									});
								}
								if(result.data.tcid != 0){
									$('#editrecordControl').append(new Option(result.data.cname+' ('+result.data.cunq+')', result.data.tcid, true));
									$('#editrecordControl').val(result.data.tcid);
									$('#editrecordControl :selected').attr('class','x');
								}else
									$('#editrecordControl').val('');

								$('#modal-editrecord').modal('show');
							}
							else if(result.status =='error'){
								result.data.forEach(function(item){
									notify(item,'error');
								});
							}
						}
					});
				});
			});

		</script>
<?php
	require_once('footer.php');
}
?>