<?php

namespace App\Repositories\API;

use App\Models\Node;


class NodeRepository
{
    /**
     * Summary of getNodeById
     * @param int $id
     * @return Node
     */
    public function getNodeById(int $id): Node
    {
        return Node::findOrFail($id);
    }

    /**
     * Summary of createNode
     * @param array $data
     * @return Node
     */
    public function createNode(array $data): Node
    {
        return Node::create($data);
    }
}