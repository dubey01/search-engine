<?php
include("config.php");
include("classes/DomDocumentparser.php");

$alreadycrawled = array();
$crawling = array();
$alreadyFoundImages = array();


function linkExists($url){
     global $con;

     $query = $con->prepare("SELECT * FROM sites WHERE url = :url");

     $query->bindParam(":url", $url);
     $query->execute();

     return $query->rowCount() != 0;
}

function insertImage($url, $src, $alt, $title){
     global $con;

     $query = $con->prepare("INSERT INTO images(siteUrl, imageUrl, alt, title)
                             VALUES(:siteUrl, :imageUrl, :alt, :title)");

     $query->bindParam(":siteUrl", $url);
     $query->bindParam(":imageUrl", $src);
     $query->bindParam(":alt", $alt);
     $query->bindParam(":title", $title);

     return $query->execute();
}

function insertlink($url, $title, $description, $keywords){
     global $con;

     $query = $con->prepare("INSERT INTO sites(url, Title, Description, Keywords)
                             VALUES(:url, :title, :description, :keywords)");

     $query->bindParam(":url", $url);
     $query->bindParam(":title", $title);
     $query->bindParam(":description", $description);
     $query->bindParam(":keywords", $keywords);

     return $query->execute();
}

function createLink($src, $url){
	$scheme = parse_url($url)["scheme"]; //http
	$host = parse_url($url)["host"]; //www.internshala.com

	if (substr($src,0,2) == "//") {
		$src = $scheme.":".$src;
	}

	else if(substr($src,0,1) == "/") {
		$src = $scheme."://" . $host . $src;
	}

	else if(substr($src,0,2) == "./") {
		$src = $scheme."://".$host . dirname(parse_url($url)["path"]) . substr($src, 1);
	}

	else if(substr($src,0,3) == "../") {
		$src = $scheme."://".$host . "/" . $src;
	}
     
    else if(substr($src,0,5) != "https" && substr($src,0,4) != "http") {
		$src = $scheme."://".$host . "/" . $src;
	}
	return $src;
}

function getDetails($url){
	global $alreadyFoundImages;
	$parse = new DomDocumentparser($url);

	$titleArray = $parse->getTitleTags();

	if (sizeof($titleArray) == 0 || $titleArray->item(0) == NULL ) {
		return;
	}
	$title = $titleArray->item(0)->nodeValue;
	$title = str_replace("\n", "", $title);

	if ($title == "") {
		return;
	}

	$description = "";
	$keywords = "";

	$MetaArray = $parse->getMetaTags();

	foreach ($MetaArray as $meta) {
		
		if ($meta->getAttribute("name")=="description") {
			$description = $meta->getAttribute("content");
		}

		if ($meta->getAttribute("name")=="keywords") {
			$keywords = $meta->getAttribute("content");
		}
	}

		$description = str_replace("\n", "", $description);
		$keywords = str_replace("\n", "", $keywords);


        if (linkExists($url)) {
        	echo "$url already Exists<br>";
        }
        else if (insertlink($url, $title, $description, $keywords)) {
        	echo "SUCCESS: $url<br>";
        }
        else{
        	echo "FAILED to insert";
        }

        $imageArray = $parse->getImages();

        foreach ($imageArray as $image) {
        	$src = $image->getAttribute("src");
        	$title = $image->getAttribute("title");
        	$alt = $image->getAttribute("alt");

        	if (!$title && !$alt) {
        		continue;
        	}

        	$src = createLink($src, $url);

        	if(!in_array($src, $alreadyFoundImages)) {
        		$alreadyFoundImages[] = $src;

        		insertImage($url, $src, $alt, $title);
        	}
        }
	
	//echo "URL: $url, description: $description, keywords: $keywords<br>";
}

function followlinks($url){

    global $alreadycrawled;
    global $crawling;

	$parse = new DomDocumentparser($url);

	$linklist = $parse->getLinks();

	foreach ($linklist as $link) {
		$href = $link->getAttribute("href");

		if(strpos($href, "#") !== false){
			continue;
		}

		else if (substr($href,0,11) == "javascript:") {
			continue;
		}

		$href = createLink($href, $url);

		if (!in_array($href, $alreadycrawled)) {
			$alreadycrawled[] = $href;
			$crawling[] = $href;

			getDetails($href);
		}

		//else return;
	}

	array_shift($crawling);

	foreach ($crawling as $site) {
		followlinks($site);
	}
}

$start = "https://www.adobe.com/in/";
followlinks($start);

?>