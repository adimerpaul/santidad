<template>
  <q-layout view="lHh Lpr lFf">
    <q-header>
      <q-toolbar>
<!--        <q-btn-->
<!--          flat-->
<!--          dense-->
<!--          round-->
<!--          icon="menu"-->
<!--          aria-label="Menu"-->
<!--          @click="toggleLeftDrawer"-->
<!--        />-->
        <q-toolbar-title class="text-bold">
          <template v-if="!$q.screen.lt.md">
            Santidad Divina
          </template>
        </q-toolbar-title>
        <q-separator dark vertical inset />
        <q-input
          v-model="search"
          dense
          style="width: 650px;"
          outlined
          bg-color="white"
          rounded
          placeholder="Buscar producto"
          class="q-ml-sm"/>
        <q-space />
        <q-btn
          flat
          dense
          round
          icon="shopping_cart"
          aria-label="Menu"
        />
      </q-toolbar>
    </q-header>

<!--    <q-drawer-->
<!--      v-model="leftDrawerOpen"-->
<!--      show-if-above-->
<!--      bordered-->
<!--    >-->
<!--      <q-list>-->
<!--        <q-item-label-->
<!--          header-->
<!--        >-->
<!--          Essential Links-->
<!--        </q-item-label>-->

<!--        <EssentialLink-->
<!--          v-for="link in essentialLinks"-->
<!--          :key="link.title"-->
<!--          v-bind="link"-->
<!--        />-->
<!--      </q-list>-->
<!--    </q-drawer>-->

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'MainLayout',
  data () {
    return {
      leftDrawerOpen: false,
      search: ''
    }
  }
})
</script>
