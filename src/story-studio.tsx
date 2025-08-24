import { createRoot } from 'react-dom/client';
import StoryStudio from './pages/StoryStudio';

document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('cm-story-studio-root');
  if (el) {
    createRoot(el).render(<StoryStudio />);
  }
});

