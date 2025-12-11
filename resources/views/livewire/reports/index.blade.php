<?php

use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app', ['title' => 'Reports'])]
class extends Component {
    public function reports(): Collection
    {
        return auth()->user()->reports()->latest()->get();
    }

    public function delete(Report $report): void
    {
        $report->delete();

        session()->flash('success', 'Report deleted successfully.');
    }

    public function with(): array
    {
        return [
            'reports' => $this->reports(),
        ];
    }
}; ?>

<div class="max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Reports</flux:heading>
            <flux:text class="text-zinc-500">Manage your saved reports and generate PDFs.</flux:text>
        </div>
        <flux:button href="{{ route('reports.create') }}" variant="primary" icon="plus">
            New Report
        </flux:button>
    </div>

    @if(session('success'))
        <flux:callout variant="success">
            <flux:callout.text>{{ session('success') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if($reports->isEmpty())
        <flux:callout>
            <flux:callout.heading>No reports yet</flux:callout.heading>
            <flux:callout.text>Create your first report to get started.</flux:callout.text>
            <x-slot:actions>
                <flux:button href="{{ route('reports.create') }}" variant="primary" icon="plus">
                    Create Report
                </flux:button>
            </x-slot:actions>
        </flux:callout>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Client
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Position
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Date
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($reports as $report)
                        <tr wire:key="report-{{ $report->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($report->client_logo_path)
                                        <img src="{{ Storage::url($report->client_logo_path) }}" class="h-8 w-8 rounded object-contain" alt="{{ $report->client_name }}">
                                    @endif
                                    <flux:text class="font-medium">{{ $report->client_name }}</flux:text>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $report->position_title }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $report->date->format('M j, Y') }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button href="{{ route('reports.edit', $report) }}" size="sm" variant="ghost" icon="pencil">
                                        Edit
                                    </flux:button>
                                    <flux:button wire:click="delete({{ $report->id }})" wire:confirm="Are you sure you want to delete this report?" size="sm" variant="ghost" icon="trash" class="text-red-600 hover:text-red-700">
                                        Delete
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
