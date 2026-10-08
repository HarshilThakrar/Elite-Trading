@extends('layouts.app')

@section('title', 'Audit Logs - Demo ERP')
@section('header_title', 'System Audit Logs')

@section('content')
<div class="card card-custom mb-4">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="m-0 fw-bold text-primary-custom"><i class="ph ph-file-search me-2"></i> Activity Logs</h5>
        <div>
            <!-- Filters can be added here in the future -->
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4">Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module / Record ID</th>
                        <th class="text-center px-4">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="px-4 text-nowrap">
                            <span class="fw-bold">{{ $log->created_at->format('d M Y') }}</span><br>
                            <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                        </td>
                        <td>
                            @if($log->user)
                                <span class="fw-bold">{{ $log->user->name }}</span><br>
                                <small class="text-muted">{{ $log->user->email }}</small>
                            @else
                                <span class="text-muted font-italic">System / Unknown User</span>
                            @endif
                        </td>
                        <td>
                            @if(strtolower($log->action) === 'created')
                                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1"><i class="bi bi-plus-circle me-1"></i> Created</span>
                            @elseif(strtolower($log->action) === 'updated')
                                <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1"><i class="bi bi-pencil-square me-1"></i> Updated</span>
                            @elseif(strtolower($log->action) === 'deleted')
                                <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1"><i class="bi bi-trash me-1"></i> Deleted</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1">{{ ucfirst($log->action) }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold">{{ class_basename($log->model_type) }}</span> <span class="text-muted">#{{ $log->model_id }}</span>
                        </td>
                        <td class="text-center px-4">
                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#logModal{{ $log->id }}">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </td>
                    </tr>

                    <!-- Modal for Log Details -->
                    <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1" aria-labelledby="logModalLabel{{ $log->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="logModalLabel{{ $log->id }}">Log Details (#{{ $log->id }})</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <p class="mb-1"><span class="fw-bold text-muted">Action:</span> {{ ucfirst($log->action) }}</p>
                                            <p class="mb-1"><span class="fw-bold text-muted">User:</span> {{ $log->user->name ?? 'System' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><span class="fw-bold text-muted">Model:</span> {{ class_basename($log->model_type) }} (ID: {{ $log->model_id }})</p>
                                            <p class="mb-1"><span class="fw-bold text-muted">Timestamp:</span> {{ $log->created_at->format('Y-m-d H:i:s') }}</p>
                                        </div>
                                    </div>

                                    @if(strtolower($log->action) === 'updated' && $log->old_values && $log->new_values)
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card border-danger mb-3">
                                                    <div class="card-header bg-danger text-white py-1">Old Values</div>
                                                    <div class="card-body p-2 bg-light">
                                                        <pre class="mb-0 small" style="white-space: pre-wrap;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card border-success mb-3">
                                                    <div class="card-header bg-success text-white py-1">New Values</div>
                                                    <div class="card-body p-2 bg-light">
                                                        <pre class="mb-0 small" style="white-space: pre-wrap;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        @if($log->new_values)
                                            <div class="card border-secondary mb-3">
                                                <div class="card-header bg-secondary text-white py-1">Data</div>
                                                <div class="card-body p-2 bg-light">
                                                    <pre class="mb-0 small" style="white-space: pre-wrap;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            </div>
                                        @elseif($log->old_values)
                                            <div class="card border-secondary mb-3">
                                                <div class="card-header bg-secondary text-white py-1">Data</div>
                                                <div class="card-body p-2 bg-light">
                                                    <pre class="mb-0 small" style="white-space: pre-wrap;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-muted text-center mb-0 mt-3">No data recorded for this action.</p>
                                        @endif
                                    @endif
                                </div>
                                <div class="modal-footer py-2">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-x fs-1 d-block mb-3 opacity-25"></i>
                            <h5>No Audit Logs Found</h5>
                            <p>System activities will be recorded here.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white border-top p-3 d-flex justify-content-center">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
