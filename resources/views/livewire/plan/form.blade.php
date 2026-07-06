<div class="max-w-lg">
    <h1 class="mb-4 text-xl font-semibold">
        {{ $plan ? 'Edit Plan' : 'Create Plan' }}
    </h1>

    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="block text-sm font-medium">Title</label>
            <input type="text" wire:model="title" class="mt-1 w-full rounded border-gray-300">
            @error('title') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium">Description</label>
            <textarea wire:model="description" rows="4" class="mt-1 w-full rounded border-gray-300"></textarea>
            @error('description') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium">Price</label>
            <input type="number" wire:model="price" class="mt-1 w-full rounded border-gray-300">
            @error('price') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                    wire:loading.attr="disabled">
                {{ $plan ? 'Update' : 'Create' }}
            </button>
            <a href="{{ route('plans.index') }}" wire:navigate class="text-gray-600 hover:underline">
                Cancel
            </a>
        </div>
    </form>
</div>
