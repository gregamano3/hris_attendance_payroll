<?php

namespace App\Features\Employees\ManagePositions;

use App\Features\Employees\Models\Position;
use App\Features\Employees\Queries\EmployeeOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PositionsController
{
    public function index(): View
    {
        $positions = Position::query()
            ->with('department')
            ->withCount('employees')
            ->orderBy('title')
            ->paginate(20);

        return view('employees::positions.index', compact('positions'));
    }

    public function create(EmployeeOptions $options): View
    {
        return view('employees::positions.form', [
            'position' => new Position,
            'departments' => $options->departments(),
        ]);
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        $position = Position::query()->create($request->validated());

        return redirect()->route('positions.index')->with('success', "Position {$position->title} created.");
    }

    public function edit(Position $position, EmployeeOptions $options): View
    {
        return view('employees::positions.form', [
            'position' => $position,
            'departments' => $options->departments(),
        ]);
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());

        return redirect()->route('positions.index')->with('success', "Position {$position->title} updated.");
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->employees()->withTrashed()->exists()) {
            return back()->with('error', 'Positions held by employees cannot be deleted.');
        }

        $position->delete();

        return redirect()->route('positions.index')->with('success', "Position {$position->title} deleted.");
    }
}
