<?php

namespace App\Services;
use GuzzleHttp\Client;
use App\Models\Address;
use App\Models\School;
use App\Models\Student;

class SchoolFinder
{
    protected $address_service;
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->address_service = new AddressService();
    }

    public function get_assigned_school($student){
        $results = array();
        $results['current_school'] = $student->school;
        if(strlen($student->Reassignment)>0){
            $results['reassignment'] = true;
            $results['reassignment_reason'] = $student->Reassignment;
        }
        if($student->homeless_served=='Y'){
            $results['mv'] = true;
        }
        if($student->school->is_choice_school){
            $results['choice_school'] = true;
        }
        $results['home_school'] = $this->address_service->get_school_by_grade($student->address, $student->grade_level);
        return $results;
        
    }
    
    public function get_next_school($student){
        if(!$student->school->is_terminal_grade($student->grade_level)){
            if($student->homeless_served=='Y' || $student->school->is_choice_school){
                return $student->school;
            }
            if($student->Reassignment=="Reassign-Term"||$student->Reassignment=="Employee"||$student->Reassignment=="Admit-Term"){
                return $student->school;
            }
        }
        $next_school = $this->address_service->get_school_by_grade($student->address, $student->grade->next_grade);
        return $next_school;
        
        
    }
}
