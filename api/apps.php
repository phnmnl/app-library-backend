<?php
require('../lib/simple_html_dom.php');
require('appdb.php');

ini_set('display_errors', 1);
//error_reporting(E_ALL ^ E_NOTICE);
error_reporting(E_ERROR | E_PARSE);
/**
 * Created by PhpStorm.
 * User: sijinhe
 * Date: 07/10/2016
 * Time: 16:49
 */
$path = "../wiki-html";
//    $host = "http://".gethostname()."/app-library-backend/";
$host = "http://phenomenal-h2020.eu/wiki/wiki/app-library-backend/";

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':

        if(isset($_GET['app']) && $_GET['app'] != "") {
            getAppWithResponse($_GET['app']);
        }  else if(
               (isset($_GET['functionality']) && $_GET['functionality'] != "")
            || (isset($_GET['approaches']) && $_GET['approaches'] != "")
            || (isset($_GET['instrument']) && $_GET['instrument'] != "")
        ){
            getAppWithTechnology($_GET['functionality'], $_GET['approaches'], $_GET['instrument']);
        } else {
            getAllApp();
        }
        break;
    default:
        print_r(json_encode(createEmptyJSONDataArray()));
}

function getAllApp(){
    global $path;

    $dir = $path;

    $data = getFilenames($dir, '');

    $json = [];

    $json['result'] = 1;
    $json['data'] = [];


    foreach ($data['data'] as $appName){
        $appName = substr($appName, 10); //always prefix with 'container-'
        if(file_exists(getPath($appName))) {
            $json['data'][] = getApp($appName);
        }
    }

//    foreach (getAllAppDBId() as $appName){
//        $json['data'][] = getAppDBApp($appName);
//    }

    header('Content-Type: application/json');
    echo json_encode($json);
}

function getAppWithTechnology($technology, $approaches, $instrument) {
    global $path;

    $dir = $path;

    $data = getFilenames($dir, '');

    $json = [];

    $json['result'] = 1;
    $json['data'] = [];

    if($technology == ""){
        $client1 = [];
    } else {
        $client1 = explode(" ", strtoupper($technology));
    }


    if($approaches == ""){
        $client2 = [];
    } else {
        $client2 = explode(" ", strtoupper($approaches));
    }

    if($instrument == ""){
        $client3 = [];
    } else {
        $client3 = explode(" ", strtoupper($instrument));
    }

    foreach ($data['data'] as $appName){

        $appName = substr($appName, 10); //always prefix with 'container-'
        if(file_exists(getPath($appName))) {
            $app = getApp($appName);

            $server1 = array_unique($app['functionality']);
            $server2 = array_unique($app['approaches']);
            $server3 = array_unique($app['instrument']);

            //print_r(sizeof($client2));

            $result1 = matchArray($client1, $server1);
            $result2 = matchArray($client2, $server2);;
            $result3 = matchArray($client3, $server3);;

            $result = $result1 + $result2 + $result3;

            if($result > 0){
                $json['data'][] = $app;
            }
        }
    }

//    foreach (getAllAppDBId() as $appName){
//
//        $app = getAppDBApp($appName);
//        $server = array_unique(array_merge($app['technologies'], $app['analysis']));
//
//        $result = array_intersect($client, $server);
//        if(sizeof($result) > 0){
//            $json['data'][] = $app;
//        }
//    }

    header('Content-Type: application/json');
    echo json_encode($json);
}

function matchArray($client, $server){

    $result = 0;

    if(!empty($client)){

        $result = sizeof(array_intersect($client, $server));

    }

    return $result;
}

function getAppWithResponse($appName) {
    $json['result'] = 1;
    $json['data'] = [];
    if(!is_numeric($appName)){
        $appName = substr($appName, 10); //always prefix with 'container-'
        if(file_exists(getPath($appName))) {
            $json['data'][] = getApp($appName);
        }
    } else {
        $json['data'][] = getAppDBApp($appName);
    }
    echo json_encode($json);
}

function getPath($appName){
    global $path;

    $name = $appName;

    $readmePath = $path."/"."container-".$name."/"."README.html";

    return $readmePath;
}

