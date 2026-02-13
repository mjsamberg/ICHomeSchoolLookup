<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SchoolFinder;
use App\Models\Student;
use App\Models\AssignmentException;

class generate_address_exceptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate_address_exceptions {--clean}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a report of all students not attending their home school';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('clean')) {
            AssignmentException::truncate();
        }

        $school_finder = new SchoolFinder();
        $already_set_students = AssignmentException::all()->pluck('student_uid')->toArray();
        $students = Student::whereNotIn('uid',$already_set_students)->get();
        $i=0;
        foreach($students as $student){ 
            $this->info("Processing ".$student->first_name." ".$student->last_name." (".$i." of ".count($students).")");
            $i++;
            $school_lookup = $school_finder->get_assigned_school($student);
            $exception = new AssignmentException();
            $exception->student_uid = $student->uid;
            $exception->student_name = $student->first_name." ".$student->last_name;
            $exception->grade_level = $student->grade_level;
            $exception->address = $student->address->number." ".$student->address->street." ".$student->address->tag;
            $exception->current_school_number = $student->school->school_number;
            $exception->current_school_name = $student->school->school_name;
            if($school_lookup['home_school']!=false){
                $exception->home_school_number = $school_lookup['home_school']->school_number;
                $exception->home_school_name = $school_lookup['home_school']->school_name;
            } else {
                $exception->home_school_number = "NOT FOUND";
                $exception->home_school_name = "NOT FOUND";
            }
            $exception->is_mv = isset($school_lookup['mv']) ? true : false;
            $exception->is_choice_school = isset($school_lookup['choice_school']) ? true : false;
            $exception->is_reassigned = isset($school_lookup['reassignment']) ? true : false;
            if($exception->current_school_number != $exception->home_school_number&&$exception->is_mv==false&&$exception->is_choice_school==false&&$exception->is_reassigned==false)
                $exception->is_exception = true;    
            else $exception->is_exception = false;
            if(isset($school_lookup['reassignment'])){
                $exception->reassignment_reason = $school_lookup['reassignment_reason'];
            }
            $exception->save();
        }
    }
}
