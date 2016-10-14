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

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':

        if(isset($_GET['app']) && $_GET['app'] != "") {
            getAppWithResponse($_GET['app']);
        }  else if(isset($_GET['technology']) && $_GET['technology'] != ""){
            getAppWithTechnology($_GET['technology']);
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
        $appName = substr($appName, 7); //always prefix with 'docker-'

        $json['data'][] = getApp($appName);
    }

    foreach (getAllAppDBId() as $appName){
        $json['data'][] = getAppDBApp($appName);
    }

    header('Content-Type: application/json');
    echo json_encode($json);
}

function getAppWithTechnology($technology) {
    global $path;

    $dir = $path;

    $data = getFilenames($dir, '');

    $json = [];

    $json['result'] = 1;
    $json['data'] = [];

    $client = explode(" ", strtoupper($technology));

    foreach ($data['data'] as $appName){
        $appName = substr($appName, 7); //always prefix with 'docker-'

        $app = getApp($appName);

        $server = array_unique(array_merge($app['technologies'], $app['analysis']));

        $result = array_intersect($client, $server);
        if(sizeof($result) > 0){
            $json['data'][] = $app;
        }
    }

    foreach (getAllAppDBId() as $appName){

        $app = getAppDBApp($appName);
        $server = array_unique(array_merge($app['technologies'], $app['analysis']));

        $result = array_intersect($client, $server);
        if(sizeof($result) > 0){
            $json['data'][] = $app;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($json);
}

function getAppWithResponse($appName) {
    $json['result'] = 1;
    $json['data'] = [];
    if(!is_numeric($appName)){
        $json['data'][] = getApp($appName);
    } else {
        $json['data'][] = getAppDBApp($appName);
    }
    echo json_encode($json);
}

function getApp($appName){
    global $path;
    $imagePath = "http://".gethostname()."/app-library-backend/wiki-markdown/docker-".$appName."/";

    $name = $appName;

    $readmePath = $path."/"."docker-".$name."/"."README.html";

    $html = file_get_html($readmePath) or die(json_encode(createEmptyJSONDataArray()));

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
    $technology = [];
    $contributor = [];
    $analysis = [];

    foreach($html->find('h1') as $element){
        $title = $element->plaintext;
        $version = $element->next_sibling()->plaintext;
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
        if($element->plaintext == "Short description"){

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

            $element = skipComment($element, "ul");

            if( $element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {
                    $key_features[] = $element->next_sibling()->children($i)->plaintext;
                    $i++;
                }
            }

        }


        if($element->plaintext == "Metabolomics Technologies"){

            $element = skipComment($element, "ul");

            if( $element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {
                    $technology[] = strtoupper($element->next_sibling()->children($i)->plaintext);
                    $i++;
                }
            }
        }

        if($element->plaintext == "Data Analysis"){

            $element = skipComment($element, "ul");

            if( $element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {
                    $analysis[] = strtoupper($element->next_sibling()->children($i)->plaintext);
                    $i++;
                }
            }
        }

        if($element->plaintext == "Publications"){

            $element = skipComment($element, "ul");

            if($element->next_sibling()->tag == "ul"){
                $i = 0;
                while($element->next_sibling()->children($i)->tag == "li") {
                    $publications[] = $element->next_sibling()->children($i)->plaintext;
                    $i++;
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

        if($element->plaintext == "Usage Instructions"){
            while($element->next_sibling()->tag != "h2" && $element != null){
                $instructions .= $element->next_sibling()->outertext;
                $instructions .= " ";
                $element = $element->next_sibling();
            }
        }

        if($element->plaintext == "Installation" && $element != null){
            while($element->next_sibling()->tag != "h2"){
                $installation .= $element->next_sibling()->outertext;
                $installation .= " ";
                $element = $element->next_sibling();
            }
        }
    }

    $json = [];

    $json['result'] = 1;
    $json['data'] = [];


    $item = null;
    $item['id'] = $appName;
    $item['name'] = $title;
    $item['version'] = $version;
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
    $item['technologies'] = $technology;
    $item['contributors'] = $contributor;
    $item['analysis']= $analysis;


    $json['data'][] = $item;
    //echo json_encode($json);

    return $item;
}

function skipComment($element, $tag){
    while($element->next_sibling()->tag != $tag){
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