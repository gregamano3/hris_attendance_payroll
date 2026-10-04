@php use App\Features\Employees\Enums\DocumentCategory; @endphp
<div class="card">
    <div class="card-header"><h3 class="card-title">201 file</h3></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
            <tbody>
                @forelse ($employee->documents as $document)
                    <tr>
                        <td><i class="bi bi-file-earmark-text me-1"></i><a href="{{ route('documents.download', $document) }}">{{ $document->title }}</a></td>
                        <td class="small text-body-secondary">{{ $document->category->label() }}</td>
                        <td class="small text-body-secondary">{{ $document->humanSize() }} · {{ $document->created_at->format('M j, Y') }}</td>
                        <td class="actions">
                            @if ($canManage ?? false)
                                <x-delete-button :action="route('documents.destroy', $document)" confirm="Delete this document?" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="text-center text-body-secondary py-3">No documents.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($canManage ?? false)
        <form method="post" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data" class="card-footer row g-2 align-items-end">
            @csrf
            <x-form.select name="category" label="Category" :options="DocumentCategory::options()" col="col-md-3" required />
            <x-form.input name="title" label="Title" col="col-md-4" required />
            <div class="col-md-3">
                <label for="file" class="form-label">File</label>
                <input type="file" id="file" name="file" class="form-control @error('file') is-invalid @enderror" required>
                @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Upload</button></div>
        </form>
    @endif
</div>
