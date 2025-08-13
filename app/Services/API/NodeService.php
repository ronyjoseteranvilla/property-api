<?php

namespace App\Services\API;

use App\Models\Node;
use App\Repositories\API\NodeRepository;
use App\Enums\NodeType;
use Illuminate\Support\Collection;

class NodeService
{
    protected NodeRepository $nodeRepository;

    public function __construct(NodeRepository $nodeRepository)
    {
        $this->nodeRepository = $nodeRepository;
    }

    private array $allowedParents = [
        NodeType::CORPORATION->value => [null],
        NodeType::BUILDING->value => [NodeType::CORPORATION->value],
        NodeType::PROPERTY->value => [NodeType::BUILDING->value],
        NodeType::TENANCY_PERIOD->value => [NodeType::PROPERTY->value],
        NodeType::TENANT->value => [NodeType::TENANCY_PERIOD->value],
    ];

    /**
     * Summary of createNode
     * @param array $data
     * @return Node
     */
    public function createNode(array $data): Node
    {
        $parent = isset($data['parent_id']) ? $this->nodeRepository->getNodeById($data['parent_id']) : null;
        
        $this->validateParent($parent, $data['type'], $data);

        $data['height'] = $parent ? $parent->height + 1 : 0;

        $sanitizedData = $this->sanitizeExtraFields($data);

        return $this->nodeRepository->createNode($sanitizedData);
    }

    /**
     * Summary of getChildren
     * @param int $id
     * @return Collection<int, Node>
     */
    public function getChildren(int $id): Collection
    {
        $node = $this->nodeRepository->getNodeById($id);

        return $node->children;
    }


    /**
     * Summary of changeParent
     * @param int $id
     * @param int $parentId
     * @return Node
     */
    public function changeParent(int $id, int $parentId): Node
    {
        $node = $this->nodeRepository->getNodeById($id);
        $newParentNode = $this->nodeRepository->getNodeById($parentId);

        $this->preventCircularDependency($node, $newParentNode);
        $this->validateParent($newParentNode, $node->type, null, $node);

        $node->parent_id = $newParentNode->id;
        $node->height = $newParentNode->height + 1;
        $node->save();

        $this->recalculateHeightForDescendant($node);

        return $node;
    }

    /**
     * Summary of validateParent
     * @param mixed $parent
     * @param string $type
     * @param mixed $data
     * @param mixed $currentNode
     * @return void
     */
    private function validateParent(
        ?Node $parent,
        string $type,
        ?array $data = null,
        ?Node $currentNode = null
    ): void {

        $allowed = $this->allowedParents[$type] ?? [];

        $parentType = $parent ? $parent->type : null;

        if (!in_array($parentType, $allowed, true)) {
            abort(422, "Invalid Parent Type: {$parentType} for child type: {$type}");
        }

        if ($type === NodeType::TENANCY_PERIOD->value && $parent) $this->validateTenancyPeriodType($parent, $data, $currentNode);

        if ($type === NodeType::TENANT->value && $parent) $this->validateTenantType($parent, $data, $currentNode);

        if ($type === NodeType::BUILDING->value && empty($data['zip_code']) && !$currentNode) abort(422, 'Building type must have a zip_code');

        if ($type === NodeType::PROPERTY->value && empty($data['monthly_rent']) && !$currentNode) abort(422, 'Property type must have a monthly_rent');
    }

    /**
     * Summary of validateTenancyPeriodType
     * @param \App\Models\Node $parent
     * @param mixed $data
     * @param mixed $currentNode
     * @return void
     */
    private function validateTenancyPeriodType(Node $parent, ?array $data, ?Node $currentNode): void
    {
        $isActive = $data['active'] ?? false;

        if ($isActive) {
            $exists = $parent->children()->where('type', NodeType::TENANCY_PERIOD->value)->where('active', true)->exists();

            if($currentNode) {
                $exists = $parent->children()->where('type', NodeType::TENANCY_PERIOD->value)->where('active', true)->where('id', '!=', $currentNode->id)->exists();
            }

            if ($exists) abort(422, 'Only one Active Tenancy Period is allowed on a Property.');
        }

        if (array_key_exists('active', $data) && !is_bool(($data['active'])) ) 
        {
            abort(422, 'Tenancy Period type must have a boolean for active');
        }  
    }

    /**
     * Summary of validateTenantType
     * @param \App\Models\Node $parent
     * @param array $data
     * @param mixed $currentNode
     * @return void
     */
    private function validateTenantType(Node $parent, array $data, ?Node $currentNode ): void{
        if(! $currentNode || $currentNode->parent_id !== $parent->id) {
            $tenantCount = $parent->children()->where('type', NodeType::TENANT->value)->count();    

            if ($tenantCount >=4) abort(422, ' A Tenancy Period type must have at most 4 Tenants.');
        }

        if (empty($data['moved_in_date']) && !$currentNode) abort(422, 'Tenant type must have a moved_in_date');
    }

    /**
     * Summary of sanitizeExtraFields
     * @param array $data
     * @return array
     */
    private function sanitizeExtraFields(array $data): array {

        $type = $data['type'];
        $fieldsToValidate = ['zip_code', 'monthly_rent', 'active', 'moved_in_date'];
        $fieldsToKeep = [];

        if ($type === NodeType::BUILDING->value) $fieldsToKeep = ['zip_code'];
        if ($type === NodeType::PROPERTY->value) $fieldsToKeep = ['monthly_rent'];
        if ($type === NodeType::TENANCY_PERIOD->value) $fieldsToKeep = ['active'];
        if ($type === NodeType::TENANT->value) $fieldsToKeep = ['active'];


        foreach ($fieldsToValidate as $fieldToValidate) {
            if (!in_array($fieldToValidate, $fieldsToKeep, true)){
                $data[$fieldToValidate] = null;
            }
        }

        return $data;
    }

    /**
     * Summary of preventCircularDependency
     * @param \App\Models\Node $node
     * @param \App\Models\Node $newParentNode
     * @return void
     */
    private function preventCircularDependency(Node $node, Node $newParentNode): void
    {
        if ($node->id === $newParentNode->id) {
            abort(422, 'Cannot set node as its own parent');
        }

        $currentNode = $newParentNode;
        while ($currentNode) {
            if ($currentNode->id === $node->id) {
                abort(422, 'Cannot set parent to a descendant');
            }
            
            $currentNode = $currentNode->parent;
        }
    }


    /**
     * Summary of recalculateHeightForDescendant
     * @param \App\Models\Node $node
     * @return void
     */
    private function recalculateHeightForDescendant(Node $node): void 
    {
        $nodeQueue = [$node];
        $updates = [];

        while (!empty($nodeQueue)) {
            $currentNode = array_shift($nodeQueue);
            $nodeChildren = $currentNode->children;

            foreach($nodeChildren as $nodeChild) {
                $newHeight = $currentNode->height + 1;
                $updates[] = [
                    'id' => $nodeChild->id,
                    'height' => $newHeight,
                ];

                $nodeChild->height = $newHeight;
                $nodeQueue[] = $nodeChild;
            }
        }

        if (!empty($updates)) {
            $this->nodeRepository->bulkNodeHeightUpdate($updates);
        }
    }

}