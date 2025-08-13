<?php

namespace App\Repositories\API;

use App\Models\Node;


class NodeRepository
{
    public function getNodeById(int $id): Node
    {
        return Node::findOrFail($id);
    }

    public function createNode(array $data): Node
    {
        return Node::create($data);
    }
}