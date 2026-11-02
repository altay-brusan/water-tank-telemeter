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
	header("location: dashboard.php");
	exit();
}


/*
ax==new record
ax1=get selectbox
av=delete record
ac1=edit record (get details)
ac2=edit record (update details)
ab=pump on/off
*/
//NewTank

if (isset($_POST['xa']) && $_POST['xa']=='ax') {
 	$motor_name=trim($_POST['newrecordName']);
 	$control=explode("-",$_POST['newrecordControl']);
 	$motor_controlbox_id=$control[0];
 	$motor_slot=$control[1];
 	if($motor_slot > 3){
 		$err[]='Geçersiz Port.';
 	}


 	$sql="SELECT `wm`.`motor_id` FROM `water_motors` AS `wm` WHERE `wm`.`motor_deleted`=0 AND `wm`.`motor_name`=".safe($motor_name);
 	$result=$mysqli->query($sql); 	
 	if($result->num_rows > 0){
	 	$err[]='Aynı isimde Su Motoru mevcut.';
 	}

 	$sql="SELECT `wc`.`controlbox_id` FROM `water_controlbox` AS `wc` WHERE `wc`.`controlbox_deleted`=0 AND `wc`.`controlbox_id`=".safe($motor_controlbox_id)." AND `wc`.`controlbox_slot_".$motor_slot."` IS NOT NULL";
 	$result=$mysqli->query($sql); 	
 	if($result->num_rows > 0){
	 	$err[]='Seçilen Port kullanımdadır.';
 	}
	
 	if(count($err)==0){
 		$column[]='`motor_name`';
 		$data[]=safe($motor_name);
 		$column[]='`motor_slot`';
 		$data[]=safe($motor_slot);
 		$column[]='`motor_controlbox_id`';
 		$data[]=safe($motor_controlbox_id); 		
 		
	 	$sql="INSERT INTO `water_motors` (".join(",",$column)." ) VALUES (".join(",",$data).")";
	 	if($mysqli->query($sql)){
	 		$sql="UPDATE `water_controlbox` AS `wc` SET `wc`.`controlbox_slot_".$motor_slot."`=".safe($mysqli->insert_id)." WHERE `wc`.`controlbox_id`=".safe($motor_controlbox_id)." LIMIT 1";
	 		if($mysqli->query($sql)){
		 		$status="success";
		 		$err[]='İşlem başarılı.';
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
 	}
 	else{
	 	$status="error";
 	}
 	echo json_encode(array('status'=>$status, 'messages'=>$err));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='ax1'){
	$q="SELECT `wc`.`controlbox_id` , `wc`.`controlbox_name` , `wc`.`controlbox_uniqueid` , `wc`.`controlbox_slot_1` , `wc`.`controlbox_slot_2` , `wc`.`controlbox_slot_3` FROM `water_controlbox` AS `wc` WHERE `wc`.`controlbox_deleted` = 0 ORDER BY `wc`.`controlbox_name` ASC";
	if($result=$mysqli->query($q)){
		$status="success";
		$x=0;
		$y=0;
		while($row=$result->fetch_assoc()){	
			for($i=1;$i<=3;$i++){
				$option[$y]= array('id'=>$row['controlbox_id'], 'port'=>$i, 'ops'=> ($row['controlbox_slot_'.$i]!=NULL ? 'disabled="disabled"' :'') ) ;
				$y++;
			}
			$optgroup[$x]=array('name'=> $row['controlbox_name'].' ('.$row['controlbox_uniqueid'].')', 'options'=>$option );
			$x++;
			$y=0;
		}
	}
	else{
		$status="error";
		$err[]=$mysqli->error;
	}
	echo json_encode(array('status'=>$status, 'messages'=>$err, 'options'=>$optgroup));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='av' && isset($_POST['id'])){
	$sql="SELECT `wm`.* FROM `water_motors` AS `wm` WHERE `wm`.`motor_deleted`=0 AND `wm`.`motor_id`=".safe($_POST['id']);
	$query=$mysqli->query($sql);
	if($query->num_rows ==0){
		$err[]='Geçersiz Su Motoru.';
	}
	
	if(count($err)==0){
		$result=$query->fetch_assoc();
		$sql="UPDATE `water_controlbox` AS `wc` SET `wc`.`controlbox_slot_".$result['motor_slot']."` = NULL WHERE `wc`.`controlbox_id`=".safe($result['motor_controlbox_id'])." LIMIT 1";
		if($mysqli->query($sql)){
			$sql="UPDATE `water_motors` AS `wm` SET `wm`.`motor_deleted`=1, `wm`.`motor_active`=0, `wm`.`motor_status`=0 WHERE `wm`.`motor_deleted`=0 AND `wm`.`motor_id`=".$_POST['id']." LIMIT 1";
			if($mysqli->query($sql)){
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
elseif(isset($_POST['xa']) && $_POST['xa']=='ac1' && isset($_POST['id'])){
	$q="SELECT `wm`.`motor_id` AS `mid`, `wm`.`motor_name` AS `mname`,`wc`.`controlbox_name` AS `cname`, `wc`.`controlbox_uniqueid` AS `cunq`, `wc`.`controlbox_id` AS `cid`, `wm`.`motor_slot` AS `mport` FROM `water_motors` AS `wm` LEFT JOIN `water_controlbox` AS `wc` ON (`wm`.`motor_controlbox_id` = `wc`.`controlbox_id`) 
	WHERE `wm`.`motor_deleted`=0 AND `wm`.`motor_id`=".safe($_POST['id'])." LIMIT 1";
	$query=$mysqli->query($q);
	if($query->num_rows ==0){
		$err[]='Geçersiz Su Motoru.';
	}
	
	if(count($err)==0){
		$status="success";
		$err=$query->fetch_assoc();
		$q="SELECT `wc`.`controlbox_id` , `wc`.`controlbox_name` , `wc`.`controlbox_uniqueid` , `wc`.`controlbox_slot_1` , `wc`.`controlbox_slot_2` , `wc`.`controlbox_slot_3` FROM `water_controlbox` AS `wc` WHERE `wc`.`controlbox_deleted` = 0 ORDER BY `wc`.`controlbox_name` ASC";
		if($result=$mysqli->query($q)){
			$status="success";
			$x=0;
			$y=0;
			while($row=$result->fetch_assoc()){	
				for($i=1;$i<=3;$i++){
					$option[$y]= array(
						'id'=>$row['controlbox_id'],
						'port'=>$i,
						'ops'=> (
									$row['controlbox_slot_'.$i]!=NULL ? 'disabled="disabled"'. 
									( 
										($row['controlbox_id']==$err['motor_controlbox_id'] && $row['controlbox_slot_'.$i]==$err['motor_id'])  ? ' selected':'' 
									)
								:'') 
					) ;
					$y++;
				}
				$optgroup[$x]=array('name'=> $row['controlbox_name'].' ('.$row['controlbox_uniqueid'].')', 'options'=>$option );
				$x++;
				$y=0;
			}
		}
		else{
			$status="error";
			$err[]=$mysqli->error;
		}
	}else{
		$status="error";
	}
	echo json_encode(array('status'=>$status, 'data'=>$err,'options'=>$optgroup));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='ac2' && !empty($_POST['editrecordId'])){
	$record_name=trim($_POST['editrecordName']);

	$q="SELECT `wm`.* FROM `water_motors` AS `wm` WHERE `wm`.`motor_deleted`=0 AND `wm`.`motor_id`=".safe($_POST['editrecordId']);
	$result=$mysqli->query($q);
	if($result->num_rows ==0){
		$err[]='Geçersiz Su Tankı.';
	}
	else{
		$row=$result->fetch_assoc();
	}
	
	$q="SELECT `wm`.* FROM `water_motors` AS `wm` WHERE  `wm`.`motor_deleted`=0 AND `wm`.`motor_name`=".safe($_POST['editrecordName'])." AND `wm`.`motor_id`!=".safe($_POST['editrecordId']);
	$query=$mysqli->query($q);
	if($query->num_rows > 0){
		$err[]='Aynı isimde Su Tankı mevcut.';
	}
	
	if(count($err)==0){
		$q_add[]=" `wm`.`motor_name`=".safe($record_name);
		
		if(isset($_POST['editrecordControl'])){
			$control=explode("-",$_POST['editrecordControl']);
 			$motor_controlbox_id=$control[0];
 			$motor_slot=$control[1];
 			$sql="UPDATE `water_controlbox` AS `wc` SET `wc`.`controlbox_slot_".$row['motor_slot']."`= NULL WHERE `wc`.`controlbox_id`=".safe($row['motor_controlbox_id'])." LIMIT 1 ";
 			$mysqli->query($sql);
 			$sql="UPDATE `water_controlbox` AS `wc` SET `wc`.`controlbox_slot_".$motor_slot."`= ".$row['motor_id']." WHERE `wc`.`controlbox_id`=".safe($motor_controlbox_id)." LIMIT 1 ";
 			$mysqli->query($sql);
 			$q_add[]=" `wm`.`motor_controlbox_id`=".safe($motor_controlbox_id);
 			$q_add[]=" `wm`.`motor_slot`=".safe($motor_slot);
		}
		
		$q="UPDATE `water_motors` AS `wm` SET ".join(",",$q_add)." WHERE `wm`.`motor_deleted`=0 AND  `wm`.`motor_id`=".safe($_POST['editrecordId'])." LIMIT 1";
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
elseif(isset($_POST['xa']) && $_POST['xa']=='ab' && isset($_POST['id']) && isset($_POST['ac'])){
	$q="SELECT `wc`.`controlbox_uniqueid` , `wm`.`motor_id` , `wm`.`motor_slot` FROM `water_motors` AS `wm` LEFT JOIN `water_controlbox` AS `wc` ON( `wm`.`motor_controlbox_id` = `wc`.`controlbox_id`) WHERE `wm`.`motor_deleted` = 0 AND `wm`.`motor_id` = ".safe($_POST['id']);
	$query=$mysqli->query($q);
	if($query->num_rows ==0){
		$err[]='Geçersiz Su Motoru.';
	}
	if(count($err)==0){
		$row=$query->fetch_assoc();
		if($_POST['ac']=='start'){
			$status=1;
			$faction="'ON':".$row['motor_slot'];
			$naction=",'OFF':";
		}
		elseif($_POST['ac']=='stop'){
			$status=0;
			$faction="'ON':";
			$naction=",'OFF':".$row['motor_slot'];
		}
		$sql="UPDATE `water_motors` AS `wm` SET `wm`.`motor_status`=".safe($status).", `wm`.`motor_last_action` = NOW() WHERE `wm`.`motor_deleted`=0 AND `wm`.`motor_id`=".safe($_POST['id'])." LIMIT 1";
		if($mysqli->query($sql)){
			$client = new Mosquitto\Client(MQTT_CLIENT);
			$client->setCredentials(MQTT_USER, MQTT_PASS);
			$client->connect("localhost", 1883, 60);
			$result=$client->publish("water-tanks/".$row['controlbox_uniqueid']."/Action", "{".$faction.$naction."}", 1, false);
			if($result>0){
				$status='success';
				$err="İşlem başarılı.";
			}
			else{
				$status='error';
				$err="unknown error.";
			}
			$client->disconnect();
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
						<li class="nav-item"> <a class="nav-link" href="water_wells.php"><i class="fa fa-fw fa-1x py-1 fa-bitbucket"></i> Su Kuyuları</a> </li>
						<li class="nav-item"> <a class="nav-link active" href="water_pumps.php"><i class="fa fa-fw fa-1x py-1 fa-chrome"></i> Su Motorları</a> </li>
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
						<h3 class="" contenteditable="true">Su Motorları</h3>
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
								<b>Yeni Motor Ekle</b>
							</a>
							<table id="dyntable" class="table text-nowrap p-1">
							    <thead>
							        <tr>
							            <th>Motor Adı</th>
							            <th>Kontrol Kutusu</th>
							            <th>Port</th>
							            <th>Motor Durumu</th>
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
		<div class="modal" id="modal-newrecord" data-target="pumps" aria-labelledby="modal-newrecordLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Su Motoru Ekle</h5> <button type="button" class="close" data-dismiss="modal"> <span>×</span> </button>
					</div>
					<form id="newrecord" action="water_pumps.php" method="POST">
						<input type="hidden" name="xa" value="ax">
						<div class="modal-body">
							<div class="form-group row"> <label for="newrecordName" class="col-6 col-form-label">Motor Adı</label>
								<div class="col-6"><input type="text" class="form-control" id="newrecordName" name="newrecordName" placeholder="Motor Adı" required> </div>
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
		<div class="modal" id="modal-editrecord" data-target="pumps" aria-labelledby="modal-editrecordLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Su Motoru Düzenle</h5> <button type="button" class="close" data-dismiss="modal"> <span>×</span> </button>
					</div>
					<form id="editrecord" action="water_pumps.php" method="POST">
						<input type="hidden" name="xa" value="ac2">
						<input type="hidden" id="editrecordId" name="editrecordId" value="">
						<div class="modal-body">
							<div class="form-group row"> <label for="editrecordName" class="col-6 col-form-label">Motor Adı</label>
								<div class="col-6"><input type="text" class="form-control" id="editrecordName" name="editrecordName"  required> </div>
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
					lengthMenu: [ 15,25, 50, 75, 100 ],
					ajax: { 
						url:"./lists.php",
						method:"POST",
						data:{xa:'ac',cp:<?php echo $user_result['user_companyid']?>}
					},
					order: [[ 0, "asc" ], [2, "asc"], [3, "desc"]],
					columnDefs: [
						{ searchable:false, targets:[2,3,4]},
						{ orderable: false, targets: 4 },
					],
					select: true,
					drawCallback: function( settings ) {
						$("input[id^='status-']").bootstrapToggle();
					},
					scrollY: "600px",
					scrollCollapse: true,
					paging: false
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
								$('#editrecordId').val(result.data.mid);
								$('#editrecordName').val(result.data.mname);
								$('optgroup', '#editrecordControl').remove();
								var option ='';
								if(result.options != null){
									result.options.forEach(function(item){
										option+='<optgroup label="'+item.name+'">';
										item.options.forEach(function(opitem){
											option+='<option value="'+opitem.id+'-'+opitem.port+'" '+opitem.ops+'>'+item.name+' Port '+opitem.port+'</option>';
										});
										option+='</optgroup>';
									});
									$('#editrecordControl').append(option);
								}
								$('#editrecordControl').append(new Option(result.data.cname, result.data.cid+'-'+result.data.mport, true));
									$('#editrecordControl').val(result.data.cid+'-'+result.data.mport);
									$('#editrecordControl :selected').attr('class','x');
									
									
									
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
	require_once("footer.php");
}
	?>