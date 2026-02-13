<?php

namespace App\Services;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use App\Models\Address;
use App\Models\School;
use App\Models\gis_address;

class AddressService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function get_school_by_grade(Address $address, $grade){
        $schools = $this->get_address_schools($address);
        if(count($schools)==0)return false;

        //Exception for GM Horton
        if($grade=="5"&&in_array("190328", $schools)&&in_array("190346", $schools)){
            $schools = array_diff($schools, array("190328"));
        }

        foreach($schools as $s){
            $school = School::where('school_number',$s)->first();
            if($school->serves_grade($grade)){
                return $school;
            }
        }
        return false;
    }

    private function gis_query($address){
        $prefix = $address->prefix;
        if($prefix=="E") $prefix = "EAST";
        if($prefix=="W") $prefix = "WEST";
        if($prefix=="N") $prefix = "NORTH";
        if($prefix=="S") $prefix = "SOUTH";

        $dir = $address->dir;
        if($dir=="E") $dir = "EAST";   
        if($dir=="W") $dir = "WEST";
        if($dir=="N") $dir = "NORTH";
        if($dir=="S") $dir = "SOUTH";

        $tag = strtoupper($address->tag);
        if(strtoupper($tag)=="AVE") $tag = "AVENUE";
        if(strtoupper($tag)=="ST") $tag = "STREET";
        if(strtoupper($tag)=="BLVD") $tag = "BOULEVARD";
        if(strtoupper($tag)=="RD") $tag = "ROAD";   
        if(strtoupper($tag)=="BND") $tag = "BEND";
        if(strtoupper($tag)=="CIR") $tag = "CIRCLE"; 
        if(strtoupper($tag)=="CT") $tag = "COURT"; 
        if(strtoupper($tag)=="CV") $tag = "COVE";
        if(strtoupper($tag)=="DR") $tag = "DRIVE";
        if(strtoupper($tag)=="LN") $tag = "LANE";
        if(strtoupper($tag)=="PL") $tag = "PLACE";
        if(strtoupper($tag)=="TER") $tag = "TERRACE";
        if(strtoupper($tag)=="HWY") $tag = "HIGHWAY";
        if(strtoupper($tag)=="LA") $tag = "LANE";
        if(strtoupper($tag)=="PRT") $tag = "PORT";
        if(strtoupper($tag)=="RDG") $tag = "RIDGE"; 
        if(strtoupper($tag)=="TRCE") $tag = "TRACE";  
        if(strtoupper($tag)=="TRL") $tag = "TRAIL";  
        if(strtoupper($tag)=="PKWY") $tag = "PARKWAY";             
        
        if(substr($address->street,0,3)=="NC "||substr($address->street,0,3)=="US "){
            $gis_address = gis_address::where('Add_Number', $address->number)
                ->where('st_PreDir', $prefix)
                ->where('st_PosDir', $dir)
                ->where('Post_Code', $address->zip);

            $street_arr = explode(" ", $address->street);
            foreach($street_arr as $s){
                if(is_numeric(str_replace(array("-"), "", $s))){
                    $street = $s;
                    break;
                }
            }

            if(substr($address->street,0,3)=="NC ")
                $gis_address = $gis_address->where('St_PreTyp', 'NORTH CAROLINA')->where('St_Name', $street);
            if(substr($address->street,0,3)=="US ")
                $gis_address = $gis_address->where('St_PreTyp', 'UNITED STATES HIGHWAY')->where('St_Name', $street);
        }
        else{
            $gis_address = gis_address::where('Add_Number', $address->number)
                ->where('st_PreDir', $prefix)
                ->where('st_PosDir', $dir)
                ->where('Post_Code', $address->zip)
                ->where('St_PosTyp', $tag);
            
            if(substr($address->street,0,4)=="OLD ")
                $gis_address = $gis_address->where('St_PreMod', 'OLD')->where('St_Name', substr($address->street,4));
            else            $gis_address = $gis_address->where('St_Name', $address->street);

        }
        $gis_address = $gis_address->first();
        if($gis_address==null)return array();
        $schools = array();
        
        $es = School::where('tims_name', $gis_address->ES_Name)->first();
        if($es!=null) $schools[] = $es->school_number;
        $ms = School::where('tims_name', $gis_address->MS_Name)->first();
        if($ms!=null) $schools[] = $ms->school_number;
        $hs = School::where('tims_name', $gis_address->HS_Name)->first();
        if($hs!=null) $schools[] = $hs->school_number;
        return $schools;

    }

    private function query_webservice($street){
        $client = new Client();
        // Fetch the webpage
        $response = $client->get(config('app.tims_url').'/wsawqweb/webquery/WebQueryRequestController?address='.$street.'&grade=ALL_GRADES&program=&action=9');
        $html = $response->getBody()->getContents();

        // Create crawler instance
        $crawler = new Crawler($html);
        
        $schools = [];
            
        // Extract each product
        $crawler->filter('#schTable')->each(function (Crawler $node) use (&$schools) {
            $cell = $node->filter('td');
            foreach($cell as $c){
                if(strlen($c->nodeValue)==3&&is_numeric($c->nodeValue))
                    $schools[] = "190".$c->nodeValue;
            }

        });

        return $schools;
    }

    public function get_address_schools(Address $address){   
        $schools = array();
        
        $schools = $this->gis_query($address);
        if(count($schools)>0)return $schools;
        return $schools;  
        $street = trim($address->number);
        if(strlen(trim($address->prefix))>0)
            $street = $street." ".trim($address->prefix);
        $street = $street." ".trim($address->street)." ".trim($address->tag);
        $street = str_replace(" ","+",$street);
        if(strlen($address->dir)>0)
            $street = $street." ".trim($address->dir);
        $schools = $this->query_webservice($street);

            //Try without direction
        if(count($schools)==0){
            $street = trim($address->number);
            if(strlen(trim($address->prefix))>0)
                $street = $street." ".trim($address->prefix);
            $street = $street." ".trim($address->street)." ".trim($address->tag);
            $street = str_replace(" ","+",$street);
            $schools = $this->query_webservice($street);
        }


            //Try with the zip
        if(count($schools)==0){
            $street = trim($address->number);
            if(strlen(trim($address->prefix))>0)
                $street = $street." ".trim($address->prefix);
            $street = $street." ".trim($address->street)." ".trim($address->tag);
            $street = str_replace(" ","+",$street);
            if(strlen($address->dir)>0)
                $street = $street." ".trim($address->dir);
            $street = $street."%2C+".$address->zip;
            $schools = $this->query_webservice($street);
        }

            

        return $schools;

    }
}
