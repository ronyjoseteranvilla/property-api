<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\API\NodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\API\CreateNodeRequest;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\API\ChangeParentRequest;

class NodeController extends Controller
{
    protected NodeService $nodeService;

    public function __construct(NodeService $nodeService)
    {
        $this->nodeService = $nodeService;
    }

    /**
     * Summary of createNode
     * @param \App\Http\Requests\API\CreateNodeRequest $request
     * @return JsonResponse
     */
    public function createNode(CreateNodeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $node = $this->nodeService->createNode($data);

        return response()->json($node, Response::HTTP_CREATED );
    }

    /**
     * Summary of getChildren
     * @param int $id
     * @return JsonResponse
     */
    public function getChildren(int $id): JsonResponse
    {
        $node = $this->nodeService->getChildren($id);

        return response()->json($node,Response::HTTP_OK);
    }

    /**
     * Summary of changeParent
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changeParent(ChangeParentRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $node = $this->nodeService->changeParent($id, $data['parent_id']);

        return response()->json($node, Response::HTTP_OK);
    }
}
