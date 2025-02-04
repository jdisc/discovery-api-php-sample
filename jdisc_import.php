<?php
/**
 * REST API-Client JDisc (GraphQL) 
 * @author Ulf Haase
 * @package Interfaces
 * @subpackage JDisc
 * @copyright Ulf Haase Softwareentwicklung & Beratung
 * @version 0.01.01
 * @filesource
 */

// !!! Update defines below with your values !!!
define("JDISC_GRAPH_QL_URL"     , "https://127.0.0.1/graphql"); // Replace 127.0.0.1 with your IP address
define("JDISC_GRAPH_QL_USER"    , "Administrator");             // Replace Administrator with username
define("JDISC_GRAPH_QL_PASSWORD", "<your_password>");           // Put your password

//#######################################################################################
//##                                    Functions()                                    ##
//#######################################################################################

function JDisc_GQL($myArr, $bearer="")
{
$myJSON = json_encode($myArr);
$myJSON = str_replace("\r\n", "\n", $myJSON); //cleaning up windows line breaks
$myJSON = str_replace("'"   , "\"", $myJSON); //cleaning up single quotes
//echo $myJSON."\n";

$format       = "json";
$content_type = "application/".$format;
$port         = 0;

$header_array=array();
if ($bearer<>"")
    $header_array[]="Authorization: Bearer ".$bearer;
    
$header_array[]="Accept: application/".$format;
$header_array[]="Content-Type: ".$content_type;

$curl = curl_init();

$curl = curl_init(JDISC_GRAPH_QL_URL);
curl_setopt($curl, CURLOPT_URL              , JDISC_GRAPH_QL_URL);
curl_setopt($curl, CURLOPT_POST             , true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER   , true);
curl_setopt($curl, CURLOPT_FAILONERROR      , true);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 800);     // 800ms for connection timeout
curl_setopt($curl, CURLOPT_TIMEOUT_MS       , 10000);   //10sec. for response timeout
if ($port>0)
    curl_setopt($curl, CURLOPT_PORT         , $port);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST   , 2);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER   , FALSE);   // if you are using self-signed certificates then you have to use FALSE, otherwise TRUE would be wiser...

curl_setopt($curl, CURLOPT_HTTPHEADER       , $header_array);
curl_setopt($curl, CURLOPT_HEADER           , FALSE);   //--> for Debugging only: TRUE: showing HTTP-HEADER of Response-Page 
                                                        //e.g.: HTTP/1.1 100 Continue HTTP/1.1 200 OK Date: Sat, 19 Feb 2022 10:53:44 GMT Server: Apache/2.4.38 (Debian) Vary: Accept-Encoding Content-Length: 1558 Content-Type: application/xml
curl_setopt($curl, CURLOPT_POSTFIELDS       , $myJSON);

$content = curl_exec($curl); // execute the curl command

$error_no =curl_errno($curl);
$error_msg=curl_error($curl);
$http_code=curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl); // close the connection

if ($error_no>0)
    die("CURL-Error ".$error_no.": ".$error_msg."\nhttp response code:".$http_code."\n");

$json_array=json_decode($content, true); // "true" to get PHP array instead of an object

return $json_array;
}


//#######################################################################################
//##                                         Main()                                    ##
//#######################################################################################

//--------------------------------------Login--------------------------------------------

$myArr=array();
$myArr["operationName"]="login";
$myArr["query"]=
"mutation login {
   authentication {
       login (login: \"".JDISC_GRAPH_QL_USER."\", password: \"".JDISC_GRAPH_QL_PASSWORD."\") {
           accessToken
           refreshToken
           status
       }
   }
}
";
$json_array=JDisc_GQL($myArr, $bearer="");
$status=$json_array["data"]["authentication"]["login"]["status"];

if ($status=="SUCCESS")
    {
    $accessToken =$json_array["data"]["authentication"]["login"]["accessToken"];
    $refreshToken=$json_array["data"]["authentication"]["login"]["refreshToken"];
    //echo "accessToken\n".$accessToken."\n";
    //echo "Status: ".$status."\n";
    }
else
    {
    show_dump($json_array);
    die("died due to unsuccessful login!!!");
    }

//------------------------------------JQL-Query------------------------------------------

    $myArr=array();
$myArr["query"]=
"query test {
      devices {
        findAll {
          id
          name
          computername
          type
          manufacturer
          bios {
            version
          }
          serialNumber
          logicalSerialNumber
          hwVersion
          model
          operatingSystem {
            osFamily
            osVersion
            kernelVersion
          }
          mainIPAddress
          mainIP4Transport {
            ipAddress
            subnetMask
          }
        }
      }
    }
";
$json_array=JDisc_GQL($myArr, $accessToken);

//--------------------------------------Output-------------------------------------------

$device_array=$json_array["data"]["devices"]["findAll"];
if ($device_array=="")
    echo "No Records found!!!\n";
else
    {
    foreach ($device_array AS $id=>$device_array)
        {
        echo "ID           : ".$id."\n"
            ."computername : ".$device_array["computername"]."\n"
            ."name         : ".$device_array["name"]."\n"
            ;
        if (isset($device_array["mainIP4Transport"]["ipAddress"]))
            echo "ipAddress    : ".$device_array["mainIP4Transport"]["ipAddress"]."\n"
                ."subnetMask   : ".$device_array["mainIP4Transport"]["subnetMask"]."\n";
        echo "type         : ".$device_array["type"]."\n"
            ."manufacturer : ".$device_array["manufacturer"]."\n"
            ."model        : ".$device_array["model"]."\n"
            ."serialNumber : ".$device_array["serialNumber"]."\n"
            ."osFamily     : ".$device_array["operatingSystem"]["osFamily"]."\n"
            ."osVersion    : ".$device_array["operatingSystem"]["osVersion"]."\n"
            ."kernelVersion: ".$device_array["operatingSystem"]["kernelVersion"]."\n"
            ."---------------------------------------------------------------------------------------\n";
        }
    }
?>