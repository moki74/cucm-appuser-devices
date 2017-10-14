<?php
    ini_set("soap.wsdl_cache_enabled", "0");
    $conf = parse_ini_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "cucm.conf");

    $host = $conf['host'];
    $username = $conf['username'];
    $password = $conf['password'];
    if (!isset($argv[1]) || !isset($argv[2]) || !in_array($argv[2], ["add","del"])) {
        die("ERROR: Missing parameters, check if you specify appuser and action('add','del')");
    }
    $appUser = $argv[1];
    $action = $argv[2];
    $userExists = false;
      
    
    //  Read file and put phones mac's in array - delimiters can be "," , ";" ,"\n"
    $myfile = fopen("phones.txt", "r") or die("Unable to open template file!");
    $phones = fread($myfile, filesize("phones.txt"));
    fclose($myfile);
    $phones = multiexplode(array(",",".","|",":","\n"), $phones);
    
    //connect to CUCM via Soap
    $context = stream_context_create(array('ssl'=>array('allow_self_signed'=>true)));
    $wsdl = dirname(__FILE__) . DIRECTORY_SEPARATOR. $conf['cucm_version'] . DIRECTORY_SEPARATOR . 'AXLAPI.wsdl';
    $client = new SoapClient(
        $wsdl,
        array('trace'=>true,
        'exceptions'=>true,
        'location'=>"https://".$host.":8443/axl",
        'login'=>$username,
        'password'=>$password,
        'stream_context'=> stream_context_create(
            ['ssl' => [
                        'verify_peer'              => false,
                        'verify_peer_name'         => false,
                        'allow_self_signed'        => true,
                      ],
            ]
        ),
        )
    );
    
    // Check if appuser parametar exists
    $sql = "select * from applicationuser" ;
    $response = $client->executeSQLQuery(array("sql" => $sql));
    $appusers = $response->return->row;
    foreach ($appusers as $appuser) {
        if ($appuser->name == $appUser) {
            $userExists = true;
        }
    }
    if (!$userExists) {
        die("ERROR: Check if appuser : $appUser exists in CUCM ! \n");
    }
    
    // Make folder with appuser name - there will be logs.
    if (!file_exists($appUser)) {
        mkdir($appUser);
    }

    writeAppUserDataBeforeUpdate();

    $num_of_proccessed_phones = 0;
    $success = array();
    $failed = array();

    foreach ($phones as $phone) {
        if ($action == "add") {
            $sql = "insert into applicationuserdevicemap (fkapplicationuser, fkdevice, tkuserassociation) select au.pkid, d.pkid, 1 from 		 applicationuser au cross join device d where au.name = '$appUser' and d.name = '$phone' and
	       		        d.pkid not in (select fkdevice from applicationuserdevicemap where fkapplicationuser = au.pkid )";
        }
        if ($action == "del") {
            $sql = "delete from applicationuserdevicemap where 	fkapplicationuser = (select pkid from applicationuser au where 	        	   au.name = '$appUser') and fkdevice in (select pkid from device d where d.name = '$phone')" ;
        }
          
        $response = $client->executeSQLUpdate(array("sql" => $sql));
        if ($response->return->rowsUpdated != 1) {
            $failed[] = $phone;
        } else {
            $success[] = $phone;
        }
    }

    if (count($failed) > 0) {
        exit("Script finished with ERROR : " .count($failed) . " records failed - see log file \n");
    } else {
        exit("Script finished SUCCESSFULLY : " .count($success) . " records updated \n");
    }
 
    

    // function to convert various delimiters to common one
    function multiexplode($delimiters, $string)
    {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);
        return  $launch;
    }
    // write assosiated devices before update
    function writeAppUserDataBeforeUpdate()
    {
        global $appUser;
        global $client;
        $sql = "select d.name from device d
        		 join applicationuserdevicemap am on d.pkid = am.fkdevice
         		 join applicationuser ap on ap.pkid = am.fkapplicationuser
         		 where ap.name = '$appUser'";
        $response = $client->executeSQLQuery(array("sql" => $sql));
        //check if there was phones associated with this appuse
        if (isset($response->return->row)) {
            $phones = $response->return->row;
            //var_dump($phones);
            $myfile = fopen("$appUser".DIRECTORY_SEPARATOR."beforeLastUpdate.txt", "w");
            foreach ($phones as $phone) {
                fwrite($myfile, $phone->name ."\n");
            }
            fclose($myfile);
        }
    }

    function writeLog()
    {
        global $appUser;
        global $success;
        global $failed;
        $myfile = fopen("$appUser".DIRECTORY_SEPARATOR."log.txt", "wa");
        fwrite($myfile, date("d-m-Y h:i:s", time())."\n\n");
        fwrite($myfile, "Number of succesful records : " . count($success) . "\n\n");
        fwrite($myfile, "Number of failed records : " . count($failed) . "\n");
        if (count($failed) > 0) {
            foreach ($failed as $phone) {
                fwrite($myfile, $phone."\n");
            }
        }

        fclose($myfile);
    }
