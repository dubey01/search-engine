<?php
class SiteResultsProvider {

	private $con;

	public function __construct($con){
        $this->con = $con;
	}

	public function getNumResults($term){
		$query = $this->con->prepare("SELECT Count(*) as total
			                           FROM sites Where title LIKE :term
			                           OR url LIKE :term
			                           OR keywords LIKE :term
			                           OR description LIKE :term");

		$searchTerm = "%".$term."%";
		$query->bindparam(":term", $searchTerm);
		$query->execute();

		$row = $query->fetch(PDO::FETCH_ASSOC);
		return $row["total"];


	} 

    public function getResultsHTML($page, $pageSize, $term){

    	$fromlimit = ($page-1)*$pageSize;

		$query = $this->con->prepare("SELECT *
			                           FROM sites Where title LIKE :term
			                           OR url LIKE :term
			                           OR keywords LIKE :term
			                           OR description LIKE :term
			                           ORDER BY clicks DESC
			                           LIMIT :fromlimit, :pageSize");

		$searchTerm = "%".$term."%";
		$query->bindparam(":term", $searchTerm);
		$query->bindparam(":fromlimit", $fromlimit, PDO::PARAM_INT);
		$query->bindparam(":pageSize", $pageSize, PDO::PARAM_INT);
		$query->execute();

		$resultsHTML = "<div class='siteResults'>";

         while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
         	$id = $row["id"];
         	$url = $row["url"];
         	$description = $row["Description"];
         	$title = $row["Title"];

         	$title = $this->trimField($title, 55);
         	$description = $this->trimField($description, 150);
         	
         	$resultsHTML .= "<div class='resultContainer'>
   								<h3 class='title'>
   								    <a class='result' href='$url' data-linkId ='$id'>
                                       $title
   								    </a>
   								</h3>
   								<span class='url'>$url</span><br>
   								<span class='description'>$description</span>

         	                </div>";
         }

		$resultsHTML .= "</div>";

		return $resultsHTML;


	} 

	private function trimField($string, $characterlimit){

		$dots = strlen($string) > $characterlimit ? "..." : "";
		return substr($string, 0, $characterlimit) . $dots;
	}

}



?> 