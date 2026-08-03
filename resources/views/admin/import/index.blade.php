@extends('layouts.admin')
@section('title', 'Import Excel')
@section('page-title', 'Import Data HSP')
@section('breadcrumb')
    <span>Import Excel</span>
@endsection
@section('content')

@if ($errors->any())
<div class="alert danger fade-in">{{ $errors->first() }}</div>
@endif

{{-- Steps indicator --}}
<div style="display:flex;gap:0;margin-bottom:24px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;">
    <div style="flex:1;padding:14px 20px;display:flex;align-items:center;gap:12px;background:var(--primary);color:#fff;">
        <div style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:13px;font-weight:700;">1</div>
        <div>
            <div style="font-weight:600;font-size:14px;">Pilih File</div>
            <div style="font-size:12px;opacity:.8;">Upload file Excel</div>
        </div>
    </div>
    <div style="flex:1;padding:14px 20px;display:flex;align-items:center;gap:12px;opacity:.5;">
        <div style="width:28px;height:28px;border-radius:50%;background:var(--bg);display:grid;place-items:center;font-size:13px;font-weight:700;color:var(--text-secondary);">2</div>
        <div>
            <div style="font-weight:600;font-size:14px;color:var(--text);">Import</div>
            <div style="font-size:12px;color:var(--text-secondary);">Proses data</div>
        </div>
    </div>
    <div style="flex:1;padding:14px 20px;display:flex;align-items:center;gap:12px;opacity:.5;">
        <div style="width:28px;height:28px;border-radius:50%;background:var(--bg);display:grid;place-items:center;font-size:13px;font-weight:700;color:var(--text-secondary);">3</div>
        <div>
            <div style="font-weight:600;font-size:14px;color:var(--text);">Selesai</div>
            <div style="font-size:12px;color:var(--text-secondary);">Data tersimpan</div>
        </div>
    </div>
</div>

<div class="card" style="padding:0;">
    <form method="POST" action="{{ route('admin.import.store') }}" enctype="multipart/form-data" id="import-form">
        @csrf

        <div id="drop-zone"
             style="padding:64px 32px 48px;cursor:pointer;transition:all .25s;border-radius:10px 10px 0 0;position:relative;text-align:center;"
             ondragover="event.preventDefault();this.style.background='#f0f7ff'"
             ondragleave="event.preventDefault();this.style.background=''"
             ondrop="event.preventDefault();this.style.background='';handleDrop(event)">

            <div id="drop-initial">
                <div style="width:80px;height:80px;margin:0 auto 20px;border-radius:24px;background:linear-gradient(135deg,var(--primary-light),#dbeafe);display:grid;place-items:center;" id="upload-icon">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </div>
                <div style="font-size:17px;font-weight:600;color:var(--text);margin-bottom:4px;">
                    Tarik file Excel ke sini
                </div>
                <div style="font-size:14px;color:var(--text-secondary);margin-bottom:24px;">
                    atau klik untuk pilih file
                </div>
                <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
                    <span style="padding:4px 12px;background:var(--bg);border-radius:999px;font-size:12px;color:var(--text-secondary);">.xlsx</span>
                    <span style="padding:4px 12px;background:var(--bg);border-radius:999px;font-size:12px;color:var(--text-secondary);">.xls</span>
                    <span style="padding:4px 12px;background:var(--bg);border-radius:999px;font-size:12px;color:var(--text-secondary);">Maks 50 MB</span>
                </div>
            </div>

            <div id="file-preview" style="display:none;">
                <div style="display:inline-flex;align-items:center;gap:14px;background:var(--accent-light);border:1px solid #a7f3d0;border-radius:12px;padding:16px 24px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <div style="text-align:left;">
                        <div id="file-name" style="font-weight:600;font-size:14px;"></div>
                        <div style="font-size:12px;color:var(--text-secondary);" id="file-size"></div>
                    </div>
                    <button type="button" onclick="resetFile()" style="border:0;background:0;cursor:pointer;font-size:20px;color:var(--text-muted);padding:4px;line-height:1;border-radius:50%;">&times;</button>
                </div>
            </div>

            <div id="loading-overlay" style="display:none;position:absolute;inset:0;background:rgba(255,255,255,.95);border-radius:10px 10px 0 0;z-index:2;flex-direction:column;align-items:center;justify-content:center;">
                <svg width="48" height="48" viewBox="0 0 36 36" style="animation:spin 1s linear infinite;margin-bottom:20px;">
                    <circle cx="18" cy="18" r="14" fill="none" stroke="var(--primary)" stroke-width="3" stroke-dasharray="70" stroke-dashoffset="55" stroke-linecap="round"/>
                </svg>
                <div style="font-weight:600;font-size:16px;margin-bottom:6px;">Mengimport data&hellip;</div>
                <div style="font-size:13px;color:var(--text-secondary);">Proses 5&ndash;15 menit. Biarkan halaman tetap terbuka.</div>
            </div>

            <input type="file" id="file-input" name="file" accept=".xlsx,.xls" required style="display:none;" onchange="fileSelected(this)">
        </div>

        <div style="padding:18px 24px;display:flex;align-items:center;gap:16px;border-top:1px solid var(--border);background:var(--bg);border-radius:0 0 10px 10px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <label for="year" style="font-size:13px;font-weight:600;white-space:nowrap;">Tahun</label>
                <input type="number" id="year" name="year" value="{{ old('year', 2026) }}" min="2000" max="2100" required style="width:100px;">
            </div>
            <div style="flex:1;"></div>
            <button class="btn primary" type="submit" id="submit-btn" disabled style="padding:11px 28px;">
                <span id="btn-text">Pilih file terlebih dahulu</span>
            </button>
        </div>
    </form>
