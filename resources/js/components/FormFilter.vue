<template>
    <div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-info">{{ year }}</button>
                <button type="button"  class="btn btn-info dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" @click.prevent="year = item" v-for="item in formOption.years" :key="item">{{ item }}</a>
                </div>
                </div>
                <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-danger">{{ monthName }}</button>
                <button type="button"  class="btn btn-danger dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" @click.prevent="month = item.value" v-for="item in formOption.months" :key="item.value">{{ item.text }}</a>
                </div>
                </div>
                <button class="btn btn-success btn-sm ml-10" @click="download()">Download</button>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    props: {
        formFilter: {
            type: String, 
            default: '{}'
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
            formFilterJson: null
        } 
    },
    computed: {
        formOption(){
            if (this.formFilterJson == null) {
                this.formFilterJson = JSON.parse(this.formFilter)
            }

            return this.formFilterJson
        },
        monthName(){
            let month = this.month 
            let name = ''
            this.formOption.months.forEach(element => {
                if (element.value == month) {
                    name = element.text
                }
            })

            return name
        },
        yearAndMonth() {
            return `${this.year}|${this.month}`
        }
    },
    mounted() {
        this.year = this.formOption.year 
        this.month = this.formOption.month
    },
    watch: {
        yearAndMonth: function(newVal, oldVal) {
            this.changeFilter()
        }
    },
    methods: {
        changeFilter() {
            let data = {
                year: this.year, 
                month: this.month
            }
            this.$emit('changeFilter', data)
        },
        download() {
            const url = `${this.baseUrl}/download?year=${this.year}&month=${this.month}`
            document.location.href = url
        }
    }
}
</script>