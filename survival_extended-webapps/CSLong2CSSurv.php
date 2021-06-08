<?php

//$app="phpmyadmin";
//$app="opencart";
//$app="phpbb";
//$app="dokuwiki";
//$app="mediawiki";
//$app="prestashop";
//$app="phppgadmin";
$app="vanilla";

$db= new mysqli('localhost', 'root', '', 'serversmells');

//-------------------------------
print "$app\n";

$sql="select * from ". $app ."_version order by id desc limit 1";
$result= $db->query($sql);
$rowmax =  $result->fetch_assoc();

$idversion_max=$rowmax['id'];
$version_max=$rowmax['version'];
$date_max=$rowmax['date'];

$sql="select * from ". $app ."_version order by id";

//print $sql;

$result= $db->query($sql);

$time_init = time();

while ($row =  $result->fetch_assoc()){
	
	$version=$row['version'];
	$id=$row['id'];
	$date=$row['date'];

	print "\n $id $version";

	get_smells_version($id, $version, $date);

	$seconds = time() - $time_init;

	print "\nElapsed: ". floor($seconds / 3600) . gmdate(":i:s", $seconds % 3600);

}


///////////////////////////////////

function get_smells_version(int $idversion, string $version, string $date){

	global $db, $app, $idversion_max, $date_max;


	// mudar para id
	$sql="select * from ". $app ." where version='$version' order by id";

	//print "\n" . $sql;
	//return;

	$result= $db->query($sql);

	while ($row =  $result->fetch_assoc()){

		/*
		print_r($row);
		print "\n";
		continue;
		*/

		$file=$row['file'];
		$function = $row['function'];
		$class = $row['class'];
		$package = $row['package'];

		$rule=$row['rule'];
		$violation= $row['violation'];
		$violation_nonumbers= $row['violation_nonumbers'];
		
		// now in sql
		//$violation_nonumbers=  preg_replace('/\d/', '', $violation );
		
		$ruleset=$row['ruleset'];
		$priority=$row['priority'];
		$begin_line=$row['beginline'];
		$end_line=$row['endline'];
		
		$file	= $db->real_escape_string($file) ;	
		$rule	= $db->real_escape_string($rule) ;
		$ruleset 	= $db->real_escape_string($ruleset) ;
		$package	= $db->real_escape_string($package) ;
		$class	= $db->real_escape_string($class) ;
		$function	= $db->real_escape_string($function) ;
		$priority	= $db->real_escape_string($priority) ;
		$violation	= $db->real_escape_string($violation) ;
		$violation_nonumbers	= $db->real_escape_string($violation_nonumbers) ;

		$version_init = $db->real_escape_string($version) ;
		$date_init = $db->real_escape_string($date) ;

		//tira controversy
		if ($ruleset == 'Controversial Rules'){
				continue;
		}
		
		//print "function : $function";

		//testa se é novo
		$sql_tn= "select * from " . $app ."_ss_evol where 
		file='$file' and function='$function' and class='$class' and package='$package' and 
		rule='$rule' and violation_nonumbers='$violation_nonumbers'";

		//print "\n$sql_tn";

		$result_tn=$db->query($sql_tn);

		if ($result_tn->num_rows > 0){  
			// already exists, take id to see if closes

			$row_tn = $result_tn->fetch_assoc();
			$ss_id= $row_tn['id'];

			$ss_date_init=$row_tn['date_init'];
			
			////print "\nExists ";

		} else {  
			
			// novo
			// mudanca para diff e censored

			$sql_new="insert into " . $app ."_ss_evol (
			idversion_init, file, begin_line , end_line ,	 	
			rule , ruleset , package, class, function ,	
			priority , violation, violation_nonumbers, 
			version_init, date_init  
			) values (
			'$idversion', '$file', '$begin_line' , '$end_line' ,	 	
			'$rule' , '$ruleset' , '$package', '$class' , '$function' , 
			'$priority' , '$violation' , '$violation_nonumbers' ,
			'$version_init', '$date_init'  
			)";

			
			if (!$db->query($sql_new)){
				print $sql . "Insert:\n";
				file_put_contents("errori.sql", $sql_new);
				die($db->error);
			}
			
			$ss_id= $db->insert_id;
			$ss_date_init=$date_init;
			

			////print "\nNew";
		}

		/////print "idv:$idversion file:$file, function:$function, ($rule / $ruleset) $violation";
		
		/////////// testa se ultima e nao fechou ////////////////////////////////////////////////////
		
		// test if is open and last version
		// to be validated
		if ($idversion == $idversion_max){

			$sql_test="select * from " . $app ."_ss_evol where id=$ss_id";
			$result_test=$db->query($sql_test);
			$row_test= $result_test->fetch_assoc();
			if ($row_test['idversion_end']==0){ 

				$censored=0;

				//$ss_date_init= $row_test['date_init'];
				$secdiff = strtotime($date_max) - strtotime($ss_date_init);		
				$diff =  round($secdiff / (60 * 60 * 24));

				$sql_update="update " . $app ."_ss_evol set 
				date_end='$date_max',
				diff=$diff,
				censored=$censored 
				where id='$ss_id'";
	
				if (!$db->query($sql_update)){
					print $sql . "Insert:\n";
					file_put_contents("erroru.sql", $sql_update);
					die($db->error);
				}
				
				file_put_contents("sqlupdate.sql", "LAST " . $sql_update. "\r\n", FILE_APPEND);

			}




		} else {   // not last, can close/die

		

			///////////////////////////// testa se fecha ////////////////////////////////////////////////
			$idversion_next = $idversion + 1;

			//get next version
			$sqlv="select * from ". $app ."_version where id=$idversion_next";
			$resultv= $db->query($sqlv);

			$rowv = $resultv->fetch_assoc();
			$version_next= $rowv['version'];
			$date_next= $rowv['date'];

			// check if closes
			$sql_next = "select * from ". $app ." where version='$version_next' and 
			file='$file' and function='$function'  and class='$class' and package='$package' and rule='$rule' and  
			violation_nonumbers='$violation_nonumbers'";

			//print "\n" . $sql_next;
			
			$result_next=$db->query($sql_next);

			if ($result_next->num_rows == 0){  // ja nao existe

				$censored=1;

				$secdiff = strtotime($date_next) - strtotime($ss_date_init);		
				$diff =  round($secdiff / (60 * 60 * 24));

				$sql_update="update " . $app ."_ss_evol set 
				idversion_end='$idversion_next',
				version_end='$version_next', 
				date_end='$date_next',
				diff=$diff ,
				censored='$censored' where id='$ss_id'";
				
				file_put_contents("sqlupdate.sql", "CLOSE " . $sql_update . "\r\n", FILE_APPEND);

				if (!$db->query($sql_update)){
					print $sql . "Insert:\n";
					file_put_contents("erroru.sql", $sql_update);
					die($db->error);
				}

				/////print " end on version $idversion_next";


			} 
			/////////////////////////////////////////////////////
		
		}







		
		


	}

}

/*
idversion_init
idversion_end
file 	
begin_line 	
end_line 	 	
rule 		
ruleset
class 		
function 	 	
priority 		
violation 
*/