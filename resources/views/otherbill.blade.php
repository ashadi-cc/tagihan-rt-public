@if(count($data))
<div class="card-header bg-success text-white"><i class="fa fa-table mr-1"></i>
    Tunggakan tagihan
</div>
<div class="card-body">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Iuran</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $billName => $item)
                <tr>
                    <td>{{ $billName }}</td>
                    <td>
                        <span class="badge badge-danger">
                            {{ number_format($item['amount'], 2) }}
                        </span>
                    </td>
                </tr>
                @endforeach
                <tfoot>
                    <tr>
                        <th class="belum-lunas">Total Tagihan</th>
                        <th><span class="belum-lunas">{{ $total }}</span></th>
                    </tr>
                </tfoot>
            </tbody>
        </table>
    </div>
</div>
@endif