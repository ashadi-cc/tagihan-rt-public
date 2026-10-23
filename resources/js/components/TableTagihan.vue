<template>
    <div class="">
        <div class="row">
            <div class="col-md-4">
                <!-- Search form -->
                <div class="form-inline md-form form-sm">
                    <input class="form-control mr-3 w-75" type="text" :placeholder="searchPlaceholder" v-model="query"
                        aria-label="Search">
                    <i class="fa fa-search" aria-hidden="true"></i>
                    <a href="#" title="clear filter" @click.prevent="query = ''">
                        <i class="fa fa-close trash-margin" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            <div class="col-md-8" v-show="records.length > 0">
                <a class="btn btn-success pull-right" :href="downloadUrl">Download</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="no-td">No</th>
                        <th v-for="(item,index) in tableColumns" :key="index">
                            {{ item }}
                        </th>
                        <th>Hapus</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-show="loadingRecord">
                        <td :colspan="loadingColSpan" class="text-center">Memuat data...</td>
                    </tr>
                    <tr v-for="(item,index) in records" :key="item.id" v-show="loadingRecord == false">
                        <td>{{ index+1 }}</td>
                        <td>{{ item.user_blok }}</td>
                        <td>
                            <amount-row :baseUrl="baseUrl" :amountProp="item.amount" :idRecord="item.id" />
                        </td>
                        <td>
                            <status-row :baseUrl="baseUrl" :statusProp="item.status" :idRecord="item.id"></status-row>
                        </td>
                        <td><a href="#" @click.prevent="deleteData(item.id)" class="badge badge-danger">Hapus</a></td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</template>

<script>
import Swal from 'sweetalert2'
import _ from 'lodash'
import AmountRow from './AmountRow'
import StatusRow from './StatusRow'
import Toastr from 'toastr'

Toastr.options.positionClass = "toast-bottom-right"
Toastr.options.closeButton = true
Toastr.options.hideDuration = 500
Toastr.options.showDuration = 300 


export default {
    components: {
        AmountRow,
        StatusRow
    },
    props: {
        searchPlaceholder: {
            type: String, 
            default: 'Cari ...'
        },
        baseUrl: {
            type: String, 
            default: null
        },
        headerTable: {
            type: String, 
            default: ''
        },
        filterOption: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            query: '',
            loadingRecord: false,
            records: []
        };
    },
    computed: {
        tableColumns() {
            return this.headerTable.split(',')
        },
        loadingColSpan() {
            return this.tableColumns.length + 2
        },
        downloadUrl() {
            const p = {
                month: this.filterOption.month,
                year: this.filterOption.year,
                billing_id: this.filterOption.tagihan
            }

            const url = this.baseUrl + '/download?' + jQuery.param(p)

            return url
        }
    },
    mounted() {
        //
    },
    created() {
        this.debounceGetData = _.debounce(this.requestData, 500)
    },
    watch: {
        //watch query change
        query: function(newQuery, oldQuery) {
            this.debounceGetData()
        },
        filterOption:function() {
            this.requestData()
        }
    },
    methods: {
        errorMessage() {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Something went wrong!',
            })
        },
        requestData() {
            if (!this.filterOption.tagihan) {
                return
            }

            var me = this
            var params = {
                params: {
                    q: this.query, 
                    month: this.filterOption.month,
                    year: this.filterOption.year,
                    billing_id: this.filterOption.tagihan
                }
            }

            var url = `${this.baseUrl}/get`
            me.loadingRecord = true
            me.records = []
            axios.get(url, params)
                .then( response => {
                    me.loadingRecord = false
                    me.records = response.data
                })
                .catch(err => {
                    me.loadingRecord = false
                    me.errorMessage()
                })
        },

        deleteData(id) {
            const me = this
            let url = `${this.baseUrl}/${id}`
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (!result.value) {
                        return
                    }
                    axios.delete(url).then(result => {
                        if (result.data.success) {
                            Toastr.success('Data terhapus')
                            me.query = ''
                            me.requestData()
                        } else {
                            me.errorMessage();
                        }
                    }).catch(err => {
                        me.errorMessage()
                    })
            })
        }
    }
}
</script>

<style scoped>
 .no-td{
     width: 30px;
 }
 .trash-margin{margin-left: 5px;}
 .table {margin-top: 30px;}
</style>