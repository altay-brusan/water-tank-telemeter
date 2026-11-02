<?php
// Initialize the session
session_start();
include("includes/config.php") ;
///
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
$sql="SELECT `wu`.`user_permission`, `wu`.`user_id`, `wu`.`user_companyid` FROM `water_users` AS `wu` WHERE `wu`.`user_id`=".safe($_SESSION['uid']);
$user_query=$mysqli->query($sql);
$user_result=$user_query->fetch_assoc();

//
if(isset($_POST['xa']) && $_POST['xa']==1){
	$q="SELECT * FROM( SELECT IF( `wtlh`.`tank_sensor` = 'uls' , ROUND( `wtlh`.`tank_level` / `wt`.`tank_capacity` * 100) , `wtlh`.`tank_level`) AS `tank_level` , DATE_FORMAT( `wtlh`.`tank_level_lastupdate` , '%d-%m-%Y %H:%i') AS `tank_level_lastupdate`  FROM `water_tanks` AS wt LEFT JOIN `water_tanks_level_history` AS wtlh ON( `wt`.`tank_id` = `wtlh`.`tank_id`) WHERE `wt`.`tank_companyid`=".safe($user_result['user_companyid'])." AND `wt`.`tank_id` = ".safe($_POST['id'])." AND `wtlh`.`tank_level_lastupdate` BETWEEN DATE_ADD(NOW() , INTERVAL - 6 HOUR) AND NOW() GROUP BY 2 ORDER BY `wtlh`.`tank_level_lastupdate` DESC) AS x ORDER BY 2 ASC";
	$result=$mysqli->query($q);
	$data=array();
	$x=0;
	while($row=$result->fetch_assoc()) {
		$data['levels'][$x] = $row['tank_level'];
		$data['labels'][$x] = $row['tank_level_lastupdate'];
		$x++;
		
	}
	/*
	$q="SELECT `wt`.`tank_lastupdate` , `wt`.`tank_level`, `wm`.`motor_status` , `wm`.`motor_id` , `wm`.`motor_name` , `wm`.`motor_last_action` FROM `water_tanks` AS `wt` LEFT JOIN `water_motors` AS `wm` ON( `wt`.`tank_controlbox_id` = `wm`.`motor_controlbox_id`) WHERE `wt`.`tank_companyid` = ".safe($user_result['user_companyid'])." AND `wt`.`tank_id` = ".safe($_POST['id']);

	$result=$mysqli->query($q);
	$x=0;
	while($row=$result->fetch_assoc()){
		$data['last_update']=$row['tank_lastupdate'];
		$data['last_level']=$row['tank_level'];
		$interval=date_diff(date_create(date('H:i:s')),date_create($row['motor_last_action']));
		
		$data['motors'][$x]['id']=$row['motor_id'];
		$data['motors'][$x]['status']=$row['motor_status'];
		$data['motors'][$x]['name']=$row['motor_name'];
		$data['motors'][$x]['last']=$interval->format('%M ay %d gün %h saat %i dakika %s saniyedir ');
		
		$x++;
	}
	
	$q="SELECT `wm`.`motor_status` , `wm`.`motor_id` , `wm`.`motor_name` , `wm`.`motor_last_action` FROM `water_wells` AS `ww` LEFT JOIN `water_motors` AS `wm` ON( `ww`.`well_controlbox_id` = `wm`.`motor_controlbox_id`) WHERE `ww`.`well_companyid` = ".safe($user_result['user_companyid'])." AND `ww`.`well_tank_id` = ".safe($_POST['id']);

	$result=$mysqli->query($q);

	while($row=$result->fetch_assoc()){
		$interval=date_diff(date_create(date('H:i:s')),date_create($row['motor_last_action']));
		
		$data['motors'][$x]['id']=$row['motor_id'];
		$data['motors'][$x]['status']=$row['motor_status'];
		$data['motors'][$x]['name']=$row['motor_name'];
		$data['motors'][$x]['last']=$interval->format('%M ay %d gün %h saat %i dakika %s saniyedir ');
		
		$x++;
	}
	*/
	echo json_encode($data);

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
						<li class="nav-item"> <a class="nav-link active" href="dashboard<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-line-chart"></i> Dashboard</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_tanks<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-database"></i> Su Depoları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_wells<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-bitbucket"></i> Su Kuyuları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_pumps<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-chrome"></i> Su Motorları</a> </li>
						<li class="nav-item"> <a class="nav-link" href="water_controls<?php echo ($_SESSION['cp']==1 ? '_x':'')?>.php"><i class="fa fa-fw fa-1x py-1 fa-microchip"></i> Kontrol Kartları</a> </li>
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
						<h3 class="" contenteditable="true">Dashboard</h3>
					</div>
				</div>
			</div>
		</div>
		<div class="">
			<div class="container-fluid" >
				<div class="row text-center">
					<div class="col-12 p-1">
						<!-- Card:items --> 

<?php	
	//
	$q="SELECT `wt`.`tank_id`, `wt`.`tank_name`, `wt`.`tank_active`, `wt`.`tank_level`, `wt`.`tank_controlbox_id`, `wt`.`tank_lastupdate` 
	 FROM `water_tanks` AS `wt` WHERE `wt`.`tank_companyid`=".safe($user_result['user_companyid'])." AND `wt`.`tank_deleted`=0 ORDER BY `tank_active` DESC, `tank_name` ASC";

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
		  			 
			  			<div class="card d-inline-flex text-justify ">
							<div class="card-header p-0  bg-<?php echo $border?> text-white" style="height: 25px">&nbsp;<b><?php echo $row['tank_name']?></b></div>
							<div class="card-body p-0">
								<canvas id="tank-<?php echo $row['tank_id']?>" width="300" height="150"></canvas>
								<div class="row card-text-sm text-center p-0 " style="height: 25px;">
									<div class="d-inline-flex col-lg-6 col-sm-6 col-6 text-nowrap">Son Seviye</div>
									<div class="d-inline-flex col-lg-6 col-sm-6 col-6" id="last_level-<?php echo $row['tank_id'];?>"> %<?php echo $row['tank_level']?></div>
								</div>
								
								<!-- 
								<div class="row card-footer  text-center">
									<div class="d-inline-flex col-lg-12 col-sm-12 col-12 text-nowrap p-0"><button class="btn btn-block text-left" type="button" data-toggle="collapse" data-target="#tank_<?php echo $row['tank_id']?>" aria-expanded="false" aria-controls="tank_<?php echo $row['tank_id']?>">Depo Motorları</button></div>
								</div> -->

								
									<!-- Master Motors -->
<?php
/*
		if($user_result['user_permission'] ==1 ){
			$sql="SELECT `wm`.* FROM `water_motors` AS `wm` WHERE `wm`.`motor_companyid`=".safe($user_result['user_companyid'])." AND `wm`.`motor_controlbox_id`=".safe($row['tank_controlbox_id'])." AND `wm`.`motor_deleted`=0 ORDER BY `wm`.`motor_slot` ASC";

			$motor_result=$mysqli->query($sql);
			if($motor_result->num_rows > 0){
				$i=0;
				echo '<div class="collapse multi-collapse" id="tank_'.$row['tank_id'].'">';
				while($motor_row=$motor_result->fetch_assoc()){
					$i++;
					if($motor_row['motor_status']==true){
						$color='bg-success';
						$txtcolor='text-success';
						$icon='fa fa-fw fa-1x py-1 fa-pause';
						$action_status="stop";
						$last="çalışıyor";
					}
					else{
						$color='bg-danger';
						$txtcolor='text-danger';
						$icon='fa fa-fw fa-1x py-1 fa-play';
						$action_status="start";
						$last="çalışmıyor";
					}
					$interval=date_diff(date_create(date('H:i:s')),date_create($row['motor_last_action']));;
				
					$motor_row['motor_last_action']=$interval->format('%M ay %d gün %h saat %i dakika %s saniyedir ');
					echo '
					<div class="row card-text  text-center ">
						<div class="d-inline-flex col-lg-8 col-sm-8 col-8 "><a class="btn " href="#" data-toggle="tooltip"  data-html="true" data-placement="bottom" title="<b>'.$motor_row['motor_name'].'</b> '.$last.'">'.$motor_row['motor_name'].'</a></div>
						<div class="d-inline-flex col-lg-1 col-sm-1 col-1 p-0 m-0" id="motor-'.$row['tank_id'].'-'.$motor_row['motor_id'].'" ><a class="btn text-white" href="#" data-toggle="tooltip"  data-html="true" data-placement="bottom" title="<b>'.$motor_row['motor_name'].'</b> '.$last.'"><i class="'.$txtcolor.' fa fa-fw fa-1x py-1 fa-circle"></i></a></div>
						<div class="d-inline-flex col-lg-1 col-sm-1 col-1 p-0 m-0" ><a class="btn text-white " href="#" id="pump-'.$motor_row['motor_id'].'" data-target="start" data-toggle="tooltip"  data-html="true" data-placement="bottom" title="<b>'.$motor_row['motor_name'].'</b> Çalıştır"><i class="text-success fa fa-fw fa-1x py-1 fa-play"></i></a></div>
						<div class="d-inline-flex col-lg-1 col-sm-1 col-1 p-0 m-0"><a class="btn text-white" href="#" id="pump-'.$motor_row['motor_id'].'" data-target="stop" data-toggle="tooltip"  data-html="true" data-placement="bottom" title="<b>'.$motor_row['motor_name'].'</b> Durdur"><i class="text-danger fa fa-fw fa-1x py-1 fa-pause"></i></a></div>
					</div>';
				}
				echo "</div>";
			}
		}
*/
?>									

									<!-- /Master Motors -->
									
<?php
/*
		$q="SELECT `ww`.`well_name`, `ww`.`well_id`, `ww`.`well_controlbox_id` FROM `water_wells` AS `ww` WHERE `ww`.`well_companyid`=".safe($user_result['user_companyid'])." AND `ww`.`well_deleted`=0 AND `ww`.`well_tank_id`=".safe($row['tank_id'])." ORDER BY `ww`.`well_name` ASC";
		$well_result=$mysqli->query($q);
		if($well_result->num_rows > 0){
		
			while($well_row=$well_result->fetch_assoc()){							
				echo '			
									
								<div class="row card-footer ">
									<div class="d-inline-flex col-lg-12 col-sm-12 col-12 text-nowrap p-0 m-0 "><button class="btn btn-block text-left" type="button" data-toggle="collapse" data-target="#well_'.$well_row['well_id'].'" aria-expanded="false" aria-controls="well_'.$well_row['well_id'].'">'.$well_row['well_name'].' Motorları</button></div>
								</div>
								<!-- Slave Motors -->';

				if($user_result['user_permission'] ==1 ){
					$sql="SELECT `wm`.* FROM `water_motors` AS `wm` WHERE `wm`.`motor_companyid`=".safe($user_result['user_companyid'])." AND `wm`.`motor_controlbox_id`=".safe($well_row['well_controlbox_id'])." AND `wm`.`motor_deleted`=0 ORDER BY `wm`.`motor_slot` ASC";
					$motor_result=$mysqli->query($sql);
					if($motor_result->num_rows > 0){
						$i=0;
						echo '<div class="collapse multi-collapse" id="well_'.$well_row['well_id'].'">';
						while($motor_row=$motor_result->fetch_assoc()){
							$i++;
							if($motor_row['motor_status']==true){
								$color='bg-success';
								$txtcolor='text-success';
								$icon='fa fa-fw fa-1x py-1 fa-pause';
								$action_status="stop";
								$last="çalışıyor";
							}
							else{
								$color='bg-danger';
								$txtcolor='text-danger';
								$icon='fa fa-fw fa-1x py-1 fa-play';
								$action_status="start";
								$last="çalışmıyor";
							}
							$interval=date_diff(date_create(date('H:i:s')),date_create($row['motor_last_action']));;
						
							$motor_row['motor_last_action']=$interval->format('%M ay %d gün %h saat %i dakika %s saniyedir ');
							echo '
							<div class="row card-text">
								<div class="d-inline-flex col-lg-8 col-sm-8 col-8 "><a class="btn " href="#" data-toggle="tooltip"  data-html="true" data-placement="bottom" title="<b>'.$motor_row['motor_name'].'</b> '.$last.'">'.$motor_row['motor_name'].'</a></div>
								<div class="d-inline-flex col-lg-1 col-sm-1 col-1 p-0 m-0" id="motor-'.$row['tank_id'].'-'.$motor_row['motor_id'].'" ><a class="btn text-white" href="#" data-toggle="tooltip"  data-html="true" data-placement="bottom" title="<b>'.$motor_row['motor_name'].'</b> '.$last.'"><i class="'.$txtcolor.' fa fa-fw fa-1x py-1 fa-circle"></i></a></div>
								<div class="d-inline-flex col-lg-1 col-sm-1 col-1 p-0 m-0" ><a class="btn text-white " href="#" id="pump-'.$motor_row['motor_id'].'" data-target="start" data-toggle="tooltip"  data-html="true" data-placement="bottom" title="<b>'.$motor_row['motor_name'].'</b> Çalıştır"><i class="text-success fa fa-fw fa-1x py-1 fa-play"></i></a></div>
								<div class="d-inline-flex col-lg-1 col-sm-1 col-1 p-0 m-0"><a class="btn text-white" href="#" id="pump-'.$motor_row['motor_id'].'" data-target="stop" data-toggle="tooltip"  data-html="true" data-placement="bottom" title="<b>'.$motor_row['motor_name'].'</b> Durdur"><i class="text-danger fa fa-fw fa-1x py-1 fa-pause"></i></a></div>
							</div>';
						}
						echo "</div>";
					}
				}
				echo "<!-- /Slave Motors -->";	
			}
		}
*/
?>									

																
							</div>
							<div class="card-footer text-muted text-left p-0"><small id="last_update-<?php echo $row['tank_id']?>"><?php echo date( 'd-m-Y H:i', strtotime($row['tank_lastupdate']))?></small></div>
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
				tank_graphs_backup(<?php echo $user_result['user_permission']?>);
				setInterval(function() { tank_graphs_backup(<?php echo $user_result['user_permission']?>);}, 5000);
				
				
			});	
		</script>
<?php
	require_once("footer.php");
}
?>