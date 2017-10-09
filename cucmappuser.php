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

   
   // die($appUser . ", " . $action);
    
    /**
     *  Read file and put phones mac's in array - delimiters can be "," , ";" ,"\n"
     *
    */
   
    $myfile = fopen("phones.txt", "r") or die("Unable to open template file!");
    $phones = fread($myfile, filesize("phones.txt"));
    fclose($myfile);
    $phones = multiexplode(array(",",".","|",":","\n"), $phones);
//    print_r($phones);
// foreach ($phones as $phone) {
//     if ($phone == "") {
//         continue;
//     }
//     echo $phone."\n";
// }
//     die();

    
    //$client = new SoapClient(dirname(__FILE__) . DIRECTORY_SEPARATOR. $conf['cucm_version'] . DIRECTORY_SEPARATOR . 'AXLAPI.wsdl');
    //$client = new SoapClient('http://soap.amazon.com/schemas3/AmazonWebServices.wsdl');
    //var_dump($client->__getFunctions());
   
    
    $context = stream_context_create(array('ssl'=>array('allow_self_signed'=>true)));
    $wsdl = dirname(__FILE__) . DIRECTORY_SEPARATOR. $conf['cucm_version'] . DIRECTORY_SEPARATOR . 'AXLAPI.wsdl';
    //echo ($wsdl);
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
        die("ERROR: Check if appuser : $appUser exists in CUCM !");
    }
   // // var_dump($appusers);
    

   //  $sql = "select * from applicationuser" ;
   //  $response = $client->executeSQLQuery(array("sql" => $sql));
   
   //  $appusers = $response->return->row;
   //  foreach ($appusers as $appuser) {
   //      echo $appuser->name."\n";
   //  }
   // // var_dump($appusers);
   //  die();
        
    
    $num_of_proccessed_phones = 0;
    $success = [];
    $failed     = [];

    foreach ($phones as $phone) {
        if ($action == "add") {
            $sql = "insert into applicationuserdevicemap (fkapplicationuser, fkdevice, tkuserassociation) select au.pkid, d.pkid, 1 from 		applicationuser au cross join device d where au.name = '$appUser' and d.name = '$phone' and
	       		    d.pkid not in (select fkdevice from applicationuserdevicemap where fkapplicationuser = au.pkid )" ;
        }
        if ($action == "del") {
            $sql = "delete from applicationuserdevicemap where 	fkapplicationuser = (select pkid from applicationuser au where 		au.name = '$appUser') and fkdevice in (select pkid from device d where d.name = '$phone')" ;
        }
          
        $response = $client->executeSQLUpdate(array("sql" => $sql));
        if ($response->return->rowsUpdated != 1) {
            $failed[] = $phone;
        } else {
            $success[] = $phone;
        }
    }

    echo "Passed :\n";
    print_r($success);
    echo "Failed :\n";
    print_r($failed);

    // $response = $client->getPhone(array("name"=>"SEPF09E636E7B79"));
    // $sql = "insert into applicationuserdevicemap (fkapplicationuser, fkdevice, tkuserassociation) select au.pkid, d.pkid, 1 from applicationuser au cross join device d where au.name = 'TestJtapi' and d.name in ($phones) and
    // 	d.pkid not in (select fkdevice from applicationuserdevicemap where fkapplicationuser = au.pkid )" ;

    
    //DELET SQL
    // $sql = "delete from applicationuserdevicemap where 	fkapplicationuser = (select pkid from applicationuser au where 						au.name = 'TestJtapi') and fkdevice in (select pkid from device d where d.name in  ($phones) )" ;
    
    // $sql = "select * from applicationuserdevicemap where fkapplicationuser = (select pkid from applicationuser au where 						au.name = 'TestJtapi' )" ;
    
    
    


    function multiexplode($delimiters, $string)
    {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);
        return  $launch;
    }
