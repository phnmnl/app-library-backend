<?php
/**
 * Created by PhpStorm.
 * User: sijinhe
 * Date: 12/10/2016
 * Time: 16:03
 */
function getAllAppDBId(){
    $html = file_get_html('../data/appdb.txt');

    $item = [];

    foreach($html->find('application:application') as $element){
        $item[] = $element->getAttribute("id");
    }
    return $item;
}

function getAppDBApp($id){
    $html = file_get_html('../data/appdb.txt');

    $item = [];

    foreach($html->find('application:application') as $element){
        // echo $element;
        if($element->getAttribute("id") == $id){
            $default_logo = "http://cleartheairchicago.com/files/2014/06/logo-placeholder.jpg";
            $item['id'] = $element->getAttribute("id");

            $item['name'] = $element->getElementByTagName('application:name')->plaintext;
            $item['version'] = "";
            if($element->getElementByTagName('application:logo')){
                $image = $element->getElementByTagName('application:logo')->plaintext.'&size=2';
            } else {
                $image = $default_logo;
            }
            $item['logo_large'] = $image;
            $item['short_description'] = $element->getElementByTagName('application:description')->plaintext;
            $item['abstract'] = $element->getElementByTagName('application:abstract')->plaintext;
            $item['key_features'] = [];
            $item['publications'] = [];
            $item['screenshots'] = [];
            $item['authors'] = [];
            $item['website'][] = $element->getElementByTagName('application:permalink')->plaintext;;
            $item['instruction'] = "";
            $item['installation'] = "";
            $item['technologies'] = [];
            $item['contributors'] = [];
            $item['analysis'] =[];
            $item['git_repo'] = [];
        }

    }

    return $item;
}






?>