</div>

{{-- Export --}}
<div class="card" style="margin-top:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div>
            <div style="font-weight:700;font-size:16px;color:var(--text);">Export Data</div>
            <div style="font-size:13px;color:var(--text-secondary);margin-top:2px;">Download data HSP ke Excel</div>
        </div>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="{{ route('admin.export.menyeluruh') }}" class="btn primary" style="display:inline-flex;align-items:center;gap:8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export Menyeluruh
        </a>
        <div style="font-size:13px;color:var(--text-secondary);align-self:center;">
            Untuk export per analisa, buka halaman detail HSP lalu klik "Export Analisa ke Excel".
        </div>
    </div>
</div>

{{-- Riwayat Import --}}
<div class="card" style="margin-top:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div>
            <div style="font-weight:700;font-size:16px;color:var(--text);">Riwayat Import</div>
            <div style="font-size:13px;color:var(--text-secondary);margin-top:2px;">Daftar proses import yang telah dilakukan</div>
        </div>
        <span style="font-size:12px;font-weight:600;color:var(--text-secondary);background:var(--bg);padding:4px 12px;border-radius:999px;">{{ count($importHistory) }} data</span>
    </div>

    @if (count($importHistory) > 0)
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama File</th>
                    <th>Periode</th>
                    <th>Jumlah Data</th>
                    <th>Status</th>
                    <th>Waktu Import</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($importHistory as $log)
                <tr>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $log['file_name'] }}">{{ $log['file_name'] }}</td>
                    <td>{{ $log['year'] ?? '-' }}</td>
                    <td>{{ number_format($log['total_data'] ?? 0) }}</td>
                    <td>
                        @if ($log['status'] === 'berhasil')
                            <span class="badge success">Berhasil</span>
                        @else
                            <span class="badge danger" title="{{ $log['error_message'] ?? '' }}">Gagal</span>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($log['created_at'])->format('d M Y, H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div style="text-align:center;padding:36px 20px;color:var(--text-muted);">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="display:block;margin:0 auto 10px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        <div style="font-weight:500;margin-bottom:4px;">Belum ada riwayat import</div>
        <div style="font-size:13px;">Data import akan muncul di sini setelah Anda melakukan import file Excel.</div>
    </div>
    @endif
</div>

<style>
#drop-zone.dragover #upload-icon { transform: scale(1.08); }
.table-wrap { overflow-x: auto; border-radius: 6px; border: 1px solid var(--border); }
table { width:100%; border-collapse:collapse; font-size:14px; }
thead th { background:#f9fafb; padding:11px 14px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:var(--text-secondary); border-bottom:2px solid var(--border); text-align:left; white-space:nowrap; }
tbody td { padding:11px 14px; border-bottom:1px solid var(--border); vertical-align:middle; }
tbody tr:hover { background:#f9fafb; }
tbody tr:last-child td { border-bottom:0; }
.badge { display:inline-flex; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:500; }
.badge.success { background:var(--accent-light); color:#065f46; }
.badge.danger { background:var(--danger-light); color:#991b1b; }
</style>

<script>
var fileInput = document.getElementById('file-input');
var dropZone = document.getElementById('drop-zone');
var submitBtn = document.getElementById('submit-btn');
var btnText = document.getElementById('btn-text');
var loadingOverlay = document.getElementById('loading-overlay');

function fileSelected(input) {
    var file = input.files[0];
    if (!file) return resetFile();
    document.getElementById('file-preview').style.display = 'block';
    document.getElementById('file-name').textContent = file.name;
    document.getElementById('file-size').textContent = file.size < 1048576
        ? (file.size / 1024).toFixed(1) + ' KB'
        : (file.size / 1048576).toFixed(1) + ' MB';
    document.getElementById('drop-initial').style.display = 'none';
    submitBtn.disabled = false;
    btnText.textContent = 'Mulai Import';
}

function resetFile() {
    fileInput.value = '';
    document.getElementById('file-preview').style.display = 'none';
    document.getElementById('drop-initial').style.display = '';
    submitBtn.disabled = true;
    btnText.textContent = 'Pilih file terlebih dahulu';
}

function handleDrop(e) {
    var file = e.dataTransfer.files[0];
    if (!file) return;
    fileInput.files = e.dataTransfer.files;
    fileSelected(fileInput);
}

dropZone.addEventListener('dragover', function() { this.classList.add('dragover'); });
dropZone.addEventListener('dragleave', function() { this.classList.remove('dragover'); });
dropZone.addEventListener('click', function() { fileInput.click(); });

document.getElementById('import-form').addEventListener('submit', function() {
    submitBtn.disabled = true;
    btnText.textContent = 'Mengimport...';
    loadingOverlay.style.display = 'flex';
});
</script>
@endsection
