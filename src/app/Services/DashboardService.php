<?php

namespace App\Services;

class DashboardService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function management()
    {
        return "management";
    }

    public function customer()
    {
        return  ['name' => 'Saim'];
    }

    public function receptionist()
    {
        return "receptionist";
    }

    public function housekeeper()
    {
        return "housekeeper";
    }
}
