@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Admin Dashboard</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.create') }}" class="btn btn-primary">Add New Staff Member</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Staff Members</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Profile Picture</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Job Title</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staff as $member)
                            <tr>
                                <td>
                                    @if($member->profilePicture)
                                        <img src="{{ asset($member->profilePicture) }}" alt="{{ $member->firstName }}" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                    @else
                                        <img src="https://placehold.co/50x50?text={{ substr($member->firstName, 0, 1) . substr($member->lastName, 0, 1) }}" alt="{{ $member->firstName }} {{ $member->lastName }}" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                    @endif
                                </td>
                                <td>{{ $member->firstName }} {{ $member->lastName }}</td>
                                <td>{{ $member->department }}</td>
                                <td>{{ $member->jobTitle }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.edit', $member->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form action="{{ route('admin.destroy', $member->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this staff member?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No staff members found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
