@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Add New Staff Member</h1>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Staff Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('firstName') is-invalid @enderror" id="firstName" name="firstName" value="{{ old('firstName') }}" required>
                            @error('firstName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('lastName') is-invalid @enderror" id="lastName" name="lastName" value="{{ old('lastName') }}" required>
                            @error('lastName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="department" class="form-label">Department / Service <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('department') is-invalid @enderror" id="department" name="department" value="{{ old('department') }}" required>
                            @error('department')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="jobTitle" class="form-label">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('jobTitle') is-invalid @enderror" id="jobTitle" name="jobTitle" value="{{ old('jobTitle') }}" required>
                            @error('jobTitle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="profilePicture" class="form-label">Profile Picture</label>
                    <input type="file" class="form-control @error('profilePicture') is-invalid @enderror" id="profilePicture" name="profilePicture" accept="image/*">
                    <div class="form-text">Upload a profile picture (JPEG, PNG, GIF). Max size: 2MB</div>
                    @error('profilePicture')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 text-end">
                    <button type="submit" class="btn btn-primary">Add Staff Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
