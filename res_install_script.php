#!/usr/bin/php
<?php
if ($argc != 2 && $argc < 2) {
?>
Argument error (argument is missing).
<?php
} else if ($argc != 2 && 2 < $argc) {
?>
Argument error (argument exceeded).
<?php
} else {
	if ( !file_exists($argv[1]) ) {
?>
The specified path does not exist.
<?php
	} else {
		$res_directory = "vendor\\hk-r\\lib-plum\\res\\";
		if ($handle = opendir($res_directory)) {
			while(false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != "..") {
					rename($res_directory . $entry, $argv[1] . $entry);
				}
			}
			closedir($handle);
		}
	}
}
?>