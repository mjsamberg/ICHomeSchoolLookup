<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Address;
use App\Services\AddressService;
use App\Models\School;


class addressIdLookup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:address-id-lookup {id : The IC Address ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Look up an address by IC Address ID';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $id = $this->argument('id');
        $address = Address::find($id);
        $address_service = new AddressService();
        $schools = $address_service->get_address_schools($address);
        foreach($schools as $s){
            $schoolData = School::where('school_number', $s)->first();
            $this->line($schoolData->school_name);
            $grades = $schoolData->grades;
            foreach($grades as $g){
                $this->line("Grade ".$g->grade_level);
            }
        }
    
    }
}
