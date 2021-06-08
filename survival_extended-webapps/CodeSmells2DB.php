<?php

//$app="phpmyadmin";
//$app="opencart";
//$app="phpbb";
//$app="dokuwiki";
//$app="mediawiki";
//$app="prestashop";
$app="phppgadmin";
//$app="vanilla";

print $app. PHP_EOL; 

$fields=[];

$sep=";";

$db= new mysqli('localhost', 'root', '', 'serversmells');

$dir="reports/$app/";
if (!is_dir($dir)) mkdir($dir);


if ($handle = opendir($dir)) {

	$i=0;

    while (false !== ($entry = readdir($handle))) {
		//$dir . $entry;
        if ($entry != '..' && $entry != '.' && file_exists($dir . $entry)){

			if (! strstr(strtolower($entry), $app)) continue;

			//$file1='phpMyAdmin-4.7.9-all-languages.xml';
			$file1=$entry;
			
			
			
			$version=get_version2($file1);
			
			print $i++ . " - $file1 -  $version" . PHP_EOL; 

			

			$resume_file[] =convertcsv($app, $file1, $version );

			$resume_version[$version] = resumecsv($resume_file, $version, $fields );

			$resume_all= resume_all($app, $resume_version, $fields);

			write_resume_global($app, $resume_all, $fields );
			
        }
    }
    closedir($handle);
} 




///////////////////////////////////
function get_version ($file1){
	$version_sep=["-", "_"];
	$nameonly = substr($file1, 0, -4);
	foreach ($version_sep as $vsep){
		if (strpos($nameonly, $vsep)){
			$names=explode($vsep, $nameonly);
			$version=$names[1];
			if (strstr($names[2],'alpha') || strstr($names[2],'beta') || strstr($names[2],'rc')){
			$version.= $names[2];
			
			}
			return $version;
		}
	}

}


function get_version2 ($file1){
		global $app;
		
		$nameonly = substr($file1, 0, -4);
		
		$tam=strlen($app) +1;
		
		$version = substr($nameonly, $tam);

		return $version;

}




function convertcsv($app, $file1, $version ){
	
	global $sep, $db;
	
	$dir="reports/$app/";
	//$dir="reports/$app"."_tudo/";
	$dirout="csv/$app/";
	if (!is_dir($dirout)) mkdir($dirout);
	$dircode="../code/$app/";

	$nameonly = substr($file1, 0, -4);
	$real_dir= realpath($dircode) . "\\" . $nameonly. "\\";

	//$real_dir = "D:\\dwork\\code\\" . $app . "\\" .  $nameonly. "\\" ; 
	//print $real_dir;
	//exit();
	$filename = $dir . $file1;
	$str= file_get_contents($filename);
	$xml = simplexml_load_string($str);
	//----------------------------------

	//$header="version;file;projectfile;beginline;endline;rule;ruleset;package;externalInfoUrl;function;priority;violation;violation_nonumbers" . PHP_EOL;

	//$headerdb="version, file, projectfile, beginline ,endline, rule, ruleset,
	//package ,externalInfoUrl ,function , priority, violation, violation_nonumbers" ;

	$aheader=['version','file','projectfile', 'beginline' ,'endline', 'rule', 'ruleset',
	'externalInfoUrl' , 'package' ,'class', 'function' , 'priority', 'violation', 'violation_nonumbers'];

	$header = implode (";", $aheader) . PHP_EOL;
	$headerdb = implode (", ", $aheader);

	$data="";
	$bb=[];
	

	foreach ($xml->file as $file){

		$i=0;
		$datadb="";
		
		foreach ($file->violation as $violation){
			


			$line=array();

			$line['version']=$version;
			$line['file']=str_replace($real_dir, "", $file->attributes()->name);
			$line['projectfile']=$file->attributes()->name;

			//$line['file'] = str_replace("\\", "/",$line['file']);
			//$line['projectfile'] = str_replace("\\", "\/",$line['projectfile']);
			
			//$line['class']="";  // for empty cases

			foreach($violation->attributes() as $a => $b) {
				
				$ah=htmlspecialchars(trim($a));
				$line[$ah]=htmlspecialchars(trim($b));
		
				// contagem
				if ($a == "rule"){
					$bh=htmlspecialchars($b);
					if (isset($bb[$bh]))
						$bb[$bh]++;
					else
						$bb[$bh]=1;

				}
			}

			//tira controversy
			/*if ($line['ruleset'] == 'Controversial Rules'){
				continue;
			}*/

			$line['violation']=trim($violation);
			$line['violation_nonumbers']=preg_replace('/\d/', '', trim($violation) )  ;

			if ($i) $datadb.=",";
			$datadb.="(";
			$j=0;
			foreach ($aheader as $v){
				$v=htmlspecialchars($v);

				$vv=isset($line[$v])?$line[$v]:"";

				$data.=$vv . $sep;

				if ($j) $datadb.=",";
				$datadb .= "'" . $db->real_escape_string(htmlspecialchars(trim($vv))) . "'" ;
				$j++;
			}
			$data.= PHP_EOL;
			$datadb.=")";
			$i++;

		}  //end of cycle of violations in one file

		$sql="insert into $app ($headerdb) values $datadb";
		
		if (!$db->query($sql)){
			print $sql . "\n";
			file_put_contents("error.sql", $sql);
			die($db->error);
		}
			
		
		//file_put_contents("output.sql", $sql, FILE_APPEND);
		//file_put_contents("output.sql", "-------------------------------------------------------\n", FILE_APPEND);

		
	} // end of cycle of files

	file_put_contents($dirout. "/". $nameonly . ".csv", $header . $data); 
	return $bb;
}




function resumecsv($resume_file, $version, & $fields){

	$tam=sizeof($resume_file);
	
	for ($i=0;$i<$tam;$i++){
		$line=$resume_file[$i];
		foreach ($line as $a => $b) {

			$field=htmlspecialchars($a);
			$line_version[$field] = $b;
			
			if (!in_array($field, $fields))
				$fields[]=$field;
		}
	}
	return $line_version;
}

function resume_all($app, $resume_version, & $fields){
	$resume_all=[];
	foreach ($resume_version as $version => $line ){
		foreach ($fields as $f ){
			$resume_all[$version][$f] = $line[$f];
		}
	}
	return $resume_all;
}

function write_resume_global($app, $resume_all, & $fields ){
	global $sep;
	$file_resume="csv/$app" . "_resume.csv";
	$csv="version";

	//cabecalhos
	foreach ($fields as $f){
		$csv.="$sep $f";
	}
	$csv.= PHP_EOL;

	//data
	foreach ($resume_all as $version => $line ){
		$csv .=$version;
		
		print $version;
		print_r ($line);
		
		foreach ($fields as $f ){
			$csv .= $sep . $line[$f];
			
		}
		$csv.= PHP_EOL;
	}
	
	file_put_contents($file_resume, $csv);
}





/*    <violation beginline="43" endline="67" rule="Superglobals" ruleset="Controversial Rules" package="+global" externalInfoUrl="#" function="Show_page" priority="1">
      Show_page accesses the super-global variable $_SESSION.
    </violation>
*/

//echo $xml->file[0]->plot;

//INSERT INTO $app SET col1='value1', col2='value2'