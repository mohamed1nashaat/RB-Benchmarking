import { defineStore } from 'pinia'
import { ref } from 'vue'

interface Notification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
  duration?: number
}

export const useNotificationStore = defineStore('notifications', () => {
  const notifications = ref<Notification[]>([])

  const addNotification = (notification: Omit<Notification, 'id'>) => {
    const id = Date.now().toString() + Math.random().toString(36).substr(2, 9)
    const newNotification: Notification = {
      ...notification,
      id,
      duration: notification.duration || 5000
    }

    notifications.value.push(newNotification)

    // Auto-remove after duration
    if (newNotification.duration > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, newNotification.duration)
    }

    return id
  }

  const removeNotification = (id: string) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  const success = (title: string, message?: string) => {
    return addNotification({ type: 'success', title, message })
  }

  const error = (title: string, message?: string) => {
    return addNotification({ type: 'error', title, message, duration: 8000 })
  }

  const warning = (title: string, message?: string) => {
    return addNotification({ type: 'warning', title, message })
  }

  const info = (title: string, message?: string) => {
    return addNotification({ type: 'info', title, message })
  }

  const clear = () => {
    notifications.value = []
  }

  return {
    notifications,
    addNotification,
    removeNotification,
    success,
    error,
    warning,
    info,
    clear
  }
})