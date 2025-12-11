<?php

use App\Models\Report;
use App\Models\User;
use App\Services\ReportPdfGenerator;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

test('guests are redirected to login when accessing reports index', function () {
    $response = $this->get(route('reports.index'));
    $response->assertRedirect(route('login'));
});

test('guests are redirected to login when accessing report create page', function () {
    $response = $this->get(route('reports.create'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can access the reports index page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reports.index'));

    $response->assertOk();
    $response->assertSee('Reports');
});

test('authenticated users can access the create report page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reports.create'));

    $response->assertOk();
    $response->assertSee('Create Report');
});

test('authenticated users can access the edit report page', function () {
    $user = User::factory()->create();
    $report = Report::factory()->for($user)->create();

    $response = $this->actingAs($user)->get(route('reports.edit', $report));

    $response->assertOk();
    $response->assertSee('Edit Report');
});

test('report form validates required fields', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('reports.generate')
        ->call('save')
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
        ->call('save')
        ->assertHasErrors(['googleSheetUrl']);
});

test('can create a new report', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('reports.generate')
        ->set('clientName', 'Test Client')
        ->set('positionTitle', 'Senior Developer')
        ->set('date', '2025-12-05')
        ->set('googleSheetUrl', 'https://docs.google.com/spreadsheets/d/123/edit')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Report::where('client_name', 'Test Client')->exists())->toBeTrue();
});

test('can edit an existing report', function () {
    $user = User::factory()->create();
    $report = Report::factory()->for($user)->create([
        'client_name' => 'Original Client',
    ]);

    Volt::actingAs($user)
        ->test('reports.generate', ['report' => $report])
        ->assertSet('clientName', 'Original Client')
        ->set('clientName', 'Updated Client')
        ->call('save')
        ->assertHasNoErrors();

    expect($report->fresh()->client_name)->toBe('Updated Client');
});

test('report form loads existing data in edit mode', function () {
    $user = User::factory()->create();
    $report = Report::factory()->for($user)->create([
        'client_name' => 'Acme Corp',
        'position_title' => 'Engineer',
        'date' => '2025-12-05',
        'google_sheet_url' => 'https://docs.google.com/spreadsheets/d/abc/edit',
    ]);

    Volt::actingAs($user)
        ->test('reports.generate', ['report' => $report])
        ->assertSet('clientName', 'Acme Corp')
        ->assertSet('positionTitle', 'Engineer')
        ->assertSet('date', '2025-12-05')
        ->assertSet('googleSheetUrl', 'https://docs.google.com/spreadsheets/d/abc/edit');
});

test('can delete a report from index', function () {
    $user = User::factory()->create();
    $report = Report::factory()->for($user)->create();

    Volt::actingAs($user)
        ->test('reports.index')
        ->call('delete', $report)
        ->assertHasNoErrors();

    expect(Report::find($report->id))->toBeNull();
});

test('reports index shows user reports', function () {
    $user = User::factory()->create();
    $report = Report::factory()->for($user)->create([
        'client_name' => 'Visible Client',
        'position_title' => 'Visible Position',
    ]);

    $response = $this->actingAs($user)->get(route('reports.index'));

    $response->assertSee('Visible Client');
    $response->assertSee('Visible Position');
});

test('reports index does not show other users reports', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherReport = Report::factory()->for($otherUser)->create([
        'client_name' => 'Other User Client',
    ]);

    $response = $this->actingAs($user)->get(route('reports.index'));

    $response->assertDontSee('Other User Client');
});

test('generate pdf requires report to be saved first', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('reports.generate')
        ->set('clientName', 'Test Client')
        ->set('positionTitle', 'Developer')
        ->set('date', '2025-12-05')
        ->set('googleSheetUrl', 'https://docs.google.com/spreadsheets/d/123/edit')
        ->call('generateReport')
        ->assertHasErrors(['generation']);
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
        ->and($grouped['Submitted']['candidates'])->toHaveCount(2)
        ->and($grouped)->toHaveKey('Interviewing')
        ->and($grouped['Interviewing']['candidates'])->toHaveCount(1)
        ->and($grouped)->toHaveKey('Rejected')
        ->and($grouped['Rejected']['candidates'])->toHaveCount(1);
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
