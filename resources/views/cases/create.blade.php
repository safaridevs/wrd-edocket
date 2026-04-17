<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Case</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('cases.create.partials.alerts')

            <div class="grid gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">
                <aside class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-[linear-gradient(140deg,#0f172a_0%,#1d4ed8_52%,#dbeafe_100%)] p-6 text-white shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-100">Case Intake</p>
                        <h3 class="mt-3 text-2xl font-semibold leading-tight">Build the case in clear stages.</h3>
                        <p class="mt-3 text-sm leading-6 text-blue-50/90">Move from basics to parties, then documents, and finish with a full review before anything is sent to the Hearing Unit.</p>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Progress</p>
                                <p id="wizardProgressLabel" class="mt-1 text-sm font-medium text-slate-900">Step 1 of 5</p>
                            </div>
                            <span id="wizardProgressPercent" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">20%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100">
                            <div id="wizardProgressBar" class="h-2 rounded-full bg-gradient-to-r from-sky-500 via-blue-600 to-cyan-400 transition-all duration-300" style="width: 20%"></div>
                        </div>

                        <div class="mt-6 space-y-3" id="wizardStepNav">
                            <button type="button" data-step-target="0" class="wizard-step-chip w-full rounded-2xl border border-slate-200 px-4 py-3 text-left transition">
                                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 1</span>
                                <span class="mt-1 block text-sm font-semibold text-slate-900">Case Basics</span>
                                <span class="mt-1 block text-xs text-slate-500">Type, caption, office, and assignments.</span>
                            </button>
                            <button type="button" data-step-target="1" class="wizard-step-chip w-full rounded-2xl border border-slate-200 px-4 py-3 text-left transition">
                                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 2</span>
                                <span class="mt-1 block text-sm font-semibold text-slate-900">Parties & Counsel</span>
                                <span class="mt-1 block text-xs text-slate-500">Add participants, service, and representation.</span>
                            </button>
                            <button type="button" data-step-target="2" class="wizard-step-chip w-full rounded-2xl border border-slate-200 px-4 py-3 text-left transition">
                                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 3</span>
                                <span class="mt-1 block text-sm font-semibold text-slate-900">Case Numbers</span>
                                <span class="mt-1 block text-xs text-slate-500">Capture OSE file numbers and ranges.</span>
                            </button>
                            <button type="button" data-step-target="3" class="wizard-step-chip w-full rounded-2xl border border-slate-200 px-4 py-3 text-left transition">
                                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 4</span>
                                <span class="mt-1 block text-sm font-semibold text-slate-900">Documents</span>
                                <span class="mt-1 block text-xs text-slate-500">Upload required and supporting filings.</span>
                            </button>
                            <button type="button" data-step-target="4" class="wizard-step-chip w-full rounded-2xl border border-slate-200 px-4 py-3 text-left transition">
                                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 5</span>
                                <span class="mt-1 block text-sm font-semibold text-slate-900">Review & Submit</span>
                                <span class="mt-1 block text-xs text-slate-500">Check the intake summary before filing.</span>
                            </button>
                        </div>
                    </div>
                </aside>

                <form id="caseCreateForm" method="POST" action="{{ route('cases.store') }}" enctype="multipart/form-data" class="rounded-[32px] border border-slate-200 bg-white shadow-sm">
                    @csrf

                    <div class="border-b border-slate-200 px-6 py-6 sm:px-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600" id="wizardStepEyebrow">Step 1</p>
                        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h3 id="wizardStepTitle" class="text-2xl font-semibold text-slate-950">Case Basics</h3>
                                <p id="wizardStepDescription" class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Set the foundational details that determine the rest of the intake experience.</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                <span class="font-semibold text-slate-900">Guided Intake</span>
                                <span class="block mt-1">Each step validates before you move forward.</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-6 sm:px-8">
                        <section class="wizard-step" data-step-index="0">
                            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                                <div>
                                    @include('cases.create.partials.case-details')
                                </div>
                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Why This Matters</h4>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">These choices drive allowed party roles, required documents, and the review path used later in intake.</p>
                                    <div class="mt-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                                        <p class="text-sm font-semibold text-slate-900">Case Setup Tips</p>
                                        <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                                            <li>Choose the case type first so the rest of the form can adjust correctly.</li>
                                            <li>Use the caption exactly as it should appear in notices and filings.</li>
                                            <li>Set assignments here if your role requires intake routing.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Assignments</h4>
                                <p class="mt-2 text-sm leading-6 text-slate-600">If your role can route work now, assign the ALU team before the case moves forward.</p>
                                <div class="mt-5">
                                    @include('cases.create.partials.assignments')
                                </div>
                            </div>
                        </section>

                        <section class="wizard-step hidden" data-step-index="1">
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h4 class="text-lg font-semibold text-slate-950">Parties and Representation</h4>
                                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Add each party once, choose whether it is an individual or entity, then capture service and counsel details in the same flow.</p>
                                    </div>
                                    <div class="rounded-2xl bg-white px-4 py-3 text-sm text-slate-600 shadow-sm ring-1 ring-slate-100">
                                        <span class="font-semibold text-slate-900">Tip</span>
                                        <span class="block mt-1">Use one party card per participant so review and service remain clean later.</span>
                                    </div>
                                </div>
                                @include('cases.create.partials.parties')
                            </div>
                        </section>

                        <section class="wizard-step hidden" data-step-index="2">
                            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                    <h4 class="text-lg font-semibold text-slate-950">OSE File Numbers</h4>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">Capture the case numbers cleanly, including ranges when a matter spans more than one file number.</p>
                                    <div class="mt-5">
                                        @include('cases.create.partials.ose-numbers')
                                    </div>
                                </div>
                                <div class="rounded-3xl border border-slate-200 bg-[linear-gradient(180deg,#f8fafc_0%,#eff6ff_100%)] p-5">
                                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Numbering Notes</h4>
                                    <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                                        <li>Use separate entries when file numbers are unrelated.</li>
                                        <li>Use ranges only when the matter truly spans sequential records.</li>
                                        <li>Accurate case numbers improve search, docketing, and notices later.</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <section class="wizard-step hidden" data-step-index="3">
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h4 class="text-lg font-semibold text-slate-950">Document Intake</h4>
                                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Upload only what belongs with this case. The form will switch requirements based on case type and pleading choices.</p>
                                    </div>
                                    <div class="rounded-2xl bg-white px-4 py-3 text-sm text-slate-600 shadow-sm ring-1 ring-slate-100">
                                        <span class="font-semibold text-slate-900">File Check</span>
                                        <span class="block mt-1">Each file is checked against the current intake path before submission.</span>
                                    </div>
                                </div>
                                @include('cases.create.partials.documents')
                            </div>
                        </section>

                        <section class="wizard-step hidden" data-step-index="4">
                            <div class="space-y-6">
                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                    <h4 class="text-lg font-semibold text-slate-950">Review Intake Summary</h4>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">Confirm the case setup, parties, and document package before you save or submit. If something looks off, go back to that step and fix it there.</p>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2" id="wizardReviewGrid">
                                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Case Basics</p>
                                        <div id="reviewBasics" class="mt-4 space-y-3 text-sm text-slate-700"></div>
                                    </div>
                                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Assignments & Numbers</p>
                                        <div id="reviewRouting" class="mt-4 space-y-3 text-sm text-slate-700"></div>
                                    </div>
                                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Parties</p>
                                        <div id="reviewParties" class="mt-4 space-y-3 text-sm text-slate-700"></div>
                                    </div>
                                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Documents</p>
                                        <div id="reviewDocuments" class="mt-4 space-y-3 text-sm text-slate-700"></div>
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
                                    <p class="text-sm font-semibold text-emerald-900">Final Confirmation</p>
                                    <p class="mt-2 text-sm leading-6 text-emerald-800">Use <span class="font-semibold">Save Draft</span> if the intake should remain editable, or <span class="font-semibold">Submit to HU</span> once everything is ready for Hearing Unit review.</p>
                                </div>

                                @include('cases.create.partials.actions')
                            </div>
                        </section>
                    </div>

                    <div class="border-t border-slate-200 bg-slate-50 px-6 py-5 sm:px-8">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-slate-500">Draft as you go. Nothing is submitted until the final step.</div>
                            <div class="flex items-center gap-3">
                                <button type="button" id="wizardPrevBtn" class="rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900">
                                    Back
                                </button>
                                <button type="button" id="wizardNextBtn" class="rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    Continue
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include('cases.create.partials.scripts')
</x-app-layout>

