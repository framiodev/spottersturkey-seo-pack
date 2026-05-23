import app from 'flarum/forum/app';
import analytics from './analytics';

app.initializers.add('spottersturkey-seo-pack', () => { // ID değişti
  analytics();
});