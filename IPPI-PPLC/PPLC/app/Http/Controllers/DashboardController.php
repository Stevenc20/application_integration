<?php

namespace App\Http\Controllers;

use App\Models\Stock;

public function index()
{
    $overstock = Stock::where('kategori', 'overstock')->count();
    $limited = Stock::where('kategori', 'limited')->count();
    $zerostock = Stock::where('kategori', 'zero')->count();

    $data = [
        'overstock' => $overstock,
        'limited' => $limited,
        'zerostock' => $zerostock,
    ];

    return view('dashboard', compact('data'));
}