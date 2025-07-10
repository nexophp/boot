<?php

/**
 * 从文件中安装SQL
 * install_sql(local_sql_file,function($sql){
 * 	$db->query($sql);
 * })
 */
function install_sql($file, $call)
{
	$fp =  fopen($file, "r");
	while ($sql = install_sql_get_next($fp)) {
		$sql = trim($sql);
		if ($sql) {
			$call($sql);
		}
	}
	fclose($fp);
}
/*
* 从文件中逐条取sql 
*/
function install_sql_get_next($fp)
{
	$sql = "";
	while ($line = @fgets($fp, 40960)) {
		$line = trim($line);
		$line = str_replace("////", "//", $line);
		$line = str_replace("/", "'", $line);
		$line = str_replace("//r//n", "chr(13).chr(10)", $line);
		$line = stripcslashes($line);
		if (strlen($line) > 1) {
			if ($line[0] == '-' && $line[1] == "-") {
				continue;
			}
		}
		$sql .= $line . chr(13) . chr(10);
		if (strlen($line) > 0) {
			if ($line[strlen($line) - 1] == ";") {
				break;
			}
		}
	}
	return $sql;
}
