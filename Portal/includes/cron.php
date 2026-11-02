#!/data/server/php-7.2.5/bin/php
<?php
	require_once("/data/water.pievision.com/public_html/includes/config.php");
	$q="SELECT `wc`.* FROM `water_controlbox` AS `wc` ORDER BY `wc`.`controlbox_id` ASC";
	$controlbox_query=$mysqli->query($q);
	while($controlbox_row=$controlbox_query->fetch_assoc()){
		if(time() - strtotime($controlbox_row['controlbox_ping_lastupdate']) > 60 ){
			$q="UPDATE `water_controlbox` AS `wc` SET `wc`.`controlbox_active`=0 WHERE `wc`.`controlbox_id`=".safe($controlbox_row['controlbox_id'])." LIMIT 1";
			$mysqli->query($q);
		}
	}
?>