<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\API\NodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\API\CreateNodeRequest;
use Symfony\Component\HttpFoundation\Response;

class NodeController extends Controller
{
    protected $nodeService;

    public function __construct(NodeService $nodeService)
    {
        $this->nodeService = $nodeService;
    }

    public function createNode(CreateNodeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $node = $this->nodeService->createNode($data);

        return response()->json($node, Response::HTTP_CREATED );
    }

    public function getChildren(int $id): JsonResponse
    {
        $node = $this->nodeService->getChildren($id);

        return response()->json($node,Response::HTTP_OK);
    }

    public function changeParent(Request $request, int $id): JsonResponse
    {
        return response()->json(['changeParent', 200], 200);
    }

    
}
