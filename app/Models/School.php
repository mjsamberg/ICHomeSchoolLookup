<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    public function grades(): HasMany
    {
        return $this->hasMany(School_Grade::class);
    }    

    public function is_terminal_grade($grade){
        foreach($this->grades as $g){
            if($grade = $g && $g->teminal_grade==1)return true;
        }
        return false;
    }

    public function serves_grade($grade){
        foreach($this->grades as $g){
            if($grade == $g->grade_level){
                return true;
            }
        }
        return false;
    }

}
