<template>
  <q-layout>
    <q-page-container>
      <q-page class="bg-grey-4">
        <div class="row">
          <div class="col-12 col-md-4"></div>
          <div class="col-12 col-md-4 q-pa-xs">
            <q-card class="q-mt-md">
              <q-card-section class="text-center">
                <div class="text-h5 text-bold">Iniciar sesión</div>
                <q-img src="logo1.png" width="150px" />
                <div class="text-h6 text-bold">
                  Farmacia
                </div>
                <div class="text-subtitle2 text-bold">
                  Santidad divina
                </div>
              </q-card-section>
              <q-card-section>
                <q-form @submit="login">
                  <q-input
                    v-model="username"
                    label="Usuario"
                    filled
                    clearable
                    lazy-rules
                    :rules="[val => val.length > 0 || 'El usuario es requerido']"
                  />
                  <q-input
                    :type="passwordVisible ? 'text' : 'password'"
                    v-model="password"
                    label="Contraseña"
                    filled
                    lazy-rules
                    :rules="[val => val.length > 0 || 'La contraseña es requerida']"
                  >
                    <template v-slot:append>
                      <q-icon class="cursor-pointer" @click="passwordVisible = !passwordVisible" :name="passwordVisible ? 'visibility_off' : 'visibility'">
                        <q-tooltip>Contraseña</q-tooltip>
                      </q-icon>
                    </template>
                  </q-input>
                  <q-btn
                    label="Iniciar sesión"
                    color="primary"
                    no-caps
                    rounded
                    icon="o_login"
                    :loading="loading"
                    class="full-width"
                    type="submit"
                  />
                </q-form>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-4"></div>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script>
export default {
  name: 'LoginPage',
  data () {
    return {
      username: '',
      password: '',
      loading: false,
      passwordVisible: false
    }
  },
  mounted () {
    if (this.$store.isLoggedIn) {
      this.$router.push('/')
    }
  },
  methods: {
    login () {
      this.loading = true
      this.$axios.post('login', {
        email: this.username,
        password: this.password
      })
        .then(response => {
          this.$store.user = response.data.user
          this.$store.isLoggedIn = true
          this.$axios.defaults.headers.common.Authorization = `Bearer ${response.data.token}`
          localStorage.setItem('tokenSantidad', response.data.token)
          this.$router.push('/')
        })
        .catch(error => {
          this.$q.notify({
            message: error.response.data.message,
            color: 'negative',
            icon: 'o_report_problem',
            position: 'top'
          })
        })
        .finally(() => {
          this.loading = false
        })
    }
  }
}
</script>

<style scoped>

</style>
