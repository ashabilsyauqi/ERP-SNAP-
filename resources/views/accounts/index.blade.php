@extends('layouts.app')

@section('title', 'Chart of Accounts')
@section('page-title', 'Accounting / Chart of Accounts (Bagan Akun COA)')

@section('action-buttons')
<a href="{{ route('accounts.create') }}" class="btn-odoo-primary text-decoration-none">
    <i class="fa-solid fa-plus"></i>
    <span>New Account</span>
</a>
@endsection

@section('content')
<div id="main-view-wrapper" data-view-wrapper>
    <!-- Main Odoo Sheet -->
    <div class="o_form_sheet p-0 overflow-hidden">
        <div class="table-view-container">
            <div class="table-responsive">
                <table class="table table-hover o_list_table mb-0" id="main-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-3 text-center no-sort">
                                <input type="checkbox" class="form-check-input">
                            </th>
                            <th class="sortable" style="width: 140px;">Account Code</th>
                            <th class="sortable">Account Name (Nama Perkiraan)</th>
                            <th class="sortable">Account Type</th>
                            <th class="sortable text-center">Status</th>
                            <th class="text-end no-sort pe-4" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                            <tr class="search-row">
                                <td class="ps-3 text-center">
                                    <input type="checkbox" class="form-check-input">
                                </td>
                                <td>
                                    <span class="font-mono fw-bold text-indigo-700">{{ $account->kode_akun }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ $account->nama_akun }}</div>
                                    @if($account->parent)
                                        <div class="text-[10px] text-slate-400">Sub-account dari: {{ $account->parent->nama_akun }} ({{ $account->parent->kode_akun }})</div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $typeColor = match($account->tipe) {
                                            'aset' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'kewajiban' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'modal' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'pendapatan' => 'bg-teal-50 text-teal-700 border-teal-200',
                                            'beban' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200'
                                        };
                                    @endphp
                                    <span class="badge {{ $typeColor }} border text-[11px] font-semibold text-uppercase">
                                        {{ $account->tipe }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('accounts.toggle-status', $account->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm py-0 px-2 text-xs border-0 bg-transparent" title="Toggle Status">
                                            @if($account->is_active)
                                                <span class="badge bg-emerald-100 text-emerald-800 text-[10px] font-bold"><i class="fa-solid fa-circle text-[8px] me-1"></i> Aktif</span>
                                            @else
                                                <span class="badge bg-slate-200 text-slate-600 text-[10px] font-bold"><i class="fa-solid fa-circle text-[8px] me-1"></i> Nonaktif</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('accounts.edit', $account->id) }}" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Edit Akun">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </a>
                                        <form action="{{ route('accounts.destroy', $account->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus akun ini? Pastikan tidak ada mutasi kas terkait.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus Akun">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="p-4">
                                        <i class="fa-solid fa-folder-tree fs-1 text-slate-300 mb-2"></i>
                                        <p class="mb-0">Belum ada bagan akun (COA).</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
