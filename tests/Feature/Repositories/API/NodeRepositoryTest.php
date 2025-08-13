<?php


namespace Tests\Feature\Repositories\API;

use App\Enums\NodeType;
use App\Models\Node;
use App\Repositories\API\NodeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class NodeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private NodeRepository $nodeRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nodeRepository = $this->app->make(NodeRepository::class);
    }

    public function test_it_can_create_a_node(): void
    {
        $data = [
            'parent_id' => null,
            'name' => 'Test Node',
            'type' => NodeType::BUILDING->value,
            'height' => 50,
            'zip_code' => '12345',
            'monthly_rent' => 1000.50,
            'active' => true,
            'move_in_date' => now(),
        ];

        $expectedNode = $this->nodeRepository->createNode($data);

        $this->assertDatabaseHas('nodes', [
            'name' => 'Test Node',
            'zip_code' => '12345',
        ]);

        $this->assertInstanceOf(Node::class, $expectedNode);
    }

    public function test_it_can_get_a_node_by_id(): void
    {
        $node = Node::factory()->create([
            'name' => 'Test Node',
            'type' => NodeType::BUILDING->value,
        ]);

        $expectedNode = $this->nodeRepository->getNodeById($node->id);

        $this->assertTrue($expectedNode->is($node));
    }
}