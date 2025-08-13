<?php

namespace Tests\Unit\Services\API;


use App\Enums\NodeType;
use App\Models\Node;
use App\Repositories\API\NodeRepository;
use App\Services\API\NodeService;
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
        $expected_parent_id = 1;

        $request_data = [
            'name' => str()->random(10),
            'type' => NodeType::CORPORATION->value,
        ];

        $expectedNode = new Node([
            'id' => $expected_parent_id,
            'type' => NodeType::CORPORATION->value,
            'height' => 0
        ]);

        $expected_request_data = [
            'name' => $request_data['name'],
            'type' => $request_data['type'],
            'height' => 0,
            'zip_code' => null,
            'monthly_rent' => null,
            'active' => null,
            'moved_in_date' => null,
        ];

        $nodeRepositoryMock->shouldReceive('getNodeById')->never();
        $nodeRepositoryMock->shouldReceive('createNode')->once()->with($expected_request_data)->andReturn($expectedNode);
        
        $nodeService = new NodeService($nodeRepositoryMock);

        //Act
        $actualNode = $nodeService->createNode($request_data);

        //Assert
        $this->assertEquals($expectedNode, $actualNode);
    }

    public function test_invalid_parent_type_triggers_exception(): void {
        //Arrange
        $nodeRepositoryMock = $this->mockNodeRepository();
        $expected_parent_id = 1;
        $request_data = [
            'name' => str()->random(10),
            'parent_id' => $expected_parent_id,
            'type' => NodeType::BUILDING->value,
            'zip_code' => '12345'
        ];

        $invalidParentNode = new Node([
            'id' => $expected_parent_id,
            'type' => NodeType::TENANT->value,
            'height' => 0
        ]);

        $nodeRepositoryMock->shouldReceive('getNodeById')->once()->with($expected_parent_id)->andReturn($invalidParentNode);
        $nodeRepositoryMock->shouldReceive('createNode')->never();

        $nodeService = new NodeService($nodeRepositoryMock);

        //Act | Assert
        try {
            $nodeService->createNode($request_data);
            $this->fail('422 Exception was not thrown');
        } catch (HttpException $exception) {
            $this->assertEquals(422, $exception->getStatusCode());
            $this->assertStringContainsString('Invalid Parent Type:', $exception->getMessage());
        }
        
    }

    public function test_tenancy_period_allows_only_one_active(): void {
        //Arrange
        $nodeRepositoryMock = $this->mockNodeRepository();
        $expected_parent_id = 1;

        $request_data = [
            'name' => str()->random(10),
            'parent_id' => $expected_parent_id,
            'type' => NodeType::TENANCY_PERIOD->value,
            'active' => true,
        ];

        $expectedParentNode = Mockery::mock(Node::class)->makePartial();

        $expectedParentNode->id = $expected_parent_id;
        $expectedParentNode->type = NodeType::PROPERTY->value;
        $expectedParentNode->height = 2;

        $expectedParentNode->shouldReceive('children')->andReturnSelf();
        $expectedParentNode->shouldReceive('where')->with('type', NodeType::TENANCY_PERIOD->value)->andReturnSelf();
        $expectedParentNode->shouldReceive('where')->with('active', true)->andReturnSelf();
        $expectedParentNode->shouldReceive('exists')->andReturn(true);

        $nodeRepositoryMock->shouldReceive('getNodeById')->once()->with($expected_parent_id)->andReturn($expectedParentNode);

        $nodeService = new NodeService($nodeRepositoryMock);

        //Act | Assert
        try {
            $nodeService->createNode($request_data);
            $this->fail('422 Exception was not thrown');
        } catch (HttpException $exception) {
            $this->assertEquals(422, $exception->getStatusCode());
            $this->assertEquals('Only one Active Tenancy Period is allowed on a Property.', $exception->getMessage());
        }
    }

        public function test_sanitize_extra_fields_removes_irrelevant_values(): void {
        //Arrange
        $nodeRepositoryMock = $this->mockNodeRepository();
        $expected_parent_id = 1;

        $request_data = [
            'name' => str()->random(10),
            'type' => NodeType::BUILDING->value,
            'parent_id' => $expected_parent_id,
            'zip_code' => '12345',
            'active' => true,
            'monthly_rent' => 10000
        ];

        $expectedParentNode = new Node([
            'id' => $expected_parent_id,
            'name' => str()->random(10),
            'type' => NodeType::CORPORATION->value
        ]);

        $expectedNode = new Node([
                'id'=> 2,
                'type' => NodeType::BUILDING->value,
                'parent_id' => $expected_parent_id,
                'height' => 1,
                'zip_code' => '12345',
                'active' => null,
                'monthly_rent' => null

        ]);

        $expected_request_data = [
            'name' => $request_data['name'],
            'type' => $request_data['type'],
            'parent_id' => $request_data['parent_id'],
            'height' => 1,
            'zip_code' => $request_data['zip_code'],
            'monthly_rent' => null,
            'active' => null,
            'moved_in_date' => null,
        ];

        $nodeRepositoryMock->shouldReceive('getNodeById')->once()->with($expected_parent_id)->andReturn($expectedParentNode);
        $nodeRepositoryMock->shouldReceive('createNode')->once()->with($expected_request_data)->andReturn($expectedNode);

        $nodeService = new NodeService($nodeRepositoryMock);

        //Act
        $actualNode = $nodeService->createNode($request_data);

        //Assert
        $this->assertEquals($expectedNode, $actualNode);
    }



    
}
