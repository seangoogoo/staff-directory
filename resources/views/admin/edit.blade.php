@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Edit Staff Member</h1>
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
            <form action="{{ route('admin.update', $staff->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('firstName') is-invalid @enderror" id="firstName" name="firstName" value="{{ old('firstName', $staff->firstName) }}" required>
                            @error('firstName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('lastName') is-invalid @enderror" id="lastName" name="lastName" value="{{ old('lastName', $staff->lastName) }}" required>
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
                            <input type="text" class="form-control @error('department') is-invalid @enderror" id="department" name="department" value="{{ old('department', $staff->department) }}" required>
                            @error('department')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="jobTitle" class="form-label">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('jobTitle') is-invalid @enderror" id="jobTitle" name="jobTitle" value="{{ old('jobTitle', $staff->jobTitle) }}" required>
                            @error('jobTitle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="profilePicture" class="form-label">Profile Picture</label>
                    
                    @if($staff->profilePicture)
                        <div class="mb-2">
                            <img src="{{ asset($staff->profilePicture) }}" alt="{{ $staff->firstName }}" class="img-thumbnail" style="max-width: 150px">
                            <p class="form-text text-muted">Current profile picture</p>
                            
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="removeProfilePicture" name="removeProfilePicture" value="1">
                                <label class="form-check-label" for="removeProfilePicture">
                                    Remove profile picture
                                </label>
                                <div class="form-text text-muted">Check this box to remove the current profile picture</div>
                            </div>
                        </div>
                    @endif
                    
                    <input type="file" class="form-control @error('profilePicture') is-invalid @enderror" id="profilePicture" name="profilePicture" accept="image/*">
                    <div class="form-text">Upload a new profile picture (JPEG, PNG, GIF). Max size: 2MB. Leave empty to keep the current picture.</div>
                    @error('profilePicture')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 text-end">
                    <button type="submit" class="btn btn-primary">Update Staff Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
