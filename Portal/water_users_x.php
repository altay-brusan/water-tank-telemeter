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
ax1=get selectbox
av=delete record
ac1=edit record (get details)
ac2=edit record (update details)
ab=pump on/off
`wu`.`user_companyid`=".safe($user_result['user_companyid'])." AND 
*/
//NewTank

if (isset($_POST['xa']) && $_POST['xa']=='ax') {
 	$user_name=trim($_POST['newrecordName']);
 	$user_lastname=trim($_POST['newrecordLast']);
 	$user_email=trim($_POST['newrecordEmail']);
 	$user_password=trim($_POST['newrecordPass']);
 	$user_permission=trim($_POST['newrecordPerm']);
	
 	if(empty($user_password)){
 		$err[]='Şifre boş bırakılamaz.';
 	}

	if(empty($user_email)){
 		$err[]='E-mail boş bırakılamaz.';
 	}

	if(!empty($user_email)){
		$sql="SELECT `wu`.* FROM `water_users` AS `wu` WHERE `wu`.`controlbox_companyid`=".safe($user_result['user_companyid'])." AND `wu`.`user_active`=1 AND `wu`.`user_email`=".safe($user_email);
		$result=$mysqli->query($sql); 	
		if($result->num_rows > 0){
			$err[]='Aynı Email ile kullanıcı mevcut.';
		}
 	}

	
 	if(count($err)==0){
 		$column[]='`user_name`';
 		$data[]=safe($user_name);
 		$column[]='`user_lastname`';
 		$data[]=safe($user_lastname);
 		$column[]='`user_companyid`';
 		$data[]=safe($user_result['user_companyid']);
 		$column[]='`user_email`';
 		$data[]=safe($user_email); 
 		$column[]='`user_password`';
 		$data[]=safe(password_hash($user_password,PASSWORD_DEFAULT));	
 		$column[]='`user_active`';
 		$data[]=safe(1); 
		
		$column[]='`user_permission`';
 		$data[]=safe($user_permission); 

 		
	 	$sql="INSERT INTO `water_users` (".join(",",$column)." ) VALUES (".join(",",$data).")";
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
 	}
 	echo json_encode(array('status'=>$status, 'messages'=>$err));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='ax1'){
	$option[]=array('id'=>0, 'name'=>'Gözlemci');
	$option[]=array('id'=>1, 'name'=>'Yönetici');
	echo json_encode(array('status'=>'success', 'messages'=>$err, 'options'=>$option));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='av' && isset($_POST['id'])){
	$sql="SELECT `wu`.* FROM `water_users` AS `wu` WHERE `wu`.`user_companyid`=".safe($user_result['user_companyid'])." AND `wu`.`user_active`=1 AND `wu`.`user_id`=".safe($_POST['id']);
	$query=$mysqli->query($sql);
	if($query->num_rows ==0){
		$err[]='Geçersiz Kullanıcı.';
	}
	
	if(count($err)==0){
		$result=$query->fetch_assoc();
		$sql="UPDATE `water_users` AS `wu` SET `wu`.`user_active` = 0 WHERE `wu`.`user_companyid`=".safe($user_result['user_companyid'])." AND `wu`.`user_active`=1 AND `wu`.`user_id`=".safe($_POST['id']);
		if($mysqli->query($sql)){
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
elseif(isset($_POST['xa']) && $_POST['xa']=='ac1' && isset($_POST['id'])){
	$sql="SELECT `wu`.`user_id` AS `uid`, `wu`.`user_name` AS `uname` , `wu`.`user_lastname` AS `ulname`, `wu`.`user_email` AS `uemail`, `wu`.`user_permission` AS `uper`   FROM `water_users` AS `wu` WHERE `wu`.`user_companyid`=".safe($user_result['user_companyid'])." AND `wu`.`user_active`=1 AND `wu`.`user_id`=".safe($_POST['id']);

	$query=$mysqli->query($sql);
	if($query->num_rows ==0){
		$err[]='Geçersiz Kullanıcı.';
	}
	
	if(count($err)==0){
		if($row=$query->fetch_assoc()){
			unset($row['user_password']);
			$row['upername']=($row['uper']==0?'Gözlemci':'Yönetici');
			$status="success";
			$err=$row;
		}
		else{
			$status="error";
			$err[]=$mysqli->error;
		}
	}else{
		$status="error";
	}
		$option[]=array('id'=>0, 'name'=>'Gözlemci');
	$option[]=array('id'=>1, 'name'=>'Yönetici');
	echo json_encode(array('status'=>$status, 'data'=>$err,'options'=>$option));
}
elseif(isset($_POST['xa']) && $_POST['xa']=='ac2' && !empty($_POST['editrecordId'])){
	$user_name=trim($_POST['editrecordName']);
 	$user_lastname=trim($_POST['editrecordLast']);
 	$user_email=trim($_POST['editrecordEmail']);
 	$user_password=trim($_POST['editrecordPass']);
	$user_permission=trim($_POST['editrecordPerm']);
	
	$sql="SELECT `wu`.*  FROM `water_users` AS `wu` WHERE `wu`.`user_companyid`=".safe($user_result['user_companyid'])." AND `wu`.`user_active`=1 AND `wu`.`user_id`=".safe($_POST['editrecordId']);
	$result=$mysqli->query($sql);
	if($result->num_rows ==0){
		$err[]='Geçersiz Kullanıcı.';
	}
	else{
		$row=$result->fetch_assoc();
	}
	
	if(count($err)==0){
		if($user_name != $row['user_name']){
			$column[]="`wu`.`user_name`=".safe($user_name);
		}
		if($user_lastname != $row['user_lastname']){
			$column[]="`wu`.`user_lastname`=".safe($user_lastname);
		}
	
		if($user_email != $row['user_email']){
			$column[]="`wu`.`user_email`=".safe($user_email);
		}
		if(!empty($user_password)){
			$column[]="`wu`.`user_password`=".safe(password_hash($user_password, PASSWORD_DEFAULT));
		}
		
		if($user_permission != $row['user_permission']){
			$column[]="`wu`.`user_permission`=".safe($user_permission);
		}
		
		if(count($column)!=0){
			$sql="UPDATE `water_users` AS `wu` SET ".join(", ",$column)." WHERE `wu`.`user_companyid`=".safe($user_result['user_companyid'])." AND `wu`.`user_id`=".safe($row['user_id'])." LIMIT 1";
			if($mysqli->query($sql)){
				$status="success";
				$err[]="İşlem başarılı.".$sql;
			}
			else{
				$status="error";
				$err[]=$mysqli->error;
			}
		}else{
				$status="info";
				$err[]="İşlem başarılı";
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
						<li class="nav-item"> <a class="nav-link" href="dashboard<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-line-chart"></i> Dashboard</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_tanks<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-database"></i> Su Depoları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_wells<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-bitbucket"></i> Su Kuyuları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_pumps<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-chrome"></i> Su Motorları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_controls<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-microchip"></i> Kontrol Kartları</a> </li>
						<li class="nav-item"> <a class="nav-link active" href="water_users<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-users"></i> Kullanıcılar</a> </li>
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
						<h3 class="" contenteditable="true">Kullanıcılar</h3>
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
								<b>Yeni Kullanıcı Ekle</b>
							</a>
							<table id="dyntable" class="table text-nowrap p-1">
							    <thead>
							        <tr>
							            <th>Ad Soyad</th>
							            <th>Kullanıcıadı</th>
							            <th>E-Mail</th>
										<th>Yetki</th>
							            <th>Son Giriş</th>
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
		<div class="modal" id="modal-newrecord" data-target="users" aria-labelledby="modal-newrecordLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Kullanıcı Ekle</h5> <button type="button" class="close" data-dismiss="modal"> <span>×</span> </button>
					</div>
					<form id="newrecord" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">
						<input type="hidden" name="xa" value="ax">
						<div class="modal-body">
							<div class="form-group row"> <label for="newrecordName" class="col-2 col-form-label">Ad</label>
								<div class="col-10"><input type="text" class="form-control" id="newrecordName" name="newrecordName" placeholder="Ad" required> </div>
							</div>
							<div class="form-group row"> <label for="newrecordLast" class="col-2 col-form-label">Soyad</label>
								<div class="col-10"><input type="text" class="form-control" id="newrecordLast" name="newrecordLast" placeholder="Soyad" required> </div>
							</div>
							<div class="form-group row"> <label for="newrecordEmail" class="col-2 col-form-label">E-mail</label>
				            	<div class="col-10"><input type="email" class="form-control" id="newrecordEmail" name="newrecordEmail" placeholder="mail@example.com" required> </div>
				            </div>
							<div class="form-group row"> <label for="newrecordPerm" class="col-2 col-form-label">Yetki</label>
				            	<div class="col-10"><select name="newrecordPerm" id="newrecordPerm"  class="custom-select custom-select-sm" required></select> </div>
				            </div>
							<div class="form-group row"> <label for="newrecordPass" class="col-2 col-form-label">Şifre</label>
								<div class="col-10"><input type="password" class="form-control" id="newrecordPass" name="newrecordPass" placeholder="Password" required> </div>
							</div>
						</div>
						<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button> <button type="submit" class="btn btn-primary">Kaydet</button> </div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Modals:new -->
		<!-- Modals:edit -->
		<div class="modal" id="modal-editrecord" data-target="users" aria-labelledby="modal-editrecordLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Kullanıcı Düzenle</h5> <button type="button" class="close" data-dismiss="modal"> <span>×</span> </button>
					</div>
					<form id="editrecord" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">
						<input type="hidden" name="xa" value="ac2">
						<input type="hidden" id="editrecordId" name="editrecordId" value="">
						<div class="modal-body">
							<div class="form-group row"> <label for="editrecordName" class="col-2 col-form-label">Ad</label>
								<div class="col-10"><input type="text" class="form-control" id="editrecordName" name="editrecordName" placeholder="Ad" required> </div>
							</div>
							<div class="form-group row"> <label for="editrecordLast" class="col-2 col-form-label">Soyad</label>
								<div class="col-10"><input type="text" class="form-control" id="editrecordLast" name="editrecordLast" placeholder="Soyad" required> </div>
							</div>
							<div class="form-group row"> <label for="editrecordEmail" class="col-2 col-form-label">E-mail</label>
				            	<div class="col-10"><input type="email" class="form-control" id="editrecordEmail" name="editrecordEmail" placeholder="mail@example.com" required> </div>
				            </div>
							<div class="form-group row"> <label for="editrecordPerm" class="col-2 col-form-label">Yetki</label>
				            	<div class="col-10"><select name="editrecordPerm" id="editrecordPerm"  class="custom-select custom-select-sm" required></select> </div>
				            </div>
							<div class="form-group row"> <label for="editrecordPass" class="col-2 col-form-label">Şifre</label>
								<div class="col-10"><input type="password" class="form-control" id="editrecordPass" name="editrecordPass" placeholder="Password"> </div>
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
						data:{xa:'ab',cp:<?php echo $user_result['user_companyid']?>}
					},
				    order: [[ 0, "asc" ]],
				    columnDefs: [
						{ searchable:false, targets:[3,4]},
						{ orderable: false, targets: [4,5] },
					],
			        select: true,
			        drawCallback: function( settings ) {
				        $("input[id^='status-']").bootstrapToggle();
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
							console.log(data);
							var result=JSON.parse(data);
							if(result.status=='success'){
								$('#editrecord').trigger("reset");
								$('#editrecordId').val(result.data.uid);
								$('#editrecordName').val(result.data.uname);
								$('#editrecordLast').val(result.data.ulname);
								$('#editrecordEmail').val(result.data.uemail);
								$('option', '#editrecordPerm').remove();
								result.options.forEach(function(item){
									$('#editrecordPerm').append(new Option(item.name, item.id, false));
								});
								$('#editrecordPerm').append(new Option(result.data.upername, result.data.uper, true));
								$('#editrecordPerm').val(result.data.uper);
								$('#editrecordPerm :selected').attr('class','x');
								
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