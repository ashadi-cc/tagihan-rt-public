<template>
    <div class="text-edit">
        <span v-show="editMode == false">
            <a href="#" class="amount-link" @click.prevent="startEdit()">{{ amountText }}</a>
        </span>
        <div class="input-group" v-show="editMode" >
            <input type="number" class="form-control" v-model="amountValue" @keydown.esc="editMode = false" ref="amount" @keydown.enter="saveEdit()">
            <div class="input-group-append">
                <span class="input-group-text link-btn" @click="saveEdit()">
                    <i class="fa fa-check"></i>
                </span>
                <span class="input-group-text link-btn" @click="editMode = false">
                    <i class="fa fa-close"></i>
                </span>
            </div>
        </div>
    </div>
</template>
<script>
import Swal from 'sweetalert2'
import Toastr from 'toastr'

Toastr.options.positionClass = "toast-bottom-right"
Toastr.options.closeButton = true
Toastr.options.hideDuration = 500
Toastr.options.showDuration = 300 

export default {
    props: {
        baseUrl: {
            type: String,
            default: ''
        },
        amountProp: {
            type: Number,
            default: 0
        },
        idRecord: {
            type: Number,
            default: 0
        }
    },
    data() {
        return {
            editMode: false,
            amount: 0,
            amountValue: 0,
        }
    },
    mounted() {
        this.amount = this.amountProp
    },
    methods: {
        errorMessage() {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Something went wrong!',
            })
        },
        startEdit() {
            this.amountValue = this.amount
            this.editMode = true
            let me = this
            setTimeout(function(){
               me.$refs.amount.focus()
            }, 30)
        },
        saveEdit(){
            let url = `${this.baseUrl}/${this.idRecord}`
            let me = this 
            axios.put(url, {amount: this.amountValue}).then( result => {
                if (result.data.success) {
                    me.amount = me.amountValue
                    Toastr.success('Updated')
                } else {
                    me.errorMessage()
                }
                me.editMode = false
            }).catch( e => {
                me.editMode = false
                me.errorMessage()
            })
        }
    },
    computed: {
        amountText() {
            return Numeral(this.amount).format('0,0')
        }
    }
}
</script>

<style scoped>
    .link-btn{cursor: pointer;}
    .amount-link{ text-decoration: underline;}
    .text-edit{width: 200px}
</style>