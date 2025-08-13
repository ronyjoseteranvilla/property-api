<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NodeController extends Controller
{
    public function createNode(Request $request): JsonResponse
    {
        return response()->json(['CreateNode', 201], 201);
    }

    public function changeParent(Request $request, int $id): JsonResponse
    {
        return response()->json(['changeParent', 200], 200);
    }

    public function getChildren(int $id): JsonResponse
    {
        return response()->json(['getChildren', 200], 200);
    }
}
