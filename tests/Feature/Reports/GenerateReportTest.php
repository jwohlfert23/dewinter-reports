<?php

use App\Models\User;
use App\Services\ReportPdfGenerator;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

test('guests are redirected to login when accessing report generator', function () {
    $response = $this->get(route('reports.generate'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can access the report generator page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reports.generate'));

    $response->assertOk();
    $response->assertSee('Generate Weekly Report');
});

test('report form validates required fields', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('reports.generate')
        ->call('generateReport')
        ->assertHasErrors(['clientName', 'positionTitle', 'googleSheetUrl']);
});

test('report form validates google sheet url format', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('reports.generate')
        ->set('clientName', 'Test Client')
        ->set('positionTitle', 'Senior Developer')
        ->set('date', '2025-12-05')
        ->set('googleSheetUrl', 'https://example.com/not-a-sheet')
        ->call('generateReport')
        ->assertHasErrors(['googleSheetUrl']);
});

test('report pdf generator service groups candidates by status', function () {
    $generator = new ReportPdfGenerator;

    $reflection = new ReflectionClass($generator);
    $method = $reflection->getMethod('groupByStatus');
    $method->setAccessible(true);

    $candidates = [
        ['Candidate' => 'John Doe', 'Company' => 'Acme', 'Status' => 'Submitted'],
        ['Candidate' => 'Jane Smith', 'Company' => 'Tech Corp', 'Status' => 'Interviewing'],
        ['Candidate' => 'Bob Wilson', 'Company' => 'Startup', 'Status' => 'Submitted'],
        ['Candidate' => 'Alice Brown', 'Company' => 'BigCo', 'Status' => 'Rejected'],
    ];

    $grouped = $method->invoke($generator, $candidates);

    expect($grouped)->toHaveKey('Submitted')
        ->and($grouped['Submitted'])->toHaveCount(2)
        ->and($grouped)->toHaveKey('Interviewing')
        ->and($grouped['Interviewing'])->toHaveCount(1)
        ->and($grouped)->toHaveKey('Rejected')
        ->and($grouped['Rejected'])->toHaveCount(1);
});

test('report pdf generator service orders statuses correctly', function () {
    $generator = new ReportPdfGenerator;

    $reflection = new ReflectionClass($generator);
    $method = $reflection->getMethod('groupByStatus');
    $method->setAccessible(true);

    $candidates = [
        ['Candidate' => 'Person 1', 'Company' => 'Co', 'Status' => 'Rejected'],
        ['Candidate' => 'Person 2', 'Company' => 'Co', 'Status' => 'Screening'],
        ['Candidate' => 'Person 3', 'Company' => 'Co', 'Status' => 'Interviewing'],
        ['Candidate' => 'Person 4', 'Company' => 'Co', 'Status' => 'Submitted'],
        ['Candidate' => 'Person 5', 'Company' => 'Co', 'Status' => 'Not Interested'],
    ];

    $grouped = $method->invoke($generator, $candidates);
    $orderedKeys = array_keys($grouped);

    expect($orderedKeys)->toBe(['Interviewing', 'Submitted', 'Screening', 'Rejected', 'Not Interested']);
});

test('report pdf generator service parses csv data correctly', function () {
    Http::fake([
        'docs.google.com/*' => Http::response(
            "Candidate,LinkedIn URL,Company,Status,Notes\nJohn Doe,https://linkedin.com/in/johndoe,Acme Inc,Submitted,\nJane Smith,https://linkedin.com/in/janesmith,Tech Corp,Interviewing,Great candidate",
            200
        ),
    ]);

    $generator = new ReportPdfGenerator;

    $reflection = new ReflectionClass($generator);
    $method = $reflection->getMethod('fetchCsvData');
    $method->setAccessible(true);

    $candidates = $method->invoke($generator, 'https://docs.google.com/spreadsheets/test');

    expect($candidates)->toHaveCount(2)
        ->and($candidates[0]['Candidate'])->toBe('John Doe')
        ->and($candidates[0]['Company'])->toBe('Acme Inc')
        ->and($candidates[0]['Status'])->toBe('Submitted')
        ->and($candidates[1]['Candidate'])->toBe('Jane Smith')
        ->and($candidates[1]['Status'])->toBe('Interviewing');
});

test('report form can be reset', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('reports.generate')
        ->set('clientName', 'Test Client')
        ->set('positionTitle', 'Developer')
        ->set('googleSheetUrl', 'https://docs.google.com/spreadsheets/test')
        ->call('resetForm')
        ->assertSet('clientName', '')
        ->assertSet('positionTitle', '')
        ->assertSet('googleSheetUrl', '');
});
