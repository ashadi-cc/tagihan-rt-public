<template>
    <form :action="baseUrl" id="form-tagihan-upload"  method="POST" enctype="multipart/form-data"  @submit.prevent="submitForm">
        <input type="hidden" name="_token" :value="formToken">
        <div class="form-row">
            <div class="form-group col-md-1">
                <label for="">Tahun</label>
                <select name="tahun"  class="form-control" v-model="year">
                    <option v-for="item in formOption.years" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label for="">Bulan</label>
                <select name="bulan"  class="form-control" v-model="month">
                    <option v-for="item in formOption.months" :key="item.value" :value="item.value">
                        {{ item.text }}
                    </option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="">Jenis Iuran</label>
                <select name="jenis"  class="form-control" v-model="tagihan" required>
                    <option value=""></option>
                    <option v-for="tagihan in listTagihan" :key="tagihan.id" :value="tagihan.id">
                        {{ tagihan.name }}
                    </option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="">Upload tagihan {{ namaTagihan }} (.xlsx)</label>
                <input type="file" class="form-control-file"  name="xls_file" required :value="file">
                <span class="small">
                    <a href="#" @click.prevent="downloadTemplate()">Download contoh template iuran {{ namaTagihan }}</a>
                </span>
            </div>
        </div>
        <div class="form-group">
            <div v-show="isProcess">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                Upload file, Mohon menunggu...
            </div>
            <input type="submit" class="btn btn-success" value="Mulai Upload" v-show="isProcess == false">
        </div>
    </form>
</template>

<script>
import Swal from 'sweetalert2'

export default {
    props: {
        formToken: {
            type: String, 
            default: ''
        },
        formFilter: {
            type: String, 
            default: '{}'
        },
        billings: {
            type: String,
            default: '[]',
        },
        baseUrl: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            year: 0, 
            month: 0,
            tagihan: 0,
            file: '',
            isProcess: false,
            namaTagihan: '',
            namaBulan: '',
            billingJson: null,
            formFilterJson: null
        } 
    },
    watch: {
        year: function() {
            this.changeFilter()
        },
        month: function() {
            this.changeFilter()
        },
        tagihan: function() {
            this.changeFilter()
        }
    },
    computed: {
        listTagihan() {
            if (this.billingJson == null) {
                this.billingJson = JSON.parse(this.billings)
            }

            return this.billingJson
        },
        formOption(){
            if (this.formFilterJson == null) {
                this.formFilterJson = JSON.parse(this.formFilter)
            }

            return this.formFilterJson
        }
    },
    mounted() {
        this.year = this.formOption.year 
        this.month = this.formOption.month
    },
    methods: {
        setNamaTagihan() {
            let namaTagihan = '' 
            let tagihan = this.tagihan
            this.listTagihan.forEach(element => {
                if (element.id == tagihan) {
                    namaTagihan = element.name
                }
            });

            this.namaTagihan = namaTagihan
        },
        setNamaBulan() {
            this.namaBulan = '' 
            let bulan = this.month
            this.formOption.months.forEach(element => {
                if (element.value == bulan) {
                    this.namaBulan = element.text
                }
            })
        },
        changeFilter() {
            this.file = ''
            this.setNamaTagihan()
            this.setNamaBulan()

            let data = {
                year: this.year, 
                month: this.month, 
                tagihan: this.tagihan,
                namaBulan: this.namaBulan,
                namaTagihan: this.namaTagihan,
            }
            this.$emit('changeFilter', data)
        },
        downloadTemplate() {
            if (!this.tagihan) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops',
                    text: 'Silahkan pilih jenis tagihan'
                })
                return
            }

            let url = `${this.baseUrl}/template/${this.tagihan}?year=${this.year}&month=${this.namaBulan}`
            document.location.href = url
        },
        submitForm() {
            let frm = $('#form-tagihan-upload')
            let formData = new FormData(frm[0])
            var me = this
            this.isProcess = true
            axios.post(this.baseUrl,
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }
            ).then(result => {
                this.isProcess = false
                me.changeFilter()
                if (result.data.success) {
                    Swal.fire({
                        icon: 'success',
                        titleText: `Iuran ${me.namaTagihan} untuk ${this.namaBulan} ${this.year} berhasil di upload`,
                        html: `data baru/update: ${result.data.imported} <br> data fail: ${result.data.fail}`
                    })
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops',
                        text: result.data.message
                    })
                }

            }).catch( e => {
                this.isProcess = false
                Swal.fire({
                    icon: 'error',
                    title: 'Oops',
                    text: 'Format file salah'
                })
            })

        }
    }
}
</script>