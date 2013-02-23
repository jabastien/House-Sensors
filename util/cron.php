#!/usr/bin/php
<?php
$path  = dirname(realpath((dirname(__FILE__))));
require_once "$path/lib/init.php";
require_once "$path/lib/logging.php";
require_once "$path/lib/db.php";

if(!file_exists($rrdfile)){
	$initscript = $utildir."/rrdinit.php";
	echo `php $initscript`;
}
	
// Update the RRA
try{
	$rra = new RRDUpdater($rrdfile);
} catch(Exception $e){
	fatal("Could not access RRA in file: ".$rrdfile.": ".$e->getMessage());
}

$updates = array();
foreach($sources as $src){
	$key = $config[$src]['key'];
	$table = $config['general']['table'];
	$sth = $pdo->prepare("SELECT * FROM $table WHERE source=?");
	
	try{
		$sth->execute(array($key));
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		
	} catch (Exception $e){
		fatal("Could not query database: ".$e->getMessage());
	}
	if($row['value']){
		$v = $row['value'];
		if($config[$src]['scale-function']){
			$scale_function = $config[$src]['scale-function'];
			$v = eval("return( ".$scale_function." );");
			if($v == FALSE){
				warn("Bad scale function in source ".$src);
				$v = 0;
			}		
		}
		$updates[$src] = $v;
	}
	else{
		warn("cron.php: key $k returned nothing");
	}
}
if(count($updates) > 0){
	try{
		$rra->update($updates);
	} catch (Exception $e){
		fatal("Could not update rra: ".$e->getMessage());
	}
}
exit(0);

?>




