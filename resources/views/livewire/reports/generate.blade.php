<?php

use App\Models\Report;
use App\Services\ReportPdfGenerator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.app', ['title' => 'Report Form'])]
class extends Component {
    use WithFileUploads;

    public ?Report $report = null;

    public string $clientName = '';
    public $clientLogo = null;
    public ?string $existingLogoPath = null;
    public string $date = '';
    public string $positionTitle = '';
    public string $googleSheetUrl = '';

    public ?string $generatedPdfPath = null;
    public bool $isGenerating = false;

    public function mount(?Report $report = null): void
    {
        if ($report?->exists) {
            $this->report = $report;
            $this->clientName = $report->client_name;
            $this->existingLogoPath = $report->client_logo_path;
            $this->date = $report->date->format('Y-m-d');
            $this->positionTitle = $report->position_title;
            $this->googleSheetUrl = $report->google_sheet_url;
        } else {
            $this->date = now()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->validate([
            'clientName' => ['required', 'string', 'max:255'],
            'clientLogo' => ['nullable', 'image', 'max:2048'],
            'date' => ['required', 'date'],
            'positionTitle' => ['required', 'string', 'max:255'],
            'googleSheetUrl' => ['required', 'url', 'regex:/docs\.google\.com\/spreadsheets/'],
        ], [
            'googleSheetUrl.regex' => 'Please provide a valid Google Sheets URL.',
        ]);

        $logoPath = $this->existingLogoPath;
        if ($this->clientLogo) {
            $logoPath = $this->clientLogo->store('logos', 'public');
        }

        $data = [
            'client_name' => $this->clientName,
            'client_logo_path' => $logoPath,
            'date' => $this->date,
            'position_title' => $this->positionTitle,
            'google_sheet_url' => $this->googleSheetUrl,
        ];

        if ($this->report?->exists) {
            $this->report->update($data);
            session()->flash('success', 'Report updated successfully.');
        } else {
            $this->report = auth()->user()->reports()->create($data);
            session()->flash('success', 'Report created successfully.');
        }

        $this->redirect(route('reports.edit', $this->report), navigate: true);
    }

    public function generateReport(ReportPdfGenerator $generator): void
    {
        if (! $this->report?->exists) {
            $this->addError('generation', 'Please save the report first before generating a PDF.');
            return;
        }

        $this->isGenerating = true;

        $logoPath = null;
        if ($this->existingLogoPath) {
            $logoPath = storage_path('app/public/' . $this->existingLogoPath);
        }

        try {
            $pdfPath = $generator->generate([
                'client_name' => $this->clientName,
                'client_logo' => $logoPath,
                'date' => $this->date,
                'position_title' => $this->positionTitle,
                'google_sheet_url' => $this->googleSheetUrl,
            ]);

            $this->generatedPdfPath = $pdfPath;
        } catch (\Exception $e) {
            $this->addError('generation', 'Failed to generate PDF: ' . $e->getMessage());
        } finally {
            $this->isGenerating = false;
        }
    }

    public function downloadPdf()
    {
        if ($this->generatedPdfPath && file_exists($this->generatedPdfPath)) {
            return response()->download($this->generatedPdfPath);
        }
    }

    public function getViewPdfUrl(): ?string
    {
        if ($this->generatedPdfPath && file_exists($this->generatedPdfPath)) {
            return route('reports.view', ['filename' => basename($this->generatedPdfPath)]);
        }

        return null;
    }

    public function clearGeneratedPdf(): void
    {
        $this->generatedPdfPath = null;
    }

    public function isEditMode(): bool
    {
        return $this->report?->exists ?? false;
    }
}; ?>

<div class="max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $this->isEditMode() ? 'Edit Report' : 'Create Report' }}</flux:heading>
            <flux:text class="text-zinc-500">
                {{ $this->isEditMode() ? 'Update your report details or generate a new PDF.' : 'Fill out the form below to create a new report.' }}
            </flux:text>
        </div>
        <flux:button href="{{ route('reports.index') }}" variant="ghost" icon="arrow-left">
            Back to Reports
        </flux:button>
    </div>

