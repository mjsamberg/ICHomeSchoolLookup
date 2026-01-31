<?php

namespace App\Services;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use App\Models\Address;
use App\Models\School;

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
        foreach($schools as $s){
            $school = School::where('school_number',$s)->first();
            if($school->serves_grade($grade)){
                return $school;
            }
        }
        return false;
    }

    public function get_address_schools(Address $address){        
        $street = $address->number." ".$address->street." ".$address->tag;
        $street = str_replace(" ","+",$street);


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
}
