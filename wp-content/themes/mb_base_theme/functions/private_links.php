<?php


// we fixup the private links to remove the extra info

add_action( 'pre_get_posts', 'catch_private_links' );
function catch_private_links( $query ) {

// if ( areWeLoggingIn($query)){
// 	return;
// }


if (isset($query->query['resource']) && isset($query->query['name'])){

	//if ( $query->query_vars['post_type'] == "resource" ) {

		// we rewrite query AND query vars removing last section of uri

		$rPos = strrpos( $query->query['resource'], '/' );
		$nPos = strrpos( $query->query['name'], '/' );
if ($rPos){ // ONLY if there is a slash and private link after name
		$query->query['resource'] = $query->query_vars['resource'] = substr( $query->query['resource'], 0, $rPos );
		$query->query['name']     = $query->query_vars['name'] = substr( $query->query['name'], 0, $nPos );
}
		//

	}


}

function seourl($phrase, $maxLength = 100000000000000) {
	$result = strtolower($phrase);

	$result = preg_replace("~[^A-Za-z0-9-\s]~", "", $result);
	$result = trim(preg_replace("~[\s-]+~", " ", $result));
	$result = trim(substr($result, 0, $maxLength));
	$result = preg_replace("~\s~", "-", $result);

	return $result;
}


// we do the security checks

add_filter( 'template_include', 'intercept_private_url', 99 );

function intercept_private_url( $template ) {
	global $post, $wp_query;

// if my-account and we are rmotely logged in fo to unauthorisedRemote template
	
// mb patch
// if remauth do not allow access to my-account
	if ($wp_query->query['pagename'] =="my-account" && isRemoteAuth()){

	// redirect to unauthorised template
		return str_replace( "page.php", "single-unauthorisedProfileAccess.php", $template );
	}
  

	$secretString = "shdjkshjwrehjnwejsd782437243jk";
	
// get query uri parts
	$u       = $_SERVER["REQUEST_URI"];
	$urlBits = explode( '/', $_SERVER["REQUEST_URI"] );

	if ( isset($urlBits[3]) && strpos($urlBits[3],"?preview_id") !== false){
		return $template;
	}

	// check remoteauth login
	if ( $urlBits[1] == "remauth" ) {
		
		$incomingHash = str_replace( "%99*", "%2F", $urlBits[2]);
		//$incomingHash = str_replace( "%99!", "%24", $incomingHash);
		$incomingHash =  urldecode($incomingHash);
		
		if (empty($incomingHash)){
			return $template;
		}

		// date now is date('dmYH',time()
		$d = date('dmYH',time());
		
		// verify against last 2 hours worth of hashes into a comparison table
		$found=false;
		for($c=0;$c<3;$c++){

			// date x hours ago
			date_default_timezone_set("UTC");
			$dt = date('dmYH',(time()-($c*(60*60))));
			$res = password_verify($secretString.$dt,$incomingHash);

			if ($res){
				$found=true;
				break;
			}
			
		}
		
		if (!$found){
			return str_replace( "404", "single-unauthorisedRemote", $template );
		}	
		
		// go to to remote_unauthorised page as the link is older than 2 hours

			// so we have a good incoming url
			// do a transparent login and go to home page
			$creds = array();
			$creds['user_login'] = 'remoteHubUser';
			$creds['user_password'] = 'shdjkshjwrehjnwejsd782437243jk';
			
			$creds['remember'] = false;
			
			$user = wp_signon( $creds, false );
			if ( is_wp_error($user) )
			{
				$result['type'] = "error";
				$result['message'] =  __('Username or password is incorrect', 'rs-theme');
			}
			else
			{
				wp_redirect(home_url());
			}			

	
	}



	if ( $urlBits[1] == "resource" ) {

		// does the 3rd section have a random string?
		// no
		if ( empty( $urlBits[3] ) ) {
			return $template;
		}

		// proper return
		$mytemplate = "single-private-resource";

		// look up the custom string in the record to see if it matches the added string
		// get the posts meta
		$post_private_url = get_post_meta( $post->ID, 'private_url', true );
		// mod the template to the private-uri version
		if ( $post_private_url !== $urlBits[3] ) {
			$mytemplate = "single-noview-private-resource";
			// yay we have a secure private post so return the secure template

		}

		return str_replace( "single-resource", $mytemplate, $template );

	}

	return $template;
}

