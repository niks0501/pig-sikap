<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Manage Document Types</h1>
            <p class="text-sm text-gray-500 mt-1">Define required document types and their upload constraints.</p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Add New Document Type</h2>
            <form method="POST" action="{{ route('admin.document-types.store') }}">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" required class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Size (KB)</label>
                        <input type="number" name="max_size_kb" value="10240" required class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Allowed File Types (select multiple)</label>
                        <select name="allowed_file_types[]" multiple class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]">
                            <option value="pdf">PDF</option>
                            <option value="jpg">JPG</option>
                            <option value="jpeg">JPEG</option>
                            <option value="png">PNG</option>
                            <option value="doc">DOC</option>
                            <option value="docx">DOCX</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="w-full sm:w-auto rounded-lg bg-[#0c6d57] px-4 py-2 font-semibold text-white hover:bg-[#0a5a48]">
                        Add Document Type
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">File Types</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Max Size</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($documentTypes as $type)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $type->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $type->description }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ implode(', ', $type->allowed_file_types) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $type->max_size_kb }} KB</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.document-types.destroy', $type->id) }}" onsubmit="return confirm('Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 text-sm font-medium">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">No document types defined.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>