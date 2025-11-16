import { ref, watch, onMounted } from 'vue'

type Theme = 'light' | 'dark'

const isDark = ref<boolean>(false)

export function useTheme() {
  const toggleTheme = () => {
    isDark.value = !isDark.value
  }

  const setTheme = (theme: Theme) => {
    isDark.value = theme === 'dark'
  }

  const getTheme = (): Theme => {
    return isDark.value ? 'dark' : 'light'
  }

  // Apply theme to document
  const applyTheme = (theme: Theme) => {
    if (theme === 'dark') {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }

  // Watch for theme changes and apply them
  watch(isDark, (newValue) => {
    const theme = newValue ? 'dark' : 'light'
    applyTheme(theme)
    localStorage.setItem('theme', theme)
  })

  // Initialize theme on mount
  onMounted(() => {
    // Check localStorage first
    const stored = localStorage.getItem('theme') as Theme | null
    
    if (stored) {
      setTheme(stored)
    } else {
      // Fall back to system preference
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      setTheme(prefersDark ? 'dark' : 'light')
    }
    
    applyTheme(getTheme())
  })

  return {
    isDark,
    toggleTheme,
    setTheme,
    getTheme,
  }
}