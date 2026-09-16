<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOperationsRequest;
use App\Http\Requests\UpdateOperationsRequest;
use App\Models\Operations;

class OperationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOperationsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Operations $operations)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Operations $operations)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOperationsRequest $request, Operations $operations)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Operations $operations)
    {
        //
    }
}
