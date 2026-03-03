<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class InicioController extends Controller
{
    public function inicio()
    {
        $array_infos = UserController::getInfoBoxUser();
        return Inertia::render('Home', compact('array_infos'));
    }
}
