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
            <div class="col-md-8" v-show="enableDownload == 'true'">
                <a class="btn btn-success pull-right" :href="baseUrl + '/download'">Download</a>
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
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-show="loadingRecord">
                        <td :colspan="loadingColSpan" class="text-center">Memuat data...</td>
                    </tr>
                    <tr v-for="(item,index) in records" :key="item.id" v-show="loadingRecord == false">
                        <td>{{ index+1 }}</td>
                        <td v-for="value in tableRow(item)" :key="value" v-html="value">
                        </td>
                        <td class="text-center">
                            <a :href="viewUrl(item.id)" class="badge badge-primary" style="padding: 5px 10px;">View</a>
                            <a :href="editUrl(item.id)" class="badge badge-info" style="padding: 5px 10px;">Edit</a>
                            <a href="#" @click.prevent="deleteData(item.id)" class="badge badge-danger" style="padding: 5px 10px;">Hapus</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</template>

<script>
import Swal from 'sweetalert2'
import _ from 'lodash'
import Toastr from 'toastr'

Toastr.options.positionClass = "toast-bottom-right"
Toastr.options.closeButton = true
Toastr.options.hideDuration = 500
Toastr.options.showDuration = 300 

export default {
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
        headerData: {
            type: String,
            default: ''
        },
        enableDownload: {
            type: String, 
            default: "false"
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
        recordItems() {
            return this.headerData.split(',')
        },
        loadingColSpan() {
            return this.tableColumns.length + 3
        }
    },
    mounted() {
        this.requestData()
    },
    created() {
        this.debounceGetData = _.debounce(this.requestData, 500)
    },
    watch: {
        //watch query change
        query: function(newQuery, oldQuery) {
            this.debounceGetData()
        }
    },
    methods: {
        tableRow(row) {
            let records  = {}
            this.recordItems.forEach(element => {
                if (element != 'id') {
                    records[element] = row[element]
                }
            })

            return records
        },

        errorMessage(text) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: text,
            })
        },
        requestData() {
            var me = this
            var query = encodeURI(this.query)
            var url = `${this.baseUrl}/get?q=${query}`
            me.loadingRecord = true
            axios.get(url)
                .then( response => {
                    me.loadingRecord = false
                    me.records = response.data
                })
                .catch(err => {
                    me.loadingRecord = false
                    me.errorMessage('Server error')
                })
        },

        editUrl(id) {
            return `${this.baseUrl}/edit/${id}`
        },
        viewUrl(id) {
            return `${this.baseUrl}/view/${id}`
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
                            Toastr.success("deleted")
                            me.query = ''
                            me.requestData()
                        } else {
                            me.errorMessage('Tidak bisa menghapus data ini. data dipakai di table yang lain');
                        }
                    }).catch(err => {
                        me.errorMessage('Server error')
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