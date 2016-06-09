<?php 
/*Scrape Base Testing*/

include('simplehtmldom_1_5/simple_html_dom.php');

header('Content-Type: application/json');

if(!isset($_GET['url'])){
	echo '{"response": "empty"}';
	die();
}else{

	if(substr_count($_GET['url'], 'http') == 0){
		echo '{"response": "error", "reason": "Missing http(s) from url"}';
		die();
	}
	$run = new fetchSocial();
}

class fetchSocial{
	var $url = '';
	var $urls = array();
	var $scanned_pages = array();
	var $message = '';

	public function __construct(){
		$this->url = $_GET['url'];

		$this->scanPage(true);
	}
	public function scanPage($end, $url = false)
	{

		if($url == false){
			$set_url = $_GET['url'];

			$url = $set_url;
    		$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
			curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_TIMEOUT,10);
			$output = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if((int)$httpcode > 400){
				echo '{"response": "error", "reason": "Cannot access page"}';
				die();
			}

		}else{
			$set_url = $url;

			//Dont check same page again
			if($set_url == $_GET['url']){
				return array();
			}

			if(substr( $url, 0, 1 ) === "#"){
				$set_url = $_GET['url'].$set_url;
			}

			if(substr( $url, 0, 1 ) === "/"){
				$set_url = $_GET['url'].$set_url;
			}else{
				//Does not start with a slash, check if more than one dot
				if(substr_count($url, '.') == 1){
					$set_url = $_GET['url'].'/'.$set_url;
				}
			}

			//Dissmiss if link is a mailto
			if(substr( $url, 0, 7 ) === "mailto:"){
				return array();
			}
		}
		
		//Check if page is scannable
		if($end == false){
			$url = $set_url;
	    	$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
			curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_TIMEOUT,10);
			$output = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if((int)$httpcode > 400){
				return array();
			}
		}

		//echo '<hr>Checking - '.$set_url.'<hr>';

		$this->scanned_pages[] = $set_url;

		$context = stream_context_create();
		stream_context_set_params($context, array('user_agent' => 'Social/Links'));
		$html = file_get_html($set_url, 0, $context);

		//Possible social links
		$urls = array();
		//Other pages in site to check
		$toScan = array();

		//Lookup all links
		if($html == false){
			if($end == false){
				return $urls;
			}else{
				echo '{"response": "empty"}';
				die();
			}
		}

		foreach($html->find('a') as $e){

			//Also capture email address
			if (strpos($e->href, 'mailto:') !== false) {
				$urls[] = $e->href;
			}

			if($this->isexternal($e->href) == true){
				$urls[] = $e->href;
			}else{
				$toScan[] = $e->href;
			}
		}

		if($end == false){
			return $urls;
		}


		if($end == true){

			//Remove duplicates from array
			$toScan = array_unique($toScan);

			$i = 0;
			//Set maxo of 5 pages
			$max = 4;
			foreach ($toScan as $page) {
				if($i <= $max){
					$moreUrls = $this->scanPage(false, $page);

					$urls = array_merge($urls,$moreUrls);
				}else{
					$this->message = 'Page limit exceeded will only check first 5 pages';
				}
				$i++;
			}

			$this->urls = $urls;

			$this->sendResponse();
		}
	}

	//Helper function
	public function isexternal($url) {

		$baseurl = $_GET['url'];

		$baseurl = preg_replace("(^https?://)", "", $baseurl);
		$baseurl = preg_replace("(^www.)", "", $baseurl);

	 	$components = parse_url($url);
	 	if ( empty($components['host']) ) return false;  // we will treat url like '/relative.php' as relative
	 	if ( strcasecmp($components['host'], $baseurl) === 0 ) return false; // url host looks exactly like the local host
	 	return strrpos(strtolower($components['host']), '.'.$baseurl) !== strlen($components['host']) - strlen('.'.$baseurl); // check if the url host is a subdomain
	}


	public function sendResponse()
	{
		//Remove duplicates from array
		$result = array_unique($this->urls);
		//Remove duplicates from array
		$scanned = array_unique($this->scanned_pages);

		$twitter = array();
		$linkedin = array();
		$github = array();
		$facebook = array();
		$angel_list = array();
		$stackoverflow = array();
		$medium = array();
		$youtube = array();
		$flickr = array();
		$instagram = array();
		$documents = array();
		$email_address = array();

		foreach ($result as $link) {

			//Check for email address
			if (strpos($link, 'mailto:') !== false) {
				$email = explode('mailto:', $link);
				$email_address[] = $email[1];
			}

			//Check for twitter
			if (strpos($link, 'twitter.com/') !== false) {
			    $twitter[] = $link;
			}
			//Check for linkedin
			if (strpos($link, 'linkedin.com/') !== false) {
			    $linkedin[] = $link;
			}
			//Check for github
			if (strpos($link, 'github.com/') !== false) {
			    $github[] = $link;
			}
			//Check for facebook
			if (strpos($link, 'facebook.com/') !== false) {
			    $facebook[] = $link;
			}
			//Check for angel list
			if (strpos($link, 'angel.co/') !== false) {
			    $angel_list[] = $link;
			}
			//Check for stackoverflow
			if (strpos($link, 'stackoverflow.com/') !== false) {
			    $stackoverflow[] = $link;
			}
			//Check for medium
			if (strpos($link, 'medium.com/') !== false) {
			    $medium[] = $link;
			}
			//Check for youtube
			if (strpos($link, 'youtube.com/') !== false) {
			    $youtube[] = $link;
			}
			//Check for flickr
			if (strpos($link, 'flickr.com/') !== false) {
			    $flickr[] = $link;
			}
			//Check for instagram
			if (strpos($link, 'instagram.com/') !== false) {
			    $instagram[] = $link;
			}
			//Check for documents
			if (strpos($link, 'docs.google.com/') !== false) {
			    $documents[] = $link;
			}
			if (strpos($link, 'dropbox.com/s/') !== false) {
			    $documents[] = $link;
			}
			if (strpos($link, 'dropbox.com/sh/') !== false) {
			    $documents[] = $link;
			}
		}

		$response = array();
		$response['response'] = 'success';
		$response['scanned_pages'] = $scanned;
		if(!empty($this->message)){
			$response['message'] = $this->message;
		}
		$response['results'] = array();
		$response['results']['twitter'] = $twitter;
		$response['results']['linkedin'] = $linkedin;
		$response['results']['github'] = $github;
		$response['results']['facebook'] = $facebook;
		$response['results']['angel_list'] = $angel_list;
		$response['results']['stackoverflow'] = $stackoverflow;
		$response['results']['medium'] = $medium;
		$response['results']['youtube'] = $youtube;
		$response['results']['flickr'] = $flickr;
		$response['results']['instagram'] = $instagram;
		$response['results']['documents'] = $documents;
		$response['results']['email_address'] = $email_address;

		ksort($response['results']);

		//Convert to json and make response
		echo json_encode($response, JSON_PRETTY_PRINT);
	}
}




