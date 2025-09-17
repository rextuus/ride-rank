// assets/bootstrap.js
import { Application } from '@hotwired/stimulus';
import { definitionsFromContext } from '@hotwired/stimulus-webpack-helpers';

// Live Components importieren
import { registerVueControllerComponents } from '@symfony/ux-live-component';
import LiveController from '@symfony/ux-live-component';

const application = Application.start();
const context = require.context('./controllers', true, /\.js$/);
application.load(definitionsFromContext(context));




application.register('live', LiveController);
