<?php
function curl_headers($url){
  $c = curl_init($url);
  curl_setopt_array($c, array(CURLOPT_HEADER=>1, CURLOPT_RETURNTRANSFER=>1, CURLOPT_NOBODY=>1));
  $headers = curl_exec($c);
  if(preg_match("/METHOD\sNOT\sALLOWED/i",$headers)){
    curl_setopt_array($c, array(CURLOPT_NOBODY=>0, CURL_HTTPGET=>1, CURLOPT_RANGE=>1-1024));
    $headers = explode("\r\n\r\n", curl_exec($c), 2)[0];
  }
  curl_close($c);
  $headers = explode("\n", $headers);
  $res = array();
  list(, $res['status_code'], $res['status']) = explode(" ", $headers[0]);
  array_shift($headers);
  foreach($headers as $h){
    if(!trim($h))continue;
    $h = explode(":",$h,2);
    $res[trim($h[0])] = trim($h[1]);
  }
  return $res;
}
function redirect($url){
  $limit = 5;
  if(!$url)return $url;
  for($i = 0; $i < $limit; $i++){
    $headers = curl_headers($url);
    if(!array_key_exists('Location', $headers))break;
    $url = $headers['Location'];
  }
  return $url;
}
function open_media($url){
  /* TO-DO: Add support for buffering and seeking*/
  $c = curl_init($url);
  curl_exec($c);
  curl_close($c);
}
function open_page($url){
  $s = curl_init($url);
  curl_setopt($s, CURLOPT_RETURNTRANSFER, 1);
  preg_match("/(\w*:\/\/)?([\w\.]*)(\/.*)?/", $url, $url);
  $scheme = $url[1]? $url[1]: "http://";
  $domain = $url[2];
  $path = $url[3]? $url[3]: "/";
  $path = preg_replace("/(.*)(\/.*\..*)/", "$1", $path);
  $result = curl_exec($s);
  curl_close($s);
  //Convert relative URLs to absolute
  $result = preg_replace("/((src|href|action)\s*=\s*(\"|\'))(?!(http|https):\/\/)(\/[^\'\"]*)(\'|\")/i", "$1".$scheme.$domain."$5$6", $result);
  $result = preg_replace("/((src|href|action)\s*=\s*(\"|\'))(?!(http|https):\/\/)((?!\/)[^\'\"]*)(\'|\")/i", "$1".$scheme.$domain.$path."/$5$6", $result);
  //prepend the proxy URL to links
  $result = preg_replace("/((src|href|action)\s*=\s*(\"|\'))([^\'\"]*)(\'|\")/i", "$1?url=$4$5", $result);
  echo $result;
}
function show_address_form(){
  print("<form><input name='url' placeholder='Enter URL here...'/> <input type='submit' /></form>");
}
if($url = $_GET["url"]){
  $url = redirect($url);
  header("User-Agent: ".$_SERVER['HTTP_USER_AGENT'] );
  $headers = curl_headers($url);
  if(array_key_exists("Content-Type", $headers))
    header("Content-Type: ".$headers["Content-Type"]);
  if(array_key_exists("Content-Length", $headers))
    header("Content-Length: ".$headers["Content-Length"]);
  
  if(preg_match("/text/",$headers["Content-Type"]))
    open_page($url);
  else
    open_media($url);
}
else 
  show_address_form();

//   $c = curl_init($_GET["url"]);
//   curl_setopt_array($c, array(CURLOPT_HEADER=>1, CURLOPT_RETURNTRANSFER=>1, CURLOPT_NOBODY=>1));
//   print(curl_exec($c));
?>
