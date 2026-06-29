<template>
  <q-layout>
    <q-page-container>
      <q-page class="window-height window-width flex flex-center bg-gradient">

        <q-card class="login-card shadow-24 q-pa-lg">
          <q-card-section class="text-center q-pb-none">
            <div class="logo-container q-mb-md">
              <q-img
                src="logo1.png"
                width="120px"
                class="logo-img"
                spinner-color="primary"
              ></q-img>
            </div>

            <div class="text-h4 text-weight-bolder text-primary q-mb-xs">Bienvenido</div>
            <div class="text-subtitle1 text-grey-7 q-mb-md">Farmacia Santidad Divina</div>
          </q-card-section>

          <q-card-section>
            <q-form @submit="login" class="q-gutter-y-md">

              <q-input
                v-model="username"
                label="Usuario / Email"
                outlined
                rounded
                dense
                bg-color="white"
                class="input-modern"
                lazy-rules
                :rules="[val => val.length > 0 || 'El usuario es requerido']"
              >
                <template v-slot:prepend>
                  <q-icon name="person_outline" color="primary"></q-icon>
                </template>
              </q-input>

              <q-input
                :type="passwordVisible ? 'text' : 'password'"
                v-model="password"
                label="Contraseña"
                outlined
                rounded
                dense
                bg-color="white"
                class="input-modern"
                lazy-rules
                :rules="[val => val.length > 0 || 'La contraseña es requerida']"
              >
                <template v-slot:prepend>
                  <q-icon name="lock_outline" color="primary"></q-icon>
                </template>
                <template v-slot:append>
                  <q-icon
                    class="cursor-pointer hover-effect"
                    @click="passwordVisible = !passwordVisible"
                    :name="passwordVisible ? 'visibility_off' : 'visibility'"
                    color="grey-6"
                  >
                    <q-tooltip>Mostrar contraseña</q-tooltip>
                  </q-icon>
                </template>
              </q-input>

              <div class="q-mt-lg">
                <q-btn
                  label="INGRESAR AL SISTEMA"
                  color="primary"
                  unelevated
                  rounded
                  size="lg"
                  :loading="loading"
                  class="full-width btn-modern"
                  type="submit"
                >
                  <template v-slot:loading>
                    <q-spinner-facebook></q-spinner-facebook>
                  </template>
                </q-btn>
              </div>

            </q-form>
          </q-card-section>

          <q-card-section class="text-center q-pt-none q-mt-sm">
            <div class="text-caption text-grey-6">
              © {{ new Date().getFullYear() }} Sistema de Inventario
            </div>
          </q-card-section>
        </q-card>

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
          this.$store.agencia_id = response.data.user.agencia_id
          this.$store.env = response.data.env
          this.$store.isLoggedIn = true
          this.$axios.defaults.headers.common.Authorization = `Bearer ${response.data.token}`
          localStorage.setItem('tokenSantidad', response.data.token)
          localStorage.setItem('agencia_id', response.data.user.agencia_id)
          this.$router.push('/')
        })
        .catch(error => {
          this.$q.notify({
            message: error.response?.data?.message || 'Error de conexión',
            color: 'negative',
            icon: 'o_report_problem',
            position: 'top',
            timeout: 2500
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
/* Fondo degradado moderno */
.bg-gradient {
  background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
  background-size: 400% 400%;
  animation: gradientBG 15s ease infinite;
}

@keyframes gradientBG {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Tarjeta estilo Glassmorphism */
.login-card {
  width: 100%;
  max-width: 400px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  transition: transform 0.3s ease;
}

/* Efecto hover suave en el logo */
.logo-img {
  filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
  transition: transform 0.3s;
}
.logo-img:hover {
  transform: scale(1.05);
}

/* Inputs personalizados */
.input-modern :deep(.q-field__control) {
  border-radius: 12px;
  transition: all 0.3s;
}
.input-modern :deep(.q-field__control:hover) {
  background: #f8f9fa;
}

/* Botón moderno */
.btn-modern {
  font-weight: 700;
  letter-spacing: 1px;
  transition: transform 0.2s, box-shadow 0.2s;
  background: linear-gradient(90deg, var(--q-primary) 0%, #1976D2 100%);
}

.btn-modern:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(25, 118, 210, 0.4);
}

.hover-effect:hover {
  color: var(--q-primary);
}
</style>
