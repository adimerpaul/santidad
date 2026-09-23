<template>
  <div ref="contenedor" class="chart-tendencia">
    <svg v-if="ancho > 0" :width="ancho" :height="alto"
         @mousemove="mover" @mouseleave="indice = null">
      <defs>
        <linearGradient :id="gradienteId" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" :stop-color="color" stop-opacity="0.38"/>
          <stop offset="100%" :stop-color="color" stop-opacity="0.02"/>
        </linearGradient>
      </defs>

      <!-- Rejilla horizontal y escala -->
      <g v-for="linea in lineasGuia" :key="`g-${linea.y}`">
        <line :x1="margen.izq" :x2="ancho - margen.der" :y1="linea.y" :y2="linea.y" class="ct-grid"/>
        <text :x="margen.izq - 8" :y="linea.y + 4" class="ct-eje" text-anchor="end">{{ linea.texto }}</text>
      </g>

      <!-- Montañas -->
      <template v-if="tipo === 'area'">
        <path :d="areaPath" :fill="`url(#${gradienteId})`"/>
        <path :d="lineaPath" :stroke="color" fill="none" stroke-width="2.5"
              stroke-linecap="round" stroke-linejoin="round"/>
        <circle v-if="puntoActivo" :cx="puntoActivo.x" :cy="puntoActivo.y" r="5"
                :fill="color" stroke="#fff" stroke-width="2"/>
      </template>

      <!-- Histograma -->
      <template v-else>
        <rect v-for="(p, i) in puntos" :key="`b-${i}`"
              :x="p.x - anchoBarra / 2" :y="p.y"
              :width="anchoBarra" :height="Math.max(alto - margen.inf - p.y, 0)"
              :rx="Math.min(anchoBarra / 2, 4)"
              :fill="color" :opacity="indice === null || indice === i ? 0.9 : 0.45"/>
      </template>

      <!-- Guía vertical del punto señalado -->
      <line v-if="puntoActivo" :x1="puntoActivo.x" :x2="puntoActivo.x"
            :y1="margen.sup" :y2="alto - margen.inf" class="ct-guia"/>

      <!-- Etiquetas del eje X -->
      <text v-for="(p, i) in puntos" :key="`x-${i}`"
            v-show="i % saltoEtiquetas === 0 || indice === i"
            :x="p.x" :y="alto - 8" class="ct-eje" text-anchor="middle">{{ p.etiqueta }}</text>
    </svg>

    <div v-if="puntoActivo" class="ct-tooltip" :style="estiloTooltip">
      <div class="ct-tooltip-titulo">{{ puntoActivo.etiqueta }}</div>
      <div class="ct-tooltip-valor" :style="{ color }">{{ formatear(puntoActivo.valor) }}</div>
      <div v-if="puntoActivo.detalle" class="ct-tooltip-detalle">{{ puntoActivo.detalle }}</div>
    </div>

    <div v-if="!datos.length" class="ct-vacio">Sin datos en este periodo</div>
  </div>
</template>

