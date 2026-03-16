@extends('layouts.app')

@section('title', 'Edit Stock Take')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit Stock Take
                    #{{ $stockTake->stock_take_number }}</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $stockTake->branch->name }} •
                    {{ $stockTake->stock_take_date->format('M d, Y') }}</p>
            </div>
            <a href="{{ route('stock-takes.show', $stockTake) }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <!-- Add Product Form -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Add Product</h2>
            <form method="POST" action="{{ route('stock-takes.add-item', $stockTake) }}" class="flex gap-4">
                @csrf
                <div class="flex-1">
                    <select id="product_id" name="product_id" required
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">Select Product</option>
                        @foreach ($branchStocks as $branchStock)
                            @if (!in_array($branchStock->product_id, $existingProductIds))
                                <option value="{{ $branchStock->product_id }}">
                                    {{ $branchStock->product->name }} ({{ $branchStock->product->sku }}) - System:
                                    {{ $branchStock->quantity }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('product_id')
                        <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                    class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2 whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Add Product</span>
                </button>
            </form>
        </div>

        <!-- Stock Take Items -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="px-6 py-4 border-b border-themeBorder bg-themeInput/80">
                <h2 class="text-lg font-semibold text-primary tracking-tight">Items ({{ $stockTake->items->count() }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Opening Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Closing Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Variance</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($stockTake->items as $item)
                            <tr class="hover:bg-themeInput/50 transition-colors" id="item-row-{{ $item->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $item->product->name }}</div>
                                    <div class="text-xs font-medium text-themeMuted">{{ $item->product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $item->system_quantity }}</div>
                                    <div class="text-xs font-medium text-themeMuted">(System at start)</div>
                                </td>
                                <td class="px-6 py-4 min-w-[320px]">
                                    <form method="POST"
                                        action="{{ route('stock-takes.update-item', [$stockTake, $item]) }}"
                                        class="inline-form" data-item-id="{{ $item->id }}"
                                        enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div class="flex items-center space-x-2">
                                            <input type="number" name="physical_quantity"
                                                value="{{ $item->physical_quantity ?? '' }}" min="0" step="1"
                                                class="w-24 px-3 py-2 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                                                placeholder="Count">
                                            <button type="submit"
                                                class="bg-primary text-white px-3 py-2 rounded-xl font-medium hover:bg-primary-dark transition text-sm shadow-sm">
                                                Save
                                            </button>
                                        </div>
                                        <p class="text-xs font-medium text-themeMuted mt-1">Actual counted quantity</p>
                                        <div class="mt-3 border-t border-themeBorder pt-3">
                                            <label class="block text-sm font-medium text-themeBody mb-2">IMEI numbers
                                                (optional)</label>
                                            <textarea name="imeis" rows="3" maxlength="2000" placeholder="One IMEI per line or comma-separated"
                                                class="w-full px-4 py-2.5 text-sm border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"></textarea>
                                            <div class="mt-2">
                                                <label for="imei_file_{{ $item->id }}"
                                                    class="block text-xs font-medium text-themeBody mb-1">Or upload
                                                    CSV/Excel</label>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <input type="file" name="imei_file" id="imei_file_{{ $item->id }}"
                                                        accept=".csv,.xlsx,.xls"
                                                        class="block flex-1 min-w-0 text-sm text-themeBody file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20">
                                                    <button type="button"
                                                        data-item-id="{{ $item->id }}"
                                                        data-upload-url="{{ route('stock-takes.upload-imei-file', [$stockTake, $item]) }}"
                                                        data-batch-url="{{ route('stock-takes.process-imei-batch', [$stockTake, $item]) }}"
                                                        class="stock-take-upload-imei-btn bg-primary/10 text-primary px-3 py-2 rounded-lg text-sm font-medium hover:bg-primary/20 transition">
                                                        Upload file
                                                    </button>
                                                </div>
                                                <div id="imei-progress-{{ $item->id }}" class="hidden mt-2">
                                                    <div class="flex items-center justify-between text-xs text-themeBody mb-1">
                                                        <span>Processing…</span>
                                                        <span class="imei-progress-text">0 / 0</span>
                                                    </div>
                                                    <div class="w-full bg-themeInput rounded-full h-2">
                                                        <div class="imei-progress-bar h-2 rounded-full bg-primary transition-all duration-300" style="width: 0%"></div>
                                                    </div>
                                                </div>
                                                <div id="imei-report-{{ $item->id }}" class="hidden mt-2 p-3 rounded-xl bg-themeInput/80 border border-themeBorder text-sm">
                                                    <p class="font-medium text-themeHeading mb-1">Upload report</p>
                                                    <p class="imei-report-uploaded text-themeBody">Uploaded: 0</p>
                                                    <p class="imei-report-existing text-themeBody">Already existing: 0</p>
                                                    <p class="imei-report-never text-themeBody">Never recorded: 0</p>
                                                </div>
                                                <p class="mt-1 text-xs text-themeMuted">
                                                    <a href="{{ asset('sample_imei_upload.csv') }}" download
                                                        class="text-primary hover:underline font-medium">Download sample
                                                        CSV</a>
                                                    — one IMEI per row, header: <code class="text-themeBody">imei</code>.
                                                    Large files are processed in batches with a progress bar.
                                                </p>
                                            </div>
                                            <p class="mt-1 text-xs text-themeMuted">Confirm/register devices at this branch.
                                                New IMEIs will be created.</p>
                                            @error('imeis')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        @if ($item->notes)
                                            <div class="mt-1 text-xs font-medium text-themeMuted">{{ $item->notes }}</div>
                                        @endif
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($item->physical_quantity !== null && $item->variance > 0)
                                        <span
                                            class="text-sm font-medium text-emerald-600">+{{ $item->variance }}</span>
                                    @elseif ($item->physical_quantity !== null && $item->variance < 0)
                                        <span class="text-sm font-medium text-red-600">{{ $item->variance }}</span>
                                    @elseif ($item->physical_quantity !== null)
                                        <span class="text-sm font-medium text-themeBody">0</span>
                                    @else
                                        <span class="text-sm font-medium text-themeMuted">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST"
                                        action="{{ route('stock-takes.remove-item', [$stockTake, $item]) }}"
                                        class="inline" onsubmit="return confirm('Remove this product from stock take?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-red-600 hover:text-red-800 font-medium text-sm">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-themeMuted font-medium">No items
                                    added
                                    yet. Add products above to start counting.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Update Stock Take Details -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Stock Take Details</h2>
            <form method="POST" action="{{ route('stock-takes.update', $stockTake) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="stock_take_date" class="block text-sm font-medium text-themeBody mb-2">Stock Take Date
                            *</label>
                        <input type="date" id="stock_take_date" name="stock_take_date"
                            value="{{ old('stock_take_date', $stockTake->stock_take_date->format('Y-m-d')) }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('stock_take_date')
                            <p class="text-red-500 text-sm font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-themeBody mb-2">Notes</label>
                    <textarea id="notes" name="notes" rows="4"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('notes', $stockTake->notes) }}</textarea>
                    @error('notes')
                        <p class="text-red-500 text-sm font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex space-x-3">
                    <button type="submit"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <span>Update Details</span>
                    </button>
                    <a href="{{ route('stock-takes.show', $stockTake) }}"
                        class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                        <span>Cancel</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-submit count forms on Enter key
        document.querySelectorAll('.inline-form').forEach(form => {
            const input = form.querySelector('input[name="physical_quantity"]');
            if (input) {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        form.submit();
                    }
                });
            }
        });

        // Stock take IMEI file upload: batches + progress bar + report
        document.querySelectorAll('.stock-take-upload-imei-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const itemId = this.dataset.itemId;
                const fileInputId = this.dataset.fileInputId || ('imei_file_' + itemId);
                const fileInput = document.getElementById(fileInputId);
                if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                    alert('Please choose a CSV or Excel file first.');
                    return;
                }
                const uploadUrl = this.dataset.uploadUrl;
                const batchUrl = this.dataset.batchUrl;
                const progressEl = document.getElementById('imei-progress-' + itemId);
                const reportEl = document.getElementById('imei-report-' + itemId);
                const progressBar = progressEl && progressEl.querySelector('.imei-progress-bar');
                const progressText = progressEl && progressEl.querySelector('.imei-progress-text');
                const csrfToken = document.querySelector('input[name="_token"]')?.value || '';

                const formData = new FormData();
                formData.append('imei_file', fileInput.files[0]);
                formData.append('_token', csrfToken);

                try {
                    const uploadRes = await fetch(uploadUrl, { method: 'POST', body: formData });
                    const uploadData = await uploadRes.json();
                    if (!uploadRes.ok) {
                        alert(uploadData.error || uploadData.message || 'Upload failed.');
                        return;
                    }
                    const total = uploadData.total || 0;
                    if (total === 0) {
                        alert('No valid IMEIs found in the file.');
                        return;
                    }

                    const batchSize = uploadData.batch_size || 100;
                    const totalBatches = Math.ceil(total / batchSize);
                    let uploaded = 0, alreadyExisting = 0, neverRecorded = 0;

                    progressEl.classList.remove('hidden');
                    reportEl.classList.add('hidden');

                    for (let batchIndex = 0; batchIndex < totalBatches; batchIndex++) {
                        const batchRes = await fetch(batchUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                upload_id: uploadData.upload_id,
                                batch_index: batchIndex,
                                _token: csrfToken
                            })
                        });
                        const batchData = await batchRes.json();
                        if (!batchRes.ok) {
                            alert(batchData.error || 'Batch failed.');
                            break;
                        }
                        uploaded += batchData.uploaded || 0;
                        alreadyExisting += batchData.already_existing || 0;
                        neverRecorded += batchData.never_recorded || 0;

                        const processed = batchData.processed || (batchIndex + 1) * batchSize;
                        const pct = Math.min(100, Math.round((processed / total) * 100));
                        if (progressBar) progressBar.style.width = pct + '%';
                        if (progressText) progressText.textContent = processed + ' / ' + total;
                    }

                    progressEl.classList.add('hidden');
                    reportEl.classList.remove('hidden');
                    reportEl.querySelector('.imei-report-uploaded').textContent = 'Uploaded: ' + uploaded;
                    reportEl.querySelector('.imei-report-existing').textContent = 'Already existing: ' + alreadyExisting;
                    reportEl.querySelector('.imei-report-never').textContent = 'Never recorded: ' + neverRecorded;
                } catch (e) {
                    alert('Error: ' + (e.message || 'Network or server error.'));
                    if (progressEl) progressEl.classList.add('hidden');
                }
            });
        });
    </script>
@endsection
