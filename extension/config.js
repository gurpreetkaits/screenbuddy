// ScreenSense Extension Configuration
// Change this to switch between environments

const ENV = 'local'; // Change to 'production' for live site

const CONFIG = {
  local: {
    APP_URL: 'http://localhost:5173',
    API_URL: 'http://localhost:8000'
  },
  production: {
    APP_URL: 'https://record.screensense.in',
    API_URL: 'https://record.screensense.in'
  }
};

// Export the current environment config
const SCREENSENSE_URL = CONFIG[ENV].APP_URL;
const API_URL = CONFIG[ENV].API_URL;
const IS_LOCAL = ENV === 'local';
