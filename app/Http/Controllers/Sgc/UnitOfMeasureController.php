<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreUnitOfMeasureRequest;
use App\Http\Requests\Sgc\UpdateUnitOfMeasureRequest;
use App\Models\SgcUnitOfMeasure;
use Illuminate\Http\Request;

class UnitOfMeasureController extends Controller
{
    public function index(Request $request)
    {
        $per_page = min($request->integer("per_page", 15), 100);

        $paginator = SgcUnitOfMeasure::query()
            ->orderBy("name")
            ->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "units_of_measure" => $paginator->items(),
        ], 200);
    }

    public function store(StoreUnitOfMeasureRequest $request)
    {
        $unit = SgcUnitOfMeasure::create($request->validated());

        return response()->json([
            "unit_of_measure" => $unit,
        ], 201);
    }

    public function update(UpdateUnitOfMeasureRequest $request, SgcUnitOfMeasure $sgc_unit_of_measure)
    {
        $sgc_unit_of_measure->update($request->validated());

        return response()->json([
            "unit_of_measure" => $sgc_unit_of_measure->fresh(),
        ], 200);
    }

    public function destroy(SgcUnitOfMeasure $sgc_unit_of_measure)
    {
        $sgc_unit_of_measure->delete();

        return response()->json([
            "message" => "Unidade de medida removida com sucesso.",
        ], 200);
    }
}
