@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Daftar Warga</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/master/warga') }}">Daftar Warga</a></li>
            <li class="breadcrumb-item active" aria-current="page">Upload Warga</li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ url('/admin/master/warga/upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div>File harus berupa Microsoft Excel (.xlsx)</div>
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <input id="" name="xls_file" type="file" class="file" data-show-preview="false" required>
                        </div>
                        @if ($errors->all())
                            <div class="alert alert-danger" role="alert">
                                @foreach ($errors->all() as $error)
                                    {{ $error }} <br/>
                                @endforeach
                            </div>
                        @enderror
                    </div>
                </div>

                <div class="form-group mt-4 mb-0">
                    <input type="submit" class="btn btn-primary" value="Mulai Upload">
                </div>

                @if(session()->has('success'))
                <hr>
                <div class="form-group">
                    <div class="alert alert-success">
                        {{ session()->get('success') }} <a href="{{ url('/admin/master/warga') }}">Lihat daftar warga</a>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="alert alert-info">
                        {{ session('imported')}} record(s) succeed imported
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="alert alert-danger">
                        {{ session('fail')}} record(s) failed imported
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                @endif

            </form>
            <hr>
            <h4 class="small"> <a href="{{ asset('/xls-template/template_daftar_warga.xlsx') }}">Download contoh template</a></h4>
        </div>
    </div>
@endsection
