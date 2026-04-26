<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('expenses.show', $expense) }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Expense</h1>
                <p class="text-sm text-gray-500 mt-1">Update a recorded expense entry.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                Please review the form and correct the highlighted fields.
            </div>
        @endif

        <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            @csrf
            @method('PUT')

            <div class="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="expense_date" class="block text-sm font-semibold text-gray-700 mb-1">Date <span class="text-rose-500">*</span></label>
                    <input id="expense_date" name="expense_date" type="date" value="{{ old('expense_date', $expense->expense_date?->toDateString()) }}" class="w-full rounded-xl border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]" required>
                    @error('expense_date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="batch_id" class="block text-sm font-semibold text-gray-700 mb-1">Cycle <span class="text-rose-500">*</span></label>
                    <select id="batch_id" name="batch_id" class="w-full rounded-xl border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]" required>
                        @foreach ($cycles as $cycle)
                            <option value="{{ $cycle->id }}" @selected((string) old('batch_id', (string) $expense->batch_id) === (string) $cycle->id)>
                                {{ $cycle->batch_code }}{{ $cycle->isArchived() ? ' (Archived)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('batch_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-semibold text-gray-700 mb-1">Category <span class="text-rose-500">*</span></label>
                    <select id="category" name="category" class="w-full rounded-xl border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]" required>
                        @foreach ($categoryOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $expense->category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="amount" class="block text-sm font-semibold text-gray-700 mb-1">Amount (Php) <span class="text-rose-500">*</span></label>
                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', (float) $expense->amount) }}" class="w-full rounded-xl border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]" required>
                    @error('amount')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="notes" class="block text-sm font-semibold text-gray-700 mb-1">Description / Notes <span class="text-rose-500">*</span></label>
                    <textarea id="notes" name="notes" rows="3" class="w-full rounded-xl border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]" required>{{ old('notes', $expense->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2" x-data="{ fileName: '' }">
                    <label for="receipt" class="block text-sm font-semibold text-gray-700 mb-1">Replace Receipt (Optional)</label>
                    <input id="receipt" name="receipt" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="w-full rounded-xl border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                    <p class="mt-1 text-xs text-gray-500">Allowed: JPG, JPEG, PNG, WEBP, PDF up to 8MB.</p>
                    <p x-show="fileName" x-text="fileName" class="mt-1 text-xs text-gray-700" x-cloak></p>
                    @error('receipt')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror

                    @if ($expense->receipt_path)
                        <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remove_receipt" value="1" class="rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]" @checked(old('remove_receipt'))>
                            Remove existing receipt
                        </label>
                    @endif
                    @error('remove_receipt')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="{{ route('expenses.show', $expense) }}" class="inline-flex justify-center items-center px-5 py-2.5 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="inline-flex justify-center items-center px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#0c6d57] hover:bg-[#0a5a48] transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
