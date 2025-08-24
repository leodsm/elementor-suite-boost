import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';

// Mount to element with ID cm-editor-studio
const rootElement = document.getElementById('cm-editor-studio');
if (rootElement) {
  ReactDOM.createRoot(rootElement).render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  );
}