<script>
// Gráfico de área ("montañas") o de barras ("histograma") dibujado en SVG.
// Se hace a mano para no sumar una librería de charts al bundle del panel.
export default {
  name: 'ChartTendencia',
  props: {
    datos: { type: Array, default: () => [] }, // [{ etiqueta, valor, detalle }]
    tipo: { type: String, default: 'area' }, // 'area' | 'barras'
    color: { type: String, default: '#1976d2' },
    alto: { type: Number, default: 300 },
    moneda: { type: Boolean, default: true }
  },
  data () {
    return {
      ancho: 0,
      indice: null,
      margen: { izq: 62, der: 14, sup: 16, inf: 26 },
      gradienteId: `ct-grad-${Math.random().toString(36).slice(2, 9)}`,
      observer: null
    }
  },
  mounted () {
    this.medir()
    // El ancho se toma del contenedor real: el SVG no puede escalarse con
    // viewBox sin deformar textos y trazos.
    this.observer = new ResizeObserver(this.medir)
    this.observer.observe(this.$refs.contenedor)
  },
  beforeUnmount () {
    if (this.observer) this.observer.disconnect()
  },
  computed: {
    valores () {
      return this.datos.map(d => Number(d.valor) || 0)
    },
    maximo () {
      const max = Math.max(...this.valores, 0)
      if (max <= 0) return 1
      // Se redondea hacia arriba a una cifra "redonda" para que la escala
      // no muestre números como 3.478,91.
      const magnitud = Math.pow(10, Math.floor(Math.log10(max)))
      return Math.ceil(max / (magnitud / 2)) * (magnitud / 2)
    },
    anchoUtil () {
      return Math.max(this.ancho - this.margen.izq - this.margen.der, 1)
    },
    altoUtil () {
      return Math.max(this.alto - this.margen.sup - this.margen.inf, 1)
    },
    puntos () {
      const n = this.datos.length
      if (!n || !this.ancho) return []

      return this.datos.map((d, i) => {
        const valor = Number(d.valor) || 0
        // En barras cada dato ocupa una celda; en área se reparten de borde a borde.
        const x = this.tipo === 'barras'
          ? this.margen.izq + (this.anchoUtil / n) * (i + 0.5)
          : this.margen.izq + (n === 1 ? this.anchoUtil / 2 : (this.anchoUtil * i) / (n - 1))

        return {
          x,
          y: this.margen.sup + this.altoUtil - (valor / this.maximo) * this.altoUtil,
          valor,
          etiqueta: d.etiqueta,
          detalle: d.detalle
        }
      })
    },
    anchoBarra () {
      const n = this.datos.length || 1
      return Math.max(Math.min((this.anchoUtil / n) * 0.62, 46), 2)
    },
    lineaPath () {
      if (!this.puntos.length) return ''
      // Curva suave: cada tramo usa el punto medio como control, que da la
      // silueta de "montañas" sin que el trazo se dispare entre valores.
      return this.puntos.reduce((d, p, i, arr) => {
        if (i === 0) return `M ${p.x} ${p.y}`
        const previo = arr[i - 1]
        const medio = (previo.x + p.x) / 2
        return `${d} C ${medio} ${previo.y} ${medio} ${p.y} ${p.x} ${p.y}`
      }, '')
    },
    areaPath () {
      if (!this.puntos.length) return ''
      const base = this.alto - this.margen.inf
      const primero = this.puntos[0]
      const ultimo = this.puntos[this.puntos.length - 1]
      return `${this.lineaPath} L ${ultimo.x} ${base} L ${primero.x} ${base} Z`
    },
    lineasGuia () {
      return [0, 0.25, 0.5, 0.75, 1].map(fraccion => ({
        y: this.margen.sup + this.altoUtil * fraccion,
        texto: this.abreviar(this.maximo * (1 - fraccion))
      }))
    },
    saltoEtiquetas () {
      const caben = Math.floor(this.anchoUtil / 46) || 1
      return Math.max(Math.ceil(this.datos.length / caben), 1)
    },
    puntoActivo () {
      return this.indice === null ? null : this.puntos[this.indice] || null
    },
    estiloTooltip () {
      if (!this.puntoActivo) return {}
      const izquierda = this.puntoActivo.x > this.ancho / 2
      return {
        left: `${this.puntoActivo.x + (izquierda ? -12 : 12)}px`,
        top: `${Math.max(this.puntoActivo.y - 10, 4)}px`,
        transform: izquierda ? 'translateX(-100%)' : 'none'
      }
    }
  },
  methods: {
    medir () {
      if (this.$refs.contenedor) this.ancho = this.$refs.contenedor.clientWidth
    },
    mover (evento) {
      if (!this.puntos.length) return
      const x = evento.clientX - this.$refs.contenedor.getBoundingClientRect().left
      let cercano = 0
      this.puntos.forEach((p, i) => {
        if (Math.abs(p.x - x) < Math.abs(this.puntos[cercano].x - x)) cercano = i
      })
      this.indice = cercano
    },
    abreviar (valor) {
      const n = Number(valor) || 0
      if (Math.abs(n) >= 1000000) return `${(n / 1000000).toFixed(1)}M`
      if (Math.abs(n) >= 1000) return `${(n / 1000).toFixed(n % 1000 === 0 ? 0 : 1)}k`
      return n.toFixed(Number.isInteger(n) ? 0 : 1)
    },
    formatear (valor) {
      const n = Number(valor) || 0
      const texto = n.toLocaleString('es-BO', { minimumFractionDigits: this.moneda ? 2 : 0, maximumFractionDigits: this.moneda ? 2 : 0 })
      return this.moneda ? `${texto} Bs` : texto
    }
  }
}
</script>

<style scoped>
.chart-tendencia {
  position: relative;
  width: 100%;
}
.ct-grid {
  stroke: #e8eaf0;
  stroke-width: 1;
}
.ct-guia {
  stroke: #b0bec5;
  stroke-width: 1;
  stroke-dasharray: 4 4;
}
.ct-eje {
  font-size: 11px;
  fill: #90a4ae;
  font-family: inherit;
}
.ct-tooltip {
  position: absolute;
  background: rgba(38, 50, 56, 0.95);
  color: #fff;
  border-radius: 8px;
  padding: 6px 10px;
  pointer-events: none;
  white-space: nowrap;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
  z-index: 3;
}
.ct-tooltip-titulo {
  font-size: 11px;
  opacity: 0.75;
}
.ct-tooltip-valor {
  font-size: 14px;
  font-weight: 700;
  filter: brightness(1.7);
}
.ct-tooltip-detalle {
  font-size: 11px;
  opacity: 0.8;
}
.ct-vacio {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #b0bec5;
  font-size: 13px;
}
</style>
