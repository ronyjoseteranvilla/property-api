<?php

namespace Tests\Unit\Services\API;


use App\Enums\NodeType;
use App\Models\Node;
use App\Repositories\API\NodeRepository;
use App\Services\API\NodeService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class NodeServiceTest extends TestCase
{
    protected function tearDown(): void{
        Mockery::close();
        parent::tearDown();
    }

    private function mockNodeRepository(): NodeRepository|MockInterface {
        return Mockery::mock(NodeRepository::class);
    }

    public function test_create_node_without_parent(): void {
        //Arrange
        $nodeRepositoryMock = $this->mockNodeRepository();
        $expectedParentId = 1;

        $requestData = [
            'name' => str()->random(10),
            'type' => NodeType::CORPORATION->value,
        ];

        $expectedNode = new Node([
            'id' => $expectedParentId,
            'type' => NodeType::CORPORATION->value,
            'height' => 0
        ]);

        $expectedRequestData = [
            'name' => $requestData['name'],
            'type' => $requestData['type'],
            'height' => 0,
            'zip_code' => null,
            'monthly_rent' => null,
            'active' => null,
            'moved_in_date' => null,
        ];

        $nodeRepositoryMock->shouldReceive('getNodeById')->never();
        $nodeRepositoryMock->shouldReceive('createNode')->once()->with($expectedRequestData)->andReturn($expectedNode);
        
        $nodeService = new NodeService($nodeRepositoryMock);

        //Act
        $actualNode = $nodeService->createNode($requestData);

        //Assert
        $this->assertEquals($expectedNode, $actualNode);
    }

    public function test_invalid_parent_type_triggers_exception(): void {
        //Arrange
        $nodeRepositoryMock = $this->mockNodeRepository();
        $expectedParentId = 1;
        $requestData = [
            'name' => str()->random(10),
            'parent_id' => $expectedParentId,
            'type' => NodeType::BUILDING->value,
            'zip_code' => '12345'
        ];

        $invalidParentNode = new Node([
            'id' => $expectedParentId,
            'type' => NodeType::TENANT->value,
            'height' => 0
        ]);

        $nodeRepositoryMock->shouldReceive('getNodeById')->once()->with($expectedParentId)->andReturn($invalidParentNode);
        $nodeRepositoryMock->shouldReceive('createNode')->never();

        $nodeService = new NodeService($nodeRepositoryMock);

        //Act | Assert
        try {
            $nodeService->createNode($requestData);
            $this->fail('422 Exception was not thrown');
        } catch (HttpException $exception) {
            $this->assertEquals(422, $exception->getStatusCode());
            $this->assertStringContainsString('Invalid Parent Type:', $exception->getMessage());
        }
        
    }

    public function test_tenancy_period_allows_only_one_active(): void {
        //Arrange
        $nodeRepositoryMock = $this->mockNodeRepository();
        $expectedParentId = 1;

        $requestData = [
            'name' => str()->random(10),
            'parent_id' => $expectedParentId,
            'type' => NodeType::TENANCY_PERIOD->value,
            'active' => true,
        ];

        $expectedParentNode = Mockery::mock(Node::class)->makePartial();

        $expectedParentNode->id = $expectedParentId;
        $expectedParentNode->type = NodeType::PROPERTY->value;
        $expectedParentNode->height = 2;

        $expectedParentNode->shouldReceive('children')->andReturnSelf();
        $expectedParentNode->shouldReceive('where')->with('type', NodeType::TENANCY_PERIOD->value)->andReturnSelf();
        $expectedParentNode->shouldReceive('where')->with('active', true)->andReturnSelf();
        $expectedParentNode->shouldReceive('exists')->andReturn(true);

        $nodeRepositoryMock->shouldReceive('getNodeById')->once()->with($expectedParentId)->andReturn($expectedParentNode);

        $nodeService = new NodeService($nodeRepositoryMock);

        //Act | Assert
        try {
            $nodeService->createNode($requestData);
            $this->fail('422 Exception was not thrown');
        } catch (HttpException $exception) {
            $this->assertEquals(422, $exception->getStatusCode());
            $this->assertEquals('Only one Active Tenancy Period is allowed on a Property.', $exception->getMessage());
        }
    }

        public function test_sanitize_extra_fields_removes_irrelevant_values(): void {
        //Arrange
        $nodeRepositoryMock = $this->mockNodeRepository();
        $expectedParentId = 1;

        $requestData = [
            'name' => str()->random(10),
            'type' => NodeType::BUILDING->value,
            'parent_id' => $expectedParentId,
            'zip_code' => '12345',
            'active' => true,
            'monthly_rent' => 10000
        ];

        $expectedParentNode = new Node([
            'id' => $expectedParentId,
            'name' => str()->random(10),
            'type' => NodeType::CORPORATION->value
        ]);

        $expectedNode = new Node([
                'id'=> 2,
                'type' => NodeType::BUILDING->value,
                'parent_id' => $expectedParentId,
                'height' => 1,
                'zip_code' => '12345',
                'active' => null,
                'monthly_rent' => null

        ]);

        $expectedRequestData = [
            'name' => $requestData['name'],
            'type' => $requestData['type'],
            'parent_id' => $requestData['parent_id'],
            'height' => 1,
            'zip_code' => $requestData['zip_code'],
            'monthly_rent' => null,
            'active' => null,
            'moved_in_date' => null,
        ];

        $nodeRepositoryMock->shouldReceive('getNodeById')->once()->with($expectedParentId)->andReturn($expectedParentNode);
        $nodeRepositoryMock->shouldReceive('createNode')->once()->with($expectedRequestData)->andReturn($expectedNode);

        $nodeService = new NodeService($nodeRepositoryMock);

        //Act
        $actualNode = $nodeService->createNode($requestData);

        //Assert
        $this->assertEquals($expectedNode, $actualNode);
    }

    public function test_get_node_children(): void 
    {
        //Arrange
        $nodeRepositoryMock = $this->mockNodeRepository();
        $expectedParentId = 1;

        $expectedParentNode = Mockery::mock(Node::class)->makePartial();

        $expectedParentNode->id = $expectedParentId;
        $expectedParentNode->type = NodeType::PROPERTY->value;

        $expected_child_nodes = Collection::make([
            new Node([
                'name' => str()->random(10),
                'type' => NodeType::BUILDING->value,
                'id' => 2,
                'zip_code' => '2000',
            ]),
            new Node([
                'name' => str()->random(10),
                'type' => NodeType::BUILDING->value,
                'id' => 3,
                'zip_code' => '4000',
            ]),
        ]);

        $nodeRepositoryMock->shouldReceive('getNodeById')->with($expectedParentId)->andReturn($expectedParentNode);
        $expectedParentNode->shouldReceive('getAttribute')->with('children')->andReturn($expected_child_nodes);

        $nodeService = new NodeService($nodeRepositoryMock);

        //Act
        $actualChildNodes = $nodeService->getChildren($expectedParentId);

        //Assert
        $this->assertEquals($expected_child_nodes, $actualChildNodes);

    }

    
}
