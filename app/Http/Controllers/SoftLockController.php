<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Integrations\SoftLock;

class SoftLockController extends Controller
{
    public function test () {
        $softLock = new SoftLock();

        print_r($softLock);
    }
}
