<template>
    <div>
        <div v-show="loadingUser">
            <div class="d-flex justify-content-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div> 
        </div>
        <div v-show="!loadingUser">
            <h3>Tagihan Per Warga</h3>
            <div class="form-group">
                <label for="">Blok</label>
                <input type="text" class="form-control" :value="this.user.blok" disabled>
            </div>
            <div class="form-group">
                <label for="">Nama</label>
                <input type="text" class="form-control" :value="this.user.name" disabled>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6 table-responsive">
                    <h5>Tagihan Bulan Sekarang ({{ this.currentMonth }}) </h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Iuran</th>
                                <th>Nominal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in thisMonth" :key="item.id">
                                <td>{{ item.billing_name }}</td>
                                <td><span :class="formatClassAmount(item)">{{ formatAmount(item.amount) }}</span></td>
                                <td>
                                    <status-row 
                                    :idRecord="item.id"
                                    :baseUrl="baseUrl"
                                    :statusProp="item.status"
                                    @changeStatus="statusChangeThisMonth(index, item, $event)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">Lunas</th>
                                <th class="lunas">{{ lunasThisMonth }}</th>
                            </tr>
                            <tr>
                                <th colspan="2">Belum Lunas</th>
                                <th class="belum-lunas">{{ belumThisMonth }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="col-md-6 table-responsive">
                    <h5>Tunggakan tagihan</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Iuran</th>
                                <th>Bulan/Tahun</th>
                                <th>Nominal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in otherMonth" :key="item.id">
                                <td>{{ item.billing_name }}</td>
                                <td>{{ item.month_name }} / {{ item.year }}</td>
                               <td><span :class="formatClassAmount(item)">{{ formatAmount(item.amount) }}</span></td>
                                <td>
                                    <status-row 
                                    :idRecord="item.id"
                                    :baseUrl="baseUrl"
                                    :statusProp="item.status"
                                    @changeStatus="statusChangeOtherMonth(index, item, $event)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">Lunas</th>
                                <th class="lunas" colspan="2">{{ lunasOtherMonth }}</th>
                            </tr>
                            <tr>
                                <th colspan="2">Belum Lunas</th>
                                <th class="belum-lunas" colspan="2">{{ belumOtherMonth }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Swal from 'sweetalert2'
import StatusRow from './StatusRow'


export default {
    components:{
        StatusRow
    },
    props: [
        'baseUrl', 
        'blok',
        'year', 
        'month',
        'shouldChange'
    ],
    data() {
        return {
            user: {
                blok: '',
                name: ''
            },
            otherMonth: [],
            thisMonth: [],
            currentMonth: '',
            loadingUser: false
        }
    },
    watch: {
        shouldChange: function(){
            this.requestData()
        }
    },
    computed: {
        lunasThisMonth() {
            let total = 0
            this.thisMonth.forEach(el => {
                if (el.status == 'L') total = total + el.amount
            })

            return Numeral(total).format('0,0')
        },
        belumThisMonth() {
            let total = 0
            this.thisMonth.forEach(el => {
                if (el.status == 'B') total = total + el.amount
            })
            return Numeral(total).format('0,0')
        },
        lunasOtherMonth() {
            let total = 0
            this.otherMonth.forEach(el => {
                if (el.status == 'L') total = total + el.amount
            })

            return Numeral(total).format('0,0')
        },
        belumOtherMonth() {
            let total = 0
            this.otherMonth.forEach(el => {
                if (el.status == 'B') total = total + el.amount
            })

            return Numeral(total).format('0,0')
        }
    },
    methods: {
        statusChangeThisMonth(index, item, status) {
            item.status = status
            this.thisMonth.splice(index, 1, item)
        },
        statusChangeOtherMonth(index, item, status) {
            item.status = status
            this.otherMonth.splice(index, 1, item)
        },
        formatClassAmount(item) {
            let cls = {
                'badge': true
            }
            if (item.status == 'L') cls['badge-success'] = true
            if (item.status == 'B') cls['badge-danger'] = true
            if (item.status == 'T') cls['badge-warning'] = true
            if (item.status == '') cls['badge-secondary'] = true

            return cls 
        },
        formatAmount(amount){
            return Numeral(amount).format('0,0')
        },
        errorMessage() {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Something went wrong!',
            })
        },
        requestData() {
            let params = {
                params: {
                    blok: this.blok
                }
            }
            let me = this
            let url = `${this.baseUrl}/user`
            this.thisMonth = []
            this.otherMonth = []
            this.loadingUser = true
            axios.get(url, params).then(result => {
                me.user = result.data.user 
                me.thisMonth = result.data.thisMonth
                me.otherMonth = result.data.otherMonth
                me.currentMonth = result.data.currentMonth
                me.loadingUser = false
            }).catch(e => {
                me.errorMessage()
                me.loadingUser = false
            })
        }
    }
}
</script>