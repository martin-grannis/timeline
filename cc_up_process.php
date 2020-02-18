<?php

// are loading or downloading?
if ( $_POST['submit'] == "Analyse" ) {

	$target_dir    = "/home/martbarr/component_convert";
	$filename      = basename( $_FILES["fileToUpload"]["name"] );
	$tidy_filename = preg_replace( '/\s+/', '_', $filename );

	$target_file = $target_dir . '/' . $tidy_filename;

	$result = move_uploaded_file( $_FILES["fileToUpload"]["tmp_name"], $target_file );

// unzip the file into a folder of the same name
	//$destFolder = '/home/martbarr/component_convert/component_temp/' . $tidy_filename;
	$temp_folder = '/mb_working/';

	$destFolder = $_SERVER['DOCUMENT_ROOT'] . $temp_folder . $tidy_filename;
	// create the folder if it is not there
	// else delete and recreate
	$delCommand = 'rm -r ' . $destFolder;
	exec( $delCommand );
	mkdir( $destFolder );

	$zip = new ZipArchive;
	$res = $zip->open( $target_file );

	if ( $res === true ) {
		$zip->extractTo( $destFolder );
		$zip->close();
	}

//else {
//	echo 'doh!';
//}

// open the file to process.
	$componentFile   = $destFolder . '/component.json';
	$resourcesFolder = $destFolder . '/resources';

//$resFiles =[]; // array for the urls of the files
//// process the resources
//	// a list of urls for the resources.
//	if ( file_exists( $resourcesFolder ) ) {
//		recursiveDir($resourcesFolder, 'printFunc', 'printFunc');
//	}

// get the URL for theunpacked resources
	$resource_url = "https://" . $_SERVER['SERVER_NAME'] . $temp_folder . $tidy_filename . '/resources';
	// process the component.json contents
	$json_data = file_get_contents( $componentFile );
	$myJ       = json_decode( $json_data, true );

	$sender = $_SERVER['HTTP_REFERER'];
// post results back to form

	include_once( 'html_formatter_function.php' );
	$format = new Format;
//$formatted_html = $format->HTML($html);

	$postData = [
		"html_field"   => $format->HTML( $myJ['html'] ),
		"css_field"    => $myJ['stylesheet'],
		"uuid"         => $myJ['UUID'],
		"appId"        => $myJ['appId'],
		"appVersion"   => $myJ['appVersion'],
		"buildNumber"  => $myJ['buildNumber'],
		"description"  => $myJ['description'],
		"element_name" => $myJ['element_name'],
		"framework"    => $myJ['framework'],
		"level"        => $myJ['level'],
		"mobile_first" => $myJ['mobile_first'],
		"name"         => $myJ['name'],
		"resources"    => $myJ['resources'],
		"stylesheet"   => $myJ['stylesheet'],
		"type"         => $myJ['type'],
		"units"        => $myJ['units'],
		"user_email"   => $myJ['user_email'],
	];
	if ( file_exists( $resourcesFolder ) ) {
		$postData["resource_url"] = $resource_url;
	}

	session_start();
	$_SESSION = $postData;
	session_write_close();
	header( "location: cc_up.php " );

} else {
	// build a new component


//	$myJ = $_POST;

	$myJ['UUID']              = $_POST['uuid'];
	$myJ["additional_states"] = [
		"Container" => [
			"outofview" => [
				"character" => ".",
				"name"      => "Out of View",
				"value"     => 'outofview'
			]
		]
	];


	$myJ['appId']        = $_POST['appId'];
	$myJ['appVersion']   = intval($_POST['appVersion']);
	$myJ['buildNumber']  = intval($_POST['buildNumber']);
	$myJ['description']  = $_POST['description'];
	$myJ['element_name'] = $_POST['element_name'];
	$myJ['framework']    = $_POST['framework'];

	$snip= $_POST['html_field'];
	$snip = str_replace("\t", '', $snip); // remove tabs
	$snip = str_replace("\n", '', $snip); // remove new lines
	$snip = str_replace("\r", '', $snip); // remove carriage returns

	$myJ['html']         = $snip;
	$myJ["html_embed"]   = [];
	$myJ['level']        = $_POST['level'];
	$myJ['mobile_first'] = $_POST['mobile_first'] == 1 ? true : false;
	$myJ['name']         = $_POST['name'];
	$myJ['resources']    = $_POST['resources'];

	$snip= trim($_POST['css_field']);
	$snip = str_replace("\t", '', $snip); // remove tabs
	$snip = str_replace("\r", '', $snip); // remove carriage returns

	$myJ['stylesheet']   = $snip;
	$myJ['type']         = $_POST['type'];
	$myJ['units']        = $_POST['units'];
	$myJ['user_email']   = $_POST['user_email'];


	// reencode
	$encodedJson = json_encode( $myJ );

	$tidyComponentName = preg_replace( '/\s+/', '_', $myJ['name'] );
	$tempFolder        = sys_get_temp_dir() . '/' . $tidyComponentName;
	// remove just in case
	$delCommand = 'rm -r ' . $tempFolder;
	exec( $delCommand );
	mkdir( $tempFolder );
	// save new component.json file in temp folder
	file_put_contents( $tempFolder . '/component.json', $encodedJson );
	$zip = new ZipArchive;
	$res = $zip->open( $tempFolder . '/' . $tidyComponentName . '.cccomp', ZipArchive::CREATE | ZipArchive::OVERWRITE );
	$zip->addFile( $tempFolder . '/component.json', 'component.json' );
	$zip->close();
	header( 'Content-disposition: attachment; filename=' . $tidyComponentName . '.cccomp' );
	header( 'Content-type: application/zip' );
	readfile( $tempFolder . '/' . $tidyComponentName . '.cccomp' );
	die;
}