    @if(session('success'))
        <flux:callout variant="success">
            <flux:callout.text>{{ session('success') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if($generatedPdfPath)
        <flux:callout variant="success">
            <flux:callout.heading>PDF Generated Successfully!</flux:callout.heading>
            <flux:callout.text>Your PDF report has been generated and is ready to download or view.</flux:callout.text>
            <x-slot:actions>
                <flux:button href="{{ $this->getViewPdfUrl() }}" target="_blank" variant="primary" icon="eye">
                    View PDF
                </flux:button>
                <flux:button wire:click="downloadPdf" variant="filled" icon="arrow-down-tray">
                    Download
                </flux:button>
                <flux:button wire:click="clearGeneratedPdf" variant="ghost">
                    Dismiss
                </flux:button>
            </x-slot:actions>
        </flux:callout>
    @endif

    @error('generation')
        <flux:callout variant="danger">
            <flux:callout.heading>Error</flux:callout.heading>
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @enderror

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-6 md:grid-cols-2">
            <flux:field>
                <flux:label>Client Name</flux:label>
                <flux:input
                    wire:model="clientName"
                    placeholder="e.g., SafelyYou"
                    required
                />
                <flux:error name="clientName" />
            </flux:field>

            <flux:field>
                <flux:label>Position Title</flux:label>
                <flux:input
                    wire:model="positionTitle"
                    placeholder="e.g., Senior Product Manager"
                    required
                />
                <flux:error name="positionTitle" />
            </flux:field>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <flux:field>
                <flux:label>Report Date</flux:label>
                <flux:input
                    wire:model="date"
                    type="date"
                    required
                />
                <flux:error name="date" />
            </flux:field>

            <flux:field>
                <flux:label>Client Logo (Optional)</flux:label>
                <input
                    wire:model="clientLogo"
                    type="file"
                    accept="image/*"
                    class="block w-full text-sm text-zinc-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-lg file:border-0
                        file:text-sm file:font-semibold
                        file:bg-zinc-100 file:text-zinc-700
                        hover:file:bg-zinc-200
                        dark:file:bg-zinc-700 dark:file:text-zinc-200
                        dark:hover:file:bg-zinc-600"
                />
                <flux:error name="clientLogo" />
                @if($clientLogo)
                    <div class="mt-2">
                        <img src="{{ $clientLogo->temporaryUrl() }}" class="h-12 object-contain" alt="Logo preview">
                    </div>
                @elseif($existingLogoPath)
                    <div class="mt-2">
                        <img src="{{ Storage::url($existingLogoPath) }}" class="h-12 object-contain" alt="Current logo">
                        <flux:text class="text-xs text-zinc-400">Current logo</flux:text>
                    </div>
                @endif
            </flux:field>
        </div>

        <flux:field>
            <flux:label>Google Sheet URL</flux:label>
            <flux:input
                wire:model="googleSheetUrl"
                type="url"
                placeholder="https://docs.google.com/spreadsheets/d/e/.../pub?output=csv"
                required
            />
            <flux:text class="mt-1 text-sm text-zinc-500">
                Publish your Google Sheet as CSV: File > Share > Publish to web > CSV format
            </flux:text>
            <flux:error name="googleSheetUrl" />
        </flux:field>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $this->isEditMode() ? 'Update Report' : 'Create Report' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>

            @if($this->isEditMode())
                <flux:button type="button" wire:click="generateReport" variant="filled" icon="document-arrow-down" wire:loading.attr="disabled" wire:target="generateReport">
                    <span wire:loading.remove wire:target="generateReport">Generate PDF</span>
                    <span wire:loading wire:target="generateReport">Generating...</span>
                </flux:button>
            @endif
        </div>
    </form>
</div>
