@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Upload Iuran</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
        <li class="breadcrumb-item active">Upload Iuran</li>
    </ol>
    <div class="card mb-4">
        <div class="card-body">
            @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif

            <div id="app">
                <upload-tagihan 
                billings="{{ json_encode($billings) }}"
                options="{{ json_encode($options) }}"
                base-url="{{ url('/admin/tagihan/upload') }}" 
                token="{{ csrf_token() }}"/>
            </div>
        </div>
    </div>
@endsection 


@section('scripts')
    <script src="{{ mix('js/manifest.js') }}"></script>
    <script src="{{ mix('js/vendor.js') }}"></script>
    <script src="{{ mix('js/master_tagihan.js') }}"></script>
@endsection