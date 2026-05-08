<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unite;
use Illuminate\Http\Request;

class UniteController extends Controller
{
    public function index()
    {
        $unites = Unite::with('parent')->orderBy('nom')->get();
        return response()->json($unites);
    }
}
