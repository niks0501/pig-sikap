<x-app-layout>
    <div class="max-w-5xl mx-auto px-4 py-8 sm:px-6 lg:px-8 space-y-8">
        <!-- Hero Banner -->
        <div class="rounded-3xl bg-gradient-to-br from-[#0c6d57] via-[#0a5a48] to-[#074d3b] p-8 sm:p-10 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/4"></div>
            <div class="absolute bottom-0 left-1/2 w-48 h-48 bg-white/5 rounded-full translate-y-1/2"></div>
            <div class="relative">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200 mb-4">Membership Guide</p>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight mb-4">How to Join Elite Visionaries Association</h1>
                <p class="text-emerald-100/80 max-w-2xl leading-relaxed">Simple informational guide. This page does not collect or upload sensitive documents — everything stays offline.</p>
            </div>
        </div>

        <!-- Steps -->
        <div class="grid gap-5 md:grid-cols-3">
            <div class="group bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-[#0c6d57]/10 flex items-center justify-center text-[#0c6d57] font-bold text-lg group-hover:bg-[#0c6d57] group-hover:text-white transition-colors duration-300 mb-5">1</div>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Coordinate With Officers</h2>
                <p class="text-sm text-gray-500 leading-relaxed">Talk to the current officers of Elite Visionaries Association and ask for the latest meeting schedule, requirements, and profiling instructions.</p>
            </div>
            <div class="group bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-[#0c6d57]/10 flex items-center justify-center text-[#0c6d57] font-bold text-lg group-hover:bg-[#0c6d57] group-hover:text-white transition-colors duration-300 mb-5">2</div>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Prepare Requirements</h2>
                <p class="text-sm text-gray-500 leading-relaxed">Prepare paper copies only. Do not upload IDs or certificates here because this page is informational.</p>
            </div>
            <div class="group bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-[#0c6d57]/10 flex items-center justify-center text-[#0c6d57] font-bold text-lg group-hover:bg-[#0c6d57] group-hover:text-white transition-colors duration-300 mb-5">3</div>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">DSWD SLP Profiling</h2>
                <p class="text-sm text-gray-500 leading-relaxed">The SLP Focal Person checks if the applicant qualifies under program criteria such as Listahanan or 4Ps where applicable.</p>
            </div>
        </div>

        <!-- Requirements Checklist -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-gray-100 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div>
                    <p class="text-xs font-semibold text-[#0c6d57] uppercase tracking-wider">Checklist</p>
                    <h2 class="text-xl font-bold text-gray-900 mt-1">Membership File Requirements</h2>
                </div>
                <span class="self-start inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-100 text-amber-700 text-xs font-semibold">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Paper documents only
                </span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4 text-sm font-medium text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                    <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                    <span>Photocopy of Valid ID</span>
                </label>
                <label class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4 text-sm font-medium text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                    <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                    <span>Barangay Certificate of Residency</span>
                </label>
                <label class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4 text-sm font-medium text-gray-700 hover:bg-gray-50 transition cursor-pointer sm:col-span-2">
                    <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                    <span>Barangay Certificate of Indigency, if required for DSWD profiling</span>
                </label>
            </div>
        </div>

        <!-- Privacy -->
        <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-900 mb-1">Privacy Reminder</h3>
                    <p class="text-sm text-amber-700 leading-relaxed">Keep valid IDs and barangay certificates in the official paper membership file. Only add digital document uploads later if the system has a secure approved pattern for sensitive files.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
