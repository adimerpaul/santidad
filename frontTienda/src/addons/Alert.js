import { Notify } from 'quasar'

class Alert {
  static success (message) {
    Notify.create({
      progress: true,
      color: 'positive',
      position: 'top',
      message,
      // icon: 'check',
      timeout: 1500,
      actions: [{ icon: 'close', color: 'white', size: 'sm' }]
    })
  }
}
export default Alert
