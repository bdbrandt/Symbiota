<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
if (! function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if ( !array_key_exists($columnKey, $value)) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( !array_key_exists($indexKey, $value)) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if ( ! is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }
}

class GardenSearchManager {

    private $conn;
    private $searchParamsArr = array();
    private $sqlWhereArr = array();
    private $sql = '';
    private $display = '';

    function __construct(){
        $this->conn = MySQLiConnectionFactory::getCon("readonly");
    }

    public function __destruct(){
        if(!($this->conn === null)) $this->conn->close();
    }

    public function getCharacterStateArr($char,$sortseq){
        $returnArr = Array();
        $sql = 'SELECT CharStateName, cid, cs '.
            'FROM kmcs '.
            'WHERE cid = '.$char.' '.
            'ORDER BY '.($sortseq?'SortSequence':'CharStateName').' ';
        //echo "<div>Sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $returnArr[$row->CharStateName]["cid"] = $row->cid;
            $returnArr[$row->CharStateName]["cs"] = $row->cs;
        }
        $result->free();

        return $returnArr;
    }

    public function setSQLWhereArr(){
        foreach($this->searchParamsArr as $char => $charArr){
            $tempStr = '';
            if($char == 'sciname'){
                $tempArr = array();
                foreach($this->searchParamsArr[$char] as $cs){
                    $tempArr[] = '(t.SciName LIKE "'.$cs.'%")';
                    $tempArr[] = '(t.TID IN(
SELECT te.tid FROM taxa AS t LEFT JOIN taxaenumtree AS te ON t.TID = te.parenttid WHERE (((t.SciName)="'.$cs.'"))
UNION SELECT t.tid FROM taxa as t WHERE (((t.SciName)="'.$cs.'"))))';
                }
                $tempStr = '('.implode(' OR ',$tempArr).')';
            }
            elseif($char == 'common'){
                $tempArr = array();
                foreach($this->searchParamsArr[$char] as $cs){
                    $tempArr[] = '(v.VernacularName LIKE "'.$cs.'%")';
                }
                $tempStr = '('.implode(' OR ',$tempArr).')';
            }
            elseif($char == 140 || $char == 738){ //is height or width, set range
	            $tempArr = array();
	            foreach($this->searchParamsArr[$char] as $cs){
		            list($min, $max) = explode(",", $cs);
		            $tempArr[] = '(t.TID IN(SELECT TID FROM kmdescr WHERE (CID = '.$char.' AND (CS >= '.$min.' AND CS <= '.$max.'))))';

		            //$tempArr[] = '(t.TID IN(SELECT TID FROM kmdescr WHERE (CID = '.$char.' AND CS = '.$cs.')))';
	            }
	            $tempStr = '('.implode(' OR ',$tempArr).')';
            }
            else{
                $tempStr = '(t.TID IN(SELECT TID FROM kmdescr WHERE (CID = '.$char.' AND CS IN('.implode(',',$this->searchParamsArr[$char]).'))))';
            }
            $this->sqlWhereArr[] = $tempStr;
        }
        $this->sqlWhereArr[] = '(t.TID IN(SELECT TID FROM fmchklsttaxalink WHERE CLID = 54))';
    }

    public function setSQL(){
        $this->sql = '';
        $sqlWhere = 'WHERE ('.implode(' AND ',$this->sqlWhereArr).') ';
        if($this->display == 'grid'){
            $sqlSelect = 'SELECT t.TID, t.SciName ';
            $sqlFrom = 'FROM taxa AS t LEFT JOIN taxaenumtree AS te ON t.TID = te.parenttid ';
            //joining with images is SLOW!  Comment out next line, and run additional query in loop
            //$sqlFrom .= 'LEFT JOIN images AS i ON (te.tid = i.tid OR t.TID = i.tid) ';
            if(isset($this->searchParamsArr['common'])) $sqlFrom .= 'LEFT JOIN taxavernaculars AS v ON t.TID = v.TID ';
            //$sqlWhere .= 'AND te.taxauthid = 1 ';
            $sqlSuffix = 'GROUP BY t.TID ORDER BY t.SciName ';
        }
        elseif($this->display == 'list'){
            $sqlSelect = 'SELECT t.TID, t.SciName, v.VernacularName, kd.CID, ks.CharStateName, ks.cs ';
            $sqlFrom = 'FROM taxa AS t LEFT JOIN taxavernaculars AS v ON t.TID = v.TID ';
	        //joining with images is SLOW!  Comment out next line, and run additional query in loop
	        $sqlFrom .= 'LEFT JOIN kmdescr AS kd ON t.TID = kd.TID ';
            $sqlFrom .= 'LEFT JOIN kmcs AS ks ON kd.CID = ks.cid AND kd.CS = ks.cs ';
	        //$sqlFrom .= 'LEFT JOIN images AS i ON (t.TID = i.tid) ';
            //$sqlWhere .= 'AND (kd.CID IN(137,680,683,140,738,684)) ';
            $sqlSuffix = 'GROUP BY t.TID ORDER BY t.SciName ';
        }
        $this->sql = $sqlSelect.$sqlFrom.$sqlWhere.$sqlSuffix;
    }

    public function getDataArr(){
        $returnArr = array();
        //echo $this->sql; exit;
        $result = $this->conn->query($this->sql);
        while($row = $result->fetch_object()){
            $tid = $row->TID;
            if(!isset($returnArr[$tid]['sciname'])) $returnArr[$tid]['sciname'] = $row->SciName;
            if($this->display == 'grid'){
            	//run query on images table to get thumbnail image.
	            $sql="SELECT i.thumbnailurl, i.url FROM images AS i WHERE tid = ".$this->conn->escape_string($tid) . " ORDER BY i.sortsequence LIMIT 1";
	            //show large image instead of thumbnail in grid, as thumb is too small
	            $imgThumbnail = $this->conn->query($sql)->fetch_object()->url;
                //prepend image domain if image does not already contain a domain
                if(array_key_exists("IMAGE_DOMAIN",$GLOBALS)){
                    if(substr($imgThumbnail,0,1)=="/") $imgThumbnail = $GLOBALS["IMAGE_DOMAIN"].$imgThumbnail;
                }
                $returnArr[$tid]['url'] = $imgThumbnail;
            }
            elseif($this->display == 'list'){
                $cid = $row->CID;
	            //run query on images table to get thumbnail image.
	            $sql="SELECT i.thumbnailurl FROM images AS i WHERE tid = ".$this->conn->escape_string($tid) . " ORDER BY i.sortsequence LIMIT 1";
	            $imgThumbnail = $this->conn->query($sql)->fetch_object()->thumbnailurl;
	            if(array_key_exists("IMAGE_DOMAIN",$GLOBALS)){
		            if(substr($imgThumbnail,0,1)=="/") $imgThumbnail = $GLOBALS["IMAGE_DOMAIN"].$imgThumbnail;
	            }
		            $returnArr[$tid]['url'] = $imgThumbnail;
	            //build additional attribute values
                $attribs = $this->getGridAttribs($this->conn->escape_string($tid));
                $returnArr[$tid]['type'] = $attribs['type'];
                $returnArr[$tid]['sunlight'] = $attribs['sunlight'];
                $returnArr[$tid]['moisture'] = $attribs['moisture'];
                $returnArr[$tid]['height_string'] = $attribs['height_string'];
                $returnArr[$tid]['width_string'] = $attribs['width_string'];
                $returnArr[$tid]['ease'] = $attribs['ease'];

                if(!isset($returnArr[$tid]['common'])) $returnArr[$tid]['common'] = $row->VernacularName;
                //if(!isset($returnArr[$tid]['type']) && $cid == 137) {
	            //    $returnArr[$tid]['type'] = $row->CharStateName;
	            //    if($row->cs == 3) $returnArr[$tid]['type_class'] = "planttype1";
	            //    if($row->cs == 2) $returnArr[$tid]['type_class'] = "planttype2";
	            //    if($row->cs == 6) $returnArr[$tid]['type_class'] = "planttype3";
	            //    if($row->cs == 1) $returnArr[$tid]['type_class'] = "planttype4";
	            //    if($row->cs == 4) $returnArr[$tid]['type_class'] = "planttype5";
	            //    if($row->cs == 5) $returnArr[$tid]['type_class'] = "planttype6";
                //}
                //if(!isset($returnArr[$tid]['light']) && $cid == 680) {
                //	$returnArr[$tid]['light'] = $row->CharStateName;
	            //    if($row->cs == 1) $returnArr[$tid]['light_class'] = "sunlight1";
	            //    if($row->cs == 3) $returnArr[$tid]['light_class'] = "sunlight2";
	            //    if($row->cs == 4) $returnArr[$tid]['light_class'] = "sunlight3";
                //}
                //if(!isset($returnArr[$tid]['moisture']) && $cid == 683) {
                //	$returnArr[$tid]['moisture'] = $row->CharStateName;
	            //    if($row->cs == 1) $returnArr[$tid]['moisture_class'] = "moisture1";
	            //    if($row->cs == 2) $returnArr[$tid]['moisture_class'] = "moisture2";
	            //    if($row->cs == 3) $returnArr[$tid]['moisture_class'] = "moisture3";
	            //    if($row->cs == 4) $returnArr[$tid]['moisture_class'] = "moisture5";
	            //    if($row->cs == 5) $returnArr[$tid]['moisture_class'] = "moisture4";
                //}
                //if(!isset($returnArr[$tid]['ease']) && $cid == 684) $returnArr[$tid]['ease'] = $row->CharStateName;
                //if(!isset($returnArr[$tid]['maxheight']) && $cid == 140) $returnArr[$tid]['maxheight'] = $row->CharStateName;
                //if(!isset($returnArr[$tid]['maxwidth']) && $cid == 738) $returnArr[$tid]['maxwidth'] = $row->CharStateName;

            }
        }
        $result->free();
        return $returnArr;
    }

    public function setSearchParams($json){
        $paramsArr = json_decode($json,true);
        if(is_array($paramsArr)){
            foreach($paramsArr as $str){
                $parts = explode("--",$str);
                $char = $parts[0];
                $cs = $parts[1];
                if(!$this->searchParamsArr[$char]) $this->searchParamsArr[$char] = array();
                if(!in_array($cs,$this->searchParamsArr[$char])) $this->searchParamsArr[$char][] = $cs;
            }
        }
    }

    public function setDisplay($dis){
        $this->display = $dis;
    }

    public function getGridAttribs($tid) {
        $attribs = array();
        $sql = "Select d.cid, c.charname, cs.charstatename, cs.cs FROM kmdescr d ";
        $sql .= "Left Join kmcharacters c ON c.CID = d.CID ";
        $sql .= "Left Join kmcs as cs ON d.CS = cs.cs AND cs.cid = d.cid ";
        $sql .= "where tid = ";
        $sql .= $this->conn->escape_string($tid);
        $sql .= " order by  d.cid, cs.cs ";
        //echo $sql;
        $result = $this->conn->query($sql);

        while ($row = $result->fetch_array()) {
            $tmp[$row["cid"]][] = array(
                "charname" => $row["charname"],
                "charstatename" => $row["charstatename"],
                "cs" => $row["cs"] );
        }
        //var_dump($tmp);
        foreach ($tmp[137] as $value) { //habit
            switch ($value['charstatename']) {
                case "tree":
                    $attribs["type"] .= "<img src='../images/plant_type_icon1.png' alt='Tree' >";
                    break;
                case "shrub":
                    $attribs["type"] .= "<img src='../images/plant_type_icon2.png' alt='Shrub' >";
                    break;
                case "vine":
                    $attribs["type"] .= "<img src='../images/plant_type_icon2.png' alt='Vine' >";
                    break;
                case "herb":
                    $attribs["type"] .= "<img src='../images/plant_type_icon4.png' alt='Herb' >";
                    break;
                case "grass or grass-like":
                    $attribs["type"] .= "<img src='../images/plant_type_icon5.png' alt='Grass or grass-like' >";
                    break;
                case "fern or fern ally":
                    $attribs["type"] .= "<img src='../images/plant_type_icon6.png' alt='Fern or fern ally' >";
                    break;
            }
        }
        foreach ($tmp[680] as $value) { //sunlight
            switch ($value['charstatename']) {
                case "sun":
                    $attribs["sunlight"] .= "<img src='../images/sunlight_icon1.png' alt='Sun' >";
                    break;
                case "part shade":
                    $attribs["sunlight"] .= "<img src='../images/sunlight_icon3.png' alt='Part Shade' >";
                    break;
                case "shade":
                    $attribs["sunlight"] .= "<img src='../images/sunlight_icon4.png' alt='Shade' >";
                    break;
            }
        }
        foreach ($tmp[683] as $value) { //moisture
            switch ($value['charstatename']) {
                case "dry":
                    $attribs["moisture"].= "<img src='../images/moisture_icon1.png' alt='Dry' >";
                    break;
                case "moist":
                    $attribs["moisture"].= "<img src='../images/moisture_icon3.png' alt='Moist' >";
                    break;
                case "wet":
                    $attribs["moisture"].= "<img src='../images/moisture_icon4.png' alt='Wet' >";
                    break;
            }
        }
        $attribs["minheight"] = is_array($tmp["140"]) ? min(array_column($tmp["140"], 'charstatename')) : '';
        $attribs["maxheight"] = is_array($tmp["140"]) ? max(array_column($tmp["140"], 'charstatename')) : '';
        $attribs["height_string"] = !empty($attribs['minheight']) && !($attribs['minheight'] == $attribs['maxheight']) ? $attribs["minheight"] . "-" : "";
        $attribs["height_string"] .= $attribs["maxheight"];
        $attribs["height_string"] .= (isset($attribs["minheight"]) || isset($attribs["maxheight"])) ? "H;" : "";
        $attribs["minwidth"] = is_array($tmp["738"]) ? min(array_column($tmp["738"], 'charstatename')) : '';
        $attribs["maxwidth"] = is_array($tmp["738"]) ? max(array_column($tmp["738"], 'charstatename')) : '';
        $attribs["width_string"] = !empty($attribs['minwidth']) && !($attribs['minwidth'] == $attribs['maxwidth']) ? $attribs["minwidth"] . "-" : "";
        $attribs["width_string"] .= $attribs["maxwidth"];
        $attribs["width_string"] .= (isset($attribs["minwidth"]) || isset($attribs["maxwidth"])) ? "W" : "";
        $attribs["ease"] = implode(", ",array_map(function($a){
            return ucwords($a["charstatename"]);
        },$tmp[684]));

        //var_dump($attribs);
        return $attribs;
    }
}
?>