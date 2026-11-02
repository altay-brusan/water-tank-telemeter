<?php

// Initialize the session
session_start();
include("includes/config.php") ;

// Check if the user is logged in, if not then redirect him to login page



if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$sql="SELECT `wu`.`user_permission`, `wu`.`user_companyid` FROM `water_users` AS `wu` WHERE `wu`.`user_id`=".safe($_SESSION['uid']);
$user_query=$mysqli->query($sql);
$user_result=$user_query->fetch_assoc();
if($user_result['user_permission'] != 1){
	if($_SESSION['cp']==1)
		header("location: dashboard_x.php");
	else
		header("location: dashboard.php");
	exit();
}

	


/*
ax==new record
av=delete record
ac1=edit record (get details)
ac2=edit record (update details)
ac3=disconnect controlbox from source/tank
`wc`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND 
*/
//NewControlbox
if (isset($_POST['xa']) && $_POST['xa']=='ax') {
 	$controlbox_name=trim($_POST['newrecordName']);
 	$controlbox_uniqueid=trim($_POST['newrecordUnq']);
	
	$sql="SELECT `wc`.`controlbox_id` FROM `water_controlbox` AS `wc` WHERE `wc`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND `wc`.`controlbox_deleted`=0 AND `wc`.`controlbox_uniqueid`=".safe($controlbox_uniqueid);
 	$result=$mysqli->query($sql); 	
 	if($result->num_rows > 0){
	 	$err[]='Kontrol Kutusu mevcut.';
 	}
	
	$sql="SELECT `wc`.`controlbox_id` FROM `water_controlbox` AS `wc` WHERE `wc`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND `wc`.`controlbox_deleted`=0 AND `wc`.`controlbox_name`=".safe($controlbox_name);
 	$result=$mysqli->query($sql); 	
 	if($result->num_rows > 0){
	 	$err[]='Aynı isimde Kontrol Kutusu mevcut.';
 	}

 	if(count($err)==0){
	 	$sqlx="INSERT INTO `water_controlbox` (`controlbox_companyid`, `controlbox_name`, `controlbox_uniqueid`) VALUES (".safe($user_result['user_companyid']).", ".safe($controlbox_name).",".safe($controlbox_uniqueid).")";
	 	if($mysqli->query($sqlx)){
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
elseif(isset($_POST['xa']) && $_POST['xa']=='ax1'){
	echo json_encode(array('status'=>'success', 'messages'=>$err));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='av' && !empty($_POST['id'])){
	$sql="SELECT `wc`.* FROM `water_controlbox` AS `wc` WHERE `wc`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND `wc`.`controlbox_deleted`=0 AND `wc`.`controlbox_id`=".safe($_POST['id']);
	$query=$mysqli->query($sql);
	if($query->num_rows ==0){
		$err[]='Geçersiz Kontrol Kutusu.';
	}
	
	if(count($err)==0){
		$q[]="UPDATE `water_controlbox` AS `wc` SET `wc`.`controlbox_active`=0, `wc`.`controlbox_deleted`=1, `wc`.`controlbox_slot_1`=NULL, `wc`.`controlbox_slot_2`=NULL, `wc`.`controlbox_slot_3`=NULL WHERE `wc`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND `wc`.`controlbox_deleted`=0 AND `wc`.`controlbox_id`=".safe($_POST['id'])." LIMIT 1";
		$q[]="UPDATE `water_tanks` AS `wt` SET `wt`.`tank_controlbox_id`=0 WHERE `wt`.`tank_companyid`=".safe($user_result['user_companyid'])." AND `wt`.`tank_deleted`=0 AND `wt`.`tank_controlbox_id`=".safe($_POST['id'])." LIMIT 1";
		$q[]="UPDATE `water_wells` AS `ww` SET `ww`.`well_controlbox_id`=0 WHERE `ww`.`well_companyid`=".safe($user_result['user_companyid'])." AND `ww`.`well_deleted`=0 AND `wt`.`well_controlbox_id`=".safe($_POST['id'])." LIMIT 1";
		$q[]="UPDATE `water_motors` AS `wm` SET `wm`.`motor_controlbox_id`=0 WHERE `wm`.`motor_companyid`=".safe($user_result['user_companyid'])." AND `wm`.`motor_deleted`=0 AND `wm`.`motor_controlbox_id`=".safe($_POST['id'])." LIMIT 3";
		for($i=0;$i <count($q);$i++){
			$mysqli->query($q[$i]);
		}
		$status="success";
		$err[]='İşlem başarılı.';
	}
	else{
		$status="error";
	}
	echo json_encode(array('status'=>$status, 'messages'=>$err));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='ac1' && !empty($_POST['id'])){
	$q="SELECT `wc`.`controlbox_name` AS `cname`, `wc`.`controlbox_uniqueid` AS `cunq`, `wc`.`controlbox_active` AS `cac`, `wt`.`tank_name` AS `tname`, `wt`.`tank_id` AS `tid`, `wc`.`controlbox_id` AS `cid`
 FROM `water_controlbox` AS `wc` LEFT JOIN `water_tanks` AS `wt` ON (`wc`.`controlbox_id`=`wt`.`tank_controlbox_id`)
 WHERE `wc`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND `wc`.`controlbox_deleted`=0 AND `controlbox_id`=".safe($_POST['id'])." LIMIT 1 ";
	$query=$mysqli->query($q);
	if($query->num_rows ==0){
		$err[]='Geçersiz Kontrol Kutusu.';
	}
	
	if(count($err)==0){
		$status="success";
		$err=$query->fetch_assoc();
	}
	else{
		$status="error";
	}
	echo json_encode(array('status'=>$status, 'data'=>$err));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='ac2' && !empty($_POST['editrecordId'])){
	$q="SELECT `wc`.* FROM `water_controlbox` AS `wc` WHERE `wc`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND `wc`.`controlbox_deleted`=0 AND `wc`.`controlbox_id`=".safe($_POST['editrecordId']);
	$query=$mysqli->query($q);
	if($query->num_rows ==0){
		$err[]='Geçersiz Kontrol Kutusu.';
	}
	
	$q="SELECT `wc`.* FROM `water_controlbox` AS `wc` WHERE `wc`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND `wc`.`controlbox_deleted`=0 AND `wc`.`controlbox_name`=".safe($_POST['editrecordName'])." AND `wc`.`controlbox_id`!=".safe($_POST['editrecordId']);
	$query=$mysqli->query($q);
	if($query->num_rows > 0){
		$err[]='Aynı isimde Kontrol Kutusu mevcut.';
	}
	
	$q="SELECT `wc`.* FROM `water_controlbox` WHERE `wc`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND `wc`.`controlbox_deleted`=0 AND `wc`.`controlbox_uniqueid`=".safe($_POST['editrecordUnq'])." AND `wc`.`controlbox_id`!=".safe($_POST['editrecordId']);
	$query=$mysqli->query($q);
	if($query->num_rows > 0){
		$err[]='Kontrol Kutusu mevcut.';
	}
	
	if(count($err)==0){
		$q_add[]=" `wc`.`controlbox_name`=".safe($_POST['editrecordName']);
		$q_add[]=" `wc`.`controlbox_uniqueid`=".safe($_POST['editrecordUnq']);
		$q="UPDATE `water_controlbox` AS `wc` SET ".join(",",$q_add)." WHERE `wc`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND `wc`.`controlbox_deleted`=0 AND `controlbox_id`=".safe($_POST['editrecordId'])." LIMIT 1" ;
	 	if($mysqli->query($q)){
	 		$status="success";
	 		$err[]='İşlem başarılı.';
	 	}else{
		 	$status="error";
	 		$err[]=$mysqli->error;
	 	}
	}
	else{
		$status="error";
	}
	echo json_encode(array('status'=>$status, 'messages'=>$err));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='ac3' && !empty($_POST['id'])){
	$q="SELECT `wc`.* FROM `water_controlbox` AS `wc` WHERE `wc`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND `wc`.`controlbox_deleted`=0 AND `wc`.`controlbox_id`=".safe($_POST['id']);
	$query=$mysqli->query($q);
	if($query->num_rows ==0){
		$err[]='Geçersiz Kontrol Kutusu.';
	}
	
	if(count($err)==0){
		$status=($_POST['status']=='true' ? 1:0);
		$mysqli->query("UPDATE `water_tanks` AS `wt` SET `wt`.`tank_controlbox_id`=0 WHERE `wt`.`tank_companyid`=".safe($user_result['user_companyid'])." AND `wt`.`tank_deleted`=0 AND `wt`.`tank_controlbox_id`=".safe($_POST['id'])." LIMIT 1");
		$mysqli->query("UPDATE `water_wells` AS `ww` SET `ww`.`well_controlbox_id`=0 WHERE `ww`.`well_companyid`=".safe($user_result['user_companyid'])." AND `ww`.`well_deleted`=0 AND `ww`.`well_controlbox_id`=".safe($_POST['id'])." LIMIT 1");
 		$status="success";
 		$err[]='İşlem başarılı';
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
						<li class="nav-item"> <a class="nav-link" href="dashboard<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-line-chart"></i> Dashboard</a> </li>				
						<li class="nav-item"> <a class="nav-link" href="water_tanks<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-database"></i> Su Depoları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_wells<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-bitbucket"></i> Su Kuyuları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_pumps<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-chrome"></i> Su Motorları</a> </li>
						<li class="nav-item"> <a class="nav-link active" href="water_controls<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-microchip"></i> Kontrol Kartları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_users<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-users"></i> Kullanıcılar</a> </li>
						<!--<li class="nav-item"> <a class="nav-link" href="#" data-toggle="modal" data-target="#modal-settings"><i class="fa fa-fw fa-1x py-1 fa-cog"></i> Ayarlar</a> </li>-->
						<li class="nav-item"> <a class="nav-link" href="logout.php"><i class="fa fa-fw fa-1x py-1 fa-sign-out"></i> Çıkış</a> </li>
					</ul>
				</div>
			</div>
		</nav>
		<div class="pb-2">
			<div class="container d-flex border-bottom">
				<div class="row">
					<div class="col-md-12">
						<h3 class="" contenteditable="true">Kontrol Kartları</h3>
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
								<b>Yeni Kontrol Kutusu Ekle</b>
							</a>
							<table id="dyntable" class="table text-nowrap p-1">
							    <thead>
							         <tr>
							            <th>Kutu Adı</th>
							            <th>Kutu Kodu</th>
							            <th>Kutu Durumu</th>
							            <th>Sinyal Kalitesi</th>
										<th>Son Kontrol</th>
										<th>Su Deposu / Su Kuyusu</th>
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
		<div class="modal" id="modal-newrecord" data-target="controls" aria-labelledby="modal-newrecordLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Yeni Kontrol Kutusu</h5> <button type="button" class="close" data-dismiss="modal"> <span>×</span> </button>
					</div>
					<form id="newrecord" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">
						<input type="hidden" name="xa" value="ax">
						<div class="modal-body">
							<div class="form-group row"> <label for="newrecordName" class="col-6 col-form-label">Kutu Adı</label>
								<div class="col-6"><input type="text" class="form-control" id="newrecordName" name="newrecordName" placeholder="Kutu Adı" required> </div>
							</div>
							<div class="form-group row"> <label for="newrecordUnq" class="col-6 col-form-label">Kutu kodu</label>
								<div class="col-6"><input type="numeric" class="form-control" id="newrecordUnq" name="newrecordUnq"  required> </div>
							</div>
							<div class="form-group row"> <label for="newrecordSrc" class="col-6 col-form-label">Depo / Kuyu</label>
				            	<div class="col-6"><select name="newrecordSrc" id="newrecordSrc"  class="custom-select custom-select-sm"  disabled></select></div>
				            </div>
							<div class="form-group row"> <label for="newrecordActive" class="col-6 col-form-label">Kutu Durumu</label>
				            	<div class="col-6"><input name="newrecordActive" id="newrecordActive"  class="x" type="checkbox" data-size="small"  data-width="75" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" disabled></div>
				            </div>

						</div>
						<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button> <button type="submit" class="btn btn-primary">Kaydet</button> </div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Modals:new -->

		<!-- Modals:edit -->
		<div class="modal" id="modal-editrecord" data-target="controls" aria-labelledby="modal-editrecordLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Kontrol Kutusu Düzenle</h5> <button type="button" class="close" data-dismiss="modal"> <span>×</span> </button>
					</div>
					<form id="editrecord" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">
						<input type="hidden" name="xa" value="ac2">
						<input type="hidden" id="editrecordId" name="editrecordId" value="">
						<div class="modal-body">
							<div class="form-group row"> <label for="editrecordName" class="col-6 col-form-label">Kutu Adı</label>
								<div class="col-6"><input type="text" class="form-control" id="editrecordName" name="editrecordName"  required> </div>
							</div>
							<div class="form-group row"> <label for="editrecordUnq" class="col-6 col-form-label">Kutu Kodu</label>
								<div class="col-6"><input type="text" class="form-control" id="editrecordUnq" name="editrecordUnq" required> </div>
							</div>
							<div class="form-group row"> <label for="editrecordSrc" class="col-6 col-form-label">Depo / Kuyu</label>
				            	<div class="col-4"><select name="editrecordSrc" id="editrecordSrc"  class="custom-select custom-select-sm"  disabled></select></div>
								<div class="col-2"><a href="#" id="disconnect" class="btn btn-danger btn-sm disconnect" role="button" data-target="controls"><i class="fa fa-fw fa-1x py-1 fa-trash"></i></a></div>
				            </div>
							<div class="form-group row"> <label for="editrecordActive" class="col-6 col-form-label">Kutu Durumu</label>
				            	<div class="col-6"><input  id="editrecordActive" type="checkbox" data-size="small"  data-width="75" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" ></div>
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
						data:{xa:'av',cp:<?php echo $user_result['user_companyid']?>}
					},
					order: [[0, "asc"], [2, "desc"], [ 4, "desc" ],],
					columnDefs: [
						{ searchable:false, targets:[2,4]},
						{ orderable: false, targets: 5 },
					],
					select: true,
					drawCallback: function( settings ) {
						$("input[id^='status-']").bootstrapToggle();
						//feather.replace();
					}
				} );
				
				setInterval($('#dyntable').DataTable().ajax.reload, 30000); 
				
				$(document).on('click', '.edit-record', function(){
					var id = $(this).attr("id").substring(5);
					$.ajax({
						url:"<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>",
						method:"POST",
						data:{xa:'ac1',id:id},
						success:function(data){
							var result=JSON.parse(data);
							if(result.status=='success'){
								$('#editrecord').trigger("reset");
								$('#editrecordId').val(result.data.cid);
								$('#editrecordName').val(result.data.cname);
								$('#editrecordUnq').val(result.data.cunq);
								if(result.data.cac == 1){
									$('#editrecordActive').attr('checked','checked');
									$('#editrecordActive').bootstrapToggle('on');				
								}else{
									
									$('#editrecordActive').removeAttr('checked');
									$('#editrecordActive').bootstrapToggle('off');
								}
								$('#editrecordSrc').empty();
								if(result.data.tid != null){
									$('#editrecordSrc').append(new Option(result.data.tname, result.data.tid));
									$('#editrecordSrc').val(result.data.tid);
									$('#disconnect').show();
								}else{
									$('#disconnect').hide();
								}
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
				
			} );

		</script>
<?php
	require_once('footer.php');
}
	?>