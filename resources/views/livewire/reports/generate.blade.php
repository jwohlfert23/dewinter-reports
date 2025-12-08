<?php

use App\Services\ReportPdfGenerator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.app', ['title' => 'Generate Report'])]
class extends Component {
    use WithFileUploads;

    public string $clientName = '';
    public $clientLogo = null;
    public string $date = '';
    public string $positionTitle = '';
    public string $googleSheetUrl = '';

    public ?string $generatedPdfPath = null;
    public bool $isGenerating = false;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function generateReport(ReportPdfGenerator $generator): void
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

        $this->isGenerating = true;

        $logoPath = null;
        if ($this->clientLogo) {
            $logoPath = $this->clientLogo->store('logos', 'public');
            $logoPath = storage_path('app/public/' . $logoPath);
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

    public function resetForm(): void
    {
        $this->reset(['clientName', 'clientLogo', 'positionTitle', 'googleSheetUrl', 'generatedPdfPath']);
        $this->date = now()->format('Y-m-d');
    }
}; ?>

<div class="max-w-4xl space-y-6">
    <flux:heading size="xl">Generate Weekly Report</flux:heading>
    <flux:text class="text-zinc-500">Fill out the form below to generate a PDF report from your Google Sheet data.</flux:text>

    @if($generatedPdfPath)
        <flux:callout variant="success">
            <flux:callout.heading>Report Generated Successfully!</flux:callout.heading>
            <flux:callout.text>Your PDF report has been generated and is ready to download.</flux:callout.text>
            <x-slot:actions>
                <flux:button href="{{ $this->getViewPdfUrl() }}" target="_blank" variant="primary" icon="eye">
                    View PDF
                </flux:button>
                <flux:button wire:click="downloadPdf" variant="filled" icon="arrow-down-tray">
                    Download
                </flux:button>
                <flux:button wire:click="resetForm" variant="ghost">
                    Generate Another
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

    <form wire:submit="generateReport" class="space-y-6">
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
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generateReport">Generate PDF Report</span>
                <span wire:loading wire:target="generateReport">Generating...</span>
            </flux:button>
        </div>
    </form>
</div>
