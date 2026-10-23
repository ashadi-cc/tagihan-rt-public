@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Upload Daftar Iuran</h1>
    @include('admin.master._header_billing', ['billing_list' => '', 'billing_upload' => 'active'])
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ url('/admin/master/tagihan/upload') }}" method="POST" enctype="multipart/form-data">
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
                        {{ session()->get('success') }} <a href="{{ url('/admin/master/tagihan') }}">Lihat daftar Iuran</a>
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
            <h4 class="small"> <a href="{{ asset('/xls-template/template_daftar_iuran.xlsx') }}">Download contoh template</a></h4>
        </div>
    </div>
@endsection
