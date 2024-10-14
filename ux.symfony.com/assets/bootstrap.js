import { startStimulusApp } from '@symfony/stimulus-bundle';
import Clipboard from 'stimulus-clipboard';
import twigComponentLoader  from './twig-component-loader.js'

const app = startStimulusApp();

twigComponentLoader(app);

app.register('clipboard', Clipboard);
