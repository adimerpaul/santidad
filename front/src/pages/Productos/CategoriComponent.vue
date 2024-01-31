<template>
  <q-card style="width: 500px; max-width: 80vw;">
    <q-card-section class="row items-center q-pb-none">
      <div class="text-subtitle2 text-bold text-grey">
        Editar categoria
      </div>
      <q-space/>
      <q-btn icon="o_highlight_off" flat round dense v-close-popup />
    </q-card-section>
    <q-card-section>
      <q-table flat bordered :rows="categories" hide-header
               :columns="categoriesTableColumns" :rows-per-page-options="[0]" >
        <template v-slot:body="props">
          <q-tr :props="props">
            <q-td :props="props" key="name">
              {{props.row.name}}
            </q-td>
            <q-td :props="props" key="actions">
              <q-btn flat dense round color="grey" icon="o_edit" class="cursor-pointer" @click="categoryEdit(props.row)"/>
              <q-btn flat dense round color="red" icon="o_delete" class="cursor-pointer" @click="categoryDelete(props.row)"/>
            </q-td>
          </q-tr>
        </template>
      </q-table>
    </q-card-section>
    <q-card-section class="row items-center q-pb-none">
      <div class="text-subtitle2 text-bold text-grey">
        Editar Sub categoria
      </div>
      <q-space/>
    </q-card-section>
  </q-card>
</template>
<script>
export default {
  name: 'CategoriComponent',
  props: {
    categories: {
      type: Array,
      required: true
    }
  },
  data () {
    return {
      categoriesTableColumns: [
        { name: 'name', label: 'Nombre', align: 'left', field: 'name', sortable: true },
        { name: 'actions', label: 'Acciones', align: 'left', field: 'actions' }
      ],
      categoriesTable: []
    }
  },
  methods: {
    categoryEdit (category) {
      this.categorySelected = category
      this.categoryDialog = false
      this.$q.dialog({
        title: 'Editar categoria',
        message: 'Ingresa el nuevo nombre de la categoria',
        prompt: {
          model: category.name,
          type: 'text'
        },
        cancel: true,
        persistent: true
      }).onOk(data => {
        this.$axios.put(`categories/${category.id}`, { name: data }).then(res => {
          // emit
          this.$emit('categoriesGet')
          this.$alert.success('Categoria editada correctamente')
        }).catch(err => {
          console.log(err)
          this.$alert.error('No se pudo editar la categoria')
        })
      })
    }
  },
  mounted () {
  }
}
</script>
