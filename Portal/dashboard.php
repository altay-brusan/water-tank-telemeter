<?php
// Initialize the session
session_start();
include("includes/config.php") ;

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
$sql="SELECT `wu`.`user_permission`, `wu`.`user_id` FROM `water_users` AS `wu` WHERE `wu`.`user_id`=".safe($_SESSION['uid']);
$user_query=$mysqli->query($sql);
$user_result=$user_query->fetch_assoc();


if(isset($_POST['xa']) && $_POST['xa']==1){
	$q="SELECT * FROM( SELECT IF( `wtlh`.`tank_sensor` = 'uls' , ROUND( `wtlh`.`tank_level` / `wt`.`tank_capacity` * 100) , `wtlh`.`tank_level`) AS `tank_level` , DATE_FORMAT( `wtlh`.`tank_level_lastupdate` , '%d-%m-%Y %H:%i') AS `tank_level_lastupdate` FROM `water_tanks` AS wt LEFT JOIN `water_tanks_level_history` AS wtlh ON( `wt`.`tank_id` = `wtlh`.`tank_id`) WHERE `wt`.`tank_id` = ".safe($_POST['id'])." AND `wtlh`.`tank_level_lastupdate` BETWEEN DATE_ADD(NOW() , INTERVAL - 6 HOUR) AND NOW() GROUP BY 2 ORDER BY `wtlh`.`tank_level_lastupdate` DESC) AS x ORDER BY 2 ASC";
	$result=$mysqli->query($q);
	$data=array();
	$x=0;
	while($row=$result->fetch_assoc()) {
		$data['levels'][$x] = $row['tank_level'];
		$data['labels'][$x] = $row['tank_level_lastupdate'];
		$x++;
		
	}
	$q="SELECT `wt`.`tank_lastupdate`, `wt`.`tank_level`, `wm`.`motor_status`, `wm`.`motor_id`, `wm`.`motor_name`, `wm`.`motor_last_action` FROM `water_tanks` AS `wt` LEFT JOIN `water_controlbox` AS `wc` ON( `wt`.`tank_controlbox_id` = `wc`.`controlbox_id`) RIGHT JOIN `water_motors` AS `wm` ON( `wc`.`controlbox_id` = `wm`.`motor_controlbox_id`) WHERE `wt`.`tank_id` = ".safe($_POST['id']);
	$result=$mysqli->query($q);
	$x=0;
	while($row=$result->fetch_assoc()){
		$data['last_update']=$row['tank_lastupdate'];
		$data['motors'][$x]['id']=$row['motor_id'];
		$data['motors'][$x]['status']=$row['motor_status'];
		$data['motors'][$x]['name']=$row['motor_name'];
		
		$interval=date_diff(date_create(date('H:i:s')),date_create($row['motor_last_action']));;
		
		$data['motors'][$x]['last']=$interval->format('%M ay %d gün %h saat %i dakika %s saniyedir ');
		$data['last_level']=$row['tank_level'];
		$x++;
	}
	
	
	echo json_encode($data);

}
elseif(isset($_POST['xa']) && $_POST['xa']==2){
	$hi_level=$_POST['hi_level'];
	$lo_level=$_POST['lo_level'];
	$sid=$_POST['editrecordSid'];
	
	if($hi_level==$lo_level)
		$err[]="İki seviye aynı olamaz.";
	
	if($lo_level > $hi_level)
		$err[]="Alt Seviye, Üst Seviyeden fazla olamaz.";
	
	if($user_result['user_permission']==0)
		$err[]="Yetki Gerekli.";
	
	if(count($err)==0){
		$q_add[]='`ws`.`setting_hi_level`='.safe($hi_level);
 		$q_add[]='`ws`.`setting_lo_level`='.safe($lo_level);
		
		$sql="UPDATE `water_settings` AS `ws` SET ".join(',', $q_add)." WHERE `ws`.`setting_id`=".safe($sid)."  LIMIT 1";
		if($mysqli->query($sql)){
			$status='success';
			$err[]="İşlem Başarılı.";
		}
		else{
			$status='error';
			$err[]=$mysqli->error;
		}
	}
	else{
		$status='error';
	}
	echo json_encode(array('status'=>$status, 'messages'=>$err));
}
elseif(isset($_POST['xa']) && $_POST['xa']==3){
	$sql="SELECT `ws`.`setting_id` AS `sid`, `ws`.`setting_hi_level` AS `hi_level`, `ws`.`setting_lo_level` AS `lo_level` FROM `water_settings` AS `ws`  WHERE `ws`.`setting_active`=1 LIMIT 1";
	$result=$mysqli->query($sql);
	$row=$result->fetch_assoc();
	$status='success';
	echo json_encode(array('status'=>$status, 'messages'=>$row));
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
						<li class="nav-item"> <a class="nav-link active" href="dashboard.php"><i class="fa fa-fw fa-1x py-1 fa-line-chart"></i> Dashboard</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_tanks.php"><i class="fa fa-fw fa-1x py-1 fa-database"></i> Su Depoları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_wells.php"><i class="fa fa-fw fa-1x py-1 fa-bitbucket"></i> Su Kuyuları</a> </li>
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
						<h3 class="" contenteditable="true">Dashboard</h3>
					</div>
				</div>
			</div>
		</div>
		<div class="">
			<div class="container" >
				<div class="row">
					<div class="col-12 p-1">
						<!-- Card:items --> 

<?php	//AND `wt`.`tank_active`=1
	$q="SELECT * FROM `water_tanks` AS `wt` WHERE `wt`.`tank_deleted`=0 AND `wt`.`tank_type`=0 AND `wt`.`tank_companyid`=0  ORDER BY `tank_active` DESC, `tank_name` ASC";
	$query = $mysqli->query($q);
	$i=1;
	while($row =$query->fetch_assoc() ){
		if($row['tank_active']==true){
			if($row['tank_level'] >= 0 && $row['tank_level'] <= 20){
				$border="danger";
			}elseif($row['tank_level'] > 20 && $row['tank_level'] <= 40){
				$border="warning";
			}elseif($row['tank_level'] > 40 && $row['tank_level'] <= 60){
				$border="info";
			}elseif($row['tank_level'] > 60 && $row['tank_level'] <= 80){
				$border="info";
			}elseif($row['tank_level'] > 80 && $row['tank_level'] <= 100){
				$border="success";
			}

		}else{
			$border="dark";
		}
?>
		  			 
			  			<div class="card d-inline-flex text-justify">
							<div class="card-header bg-<?php echo $border?> text-white"><b><?php echo $row['tank_name']?></b></div>
							<div class="card-body">
								<canvas id="tank-<?php echo $row['tank_id']?>" width="260" height="150"></canvas>
								<div class="row card-text pb-2 text-center">
									<div class="d-inline-flex col-lg-6 col-sm-6 col-6 text-nowrap">Son Seviye</div>
									<div class="d-inline-flex col-lg-6 col-sm-6 col-6" id="last_level-<?php echo $row['tank_id'];?>"> %<?php echo $row['tank_level']?></div>
								</div>
								<div class="row text-center">
									<!-- Motors -->
<?php
if($user_result['user_permission'] ==1 ){
	$sql="SELECT `wm`.* FROM `water_motors` AS `wm` WHERE `wm`.`motor_controlbox_id`=".safe($row['tank_controlbox_id'])." AND `wm`.`motor_deleted`=0 ORDER BY `wm`.`motor_slot` ASC";
	$motor_result=$mysqli->query($sql);
	if($motor_result->num_rows > 0){
		while($motor_row=$motor_result->fetch_assoc()){
			if($motor_row['motor_status']==true){
				$color='bg-success';
				$icon='fa fa-fw fa-1x py-1 fa-pause';
				$action_status="stop";
				$last="çalışıyor";
			}
			else{
				$color='bg-danger';
				$icon='fa fa-fw fa-1x py-1 fa-play';
				$action_status="start";
				$last="çalışmıyor";
			}
			$interval=date_diff(date_create(date('H:i:s')),date_create($row['motor_last_action']));;
		
			$motor_row['motor_last_action']=$interval->format('%M ay %d gün %h saat %i dakika %s saniyedir ');
			echo '
			<div class="col-md-4 p-0 col-4" id="motor-'.$row['tank_id'].'-'.$motor_row['motor_id'].'">
				<div class="p-1 " ><a class="btn '.$color.' text-white" href="#" id="pump-'.$motor_row['motor_id'].'" data-target="'.$action_status.'" data-toggle="tooltip"  data-html="true" data-placement="bottom" title="<b>'.$motor_row['motor_name'].'</b><br>'.$motor_row['motor_last_action'].' '.$last.'"><i class="'.$icon.'"></i></a></div>
			</div>';
		}
	}
}
?>									

									<!-- /Motors -->
								</div>
							</div>
							<div class="card-footer text-muted text-left p-2"><small id="last_update-<?php echo $row['tank_id']?>"><?php echo date( 'd-m-Y H:i', strtotime($row['tank_lastupdate']))?></small></div>
						</div>
<?php
		$i++;
	}
?>		  		
						<!-- /Card:items -->				  				
		  			</div>
				</div>
			</div>
		</div>

		<script>
		
			$(document).ready(function(){
				tank_graphs(<?php echo $user_result['user_permission']?>);
				setInterval(tank_graphs, 300000);
				
			});	
		</script>
<?php
	require_once("footer.php");
}
?>