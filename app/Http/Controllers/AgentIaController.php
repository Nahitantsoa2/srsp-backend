<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAgentIaRequest;
use App\Models\AgentIa;

class AgentIaController extends Controller
{
    // GET /api/admin/agents
    public function index()
    {
        return AgentIa::withCount('messages')->get();
    }

    // PATCH /api/admin/agents/{id}
    public function update(UpdateAgentIaRequest $request, $id)
    {
        $agent = AgentIa::findOrFail($id);

        $agent->update($request->validated());

        return response()->json($agent);
    }
}
