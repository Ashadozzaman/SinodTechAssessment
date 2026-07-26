<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Supports `search` (matches name/address).
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);

        $branches = Branch::search($filters['search'] ?? null)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Branches/Index', [
            'branches' => BranchResource::collection($branches)->response()->getData(true),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Branches/CreateBranch');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBranchRequest $request)
    {
        Branch::create($request->validated());

        return to_route('branches.index')->with('success', 'Branch created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch)
    {
        return Inertia::render('Branches/EditBranch', [
            'branch' => BranchResource::make($branch),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $branch->update($request->validated());

        return to_route('branches.index')->with('success', 'Branch updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        $branch->delete();

        return to_route('branches.index')->with('success', 'Branch deleted successfully.');
    }
}
