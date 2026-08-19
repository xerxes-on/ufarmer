<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\User;
use Modules\PlantScanner\Models\ScannedPlant;
use Modules\PlantScanner\Services\AI\AiAnalysisService;

class FakeAuthedUser implements \Illuminate\Contracts\Auth\Authenticatable
{
    public function __construct(public int $id) {}

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function getAuthPassword()
    {
        return '';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}

beforeEach(function (): void {
    // Bypass external auth middleware for API tests
    $this->withoutMiddleware(\Xerxes\AuthBridge\Http\Middleware\AuthBridgeMiddleware::class);

    // Create a simple user row (for FKs) and authenticate with a fake Authenticatable
    $this->dbUser = User::create([
        'phone' => '+998900000000',
        'application_alias' => 'farmer',
    ]);
    $this->be(new FakeAuthedUser($this->dbUser->id), 'api');

    // Set up fake disk for file testing
    Storage::fake('public');
});

it('successfully scans plant image and returns ai response', function (): void {
    // Mock the AI analysis service to return successful response
    $this->mock(AiAnalysisService::class, function ($mock): void {
        $mock->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'success' => true,
                'analysis' => 'This appears to be a healthy tomato plant (Solanum lycopersicum). The leaves show good green coloration and no visible signs of disease or pest damage. The plant appears to be well-hydrated and in good growing condition.',
                'provider' => 'openrouter',
            ]);

        // Also mock analyzeWithPrompt in case it's called
        $mock->shouldReceive('analyzeWithPrompt')
            ->andReturn([
                'success' => true,
                'analysis' => 'This appears to be a healthy tomato plant (Solanum lycopersicum). The leaves show good green coloration and no visible signs of disease or pest damage. The plant appears to be well-hydrated and in good growing condition.',
                'provider' => 'openrouter',
            ]);
    });

    // Create test image file - will use i.png when provided
    $imageContent = file_exists(base_path('i.png'))
        ? file_get_contents(base_path('i.png'))
        : base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='); // 1x1 transparent PNG

    $testImage = UploadedFile::fake()->createWithContent('test-plant.png', $imageContent);

    $response = $this->postJson('/api/plant-scanner/scan', [
        'image' => $testImage,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'analysis',
                'ai_provider',
                'created_at',
                'is_duplicate',
                'structured_data',
                'tags',
                'photos',
            ],
        ])
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.is_duplicate', false);

    // Verify AI analysis content
    expect($response->json('data.analysis'))->toContain('tomato plant');
    expect($response->json('data.analysis'))->toContain('healthy');

    // Verify database record was created
    $scanId = $response->json('data.id');
    $this->assertDatabaseHas('scanned_plants', [
        'id' => $scanId,
        'farmer_id' => $this->dbUser->id,
        'status' => 'completed',
    ]);

    $scannedPlant = ScannedPlant::find($scanId);
    expect($scannedPlant->ai_analysis)->toContain('tomato plant');
});

it('handles ai analysis failure gracefully', function (): void {
    // Mock the AI analysis service to return failure
    $this->mock(AiAnalysisService::class, function ($mock): void {
        $mock->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'AI service temporarily unavailable',
                'provider' => 'openrouter',
            ]);
    });

    $testImage = UploadedFile::fake()->image('plant.jpg', 800, 600);

    $response = $this->postJson('/api/plant-scanner/scan', [
        'image' => $testImage,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'failed');

    // Verify database record shows failed status
    $scanId = $response->json('data.id');
    $this->assertDatabaseHas('scanned_plants', [
        'id' => $scanId,
        'status' => 'failed',
    ]);
});

