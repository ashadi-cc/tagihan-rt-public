<template>
    <div>
        <form-tagihan :formToken="token" :formFilter="options" :billings="billings" :baseUrl="baseUrl" @changeFilter="changeFilterOption($event)" />
        <div>
            <h4>{{ headerTable }}</h4>
            <hr>
            <table-tagihan 
                :baseUrl="baseUrl"
                headerTable="Blok,Nominal,Status"
                searchPlaceholder="cari blok rumah..."
                :filterOption="formOption"
            />
        </div>
    </div>
</template>

<script>
import FormTagihan from './FormTagihan'
import TableTagihan from './TableTagihan'

export default {
    props: [
        'token',
        'billings',
        'options',
        'baseUrl',
    ],
    data() {
        return {
            headerTable: 'Data Iuran',
            formOption: {}
        }
    },
    components: {
        FormTagihan,
        TableTagihan
    },
    methods: {
        changeFilterOption(data)
        {
            if (!data.tagihan) {
                return;
            }

            this.headerTable = `Data Iuran ${data.namaTagihan} ${data.namaBulan} ${data.year}`
            this.formOption = data
        }
    }
}
</script>