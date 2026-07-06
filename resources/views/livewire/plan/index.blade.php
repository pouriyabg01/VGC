<div>
    @if (session()->has('message'))
        <div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Plans</h1>
        <a href="{{ route('plans.create') }}" wire:navigate
           class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
            + New Plan
        </a>
    </div>

    <table class="w-full border-collapse text-left">
        <thead>
        <tr class="border-b">
            <th class="py-2">Title</th>
            <th class="py-2">Price</th>
            <th class="py-2">Description</th>
            <th class="py-2 text-right">Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($plans as $plan)
            <tr class="border-b" wire:key="plan-{{ $plan->id }}">
                <td class="py-2">{{ $plan->title }}</td>
                <td class="py-2">{{ $plan->price }}</td>
                <td class="py-2">{{ Str::limit($plan->description, 60) }}</td>
                <td class="py-2 text-right space-x-2">
                    <a href="{{ route('plans.edit', $plan) }}" wire:navigate
                       class="text-blue-600 hover:underline">Edit</a>
                    <button
                        wire:click="delete({{ $plan->id }})"
                        wire:confirm="Are you sure you want to delete this plan?"
                        class="text-red-600 hover:underline">
                        Delete
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="py-4 text-center text-gray-500">No plans yet.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $plans->links() }}
    </div>
</div>