function getApp($appName){
    global $host;
    $imagePath = $host."wiki-markdown/container-".$appName."/";

    $readmePath = getPath($appName);

    $html = file_get_html($readmePath);

    $title = "";
    $version = "";
    $logo = "";
    $short_description = "";
    $description = "";
    $key_features = [];
    $publications = [];
    $screenshots = [];
    $provider = [];
    $website = [];
    $instructions = "";
    $installation = "";
    $functionality = [];
    $contributor = [];
    $approaches = [];
    $instrument = [];
    $gitRepo = [];

    foreach($html->find('h1') as $element){
        $title = $element->plaintext;

        if($element->next_sibling()->tag != "h2"){
            $version = $element->next_sibling()->plaintext;
        }
        break;
    }

    foreach($html->find('img') as $element){

        if($element->getAttribute("alt") == "Logo"){
            $logo = $element->getAttribute("src");
            break;
        }

    }

    foreach($html->find('img') as $element){

        if($element->getAttribute("alt") == "screenshot"){
            $screenshots[] = $imagePath.$element->getAttribute("src");

        }
    }



    foreach($html->find('h2') as $element){
        if($element->plaintext == "Short description" || $element->plaintext == "Short Description"){

            $element = skipComment($element, "p");

            while($element->next_sibling()->tag == "p"){
                $short_description = $element->next_sibling()->plaintext;
                break;
            }

        }

        if($element->plaintext == "Description"){

            $element = skipComment($element, "p");

            while($element->next_sibling()->tag == "p"){
                $description .= $element->next_sibling()->plaintext;
                $description .= " ";
                $element = $element->next_sibling();
            }

        }

        if($element->plaintext == "Key features"){

//            $element = skipComment($element, "p");
            $element = skipComment($element, "ul");

            if( $element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {
                    $key_features[] = $element->next_sibling()->children($i)->plaintext;
                    $i++;
                }
            }

        }


        if($element->plaintext == "Functionality"){

            $element = skipComment($element, "ul");

            if( $element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {

//                    $temp = $element->next_sibling()->children($i)->plaintext;
                    $temp = explode("/", strtoupper($element->next_sibling()->children($i)->plaintext));

//                    foreach($temp as $v) {
//                        $functionality[] = trim($v);
//                        $functionality[] = "/";
//                    }

                    $functionality[] = preg_replace('/\s+/', '_', trim($temp[sizeof($temp) - 1]));
//                    $temp = preg_replace('/\s+/', '', $temp);
//                    $functionality[] = $temp;
                    $i++;
                }
            }
        }

        if($element->plaintext == "Approaches"){

            $element = skipComment($element, "ul");

            if( $element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {
                    $temp = explode("/", strtoupper($element->next_sibling()->children($i)->plaintext));

//                    foreach($temp as $v) {
//                        $approaches[] = trim($v);
//                    }

                    $approaches[] = preg_replace('/\s+/', '_', trim($temp[sizeof($temp) - 1]));

                    $i++;
                }
            }
        }

        if($element->plaintext == "Instrument Data Types"){

            $element = skipComment($element, "ul");

            if( $element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {
                    $temp = explode("/", strtoupper($element->next_sibling()->children($i)->plaintext));

//                    foreach($temp as $v) {
//                        $instrument[] = trim($v);
//                    }

                    $instrument[] = preg_replace('/\s+/', '_', trim($temp[sizeof($temp) - 1]));

                    $i++;
                }
            }
        }

        if($element->plaintext == "Publications"){

            if($element->next_sibling()->tag != null){

                $element = skipComment($element, "ul");

                if($element->next_sibling()->tag == "ul"){
                    $i = 0;
                    while($element->next_sibling()->children($i)->tag == "li") {
                        $publications[] = $element->next_sibling()->children($i)->plaintext;
                        $i++;
                    }
                }
            }
        }

        if($element->plaintext == "Tool Authors"){
            $element = skipComment($element, "ul");

            if($element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {
                    $provider[] = $element->next_sibling()->children($i)->plaintext;
                    $i++;
                }
            }
        }

        if($element->plaintext == "Container Contributors"){
            $element = skipComment($element, "ul");

            if($element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {
                    $contributor[] = $element->next_sibling()->children($i)->plaintext;
                    $i++;
                }
            }
        }

        if($element->plaintext == "Website"){
            $element = skipComment($element, "ul");

            if($element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {
                    $website[] = $element->next_sibling()->children($i)->plaintext;
                    $i++;
                }
            }
        }

        if($element->plaintext == "Git Repository"){
            $element = skipComment($element, "ul");

            if($element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {
                    $gitRepo[] = $element->next_sibling()->children($i)->plaintext;
                    $i++;
                }
            }
        }

        if($element->plaintext == "Usage Instructions"){
            while($element->next_sibling()->tag != "h2" && $element != null){
                $instructions .= $element->next_sibling()->outertext;
                $instructions .= " ";
                $element = $element->next_sibling();
            }
        }

        if($element->plaintext == "Installation" && $element != null){
            while($element->next_sibling() != null && $element->next_sibling()->tag != "h2"){
                $installation .= $element->next_sibling()->outertext;
                $installation .= " ";
                $element = $element->next_sibling();
            }
        }
    }

//    $json = [];
//
//    $json['result'] = 1;
//    $json['data'] = [];


    $item = null;
    $item['id'] = $appName;
    $item['name'] = $title;
    $item['version'] = $version;

    if($logo == "" || $logo == null){
        $imagePath = $host;
        $logo = "img/default_app.png";
    }
    $item['logo_large'] = $imagePath.$logo;
    $item['short_description'] = $short_description;
    $item['abstract'] = $description;
    $item['key_features'] = $key_features;
    $item['publications'] = $publications;
    $item['screenshots'] = $screenshots;
    $item['authors'] = $provider;
    $item['website'] = $website;
    $item['instruction'] = $instructions;
    $item['installation'] = $installation;
    $item['functionality'] = $functionality;
    $item['contributors'] = $contributor;
    $item['approaches']= $approaches;
    $item['instrument']= $instrument;
    $item['git_repo'] = $gitRepo;

//    $json['data'][] = $item;
    //echo json_encode($json);

    return $item;
}

function skipComment($element, $tag){

    while($element->next_sibling() != null && $element->next_sibling()->tag != $tag){
        if($element->next_sibling()->tag == "h2"){
            break;
        }
        $element = $element->next_sibling();
    }


    return $element;
}

function getFilenames($dir, $format){

    $data = createEmptyJSONDataArray();

    if($format !== 'array'){
        if(is_dir($dir)){
            $indir = array_filter(scandir($dir), function($item) {
                return $item[0] !== '.';
            });

            $data['result'] = 1;
            $data['data'] = array_values($indir);
        }
    } else {
        if(is_dir($dir)){
            $indir = array_filter(scandir($dir), function($item) {
                return $item[0] !== '.';
            });

            $data['result'] = 1;
            $data['data'] = array_values($indir);
        }

    }

    //print_r(json_encode($data));

    return $data;
}

function createEmptyJSONDataArray(){
    $data = array();

    $data['result'] = 0;
    $data['data'] = json_decode ("{}");

    return $data;
}

?>