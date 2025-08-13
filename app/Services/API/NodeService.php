<?php

namespace App\Services\API;

use App\Models\Node;
use App\Repositories\API\NodeRepository;
use App\Enums\NodeType;
use Illuminate\Database\Eloquent\Collection;

class NodeService
{
    protected $nodeRepository;

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

        $sanitized_data = $this->sanitizeExtraFields($data);

        return $this->nodeRepository->createNode($sanitized_data);
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


    public function changeParent(array $data)
    {
        //TODO: Add business Logic
    }

    private function validateParent(
        ?Node $parent,
        string $type,
        ?array $data = null,
    ): void {

        $allowed = $this->allowedParents[$type] ?? [];

        $parentType = $parent ? $parent->type : null;

        if (!in_array($parentType, $allowed, true)) {
            abort(422, "Invalid Parent Type: {$parentType} for child type: {$type}");
        }

        if ($type === NodeType::TENANCY_PERIOD->value && $parent) $this->validateTenancyPeriodType($parent, $data);

        if ($type === NodeType::TENANT->value && $parent) $this->validateTenantType($parent, $data);

        if ($type === NodeType::BUILDING->value && empty($data['zip_code'])) abort(422, 'Building type must have a zip_code');

        if ($type === NodeType::PROPERTY->value && empty($data['monthly_rent'])) abort(422, 'Property type must have a monthly_rent');
    }

    private function validateTenancyPeriodType(Node $parent, ?array $data): void
    {
        $isActive = $data['active'] ?? false;

        if ($isActive) {
            $exists = $parent->children()->where('type', NodeType::TENANCY_PERIOD->value)->where('active', true)->exists();

            if ($exists) abort(422, 'Only one Active Tenancy Period is allowed on a Property.');
        }

        if (array_key_exists('active', $data) && !is_bool(($data['active'])) ) 
        {
            abort(422, 'Tenancy Period type must have a boolean for active');
        }  
    }

    private function validateTenantType(Node $parent, array $data): void{
        $tenant_count = $parent->children()->where('type', NodeType::TENANT->value)->count();

        if ($tenant_count >=4) abort(422, ' A Tenancy Period type must have at most 4 Tenants.');

        if (empty($data['moved_in_date'])) abort(422, 'Tenant type must have a moved_in_date');
    }

    private function sanitizeExtraFields(array $data): array {

        $type = $data['type'];
        $fields_to_validate = ['zip_code', 'monthly_rent', 'active', 'moved_in_date'];
        $fields_to_keep = [];

        if ($type === NodeType::BUILDING->value) $fields_to_keep = ['zip_code'];
        if ($type === NodeType::PROPERTY->value) $fields_to_keep = ['monthly_rent'];
        if ($type === NodeType::TENANCY_PERIOD->value) $fields_to_keep = ['active'];
        if ($type === NodeType::TENANT->value) $fields_to_keep = ['active'];


        foreach ($fields_to_validate as $field_to_validate) {
            if (!in_array($field_to_validate, $fields_to_keep, true)){
                $data[$field_to_validate] = null;
            }
        }

        return $data;
    }

    
}