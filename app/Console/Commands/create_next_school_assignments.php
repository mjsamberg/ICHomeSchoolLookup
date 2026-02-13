<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportNextSchoolAssignment;
use App\Models\Student;
use App\Services\SchoolFinder;

class create_next_school_assignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create_next_school_assignments {grade? : Specific Grade Level to Generate} {school? : specific school to generate} {--clean}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $school_finder = new SchoolFinder();
        if ($this->option('clean')) {
            ReportNextSchoolAssignment::truncate();
        }
        if($this->argument('grade')){
            $this->info("Generating assignments for grade ".$this->argument('grade'));
        } else {
            $this->info("Generating assignments for all grades");
        }
        if($this->argument('school')){
            $this->info("Generating assignments for school ".$this->argument('school'));
        } else {
            $this->info("Generating assignments for all schools"); 
        }

        $already_set_students = ReportNextSchoolAssignment::query();
        if($this->argument('grade')){
            $already_set_students = $already_set_students->where('current_grade_level',$this->argument('grade'));
        }
        if($this->argument('school')){
            $already_set_students = $already_set_students->where('current_school_number',$this->argument('school'));
        }

        $already_set_students = $already_set_students->pluck('student_uid')->toArray();

        $students = Student::whereNotIn('uid',$already_set_students);
        if($this->argument('grade')){
            $students->where('grade_level',$this->argument('grade'));
        }
        if($this->argument('school')){
            $students->where('school_number',$this->argument('school'));
        }
        $students = $students->get();

        $i=0;
        foreach($students as $student) { 
            $this->info("Processing ".$student->first_name." ".$student->last_name." (".$i." of ".count($students).")");
            $i++;
            $school_lookup = $school_finder->get_next_school($student);
            $report = new ReportNextSchoolAssignment();
            $report->student_uid = $student->uid;
            $report->student_name = $student->first_name." ".$student->last_name;
            $report->current_grade_level = $student->grade_level;
            $report->next_grade_level = $student->grade->next_grade;
            $report->address = $student->address->number." ".$student->address->street." ".$student->address->tag;
            $report->current_school_number = $student->school->school_number;
            $report->current_school_name = $student->school->school_name;
            if($school_lookup!=false){
                $report->next_school_number = $school_lookup->school_number;
                $report->next_school_name = $school_lookup->school_name;
            } else {
                $report->next_school_number = "NOT FOUND";
                $report->next_school_name = "NOT FOUND";
            }
            $report->is_mv = isset($school_lookup['mv']) ? true : false;
            $report->is_choice_school = isset($school_lookup['choice_school']) ? true : false;
            $report->is_reassigned = isset($school_lookup['reassignment']) ? true : false;
            if($report->current_school_number != $report->home_school_number&&$report->is_mv==false&&$report->is_choice_school==false&&$report->is_reassigned==false)
                $report->is_exception = true;    
            else $report->is_exception = false;
            if(isset($school_lookup['reassignment'])){
                $report->reassignment_reason = $school_lookup['reassignment_reason'];
            }
            $report->save();
        }
        
        
        
    }
}
