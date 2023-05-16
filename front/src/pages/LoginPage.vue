<template>
  <q-layout>
    <q-page-container>
      <q-page>
        <div class="row">
          <div class="col-12 col-md-3"></div>
          <div class="col-12 col-md-6">
            <q-card class="q-mt-lg">
              <q-card-section class="text-center">
                <div class="text-h6">Iniciar sesión</div>
                <q-img src="logoSela.png" width="150px" />
                <div class="text-h6">
                  Control de pozos, tanques y redes
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
                    icon="o_login"
                    :loading="loading"
                    class="full-width"
                    type="submit"
                  />
                </q-form>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-3"></div>
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
  methods: {
    login () {
      this.loading = true
      this.$axios.post('login', {
        email: this.username,
        password: this.password
      })
        .then(response => {
          this.$store.user = response.data.user
          this.$store.isLogged = true
          this.$axios.defaults.headers.common.Authorization = `Bearer ${response.data.token}`
          localStorage.setItem('tokenControl', response.data.token)
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