it('detects duplicate images using hash comparison', function (): void {
    // Mock successful AI analysis for first scan
    $this->mock(AiAnalysisService::class, function ($mock): void {
        $mock->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'success' => true,
                'analysis' => 'Test analysis result',
                'provider' => 'openrouter',
            ]);
    });

    $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    $testImage1 = UploadedFile::fake()->createWithContent('plant1.png', $imageContent);

    // First scan - should succeed
    $firstResponse = $this->postJson('/api/v1/plant-scanner/scan', [
        'image' => $testImage1,
    ]);
    $firstResponse->assertStatus(201);

    // Second scan with same image content - should detect duplicate
    $testImage2 = UploadedFile::fake()->createWithContent('plant2.png', $imageContent);
    $secondResponse = $this->postJson('/api/v1/plant-scanner/scan', [
        'image' => $testImage2,
    ]);

    $secondResponse->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'This image has already been scanned.')
        ->assertJsonStructure([
            'data' => [
                'duplicate_of',
                'original_scan',
            ],
        ]);

    expect($secondResponse->json('data.duplicate_of'))->toBe($firstResponse->json('data.id'));
});

it('validates required image parameter', function (): void {
    $response = $this->postJson('/api/plant-scanner/scan', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

it('validates image file type and size', function (): void {
    // Test invalid file type
    $textFile = UploadedFile::fake()->create('document.txt', 100);

    $response = $this->postJson('/api/plant-scanner/scan', [
        'image' => $textFile,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);

    // Test oversized file (mock a 20MB file)
    $largeImage = UploadedFile::fake()->create('large.jpg', 20480);

    $response = $this->postJson('/api/plant-scanner/scan', [
        'image' => $largeImage,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

it('retrieves user scan history', function (): void {
    // Create some scanned plants for the user
    ScannedPlant::create([
        'farmer_id' => $this->dbUser->id,
        'status' => 'completed',
        'ai_analysis' => 'Test analysis 1',
        'photos' => ['/path/to/photo1.jpg'],
        'image_hash' => 'hash123',
    ]);

    ScannedPlant::create([
        'farmer_id' => $this->dbUser->id,
        'status' => 'completed',
        'ai_analysis' => 'Test analysis 2',
        'photos' => ['/path/to/photo2.jpg'],
        'image_hash' => 'hash456',
    ]);

    $response = $this->getJson('/api/plant-scanner/scans');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'farmer_id',
                    'status',
                    'ai_analysis',
                    'structured_data',
                    'tags',
                    'photos',
                    'created_at',
                    'updated_at',
                ],
            ],
        ])
        ->assertJsonPath('success', true);

    expect(count($response->json('data')))->toBe(2);
});

it('retrieves specific scan by id', function (): void {
    $scannedPlant = ScannedPlant::create([
        'farmer_id' => $this->dbUser->id,
        'status' => 'completed',
        'ai_analysis' => 'Detailed plant analysis',
        'structured_data' => [
            'plant_type' => 'tomato',
            'health_status' => 'healthy',
        ],
        'tags' => ['tomato', 'healthy', 'vegetable'],
        'photos' => ['/path/to/scan.jpg'],
        'image_hash' => 'unique_hash',
    ]);

    $response = $this->getJson("/api/plant-scanner/scans/{$scannedPlant->id}");

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $scannedPlant->id)
        ->assertJsonPath('data.ai_analysis', 'Detailed plant analysis')
        ->assertJsonPath('data.structured_data.plant_type', 'tomato')
        ->assertJsonPath('data.tags.0', 'tomato');
});

it('returns 404 for non-existent scan', function (): void {
    $response = $this->getJson('/api/plant-scanner/scans/99999');

    $response->assertStatus(404);
});

it('prevents access to other users scans', function (): void {
    // Create another user
    $otherUser = User::create([
        'phone' => '+998900000001',
        'application_alias' => 'farmer',
    ]);

    // Create scan for other user
    $otherUserScan = ScannedPlant::create([
        'user_id' => $otherUser->id,
        'status' => 'completed',
        'ai_analysis' => 'Private scan',
        'photos' => ['/path/to/private.jpg'],
        'image_hash' => 'private_hash',
    ]);

    $response = $this->getJson("/api/plant-scanner/scans/{$otherUserScan->id}");

    $response->assertStatus(404);
});
