import { useEffect, useState } from 'react';

type Page = {
  id?: number;
  type: 'image' | 'video';
  url: string;
};

interface WPAttachment {
  url: string;
}
interface WPSelection {
  first: () => { toJSON: () => WPAttachment };
}
interface WPMediaState {
  get: (key: string) => WPSelection;
}
interface WPMedia {
  on: (event: string, callback: () => void) => void;
  state: () => WPMediaState;
  open: () => void;
}
interface WPGlobal {
  media: (args: unknown) => WPMedia;
}
declare const wp: WPGlobal;
declare const cmStoryStudio: {
  restUrl: string;
  nonce: string;
  storyId: number;
};

export default function StoryStudio() {
  const [pages, setPages] = useState<Page[]>([]);

  useEffect(() => {
    if (!cmStoryStudio.storyId) return;
    fetch(`${cmStoryStudio.restUrl}stories/${cmStoryStudio.storyId}`, {
      headers: { 'X-WP-Nonce': cmStoryStudio.nonce }
    })
      .then((res) => res.json())
      .then((data) => {
        if (Array.isArray(data.pages)) {
          setPages(data.pages);
        }
      });
  }, []);

  const openMedia = (type: 'image' | 'video') => {
    const frame = wp.media({
      title: type === 'image' ? 'Select Image' : 'Select Video',
      library: { type },
      multiple: false,
    });

    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON();
      setPages((p) => [...p, { type, url: attachment.url }]);
    });

    frame.open();
  };

  const removePage = (index: number) => {
    setPages((p) => p.filter((_, i) => i !== index));
  };

  const save = async () => {
    await fetch(`${cmStoryStudio.restUrl}stories/${cmStoryStudio.storyId}/pages`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': cmStoryStudio.nonce,
      },
      body: JSON.stringify({ pages }),
    });
  };

  return (
    <div className="cm-story-studio">
      <div className="actions">
        <button onClick={() => openMedia('image')}>Add Image</button>
        <button onClick={() => openMedia('video')}>Add Video</button>
        <button onClick={save}>Save</button>
      </div>
      <ul className="preview">
        {pages.map((page, index) => (
          <li key={index}>
            {page.type === 'image' ? (
              <img src={page.url} style={{ maxWidth: '120px' }} />
            ) : (
              <video src={page.url} controls style={{ maxWidth: '120px' }} />
            )}
            <button onClick={() => removePage(index)}>Remove</button>
          </li>
        ))}
      </ul>
    </div>
  );
}

