@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12 mb-4">
            <h1 class="text-center">Staff Directory</h1>
        </div>
    </div>
    
    <div class="row row-cols-1 row-cols-md-3 g-4">
        @foreach($staff as $member)
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="text-center pt-3">
                        @if($member->profilePicture)
                            <img src="{{ asset($member->profilePicture) }}" alt="{{ $member->firstName }} {{ $member->lastName }}" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                        @else
                            <img src="https://placehold.co/150x150?text={{ substr($member->firstName, 0, 1) . substr($member->lastName, 0, 1) }}" alt="{{ $member->firstName }} {{ $member->lastName }}" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                        @endif
                    </div>
                    <div class="card-body text-center">
                        <h5 class="card-title">{{ $member->firstName }} {{ $member->lastName }}</h5>
                        <p class="card-text text-muted mb-1">{{ $member->jobTitle }}</p>
                        <p class="card-text"><span class="badge bg-secondary">{{ $member->department }}</span></p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    @if(count($staff) == 0)
        <div class="row">
            <div class="col-md-12 text-center">
                <p>No staff members found.</p>
            </div>
        </div>
    @endif
</div>
@endsection
