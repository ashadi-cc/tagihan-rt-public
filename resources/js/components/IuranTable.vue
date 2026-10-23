<template>
    <div>
        <div v-show="!editMode">
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
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="head-keterangan">
                        Keterangan
                    </div>
                    <div class="btn btn-danger btn-sm">Belum Lunas</div>
                    <div class="btn btn-success btn-sm">Lunas</div>
                    <div class="btn btn-warning btn-sm">Tidak Wajib</div>
                    <div class="btn btn-secondary btn-sm">Status belum ada</div>
                    <div class="btn btn-secondary btn-sm">N/A (Kosong)</div>
                </div>
            </div>
            <div class="text-center empty-data" v-show="emptyData && !loadingRecord">
                Data Iuran tidak ada
            </div>
            <div v-show="loadingRecord" class="loading-data">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="table-responsive" v-show="emptyData == false">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="no-td">No</th>
                            <th v-for="(item,index) in tableColumns" :key="index">
                                {{ item }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-show="loadingRecord == false">
                            <td colspan="2"><strong>Summary</strong></td>
                            <td v-for="item in summary" :key="item.billing_id" class="summary">
                                <span class="lunas">{{ formatAmount(item.lunas) }}</span> / <span class="belum-lunas">{{ formatAmount(item.belum) }}</span>
                            </td>
                        </tr>
                        <tr v-for="(item,index) in records" :key="item.id" v-show="loadingRecord == false">
                            <td>{{ index+1 }}</td>
                            <td>
                                <a href="#" @click.prevent="getDetail(item.blok)">
                                    {{ item.blok }}
                                </a>
                            </td>
                            <td v-for="(value, idx) in tableRow(item)" :key="idx">
                                <div v-if="value === false">
                                    <div class="btn btn-sm btn-secondary">
                                        N/A
                                    </div>
                                </div>
                                <status-amount-row :item="value" :baseUrl="baseUrl" v-else @changeStatus="requestSummary()">
                                </status-amount-row>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
        <div v-show="editMode">
            <detail-user 
                :baseUrl="baseUrl"
                :blok="blok"
                :year="filterOption.year"
                :month="filterOption.month"
                :shouldChange="shouldChange"
            />
            <hr>
            <a href="#" class="btn btn-danger" @click.prevent="backToBilling()">Kembali ke daftar Iuran</a>
        </div>
    </div>
</template>

<script>
import Swal from 'sweetalert2'
import _ from 'lodash'
import Toastr from 'toastr'
import StatusAmountRow from './StatusAmountRow'
import DetailUser from './DetailUser'

Toastr.options.positionClass = "toast-bottom-right"
Toastr.options.closeButton = true
Toastr.options.hideDuration = 500
Toastr.options.showDuration = 300 

export default {
    components: {
        StatusAmountRow,
        DetailUser
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
        filterOption: {
            type: Object,
            default: {}
        }
    },
    data() {
        return {
            query: '',
            headerTable: '',
            headerData: '',
            loadingRecord: false,
            records: [],
            summary: [],
            blok: '',
            editMode: false,
            shouldChange: true,
        };
    },
    computed: {
        tableColumns() {
            return this.headerTable.split(',')
        },
        recordItems() {
            return this.headerData.split(',')
        },
        loadingColSpan() {
            return this.tableColumns.length + 1
        },
        emptyData() {
            return this.records.length ? false: true 
        }
    },
    mounted() {

    },
    created() {
        this.debounceGetData = _.debounce(this.requestData, 500)
    },
    watch: {
        query: function(newQuery, oldQuery) {
            this.debounceGetData()
        },
        filterOption: function(){
            this.requestData()
        },
        editMode: function(newVal) {
              this.$emit('getDetail', newVal)
        }
    },
    methods: {
        formatAmount(amount) {
            return Numeral(amount).format('0,0')
        },
        tableRow(row) {
            let records  = {}
            this.recordItems.forEach(element => {
                if (element != 'id' && element != 'blok') {
                    records[element] = row[element]
                }
            })

            return records
        },

        errorMessage() {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Something went wrong!',
            })
        },
        requestSummary() {
            let url = `${this.baseUrl}/summary`
            let params = {
                params: {
                    q: this.query, 
                    summary: this.headerData, 
                    year: this.filterOption.year,
                    month: this.filterOption.month
                }
            }
            this.summary = []
            let me = this
            axios.get(url, params).then(result => {
                me.summary = result.data
            }).catch(e => {
                me.errorMessage()
            })
        },
        getDetail(blok) {
            this.editMode = true
            this.blok = blok
            this.shouldChange = !this.shouldChange
        },
        backToBilling(){
            this.editMode = false 
            this.requestData()
        },
        requestData() {
            let me = this
            let url = `${this.baseUrl}/get`
            let params = {
                params: {
                    q: this.query, 
                    year: this.filterOption.year,
                    month: this.filterOption.month
                }
            }
            me.loadingRecord = true
            me.records = []
            axios.get(url, params)
                .then( response => {
                    me.loadingRecord = false

                    me.headerTable = response.data.headers 
                    me.headerData = response.data.columns
                    me.records = response.data.data

                    me.requestSummary()
                })
                .catch(err => {
                    me.loadingRecord = false
                    me.errorMessage()
                })
        }
    }
}
</script>

<style scoped>
 .no-td{
     width: 30px;
 }
 .head-keterangan {
     padding: 10px;
     padding-left: 0px;
     font-weight: bold;
 }
 .trash-margin{margin-left: 5px;}
 .table {margin-top: 30px;}
 .empty-data{
     padding: 30px;
     margin-top: 30px;
     border: 1px solid #ced4da;
     color: #495057;
 }
 .loading-data {
     padding: 10px;
 }
 .summary {
     font-size: 12px;
     font-weight: bold;
 }
 .lunas {
     color: green;
 }
 .belum-lunas {
     color: red;
 }
</style>