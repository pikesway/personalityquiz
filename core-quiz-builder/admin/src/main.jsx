import App from './App.jsx';

// Wait for DOM to be ready and WordPress globals to be available
function initApp() {
  const rootElement = document.getElementById('root');
  
  if (rootElement && window.React && window.ReactDOM) {
    const root = window.ReactDOM.createRoot(rootElement);
    root.render(window.React.createElement(App));
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}