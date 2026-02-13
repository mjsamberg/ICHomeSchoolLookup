<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SchoolFinder;
use App\Models\Student;
use App\Models\Address;

class debug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:debug  {uid : The Student UID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Returns the next school for a student';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $student = Student::where('uid', $this->argument('uid'))->first();
        $school_finder = new SchoolFinder();
        $school_lookup = $school_finder->get_assigned_school($student);
        $next_school = $school_finder->get_next_school($student);
        if($school_lookup['home_school']==false || $student->school->school_number!=$school_lookup['home_school']->school_number){
            $this->error("SCHOOL ASSIGNMENT MISMATCH!");
        }
        $this->info("Student: ".$student->first_name." ".$student->last_name);
        $this->info("Grade: ".$student->grade_level);
        $this->info("Address: ".$student->address->number." ".$student->address->street." ".$student->address->tag);
        $this->info("Current School: ".$school_lookup['current_school']->school_name);
        $this->info("Reassignment: ".(isset($school_lookup['reassignment']) ? "Yes - ".$school_lookup['reassignment_reason'] : "No"));
        $this->info("Homeless/MV Status: ".(isset($school_lookup['mv']) ? "Yes" : "No"));
        $this->info("Choice School: ".(isset($school_lookup['choice_school']) ? "Yes" : "No"));
        $this-> info("Home School: ".($school_lookup['home_school']!=false ? $school_lookup['home_school']->school_name : "Not Found"));
        $this-> info("Next Year School: ".($next_school!=false ? $next_school->school_name : "Not Found"));
        print_r($student->address->toArray());
    }
}
