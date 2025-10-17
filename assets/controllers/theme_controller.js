import { Controller } from "stimulus";

export default class extends Controller {
  static targets = ["system", "light", "dark"];

  connect() {
    // Initialize theme from localStorage or default to system
    const savedTheme = localStorage.getItem('theme') || 'system';
    this.setTheme(savedTheme);
  }

  setSystem() {
    this.setTheme('system');
  }

  setLight() {
    this.setTheme('light');
  }

  setDark() {
    this.setTheme('dark');
  }

  setTheme(theme) {
    // Handle system theme detection
    let actualTheme = theme;
    if (theme === 'system') {
      actualTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    
    // Update the HTML data attribute
    document.documentElement.setAttribute('data-theme', actualTheme);
    
    // Save the user's preference to localStorage
    localStorage.setItem('theme', theme);
    
    // Update segment UI
    this.updateSegmentUI(theme);
    
    // Listen for system theme changes if in system mode
    if (theme === 'system') {
      this.setupSystemThemeListener();
    } else {
      this.removeSystemThemeListener();
    }
  }

  updateSegmentUI(theme) {
    // Remove active class from all segments
    if (this.hasSystemTarget) this.systemTarget.classList.remove('active');
    if (this.hasLightTarget) this.lightTarget.classList.remove('active');
    if (this.hasDarkTarget) this.darkTarget.classList.remove('active');
    
    // Add active class to current theme
    if (theme === 'system' && this.hasSystemTarget) {
      this.systemTarget.classList.add('active');
    } else if (theme === 'light' && this.hasLightTarget) {
      this.lightTarget.classList.add('active');
    } else if (theme === 'dark' && this.hasDarkTarget) {
      this.darkTarget.classList.add('active');
    }
  }

  setupSystemThemeListener() {
    if (!this.systemThemeListener) {
      this.systemThemeListener = (e) => {
        if (localStorage.getItem('theme') === 'system') {
          const actualTheme = e.matches ? 'dark' : 'light';
          document.documentElement.setAttribute('data-theme', actualTheme);
        }
      };
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', this.systemThemeListener);
    }
  }

  removeSystemThemeListener() {
    if (this.systemThemeListener) {
      window.matchMedia('(prefers-color-scheme: dark)').removeEventListener('change', this.systemThemeListener);
      this.systemThemeListener = null;
    }
  }

  disconnect() {
    this.removeSystemThemeListener();
  }
}