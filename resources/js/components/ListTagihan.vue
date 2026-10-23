<template>
    <div>
        <form-filter :formFilter="options" :baseUrl="baseUrl"  @changeFilter="changeFilterOption($event)" v-show="!editMode"/>
        <iuran-table :baseUrl="baseUrl" searchPlaceholder="cari blok..." :filterOption="filterOptions" @getDetail="editMode = $event" />
    </div>
</template>
<script>
import FormFilter from './FormFilter'
import IuranTable from './IuranTable'

export default {
    components:{
        FormFilter,
        IuranTable
    },
    props:['baseUrl', 'options'],
    data() {
        return {
            filterOptions: {},
            editMode: false,
        }
    },
    methods: {
        changeFilterOption(data) {
            this.filterOptions = data
        }
    }
}
</script>