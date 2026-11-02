<?php

/*
 * DataTables example server-side processing script.
 *
 * Please note that this script is intentionally extremely simple to show how
 * server-side processing can be implemented, and probably shouldn't be used as
 * the basis for a large complex system. It is suitable for simple use cases as
 * for learning.
 *
 * See http://datatables.net/usage/server-side for full details on the server-
 * side processing requirements of DataTables.
 *
 * @license MIT - http://datatables.net/license_mit
 */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Easy set variables
 */
 require( 'includes/config.php' );
if($_POST['xa']=='az'){
	$table = 'water_tanks';
	$primaryKey = 'tank_id';
	$columns = array(
		array( 'dt' => 0, 'db' => '`wt`.`tank_name`', 'field'=>'tank_name'),
		array( 'dt' => 1, 'db' => '`wt`.`tank_capacity`', 'field'=>'tank_capacity', 
			'formatter' => function($data){
				return $data.' cm';
			}
		),
		array( 'dt' => 2, 'db' => '`wt`.`tank_level`', 'field'=>'tank_level',
			'formatter'=> function( $data ) {
				if($data > 0 && $data <= 20){
					$ctype="bg-danger";
				}
				else if($data > 20 && $data <= 40){
					$ctype="bg-warning";
				}
				else if($data > 40 && $data <= 60){
					$ctype="bg-info";
				}else if($data > 60 && $data <= 80){
					$ctype="bg-info";
				}
				else if($data > 80 && $data <= 100){
					$ctype="bg-success";
				}
				return '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated '.$ctype.'" role="progressbar" style="width: '.$data.'%;" aria-valuenow="'.$data.'" aria-valuemin="0" aria-valuemax="100">%'.$data.'</div></div>';      			
			}
		),
		array( 'dt' => 3, 'db' => '`wt`.`tank_active`', 'field'=>'tank_active',
			'formatter' => function($data, $row){
				if($data==1)
					return '<input name="state-'.$row[5].'" id="state-'.$row[5].'" type="checkbox" data-target="tanks" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" checked >';
				else
					return '<input name="state-'.$row[5].'" id="state-'.$row[5].'" type="checkbox" data-target="tanks" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" >';
			}
		),
		array( 'dt' => 4, 'db' => '`wc`.`controlbox_active`', 'field'=>'controlbox_active',
			'formatter' => function($data,$row){
				if($data !=null){
					if($data==1)
						return '<input id="status-'.$row[5].'"  type="checkbox" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" checked disabled>';
					else
						return '<input id="status-'.$row[5].'"  type="checkbox" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" disabled>';	
				}
				else{
					return '';
				}
			}
		),
		array( 'dt' => 5, 'db' => '`wt`.`tank_id`', 'field'=>'tank_id',
			'formatter' => function($data){
				return '<a href="#" id="edit-'.$data.'" class="btn btn-primary btn-sm edit-record" role="button" data-target="tanks"><i class="fa fa-fw fa-1x py-1 fa-edit"></i> Düzenle</a> <a href="#" id="delete-'.$data.'" class="btn btn-danger btn-sm delete-record" role="button" data-target="tanks"><i class="fa fa-fw fa-1x py-1 fa-trash"></i> Sil</a>' ;
			}
		),
	);
	$joinQuery = "FROM `water_tanks` AS `wt` LEFT JOIN `water_controlbox` AS `wc` ON (`wt`.`tank_controlbox_id` = `wc`.`controlbox_id`)";
	$extraWhere = "`wt`.`tank_companyid`=".safe($_POST['cp'])." AND `wt`.`tank_deleted`=0";
}
elseif($_POST['xa']=='ax'){
	$table = 'water_wells';
	$primaryKey = 'well_id';
	$columns = array(
		array( 'dt' => 0, 'db' => '`ww`.`well_name`', 'field'=>'well_name'),
		
		array( 'dt' => 1, 'db' => '`ww`.`well_active`', 'field'=>'well_active',
			'formatter' => function($data, $row){
				if($data==1)
					return '<input name="state-'.$row[3].'" id="state-'.$row[3].'" type="checkbox" data-target="wells" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" checked >';
				else
					return '<input name="state-'.$row[3].'" id="state-'.$row[3].'" type="checkbox" data-target="wells" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" >';
			}
		),
		array( 'dt' => 2, 'db' => '`wc`.`controlbox_active`', 'field'=>'controlbox_active',
			'formatter' => function($data,$row){
				if($data !=null){
					if($data==1)
						return '<input id="status-'.$row[3].'" class="x" type="checkbox" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" checked disabled>';
					else
						return '<input id="status-'.$row[3].'" class="x" type="checkbox" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" disabled>';	
				}
				else{
					return '';
				}
			}
		),
		array( 'dt' => 3, 'db' => '`ww`.`well_id`', 'field'=>'well_id',
			'formatter' => function($data){
				return '<a href="#" id="edit-'.$data.'" class="btn btn-primary btn-sm edit-record" role="button" data-target="wells"><i class="fa fa-fw fa-1x py-1 fa-edit"></i> Düzenle</a> <a href="#" id="delete-'.$data.'" class="btn btn-danger btn-sm delete-record" role="button" data-target="wells"><i class="fa fa-fw fa-1x py-1 fa-trash"></i> Sil</a>' ;
			}
		),
		
	);
	$joinQuery = "FROM `water_wells` AS `ww` LEFT JOIN `water_controlbox` AS `wc` ON (`ww`.`well_controlbox_id` = `wc`.`controlbox_id`)";
	$extraWhere = "`ww`.`well_companyid`=".safe($_POST['cp'])." AND `ww`.`well_deleted`=0";

}
elseif($_POST['xa']=='av'){
	$table = 'water_controlbox';
	$primaryKey = 'controlbox_id';
	$columns = array(
		array( 'dt' => 0, 'db' => '`wc`.`controlbox_name`',  'field' => 'controlbox_name' ),
		array( 'dt' => 1, 'db' => '`wc`.`controlbox_uniqueid`',  'field' => 'controlbox_uniqueid' ),
		array( 'dt' => 2, 'db' => '`wc`.`controlbox_active`',     'field' => 'controlbox_active', 'formatter' => function ($d,$row){
			if($d == true)
				return '<input id="status-'.$row[6].'" type="checkbox" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" checked disabled >';
			else
				return '<input id="status-'.$row[6].'" type="checkbox" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Aktif" data-off="Pasif" disabled>';
			
			
		}),
		array( 'dt' => 3, 'db' => '`wc`.`controlbox_last_csq`',  'field' => 'controlbox_last_csq', 'formatter' => function ($data){
			if($data == 0){
				$ctype="bg-danger";
				$width=25;
			}
			else if($data == 1){
				$ctype="bg-warning";
				$width=50;
			}
			else if($data > 2 && $data <= 30){
				$ctype="bg-info";
				$width=75;
			}
			else if($data ==31){
				$ctype="bg-success";
				$width=100;
			}
			return '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated text-dark '.$ctype.'" role="progressbar" style="width: '.$width.'%;" aria-valuenow="'.$data.'" aria-valuemin="0" aria-valuemax="31">'.$data.'</div></div>';      			
			
		} ),
		array( 'dt' => 4, 'db' => '`wc`.`controlbox_status_lastupdate`',    'field' => 'controlbox_status_lastupdate', 'formatter' => function( $d ) {
            return date( 'd-m-Y H:i', strtotime($d));
        } ),
		array( 'dt' => 5, 'db' => '`wt`.`tank_name`', 'field' => 'tank_name' ),
		array( 'dt' => 6, 'db' => '`wc`.`controlbox_id`', 'field' => 'controlbox_id', 'formatter'=>function($d){
			return '<a href="#" id="edit-'.$d.'" class="btn btn-primary btn-sm edit-record" role="button" data-target="controls"><i data-feather="edit"></i> Düzenle</a> <a href="#" id="delete-'.$d.'" class="btn btn-danger btn-sm delete-record"  role="button" data-target="controls" ><i data-feather="trash-2"></i> Sil</a>';
		}),
		
	);
	
	$joinQuery = "FROM `water_controlbox` AS `wc` LEFT JOIN `water_tanks` AS `wt` ON (`wc`.`controlbox_id`=`wt`.`tank_controlbox_id`) ";
	$extraWhere = "`wc`.`controlbox_companyid`=".safe($_POST['cp'])." AND `wc`.`controlbox_deleted`=0";



}
elseif($_POST['xa']=='ac'){
	$table = 'water_motors';
	$primaryKey = 'motor_id';
	$columns = array(
		array( 'dt' => 0, 'db' => '`wm`.`motor_name`',  'field' => 'motor_name' ),
		array( 'dt' => 1, 'db' => '`wc`.`controlbox_name`',  'field' => 'controlbox_name' ),
		array( 'dt' => 2, 'db' => '`wm`.`motor_slot`',     'field' => 'motor_slot' ),
		array( 'dt' => 3, 'db' => '`wm`.`motor_status`', 'field' => 'motor_status', 'formatter'=> function($d,$row){			
			if($d==1)
				return '<input name="state-'.$row[4].'" id="state-'.$row[4].'" type="checkbox" data-target="pumps" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Durdur" data-off="Çalıştır"  checked>';
			else
				return '<input name="state-'.$row[4].'" id="state-'.$row[4].'" type="checkbox" data-target="pumps" data-size="mini" data-height="20" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Durdur" data-off="Çalıştır"  >';
		} ),
		
		array( 'dt' => 4, 'db' => '`wm`.`motor_id`', 'field' => 'motor_id', 'formatter'=>function($d,$row){
			return '<a href="#" id="edit-'.$row[4].'" class="btn btn-primary btn-sm edit-record" role="button" data-target="pumps"><i class="fa fa-fw fa-1x py-1 fa-edit"></i> Düzenle</a> <a href="#" id="delete-'.$row[4].'" class="btn btn-danger btn-sm delete-record" role="button" data-target="pumps"><i class="fa fa-fw fa-1x py-1 fa-trash"></i> Sil</a>' ;
		}),
		
	);
	
	$joinQuery = "FROM `water_motors` AS `wm` LEFT JOIN `water_controlbox` AS `wc` ON (`wm`.`motor_controlbox_id` = `wc`.`controlbox_id`)";
	$extraWhere = "`wm`.`motor_companyid`=".safe($_POST['cp'])." AND `wm`.`motor_deleted`=0";



}
elseif($_POST['xa']=='ab'){
	$table = 'water_users';
	$primaryKey = 'user_id';
	$columns = array(
		array( 'dt' => 0, 'db' => '`wu`.`user_name`', 'field' => 'user_name' ),
		array( 'dt' => 1, 'db' => '`wu`.`user_lastname`', 'field' => 'user_lastname' ),
		array( 'dt' => 2, 'db' => '`wu`.`user_email`', 'field' => 'user_email' ),
		array( 'dt' => 3, 'db' => '`wu`.`user_permission`', 'field' => 'user_permission', 'formatter'=> function($d){
			return ($d==1 ? 'Yönetici':'Gözlemci');
		}),
		array( 'dt' => 4, 'db' => '`wu`.`user_lastlogin`', 'field' => 'user_lastlogin', 'formatter'=> function($d,$row){			
				 return date( 'd-m-Y H:i', strtotime($d));
		} ),
		array( 'dt' => 5, 'db' => '`wu`.`user_id`', 'field' => 'user_id', 'formatter'=>function($d,$row){
			return '<a href="#" id="edit-'.$row[5].'" class="btn btn-primary btn-sm edit-record" role="button" data-target="users"><i class="fa fa-fw fa-1x py-1 fa-edit"></i> Düzenle</a> <a href="#" id="delete-'.$row[5].'" class="btn btn-danger btn-sm delete-record" role="button" data-target="users"><i class="fa fa-fw fa-1x py-1 fa-trash"></i> Sil</a>' ;
		}),
		
	);
	
	$joinQuery = "FROM `water_users` AS `wu`";
	$extraWhere = "`wu`.`user_companyid`=".safe($_POST['cp'])." AND `wu`.`user_active`=1";



}

// SQL server connection information ($sql_details) is loaded from includes/config.php


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * If you just want to use the basic configuration for DataTables with PHP
 * server-side, there is no need to edit below this line.
 */

require( 'includes/ssp.modded.class.php' );

echo json_encode(
	SSP::simple( $_POST, $sql_details, $table, $primaryKey, $columns, $joinQuery, $extraWhere, NULL, NULL/*$groupBy, $having*/ )
);

